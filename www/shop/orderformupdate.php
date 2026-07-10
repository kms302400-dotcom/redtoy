<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/mailer.lib.php');

$data = array();
if (isset($_SESSION["payinfo"]) && count($_SESSION["payinfo"]) > 0) {
    $default['de_pg_service'] = "danal";
    foreach($_SESSION["payinfo"]["order_info"] as $key => $val) {
        $data[$key] = $val;
        $_POST[$key] = $val;
    }
    
    $payMethod = $_SESSION["payinfo"]["paymethod"];
    $payAmount = $_SESSION["payinfo"]["amount"];
    $od_b_zip = $_POST["od_b_zip"];
    $od_settle_case = $payMethod;
} else {
    $payMethod = $_POST["od_settle_case"];
    $payAmount = $_POST["od_settle_case"];
    $od_settle_case = $_POST["od_settle_case"];
}

//이니시스 lpay 요청으로 왔다면 $default['de_pg_service'] 값을 이니시스로 변경합니다.
if( in_array($od_settle_case, array('lpay', 'inicis_kakaopay')) ){
    $default['de_pg_service'] = 'inicis';
}

// 타 PG 사용시 NHN KCP 네이버페이로 결제 요청이 왔다면 $default['de_pg_service'] 값을 kcp 로 변경합니다.
if(function_exists('is_use_easypay') && is_use_easypay('global_nhnkcp') && isset($_POST['enc_data']) && $_POST['enc_data'] && isset($_POST['site_cd']) && isset($_POST['nhnkcp_pay_case']) && $_POST['nhnkcp_pay_case'] === "naverpay"){
    $default['de_pg_service'] = 'kcp';
}

if(function_exists('add_order_post_log')) add_order_post_log('init', 'init');

if(($od_settle_case != '무통장' && $od_settle_case != 'KAKAOPAY') && $default['de_pg_service'] == 'lg' && !$_POST['LGD_PAYKEY']){
    if(function_exists('add_order_post_log')) add_order_post_log('결제등록 요청 후 주문해 주십시오.');
    alert('결제등록 요청 후 주문해 주십시오.');
}

// 장바구니가 비어있는가?
if (get_session("ss_direct"))
    $tmp_cart_id = get_session('ss_cart_direct');
else
    $tmp_cart_id = get_session('ss_cart_id');

if (get_cart_count($tmp_cart_id) == 0) {    // 장바구니에 담기
    if(function_exists('add_order_post_log')) add_order_post_log('장바구니가 비어 있습니다.');
    alert('장바구니가 비어 있습니다.\\n\\n이미 주문하셨거나 장바구니에 담긴 상품이 없는 경우입니다.', G5_SHOP_URL.'/cart.php');
}

$sql = "select * from {$g5['g5_shop_order_table']} limit 1";
$check_tmp = sql_fetch($sql);

// 변수 초기화
$od_other_pay_type = '';

$od_temp_point = isset($_POST['od_temp_point']) ? (int) $_POST['od_temp_point'] : 0;
$od_hope_date = isset($_POST['od_hope_date']) ? clean_xss_tags($_POST['od_hope_date'], 1, 1) : '';
$ad_default = ! empty($_POST['ad_default']) ? (int) $_POST['ad_default'] : 0;

$error = "";
// 장바구니 상품 재고 검사
$sql = " select it_id,
                ct_qty,
                it_name,
                io_id,
                io_type,
                ct_option
           from {$g5['g5_shop_cart_table']}
          where od_id = '$tmp_cart_id'
            and ct_select = '1' ";
$result = sql_query($sql);
for ($i=0; $row=sql_fetch_array($result); $i++)
{
    
    // 상품에 대한 현재고수량
    if($row['io_id']) {
        $it_stock_qty = (int)get_option_stock_qty($row['it_id'], $row['io_id'], $row['io_type']);
    } else {
        $it_stock_qty = (int)get_it_stock_qty($row['it_id']);
    }
    // 장바구니 수량이 재고수량보다 많다면 오류
    if ($row['ct_qty'] > $it_stock_qty)
        $error .= "{$row['ct_option']} 의 재고수량이 부족합니다. 현재고수량 : $it_stock_qty 개\\n\\n";
}

if($i == 0) {
    if(function_exists('add_order_post_log')) add_order_post_log('장바구니가 비어 있습니다.');
    alert('장바구니가 비어 있습니다.\\n\\n이미 주문하셨거나 장바구니에 담긴 상품이 없는 경우입니다.', G5_SHOP_URL.'/cart.php');
}

if ($error != "")
{
    $error .= "다른 고객님께서 {$od_name}님 보다 먼저 주문하신 경우입니다. 불편을 끼쳐 죄송합니다.";
    if(function_exists('add_order_post_log')) add_order_post_log($error);
    alert($error);
}

$i_price     = isset($_POST['od_price']) ? (int) $_POST['od_price'] : 0;
$i_send_cost  = isset($_POST['od_send_cost']) ? (int) $_POST['od_send_cost'] : 0;
$i_send_cost2  = isset($_POST['od_send_cost2']) ? (int) $_POST['od_send_cost2'] : 0;
$i_send_coupon  = isset($_POST['od_send_coupon']) ? abs((int) $_POST['od_send_coupon']) : 0;
$i_temp_point = isset($_POST['od_temp_point']) ? (int) $_POST['od_temp_point'] : 0;

// 주문금액이 상이함
$sql = " select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as od_price,
              COUNT(distinct it_id) as cart_count
            from {$g5['g5_shop_cart_table']} where od_id = '$tmp_cart_id' and ct_select = '1' ";
$row = sql_fetch($sql);
$tot_ct_price = $row['od_price'];
$cart_count = $row['cart_count'];
$tot_od_price = $tot_ct_price;

// 쿠폰금액계산
$tot_cp_price = $tot_it_cp_price = $tot_od_cp_price = 0;
if($is_member) {
    // 상품쿠폰
    $it_cp_cnt = (isset($_POST['cp_id']) && is_array($_POST['cp_id'])) ? count($_POST['cp_id']) : 0;
    $arr_it_cp_prc = array();
    for($i=0; $i<$it_cp_cnt; $i++) {
        $cid = isset($_POST['cp_id'][$i]) ? clean_xss_tags($_POST['cp_id'][$i], 1, 1) : '';
        $it_id = isset($_POST['it_id'][$i]) ? safe_replace_regex($_POST['it_id'][$i], 'it_id') : '';
        $sql = " select cp_id, cp_method, cp_target, cp_type, cp_price, cp_trunc, cp_minimum, cp_maximum
                    from {$g5['g5_shop_coupon_table']}
                    where cp_id = '$cid'
                      and mb_id IN ( '{$member['mb_id']}', '전체회원' )
                      and cp_start <= '".G5_TIME_YMD."'
                      and cp_end >= '".G5_TIME_YMD."'
                      and cp_method IN ( 0, 1 ) ";
        $cp = sql_fetch($sql);
        if(! (isset($cp['cp_id']) && $cp['cp_id']))
            continue;

        // 사용한 쿠폰인지
        if(is_used_coupon($member['mb_id'], $cp['cp_id']))
            continue;

        // 분류할인인지
        if($cp['cp_method']) {
            $sql2 = " select it_id, ca_id, ca_id2, ca_id3
                        from {$g5['g5_shop_item_table']}
                        where it_id = '$it_id' ";
            $row2 = sql_fetch($sql2);

            if(!$row2['it_id'])
                continue;

            if($row2['ca_id'] != $cp['cp_target'] && $row2['ca_id2'] != $cp['cp_target'] && $row2['ca_id3'] != $cp['cp_target'])
                continue;
        } else {
            if($cp['cp_target'] != $it_id)
                continue;
        }

        // 상품금액
        $sql = " select SUM( IF(io_type = '1', io_price * ct_qty, (ct_price + io_price) * ct_qty)) as sum_price
                    from {$g5['g5_shop_cart_table']}
                    where od_id = '$tmp_cart_id'
                      and it_id = '$it_id'
                      and ct_select = '1' ";
        $ct = sql_fetch($sql);
        $item_price = $ct['sum_price'];

        if($cp['cp_minimum'] > $item_price)
            continue;

        $dc = 0;
        if($cp['cp_type']) {
            $dc = floor(($item_price * ($cp['cp_price'] / 100)) / $cp['cp_trunc']) * $cp['cp_trunc'];
        } else {
            $dc = $cp['cp_price'];
        }

        if($cp['cp_maximum'] && $dc > $cp['cp_maximum'])
            $dc = $cp['cp_maximum'];

        if($item_price < $dc)
            continue;

        $tot_it_cp_price += $dc;
        $arr_it_cp_prc[$it_id] = $dc;
    }

    $tot_od_price -= $tot_it_cp_price;

    // 주문쿠폰
    if(isset($_POST['od_cp_id']) && $_POST['od_cp_id']) {
        $sql = " select cp_id, cp_type, cp_price, cp_trunc, cp_minimum, cp_maximum
                    from {$g5['g5_shop_coupon_table']}
                    where cp_id = '{$_POST['od_cp_id']}'
                      and mb_id IN ( '{$member['mb_id']}', '전체회원' )
                      and cp_start <= '".G5_TIME_YMD."'
                      and cp_end >= '".G5_TIME_YMD."'
                      and cp_method = '2' ";
        $cp = sql_fetch($sql);

        // 사용한 쿠폰인지
        $cp_used = is_used_coupon($member['mb_id'], $cp['cp_id']);

        $dc = 0;
        if(!$cp_used && $cp['cp_id'] && ($cp['cp_minimum'] <= $tot_od_price)) {
            if($cp['cp_type']) {
                $dc = floor(($tot_od_price * ($cp['cp_price'] / 100)) / $cp['cp_trunc']) * $cp['cp_trunc'];
            } else {
                $dc = $cp['cp_price'];
            }

            if($cp['cp_maximum'] && $dc > $cp['cp_maximum'])
                $dc = $cp['cp_maximum'];

            if($tot_od_price < $dc)
                die('Order coupon error.');

            $tot_od_cp_price = $dc;
            $tot_od_price -= $tot_od_cp_price;
        }
    }

    $tot_cp_price = $tot_it_cp_price + $tot_od_cp_price;
}

if ((int)($row['od_price'] - $tot_cp_price) !== $i_price - $tot_cp_price) {
    if(function_exists('add_order_post_log')) add_order_post_log('쿠폰금액 최종 계산 Error.');
    die("쿠폰금액 최종 계산 Error.");
}

// 배송비가 상이함
$send_cost = get_sendcost($tmp_cart_id);

$tot_sc_cp_price = 0;
if($is_member && $send_cost > 0) {
    // 배송쿠폰
    if(isset($_POST['sc_cp_id']) && $_POST['sc_cp_id']) {
        $sql = " select cp_id, cp_type, cp_price, cp_trunc, cp_minimum, cp_maximum
                    from {$g5['g5_shop_coupon_table']}
                    where cp_id = '{$_POST['sc_cp_id']}'
                      and mb_id IN ( '{$member['mb_id']}', '전체회원' )
                      and cp_start <= '".G5_TIME_YMD."'
                      and cp_end >= '".G5_TIME_YMD."'
                      and cp_method = '3' ";
        $cp = sql_fetch($sql);

        // 사용한 쿠폰인지
        $cp_used = is_used_coupon($member['mb_id'], $cp['cp_id']);

        $dc = 0;
        if(!$cp_used && $cp['cp_id'] && ($cp['cp_minimum'] <= $tot_od_price)) {
            if($cp['cp_type']) {
                $dc = floor(($send_cost * ($cp['cp_price'] / 100)) / $cp['cp_trunc']) * $cp['cp_trunc'];
            } else {
                $dc = $cp['cp_price'];
            }

            if($cp['cp_maximum'] && $dc > $cp['cp_maximum'])
                $dc = $cp['cp_maximum'];

            if($dc > $send_cost)
                $dc = $send_cost;

            $tot_sc_cp_price = $dc;
        }
    }
}

if ((int)($send_cost - $tot_sc_cp_price) !== (int)($i_send_cost - $i_send_coupon)) {
    if(function_exists('add_order_post_log')) add_order_post_log('배송비 최종 계산 Error..');
    die("배송비 최종 계산 Error..");
}

// 추가배송비가 상이함
$od_b_zip   = preg_replace('/[^0-9]/', '', $od_b_zip);
$od_b_zip1  = substr($od_b_zip, 0, 3);
$od_b_zip2  = substr($od_b_zip, 3);
$zipcode = $od_b_zip;
$sql = " select sc_id, sc_price from {$g5['g5_shop_sendcost_table']} where sc_zip1 <= '$zipcode' and sc_zip2 >= '$zipcode' ";
$tmp = sql_fetch($sql);
if(! (isset($tmp['sc_id']) && $tmp['sc_id']))
    $send_cost2 = 0;
else
    $send_cost2 = (int) $tmp['sc_price'];

if($send_cost2 !== $i_send_cost2){
    if(function_exists('add_order_post_log')) add_order_post_log('추가배송비 최종 계산 Error...');
    die("추가배송비 최종 계산 Error...");
}

// 결제포인트가 상이함
// 회원이면서 포인트사용이면
$temp_point = 0;
if ($is_member && $config['cf_use_point'])
{
    if($member['mb_point'] >= $default['de_settle_min_point']) {
        $temp_point = (int)$default['de_settle_max_point'];

        if($temp_point > (int)$tot_od_price)
            $temp_point = (int)$tot_od_price;

        if($temp_point > (int)$member['mb_point'])
            $temp_point = (int)$member['mb_point'];

        $point_unit = (int)$default['de_settle_point_unit'];
        $temp_point = (int)((int)($temp_point / $point_unit) * $point_unit);
    }
}

if (($i_temp_point > (int)$temp_point || $i_temp_point < 0) && $config['cf_use_point']) {
    if(function_exists('add_order_post_log')) add_order_post_log('포인트 최종 계산 Error....');
    die("포인트 최종 계산 Error....");
}

if ($od_temp_point)
{
    if ($member['mb_point'] < $od_temp_point) {
        if(function_exists('add_order_post_log')) add_order_post_log('회원님의 포인트가 부족하여 포인트로 결제 할 수 없습니다.');
        alert('회원님의 포인트가 부족하여 포인트로 결제 할 수 없습니다.');
    }
}

// 주문번호를 얻는다.
$od_id = get_session('ss_order_id');

if ($default['de_pg_service'] == "danal") {
    /*[ 필수 데이터 ]***************************************/
    $REQ_DATA = array();
    switch($payMethod) {
        case "card" :
            include "../lib/danal_function.php";
            
            //************ 복호화 *********************
        	$RES_STR = toDecrypt( $_POST['RETURNPARAMS'] );
        	$RET_MAP = str2data( $RES_STR );
        	
        	$RET_RETURNCODE = $RET_MAP["RETURNCODE"];
        	$RET_RETURNMSG = $RET_MAP["RETURNMSG"];
        	
        	//*****  신용카드 인증결과 확인 *****************
        	$RES_DATA = array();
        	if( is_null($RET_RETURNCODE) || $RET_RETURNCODE != "0000" ){
        		// returnCode가 없거나 또는 그 결과가 성공이 아니라면 실패 처리
        		
        		$RES_DATA["RETURNCODE"] = $RET_RETURNCODE;
        		$RES_DATA["RETURNMSG"] = $RET_RETURNMSG;
        	}
        	else{
        		//***** 신용카드 인증 성공 시 결제 완료 요청 *****
        		
        		/*[ 필수 데이터 ]***************************************/
        		$REQ_DATA = array();
        		
        		/**************************************************
        		 * 결제 정보
        		**************************************************/
        		$REQ_DATA["TID"] = $RET_MAP["TID"];
        		$REQ_DATA["AMOUNT"] = $payAmount; // 최초 결제요청(AUTH)시에 보냈던 금액과 동일한 금액을 전송
        				
        		/**************************************************
        		 * 기본 정보
        		 **************************************************/
        		$REQ_DATA["TXTYPE"] = "BILL";
        		$REQ_DATA["SERVICETYPE"] = "DANALCARD";

        		$RES_DATA = CallCredit($REQ_DATA, false);
        	}
        	
        	if ( $RES_DATA['RETURNCODE'] == "0000" ) {
                $data["P_TID"] = $RES_DATA['TID'];
                $data["P_AMT"] = $RES_DATA['AMOUNT'];
                $data["P_AUTH_DT"] = $RES_DATA['TRANDATE'] . $RES_DATA['TRANTIME'];
                $data["P_FN_NM"] = mb_convert_encoding($RES_DATA['CARDNAME'], "UTF-8", "UHC");
        		/*
    = RESULT =
    [param:1]
    Array
    (
        [RETURNPARAMS] => tm5yNjvXr/b/m13UKC3+GVbAUhhg912ggVwnT5UwE14xsrvlP42u9KsZfz064N/41luA4HqkOlcA+7/Ljglhbr5tZVz+mnRBgkKpia8b82ZlQOdAkpvyOJ1slyS6GwtI
    )

    = RESULT =
    [param:1]
    Array
    (
        [RETURNCODE] => 0000
        [RETURNMSG] => ����
        [CARDCODE] => 0931
        [USERPHONE] => 
        [BYPASSVALUE] => JTdCJTIyc3dfZGlyZWN0JTIyJTNBJTIyMSUyMiUyQyUyMml0X2lkJTIyJTNBJTVCJTIyMTczNzg2NjkyNiUyMiU1RCUyQyUyMml0X25hbWUlMjIlM0ElNUIlMjJ0ZXN0JTIyJTVEJTJDJTIyaXRfcHJpY2UlMjIlM0ElNUIlMjIxJTJDMDAwJTIyJTVEJTJDJTIyY3BfcHJpY2UlMjIlM0ElNUIlMjIwJTIyJTVEJTJDJTIyY3BfaWQlMjIlM0E
        [QUOTA] => 00
        [TRXAMOUNT] => 1000
        [TRANTIME] => 022039
        [TRANDATE] => 20250201
        [AMOUNT] => 1000
        [ITEMNAME] => test
        [DEPOSIT_AMT] => 0
        [DISCOUNTAMOUNT] => 0
        [TID] => 202502010218441515374400
        [ORDERID] => 2025020102183552
        [CARDNO] => 532750******5440
        [CARDAUTHNO] => 27219706
        [CARDNAME] => �佺��ũ
        [USERNAME] => ������
        [USERID] => naver_a072095a1
    )

        		*/
        	} else {
        		$RETURNCODE = iconv("CP949", "UTF8", $RES_DATA['RETURNCODE']);
        		$RETURNMSG = iconv("CP949", "UTF8", $RES_DATA['RETURNMSG']);
        		
        		alert($RETURNMSG);
        	}
            break;
        case "hpp" :
        case "mobile" :
            include "../lib/danal_function_teledit.php";
            
            $BillErr = false;
        	$TransR = array();

        	/*
        	 * Get ServerInfo
        	 */
        	$ServerInfo = $_POST["ServerInfo"]; 

        	/*
        	 * NCONFIRM
        	 */
        	$nConfirmOption = 1; 
        	$TransR["Command"] = "NCONFIRM";
        	$TransR["OUTPUTOPTION"] = "DEFAULT";
        	$TransR["ServerInfo"] = $ServerInfo;
        	$TransR["IFVERSION"] = "V1.1.2";
        	$TransR["ConfirmOption"] = $nConfirmOption;

        	/*
        	 * ConfirmOption이 1이면 CPID, AMOUNT 필수 전달
        	 */
        	if( $nConfirmOption == 1 )
        	{
        		$TransR["CPID"] = "A010078853";
        		$TransR["AMOUNT"] = $payAmount;
        	}

        	$RES_DATA = CallTeledit( $TransR,false );
        	if( $RES_DATA["Result"] == "0" )
        	{
        		/*
        		 * NBILL
        		 */

        		$TransR = array();

        		$nBillOption = 0;
        		$TransR["Command"] = "NBILL";
        		$TransR["OUTPUTOPTION"] = "DEFAULT";
        		$TransR["BillOption"] = $nBillOption;
        		$TransR["ServerInfo"] = $ServerInfo;
        		$TransR["IFVERSION"] = "V1.1.2";

        		$Res2 = CallTeledit( $TransR,false );

        		if( $Res2["Result"] != "0" )
        		{
        			$BillErr = true;
        		}
        	}
        	
        	if( $RES_DATA["Result"] == "0" && $Res2["Result"] == "0" )
        	{
        		/**************************************************************************
        		 *
        		 * 결제 완료에 대한 작업 
        		 * - AMOUNT, ORDERID 등 결제 거래내용에 대한 검증을 반드시 하시기 바랍니다.
        		 * - CAP, RemainAmt: 개인정보 정책에 의해 잔여 한도 금액은 미전달 됩니다. (“000000”)
        		 *
        		 **************************************************************************/
        		 /*
        		 p($RES_DATA, $Res2);
        		 exit;
        		 = RESULT =
    [param:1]
    Array
    (
        [Result] => 0
        [ErrMsg] => No Information
        [TID] => 020105544352064422
        [CPID] => 9010050028
        [SUBCP] => none
        [AMOUNT] => 1000
        [ORDERID] => 2025020105350135
        [] => 
    )

    [param:2]
    Array
    (
        [Result] => 0
        [ErrMsg] => No Information
        [TID] => 020105544352064422
        [RemainAmt] => 000000
        [ORDERID] => 2025020105350135
        [DNTID] => 2025020105544352064422
        [DATE] => 20250201055650
        [Authkey] => NA
        [Iden] => NA
        [] => 
    )
        		*/
        		 
        		$data["P_TID"] = $RES_DATA["TID"];
                $data["P_AUTH_DT"] = $Res2["DATE"];
                $data["P_AMT"] = $RES_DATA["AMOUNT"];
        	} else {
        		/**************************************************************************
        		 *
        		 * 결제 실패에 대한 작업 
        		 *
        		 **************************************************************************/

        		if( $BillErr ) $RES_DATA = $Res2;
        		
        		$result_encoding = mb_detect_encoding($RES_DATA["Result"], ["UHC", "CP949", "UTF-8"]);
        		$error_encoding  = mb_detect_encoding($RES_DATA["ErrMsg"], ["UHC", "CP949", "UTF-8"]);

        		$RETURNCODE = mb_convert_encoding($RES_DATA["Result"], "UTF-8", $result_encoding);
        		$RETURNMSG = mb_convert_encoding($RES_DATA["ErrMsg"], "UTF-8", $error_encoding);
        		
        		//show_error_msg(headerbar(3, "결제 실패", true, false, array(), ""), $RETURNMSG, $RETURNCODE, "다시 시도", "/");
        		alert($RETURNMSG);
        		exit;
        	}
            break;
        default :
            //show_error_msg(headerbar(3, "결제 실패", true, false, array(), ""), "<b>결제방법</b>이<br />잘못되었습니다.", "다시 선택 후<br />결제시도 부탁드립니다.", "다시 시도", "/");
            if ($payMethod != "무통장") {
                alert("결제방법이 잘못되었습니다.");
            }
            break;
    }
    
    if ($RES_DATA) {
        $sql = " INSERT INTO g5_shop_order_reqdata SET ";
        $sql .= " mb_id = '" . $member["mb_id"] . "' ";
        $sql .= ", reqdata = '" . json_encode($RES_DATA) . "' ";
        $sql .= ", reqdate = now() + interval 9 hour ";
        
        sql_query($sql);
    }
    
    $i_with_point_price = $i_price;
    $i_price = $i_price + $i_send_cost + $i_send_cost2 - $i_temp_point - $i_send_coupon;
    $order_price = $tot_od_price + $send_cost + $send_cost2 - $tot_sc_cp_price - $od_temp_point;

    $od_status = '주문';
    $od_tno    = '';
    $od_settle_case = $payMethod; //$order_data["od_settle_case"];
    
    if ($od_settle_case == "bank")
    {
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = $data["P_AMT"];
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = $data["P_AUTH_DT"];
        $od_deposit_name    = $data["P_UNAME"];
        $od_bank_account    = $data["P_FN_NM"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = $od_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "vbank")
    {
        $od_receipt_point   = $i_temp_point;
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = 0;
        $od_bank_account    = $data["P_FN_NM"].' '.$data["P_VACT_NUM"] . " " . $data["P_VACT_NAME"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = $od_price - $od_receipt_price;
    }
    else if ($od_settle_case == "hpp" || $od_settle_case == "mobile")
    {
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = $data["P_AMT"];
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = $data["P_AUTH_DT"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = 0;
        if($od_misu == 0)
            $od_status      = '입금';
            
        //insert_point($member['mb_id'], $i_with_point_price * 0.05, "주문번호 $od_id 결제", '@member', $od_id, '상품구입');
    }
    else if ($od_settle_case == "card")
    {
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = $data["P_AMT"];
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = $data["P_AUTH_DT"];
        $od_bank_account    = $data["P_FN_NM"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = 0;
        if($od_misu == 0)
            $od_status      = '입금';
            
        insert_point($member['mb_id'], $i_with_point_price * 0.05, "주문번호 $od_id 결제", '@member', $od_id, '상품구입');
    }
    else if ($od_settle_case == "naver")
    {
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = $data["P_AMT"];
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = $data["P_AUTH_DT"];
        $od_bank_account    = $data["P_FN_NM"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = $od_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "kakao")
    {
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = $data["P_AMT"];
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = $data["P_AUTH_DT"];
        $od_bank_account    = $data["P_FN_NM"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = $od_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "payco")
    {
        $od_tno             = $data["P_TID"];
        $od_receipt_price   = $data["P_AMT"];
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = $data["P_AUTH_DT"];
        $od_bank_account    = $data["P_FN_NM"];
        $pg_price           = $data["P_AMT"];
        $od_misu            = $od_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "무통장")
    {
        $od_receipt_point   = $i_temp_point;
        $od_receipt_price   = 0;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0) {
            $od_status      = '입금';
            $od_receipt_time = G5_TIME_YMDHIS;
        }
    }
    else
    {
        die("od_settle_case Error!!!");
    }

    $od_pg = "danal";
} else { // danal else
    $i_price = $i_price + $i_send_cost + $i_send_cost2 - $i_temp_point - $i_send_coupon;
    $order_price = $tot_od_price + $send_cost + $send_cost2 - $tot_sc_cp_price - $od_temp_point;

    $od_status = '주문';
    $od_tno    = '';
    if ($od_settle_case == "무통장")
    {
        $od_receipt_point   = $i_temp_point;
        $od_receipt_price   = 0;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0) {
            $od_status      = '입금';
            $od_receipt_time = G5_TIME_YMDHIS;
        }
    }
    else if ($od_settle_case == "계좌이체")
    {
        switch($default['de_pg_service']) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_result.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inistdpay_result.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub.php';
                $bank_name  = iconv("cp949", "utf-8", $bank_name);
                break;
        }

        $od_tno             = $tno;
        $od_receipt_price   = $amount;
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
        $od_deposit_name    = $od_name;
        $od_bank_account    = $bank_name;
        $pg_price           = $amount;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "가상계좌")
    {
        switch($default['de_pg_service']) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_result.php';
                $od_receipt_time = '0000-00-00 00:00:00';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inistdpay_result.php';
                $od_app_no = $app_no;
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub.php';
                $bankname   = iconv("cp949", "utf-8", $bankname);
                $depositor  = iconv("cp949", "utf-8", $depositor);
                break;
        }

        $od_receipt_point   = $i_temp_point;
        $od_tno             = $tno;
        $od_receipt_price   = 0;
        $od_bank_account    = $bankname.' '.$account;
        $od_deposit_name    = $depositor;
        $pg_price           = $amount;
        $od_misu            = $i_price - $od_receipt_price;
    }
    else if ($od_settle_case == "휴대폰")
    {
        switch($default['de_pg_service']) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_result.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inistdpay_result.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub.php';
                break;
        }

        $od_tno             = $tno;
        $od_receipt_price   = $amount;
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
        $od_bank_account    = $commid . ($commid ? ' ' : '').$mobile_no;
        $pg_price           = $amount;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "신용카드")
    {
        switch($default['de_pg_service']) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_result.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inistdpay_result.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub.php';
                $card_name  = iconv("cp949", "utf-8", $card_name);
                break;
        }
        
        $od_tno             = $tno;
        $od_app_no          = $app_no;
        $od_receipt_price   = $amount;
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
        $od_bank_account    = $card_name;
        $pg_price           = $amount;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "간편결제" || (($od_settle_case == "lpay" || $od_settle_case == "inicis_kakaopay") && $default['de_pg_service'] === 'inicis') )
    {
        switch($default['de_pg_service']) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_result.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inistdpay_result.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub.php';
                $card_name  = iconv("cp949", "utf-8", $card_name);
                break;
        }

        $od_tno             = $tno;
        $od_app_no          = $app_no;
        $od_receipt_price   = $amount;
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
        $od_bank_account    = $card_name;
        $pg_price           = $amount;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else if ($od_settle_case == "KAKAOPAY")
    {
        include G5_SHOP_PATH.'/kakaopay/kakaopay_result.php';

        $od_tno             = $tno;
        $od_app_no          = $app_no;
        $od_receipt_price   = $amount;
        $od_receipt_point   = $i_temp_point;
        $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
        $od_bank_account    = $card_name;
        $pg_price           = $amount;
        $od_misu            = $i_price - $od_receipt_price;
        if($od_misu == 0)
            $od_status      = '입금';
    }
    else
    {
        die("od_settle_case Error!!!");
    }

    $od_pg = $default['de_pg_service'];
    if($od_settle_case == 'KAKAOPAY')
        $od_pg = 'KAKAOPAY';
}

$tno = isset($tno) ? $tno : '';
$od_receipt_time = isset($od_receipt_time) ? $od_receipt_time : '';
$od_app_no = isset($od_app_no) ? $od_app_no : '';

// 주문금액과 결제금액이 일치하는지 체크
if($tno) {
    if((int)$order_price !== (int)$pg_price) {
        $cancel_msg = '결제금액 불일치';
        switch($od_pg) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_cancel.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inipay_cancel.php';
                break;
            case 'KAKAOPAY':
                $_REQUEST['TID']               = $tno;
                $_REQUEST['Amt']               = $amount;
                $_REQUEST['CancelMsg']         = $cancel_msg;
                $_REQUEST['PartialCancelCode'] = 0;
                include G5_SHOP_PATH.'/kakaopay/kakaopay_cancel.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub_cancel.php';
                break;
            case "danal" :
                if ($od_settle_case == "계좌이체") {
                    //CancelBank($od_tno, $pg_price, "autocancel", $cancel_msg);
                } else if ($od_settle_case == "휴대폰") {
                    //CancelTeledit($od_tno);
                } else if ($od_settle_case == "신용카드") {
                    CancelCredit($od_tno, $pg_price, "autocancel", $cancel_msg);
                }
                break;
        }
        
        if(function_exists('add_order_post_log')) add_order_post_log($cancel_msg);
        die("Receipt Amount Error");

        if(function_exists('add_order_post_log')) add_order_post_log($cancel_msg);
        die("Receipt Amount Error");
    }
}

if ($is_member)
    $od_pwd = $member['mb_password'];
else
    $od_pwd = isset($_POST['od_pwd']) ? get_encrypt_string($_POST['od_pwd']) : get_encrypt_string(mt_rand());

$od_escrow = 0;
if(isset($escw_yn) && $escw_yn === 'Y')
    $od_escrow = 1;

// 복합과세 금액
$od_tax_mny = round($i_price / 1.1);
$od_vat_mny = $i_price - $od_tax_mny;
$od_free_mny = 0;
if($default['de_tax_flag_use']) {
    $od_tax_mny = isset($_POST['comm_tax_mny']) ? (int) $_POST['comm_tax_mny'] : 0;
    $od_vat_mny = isset($_POST['comm_vat_mny']) ? (int) $_POST['comm_vat_mny'] : 0;
    $od_free_mny = isset($_POST['comm_free_mny']) ? (int) $_POST['comm_free_mny'] : 0;
}

if ($default['de_pg_service'] == "danal") {
    $od_email         = get_email_address($data["od_email"]);
    $od_name          = clean_xss_tags($data["od_name"]);
    $od_tel           = clean_xss_tags($data["od_tel"]);
    $od_hp            = clean_xss_tags($data["od_hp"]);
    $od_zip           = preg_replace('/[^0-9]/', '', $data["od_zip"]);
    $od_zip1          = substr($data["od_zip"], 0, 3);
    $od_zip2          = substr($data["od_zip"], 3);
    $od_addr1         = clean_xss_tags($data["od_addr1"]);
    $od_addr2         = clean_xss_tags($data["od_addr2"]);
    $od_addr3         = clean_xss_tags($data["od_addr3"]);
    $od_addr_jibeon   = preg_match("/^(N|R)$/", $data["od_addr_jibeon"]) ? $data["od_addr_jibeon"] : '';
    $od_b_name        = clean_xss_tags($data["od_b_name"]);
    $od_b_tel         = clean_xss_tags($data["od_b_tel"]);
    $od_b_hp          = clean_xss_tags($data["od_b_hp"]);
    $od_b_addr1       = clean_xss_tags($data["od_b_addr1"]);
    $od_b_addr2       = clean_xss_tags($data["od_b_addr2"]);
    $od_b_addr3       = clean_xss_tags($data["od_b_addr3"]);
    $od_b_addr_jibeon = preg_match("/^(N|R)$/", $data["od_b_addr_jibeon"]) ? $data["od_b_addr_jibeon"] : '';
    $od_memo          = clean_xss_tags($data["od_memo"], 1, 1, 0, 0);
    $od_deposit_name  = clean_xss_tags($data["od_deposit_name"]);
    $od_tax_flag      = $default['de_tax_flag_use'];
	$od_partner       = clean_xss_tags($data["od_partner"]);
} else {
    $od_email         = get_email_address($od_email);
    $od_name          = clean_xss_tags($od_name);
    $od_tel           = clean_xss_tags($od_tel);
    $od_hp            = clean_xss_tags($od_hp);
    $od_zip           = preg_replace('/[^0-9]/', '', $od_zip);
    $od_zip1          = substr($od_zip, 0, 3);
    $od_zip2          = substr($od_zip, 3);
    $od_addr1         = clean_xss_tags($od_addr1);
    $od_addr2         = clean_xss_tags($od_addr2);
    $od_addr3         = clean_xss_tags($od_addr3);
    $od_addr_jibeon   = preg_match("/^(N|R)$/", $od_addr_jibeon) ? $od_addr_jibeon : '';
    $od_b_name        = clean_xss_tags($od_b_name);
    $od_b_tel         = clean_xss_tags($od_b_tel);
    $od_b_hp          = clean_xss_tags($od_b_hp);
    $od_b_addr1       = clean_xss_tags($od_b_addr1);
    $od_b_addr2       = clean_xss_tags($od_b_addr2);
    $od_b_addr3       = clean_xss_tags($od_b_addr3);
    $od_b_addr_jibeon = preg_match("/^(N|R)$/", $od_b_addr_jibeon) ? $od_b_addr_jibeon : '';
    $od_memo          = clean_xss_tags($od_memo, 1, 1, 0, 0);
    $od_deposit_name  = clean_xss_tags($od_deposit_name);
    $od_tax_flag      = $default['de_tax_flag_use'];
	$od_partner       = clean_xss_tags($od_partner);
}

// 주문서에 입력
$sql = " insert {$g5['g5_shop_order_table']}
            set od_id             = '$od_id',
                mb_id             = '{$member['mb_id']}',
                od_pwd            = '$od_pwd',
                od_name           = '$od_name',
                od_email          = '$od_email',
                od_tel            = '$od_tel',
                od_hp             = '$od_hp',
                od_zip1           = '$od_zip1',
                od_zip2           = '$od_zip2',
                od_addr1          = '$od_addr1',
                od_addr2          = '$od_addr2',
                od_addr3          = '$od_addr3',
                od_addr_jibeon    = '$od_addr_jibeon',
                od_b_name         = '$od_b_name',
                od_b_tel          = '$od_b_tel',
                od_b_hp           = '$od_b_hp',
                od_b_zip1         = '$od_b_zip1',
                od_b_zip2         = '$od_b_zip2',
                od_b_addr1        = '$od_b_addr1',
                od_b_addr2        = '$od_b_addr2',
                od_b_addr3        = '$od_b_addr3',
                od_b_addr_jibeon  = '$od_b_addr_jibeon',
                od_deposit_name   = '$od_deposit_name',
                od_memo           = '$od_memo',
                od_cart_count     = '$cart_count',
                od_cart_price     = '$tot_ct_price',
                od_cart_coupon    = '$tot_it_cp_price',
                od_send_cost      = '$od_send_cost',
                od_send_coupon    = '$tot_sc_cp_price',
                od_send_cost2     = '$od_send_cost2',
                od_coupon         = '$tot_od_cp_price',
                od_receipt_price  = '$od_receipt_price',
                od_receipt_point  = '$od_receipt_point',
                od_bank_account   = '$od_bank_account',
                od_receipt_time   = '$od_receipt_time',
                od_misu           = '$od_misu',
                od_pg             = '$od_pg',
                od_tno            = '$od_tno',
                od_app_no         = '$od_app_no',
                od_escrow         = '$od_escrow',
                od_tax_flag       = '$od_tax_flag',
                od_tax_mny        = '$od_tax_mny',
                od_vat_mny        = '$od_vat_mny',
                od_free_mny       = '$od_free_mny',
                od_status         = '$od_status',
                od_shop_memo      = '',
                od_hope_date      = '$od_hope_date',
                od_time           = '".G5_TIME_YMDHIS."',
                od_ip             = '$REMOTE_ADDR',
                od_settle_case    = '$od_settle_case',
                od_other_pay_type = '$od_other_pay_type',
                od_partner = '$od_partner',
                od_test           = '{$default['de_card_test']}'
                ";
$result = sql_query($sql, false);

// 정말로 insert 가 되었는지 한번더 체크한다.
$exists_sql = "select od_id, od_tno, od_ip from {$g5['g5_shop_order_table']} where od_id = '$od_id'";
$exists_order = sql_fetch($exists_sql);

// 주문정보 입력 오류시 결제 취소
if(! $result || ! (isset($exists_order['od_id']) && $od_id && $exists_order['od_id'] === $od_id)) {
    if($tno) {
        $cancel_msg = '주문정보 입력 오류 : '.$sql;
        switch($od_pg) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_cancel.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inipay_cancel.php';
                break;
            case 'KAKAOPAY':
                $_REQUEST['TID']               = $tno;
                $_REQUEST['Amt']               = $amount;
                $_REQUEST['CancelMsg']         = $cancel_msg;
                $_REQUEST['PartialCancelCode'] = 0;
                include G5_SHOP_PATH.'/kakaopay/kakaopay_cancel.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub_cancel.php';
                break;
            case "danal" :
                if ($od_settle_case == "계좌이체") {
                    //CancelBank($od_tno, $pg_price, "autocancel", $cancel_msg);
                } else if ($od_settle_case == "휴대폰") {
                    //CancelTeledit($od_tno);
                } else if ($od_settle_case == "신용카드") {
                    CancelCredit($od_tno, $pg_price, "autocancel", $cancel_msg);
                }
                break;
        }
    }

    // 관리자에게 오류 알림 메일발송
    $error = 'order';
    include G5_SHOP_PATH.'/ordererrormail.php';
    
    if(function_exists('add_order_post_log')) add_order_post_log($cancel_msg);
    die('<p>고객님의 주문 정보를 처리하는 중 오류가 발생해서 주문이 완료되지 않았습니다.</p><p>'.strtoupper($od_pg).'를 이용한 전자결제(신용카드, 계좌이체, 가상계좌 등)은 자동 취소되었습니다.');
}

// 장바구니 상태변경
// 신용카드로 주문하면서 신용카드 포인트 사용하지 않는다면 포인트 부여하지 않음
$cart_status = $od_status;
$sql_card_point = "";
if ($od_receipt_price > 0 && !$default['de_card_point']) {
    $sql_card_point = " , ct_point = '0' ";
}

// 회원 아이디 값 변경
$sql_mb_id = "";
if ($is_member) {
    $sql_mb_id = " , mb_id = '{$member['mb_id']}' ";
}

$sql = "update {$g5['g5_shop_cart_table']}
           set od_id = '$od_id',
               ct_status = '$cart_status'
               $sql_card_point
               $sql_mb_id
         where od_id = '$tmp_cart_id'
           and ct_select = '1' ";
$result = sql_query($sql, false);

// 주문정보 입력 오류시 결제 취소
if(!$result) {
    if($tno) {
        $cancel_msg = '주문상태 변경 오류';
        switch($od_pg) {
            case 'lg':
                include G5_SHOP_PATH.'/lg/xpay_cancel.php';
                break;
            case 'inicis':
                include G5_SHOP_PATH.'/inicis/inipay_cancel.php';
                break;
            case 'KAKAOPAY':
                $_REQUEST['TID']               = $tno;
                $_REQUEST['Amt']               = $amount;
                $_REQUEST['CancelMsg']         = $cancel_msg;
                $_REQUEST['PartialCancelCode'] = 0;
                include G5_SHOP_PATH.'/kakaopay/kakaopay_cancel.php';
                break;
            case 'kcp':
                include G5_SHOP_PATH.'/kcp/pp_ax_hub_cancel.php';
                break;
            case "danal" :
                if ($od_settle_case == "계좌이체") {
                    //CancelBank($od_tno, $pg_price, "autocancel", $cancel_msg);
                } else if ($od_settle_case == "휴대폰") {
                    //CancelTeledit($od_tno);
                } else if ($od_settle_case == "신용카드") {
                    CancelCredit($od_tno, $pg_price, "autocancel", $cancel_msg);
                }
                break;
        }
    }

    // 관리자에게 오류 알림 메일발송
    $error = 'status';
    include G5_SHOP_PATH.'/ordererrormail.php';
    
    if(function_exists('add_order_post_log')) add_order_post_log($cancel_msg);
    // 주문삭제
    sql_query(" delete from {$g5['g5_shop_order_table']} where od_id = '$od_id' ");

    die('<p>고객님의 주문 정보를 처리하는 중 오류가 발생해서 주문이 완료되지 않았습니다.</p><p>'.strtoupper($od_pg).'를 이용한 전자결제(신용카드, 계좌이체, 가상계좌 등)은 자동 취소되었습니다.');
}

// 회원이면서 포인트를 사용했다면 테이블에 사용을 추가
if ($is_member && $od_receipt_point)
    insert_point($member['mb_id'], (-1) * $od_receipt_point, "주문번호 $od_id 결제");

$od_memo = nl2br(htmlspecialchars2(stripslashes($od_memo))) . "&nbsp;";

// 쿠폰사용내역기록
if($is_member) {
    $it_cp_cnt = (isset($_POST['cp_id']) && is_array($_POST['cp_id'])) ? count($_POST['cp_id']) : 0;
    for($i=0; $i<$it_cp_cnt; $i++) {
        $cid = isset($_POST['cp_id'][$i]) ? clean_xss_tags($_POST['cp_id'][$i], 1, 1) : '';
        $cp_it_id = isset($_POST['it_id'][$i]) ? clean_xss_tags($_POST['it_id'][$i], 1, 1) : '';
        $cp_prc = isset($arr_it_cp_prc[$cp_it_id]) ? (int) $arr_it_cp_prc[$cp_it_id] : 0;

        if(trim($cid)) {
            $sql = " insert into {$g5['g5_shop_coupon_log_table']}
                        set cp_id       = '$cid',
                            mb_id       = '{$member['mb_id']}',
                            od_id       = '$od_id',
                            cp_price    = '$cp_prc',
                            cl_datetime = '".G5_TIME_YMDHIS."' ";
            sql_query($sql);
        }

        // 쿠폰사용금액 cart에 기록
        $sql = " update {$g5['g5_shop_cart_table']}
                    set cp_price = '$cp_prc'
                    where od_id = '$od_id'
                      and it_id = '$cp_it_id'
                      and ct_select = '1'
                    order by ct_id asc
                    limit 1 ";
        sql_query($sql);
    }

    if(isset($_POST['od_cp_id']) && $_POST['od_cp_id']) {
        $sql = " insert into {$g5['g5_shop_coupon_log_table']}
                    set cp_id       = '{$_POST['od_cp_id']}',
                        mb_id       = '{$member['mb_id']}',
                        od_id       = '$od_id',
                        cp_price    = '$tot_od_cp_price',
                        cl_datetime = '".G5_TIME_YMDHIS."' ";
        sql_query($sql);
    }

    if(isset($_POST['sc_cp_id']) && $_POST['sc_cp_id']) {
        $sql = " insert into {$g5['g5_shop_coupon_log_table']}
                    set cp_id       = '{$_POST['sc_cp_id']}',
                        mb_id       = '{$member['mb_id']}',
                        od_id       = '$od_id',
                        cp_price    = '$tot_sc_cp_price',
                        cl_datetime = '".G5_TIME_YMDHIS."' ";
        sql_query($sql);
    }
}


include_once(G5_SHOP_PATH.'/ordermail1.inc.php');
include_once(G5_SHOP_PATH.'/ordermail2.inc.php');

// SMS BEGIN --------------------------------------------------------
// 주문고객과 쇼핑몰관리자에게 SMS 전송
if($config['cf_sms_use'] && ($default['de_sms_use2'] || $default['de_sms_use3'])) {
    $is_sms_send = (function_exists('is_sms_send')) ? is_sms_send('orderformupdate') : false;

    if($is_sms_send) {
        $sms_contents = array($default['de_sms_cont2'], $default['de_sms_cont3']);
        $recv_numbers = array($od_hp, $default['de_sms_hp']);
        $send_numbers = array($default['de_admin_company_tel'], $default['de_admin_company_tel']);

        $sms_count = 0;
        $sms_messages = array();

        for($s=0; $s<count($sms_contents); $s++) {
            $sms_content = $sms_contents[$s];
            $recv_number = preg_replace("/[^0-9]/", "", $recv_numbers[$s]);
            $send_number = preg_replace("/[^0-9]/", "", $send_numbers[$s]);

            $sms_content = str_replace("{이름}", $od_name, $sms_content);
            $sms_content = str_replace("{보낸분}", $od_name, $sms_content);
            $sms_content = str_replace("{받는분}", $od_b_name, $sms_content);
            $sms_content = str_replace("{주문번호}", $od_id, $sms_content);
            $sms_content = str_replace("{주문금액}", number_format($tot_ct_price + $od_send_cost + $od_send_cost2), $sms_content);
            $sms_content = str_replace("{회원아이디}", $member['mb_id'], $sms_content);
            $sms_content = str_replace("{회사명}", $default['de_admin_company_name'], $sms_content);

            $idx = 'de_sms_use'.($s + 2);

            if($default[$idx] && $recv_number) {
                $sms_messages[] = array('recv' => $recv_number, 'send' => $send_number, 'cont' => $sms_content);
                $sms_count++;
            }
        }

        // 무통장 입금 때 고객에게 계좌정보 보냄
        if($od_settle_case == '무통장' && $default['de_sms_use2'] && $od_misu > 0) {
            $sms_content = $od_name."님의 입금계좌입니다.\n금액:".number_format($od_misu)."원\n계좌:".$od_bank_account."\n".$default['de_admin_company_name'];

            $recv_number = preg_replace("/[^0-9]/", "", $od_hp);
            $send_number = preg_replace("/[^0-9]/", "", $default['de_admin_company_tel']);

            $sms_messages[] = array('recv' => $recv_number, 'send' => $send_number, 'cont' => $sms_content);
            $sms_count++;
        }

        // SMS 전송
        if($sms_count > 0) {
            if($config['cf_sms_type'] == 'LMS') {
                include_once(G5_LIB_PATH.'/icode.lms.lib.php');

                $port_setting = get_icode_port_type($config['cf_icode_id'], $config['cf_icode_pw']);

                // SMS 모듈 클래스 생성
                if($port_setting !== false) {
                    $SMS = new LMS;
                    $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $port_setting);
                    
                    for($s=0; $s<count($sms_messages); $s++) {
                        $strDest     = array();
                        $strDest[]   = $sms_messages[$s]['recv'];
                        $strCallBack = $sms_messages[$s]['send'];
                        $strCaller   = iconv_euckr(trim($default['de_admin_company_name']));
                        $strSubject  = '';
                        $strURL      = '';
                        $strData     = iconv_euckr($sms_messages[$s]['cont']);
                        $strDate     = '';
                        $nCount      = count($strDest);

                        $res = $SMS->Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate, $nCount);

                        $SMS->Send();
                        $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
                    }
                }
            } else {
                include_once(G5_LIB_PATH.'/icode.sms.lib.php');

                $SMS = new SMS; // SMS 연결
                $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);

                for($s=0; $s<count($sms_messages); $s++) {
                    $recv_number = $sms_messages[$s]['recv'];
                    $send_number = $sms_messages[$s]['send'];
                    $sms_content = iconv_euckr($sms_messages[$s]['cont']);

                    $SMS->Add($recv_number, $send_number, $config['cf_icode_id'], $sms_content, "");
                }

                $SMS->Send();
                $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
            }
        }
    }
}
// SMS END   --------------------------------------------------------


// orderview 에서 사용하기 위해 session에 넣고
$uid = md5($od_id.G5_TIME_YMDHIS.$REMOTE_ADDR);
set_session('ss_orderview_uid', $uid);

// 주문 정보 임시 데이터 삭제
if($od_pg == 'inicis') {
    $sql = " delete from {$g5['g5_shop_order_data_table']} where od_id = '$od_id' and dt_pg = '$od_pg' ";
    sql_query($sql);
}

if(function_exists('add_order_post_log')) add_order_post_log('', 'delete');

// 주문번호제거
set_session('ss_order_id', '');

// 기존자료 세션에서 제거
if (get_session('ss_direct'))
    set_session('ss_cart_direct', '');

// 배송지처리
if($is_member) {
    $sql = " select * from {$g5['g5_shop_order_address_table']}
                where mb_id = '{$member['mb_id']}' ";
    $row = sql_fetch($sql);

    // 기본배송지 체크
//    if($ad_default) {
//        $sql = " update {$g5['g5_shop_order_address_table']}
//                    set ad_default = '0'
//                    where mb_id = '{$member['mb_id']}' ";
//        sql_query($sql);
//    }

    $ad_subject = isset($_POST['ad_subject']) ? clean_xss_tags($_POST['ad_subject']) : '';
    if (!$ad_subject) $ad_subject = '기본 주소지';

    if(isset($row['ad_id']) && $row['ad_id']){
        $sql = " update {$g5['g5_shop_order_address_table']}
                      set ad_subject  = '$ad_subject',
                          ad_name     = '$od_b_name',
                          ad_tel      = '$od_b_tel',
                          ad_hp       = '$od_b_hp',
                          ad_zip1     = '$od_b_zip1',
                          ad_zip2     = '$od_b_zip2',
                          ad_addr1    = '$od_b_addr1',
                          ad_addr2    = '$od_b_addr2',
                          ad_addr3    = '$od_b_addr3',
                          ad_jibeon   = '$od_b_addr_jibeon'
                    where mb_id = '{$member['mb_id']}' ";
    } else {
        $sql = " insert into {$g5['g5_shop_order_address_table']}
                    set mb_id       = '{$member['mb_id']}',
                        ad_subject  = '$ad_subject',
                        ad_default  = '1',
                        ad_name     = '$od_b_name',
                        ad_tel      = '$od_b_tel',
                        ad_hp       = '$od_b_hp',
                        ad_zip1     = '$od_b_zip1',
                        ad_zip2     = '$od_b_zip2',
                        ad_addr1    = '$od_b_addr1',
                        ad_addr2    = '$od_b_addr2',
                        ad_addr3    = '$od_b_addr3',
                        ad_jibeon   = '$od_b_addr_jibeon' ";
    }
    
    sql_query($sql);
}

include_once(G5_SHOP_PATH.'/orderalimtalk.inc.php'); // wetoz : 알림톡연동

if ($default['de_pg_service'] == "danal") {
    $_SESSION["payinfo"] = null;
    if (!is_mobile()) {
?>
<script>
    opener.location.href = '<?=G5_SHOP_URL.'/orderinquiryview.php?od_id='.$od_id.'&amp;uid='.$uid?>';
    window.close();
</script>
<?
    } else {
        goto_url(G5_SHOP_URL.'/orderinquiryview.php?od_id='.$od_id.'&amp;uid='.$uid);
    }
} else {
    goto_url(G5_SHOP_URL.'/orderinquiryview.php?od_id='.$od_id.'&amp;uid='.$uid);
}
?>
<html>
    <head>
        <title>주문정보 기록</title>
        <script>
            // 결제 중 새로고침 방지 샘플 스크립트 (중복결제 방지)
            function noRefresh()
            {
                /* CTRL + N키 막음. */
                if ((event.keyCode == 78) && (event.ctrlKey == true))
                {
                    event.keyCode = 0;
                    return false;
                }
                /* F5 번키 막음. */
                if(event.keyCode == 116)
                {
                    event.keyCode = 0;
                    return false;
                }
            }

            document.onkeydown = noRefresh ;
        </script>
    </head>
</html>