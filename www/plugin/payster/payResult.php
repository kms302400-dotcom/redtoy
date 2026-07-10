<?php
include_once("../../common.php");
header("Content-Type:text/html; charset=utf-8;");

function LogToApache($log){ //로그내용 인자
    $logPathDir = $_SERVER['DOCUMENT_ROOT']."/data/log/";  //로그위치 지정

    $filePath = $logPathDir."/".date("Y")."/".date("n");
    $folderName1 = date("Y"); //폴더 1 년도 생성
    $folderName2 = date("n"); //폴더 2 월 생성

    if(!is_dir($logPathDir."/".$folderName1)){
        mkdir($logPathDir."/".$folderName1, 0777);
    }

    if(!is_dir($logPathDir."/".$folderName1."/".$folderName2)){
        mkdir(($logPathDir."/".$folderName1."/".$folderName2), 0777);
    }

    $log_file = fopen($logPathDir."/".$folderName1."/".$folderName2."/".date("Ymd").".txt", "a");
    fwrite($log_file, $log."\r\n =================================================================". G5_TIME_YMDHIS ."============================================================ \r\n");
    fclose($log_file);
}


// API CALL foreach 예시
function jsonRespDump($resp)
{
    $respArr = json_decode($resp);
    foreach ($respArr as $key => $value) {
        $RT[$key] = $value;
    }
}


/*
****************************************************************************************
* <인증 결과 파라미터>
****************************************************************************************
*/
$resultCode = $_POST['resultCode'];        // 인증결과 : 0000(성공)
$resultMsg = $_POST['resultMsg'];        // 인증겨로가 메시지
$tid = $_POST['tid'];                // 거래ID
$payMethod = $_POST['payMethod'];        // 결제수단
$ediDate = $_POST['ediDate'];            // 결제 일시
$mid = $_POST['mid'];                // 상점 아이디
$ordNo = $_POST['ordNo'];            // 싱잠 주문번호
$goodsAmt = $_POST['goodsAmt'];            // 결제 금액
$reqReserved = $_POST['mbsReserved'];        // 상점 예약필드
$signData = $_POST['signData'];            //

LogToApache("인증결과:".$resultCode."<br>");
LogToApache("주문번호 : ".$ordNo."<br>");

if (get_session("ss_direct"))
    $p_cart_id = get_session('ss_cart_direct');
else
    $p_cart_id = get_session('ss_cart_id');



logtoapache($reqReserved."//".$p_cart_id);

if($_POST["resultCode"]==="0000") {
    $sql_2 = "select * from g5_order_temp where s_cart_id = {$reqReserved}  order by idx desc";
    $od = sql_fetch($sql_2);

    $cart_id = $od['s_cart_id'];
    $od_price = $od['od_price'];// 79000;
    $od_id = $od['od_id'];
    $org_od_price = $od['org_od_price'];// 79000;
    $od_send_cost = $od['od_send_cost'];// 3000;
    $od_send_cost2 = $od['od_send_cost2'];// 0;
    $od_coupon = $od['od_coupon'];// 0;
    $od_send_coupon = $od['od_send_coupon'];// 0;
    $mb_id = $od['mb_id'];
    $item_coupon = $od['item_coupon'];// 0;
    $od_temp_point = 0;
    $od_name = $od['od_name'];// 탁동영;
    $od_email = $od['od_email'];// takty74@naver.com;
    $od_tel = $od['od_tel'];// 11111;
    $od_hp = $od['od_hp'];// 010-7297-7895;
    $od_b_name = $od['od_b_name'];// 탁동영;
    $od_b_tel = $od['od_b_tel'];// 11111;
    $od_b_hp = $od['od_b_hp'];// 010-7297-7895;
    $od_zip = $od['od_zip'];// 48093;
    $od_zip1 = substr($od_zip, 0, 3);
    $od_zip2 = substr($od_zip, 3);
    $od_addr1 = $od['od_addr1'];// 67aA7IKwIO2VtOyatOuMgOq1rCDtlbTsmrTrjIDtlbTrs4DroZwgMjU3;
    $od_addr2 = $od['od_addr2'];// MTMwN 2YuA==;
    $od_shop_memo = $od['host'];
    $od_b_zip = $od['od_b_zip'];// 48093;
    $od_b_zip1 = substr($od_b_zip, 0, 3);
    $od_b_zip2 = substr($od_b_zip, 3);
    $od_b_addr1 = $od['od_b_addr1'];// 67aA7IKwIO2VtOyatOuMgOq1rCDtlbTsmrTrjIDtlbTrs4DroZwgMjU3;
    $od_b_addr2 = $od['od_b_addr2'];// MTMwN 2YuA==;
    $od_memo = $od['od_memo'];

    LogToApache("불러오기 성공한 주문번호입니다.".$od_id."입니다.");

}else{
    alert("결제에 실패했습니다.","/");
    exit;
}
/*
*******************************************************
* <해쉬암호화> (수정하지 마세요)
* SHA-256 해쉬암호화는 거래 위변조를 막기위한 방법입니다.
*******************************************************
*/
$merchantKey = "hesukWZ0i4ui+wPzDjanvJK9KYXnpdm5M7/JNv/0f2p9HMrSYc+WwP/jGPpjV7i3gFh8FEgt9fqJfvAl9ycA6A==";                        // 상점키
$encData = bin2hex(hash('sha256', $mid . $ediDate . $goodsAmt . $merchantKey, true));

/*
****************************************************************************************
* <승인 결과 파라미터 정의>
* 샘플페이지에서는 승인 결과 파라미터 중 일부만 예시되어 있으며,
* 추가적으로 사용하실 파라미터는 연동메뉴얼을 참고하세요.
****************************************************************************************
*/

$response = "";


/*
****************************************************************************************
* <인증 결과 성공시 승인 진행>
****************************************************************************************
*/


if ($_POST["resultCode"] === "0000") {

    try {
        $data = array(
            'tid' => $tid,
            'mid' => $mid,
            'goodsAmt' => $goodsAmt,
            'ediDate' => $ediDate,
            'charSet' => 'utf-8',
            'encData' => $encData,
            'signData' => $signData
        );
        $response = reqPost($data, "https://api.payster.co.kr/payment.do");
        logtoapache($response);
        $respArr = json_decode($response);
        foreach ($respArr as $key => $value) {
            $RT[$key] = $value;
        }
        $od_settle_case = "신용카드";
        $cardTradeNo = $RT['appNo'];// 0714C0398706;
        $tno = $tid;
        $cardApprovDate = $RT['appDtm'];// 170714;
        $cardApprovTime = $RT['ediDate'];// 071437;
        $app_time = "20" . $cardApprovDate . $cardApprovTime;
        $cardName = $RT['fnNm'];// 현대카드;
        $cardCode = $RT['acqCardCd'];// 10;
        $cardApprovNo = $RT['appNo'];// 90398706;
        $app_no = $cardApprovNo;
        $resultCd = $RT['resultCd'];
        $resultMsg = $RT['resultMsg'];

        // 두번 결제시도를 하면 0300이 뜨는데 이것은 결제로 봐야 한다고 생각해서 resultCD변경
        if($resultCd ==="0300"){
            $resultCd = "3001";
            $resultMsg ="2번 결제시도 한것으로 보임, 홀빅 관리자 페이지를 한번 확인해주세요";
        }

        $sql_update = "update g5_order_temp set result_msg='{$resultMsg}', card_name = '{$cardName}', appno='{$cardTradeNo}', tid='{$tno}' where s_cart_id = {$reqReserved}";
        logtoapache($sql_update);
        sql_query($sql_update);

        if($resultCd !=="3001"){
            alert("오류가 발생하였습니다."."{$resultMsg}","/");
            exit;
        }


//  여기서부터 결제
        $amount = $od_price;// 82000;

// 장바구니가 비어있는가?
        $tmp_cart_id = $cart_id;
        LogToApache($tmp_cart_id);
        $member['mb_id'] = $mb_id;
        $od_pwd = $od['od_pwd'];



        if (get_cart_count($tmp_cart_id) == 0)// 장바구니에 담기
            LogToApache('장바구니가 비어 있습니다.\\n\\n이미 주문하셨거나 장바구니에 담긴 상품이 없는 경우입니다.', G5_SHOP_URL . '/cart2.php');

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
        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            // 상품에 대한 현재고수량
            if ($row['io_id']) {
                $it_stock_qty = (int)get_option_stock_qty($row['it_id'], $row['io_id'], $row['io_type']);
            } else {
                $it_stock_qty = (int)get_it_stock_qty($row['it_id']);
            }
            // 장바구니 수량이 재고수량보다 많다면 오류
            if ($row['ct_qty'] > $it_stock_qty)
                $error .= "{$row['ct_option']} 의 재고수량이 부족합니다. 현재고수량 : $it_stock_qty 개\\n\\n";
        }

        if ($i == 0) {
            LogToApache('장바구니가 비어 있습니다.\\n\\n이미 주문하셨거나 장바구니에 담긴 상품이 없는 경우입니다.', G5_SHOP_URL . '/cart.php');
            exit;
        }

        if ($error != "") {
            $error .= "다른 고객님께서 {$od_name}님 보다 먼저 주문하신 경우입니다. 불편을 끼쳐 죄송합니다.";
            LogToApache($error);
            exit;
        }

        $i_price = $od_price;
        $i_send_cost = $od_send_cost;
        $i_send_cost2 = $od_send_cost2;
        $i_send_coupon = $od_send_coupon;
        $i_temp_point = $od_temp_point;


// 주문금액이 상이함
        $sql = " select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as od_price,
              COUNT(distinct it_id) as cart_count
            from {$g5['g5_shop_cart_table']} where od_id = '$tmp_cart_id' and ct_select = '1' ";
        $row = sql_fetch($sql);
        $tot_ct_price = $row['od_price'];
        $cart_count = $row['cart_count'];
        $tot_od_price = $tot_ct_price;

// 쿠폰금액계산

// 배송비가 상이함
        $send_cost = get_sendcost($tmp_cart_id);


        $i_price = $i_price + $i_send_cost + $i_send_cost2 - $i_temp_point - $i_send_coupon;
        $order_price = $tot_od_price + $send_cost + $send_cost2 - $tot_sc_cp_price - $od_temp_point;
        $od_receipt_price = $order_price;

        $od_status = '주문';
        $od_tno = '';


        $od_pg = "payster";
        if ($od_settle_case == 'KAKAOPAY')
            $od_pg = 'KAKAOPAY';



        $od_pwd = get_encrypt_string($od_pwd);

// 주문번호를 얻는다.
        $od_escrow = 0;
        if ($escw_yn == 'Y')
            $od_escrow = 1;

// 복합과세 금액
        $od_tax_mny = round($i_price / 1.1);
        $od_vat_mny = $i_price - $od_tax_mny;
        $od_free_mny = 0;
        if ($default['de_tax_flag_use']) {
            $od_tax_mny = (int)$_POST['comm_tax_mny'];
            $od_vat_mny = (int)$_POST['comm_vat_mny'];
            $od_free_mny = (int)$_POST['comm_free_mny'];
        }

        $od_email = get_email_address($od_email);
        $od_name = clean_xss_tags($od_name);
        $od_tel = clean_xss_tags($od_tel);
        $od_hp = clean_xss_tags($od_hp);
        $od_zip = preg_replace('/[^0-9]/', '', $od_zip);
        $od_zip1 = substr($od_zip, 0, 3);
        $od_zip2 = substr($od_zip, 3);
        $od_addr1 = clean_xss_tags($od_addr1);
        $od_addr2 = clean_xss_tags($od_addr2);
        $od_addr3 = clean_xss_tags($od_addr3);
        $od_addr_jibeon = preg_match("/^(N|R)$/", $od_addr_jibeon) ? $od_addr_jibeon : '';
        $od_b_name = clean_xss_tags($od_b_name);
        $od_b_tel = clean_xss_tags($od_b_tel);
        $od_b_hp = clean_xss_tags($od_b_hp);
        $od_b_addr1 = clean_xss_tags($od_b_addr1);
        $od_b_addr2 = clean_xss_tags($od_b_addr2);
        $od_b_addr3 = clean_xss_tags($od_b_addr3);
        $od_b_addr_jibeon = preg_match("/^(N|R)$/", $od_b_addr_jibeon) ? $od_b_addr_jibeon : '';
        $od_memo = clean_xss_tags($od_memo);
        $od_deposit_name = clean_xss_tags($od_deposit_name);
        $od_tax_flag = $default['de_tax_flag_use'];

// 주문서에 입력
        $sql = " insert {$g5['g5_shop_order_table']}
            set od_id             = '$od_id',
                mb_id             = '$mb_id',
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
                od_receipt_time   = '" . G5_TIME_YMDHIS . "',
                od_misu           = '$od_misu',
                od_pg             = '$od_pg',
                od_tno            = '$od_tno',
                od_app_no         = '$od_app_no',
                od_escrow         = '$od_escrow',
                od_tax_flag       = '$od_tax_flag',
                od_tax_mny        = '$od_tax_mny',
                od_vat_mny        = '$od_vat_mny',
                od_free_mny       = '$od_free_mny',
                od_status         = '입금',
                od_shop_memo      = '$od_shop_memo',
                od_hope_date      = '$od_hope_date',
                od_time           = '" . G5_TIME_YMDHIS . "',
                od_ip             = '$REMOTE_ADDR',
                od_settle_case    = '$od_settle_case',
                od_test           = ''
                ";
        $result = sql_query($sql, false);






// 주문정보 입력 오류시 결제 취소
        if (!$result) {
            if ($tno) {
                $cancel_msg = '주문정보 입력 오류';
                include G5_SHOP_PATH . '/ssqpg/ssq_cancel.php';
                exit;
            }

            // 관리자에게 오류 알림 메일발송
            $error = 'order';
            include G5_SHOP_PATH . '/ordererrormail.php';

            LogToApache('<p>고객님의 주문 정보를 처리하는 중 오류가 발생해서 주문이 완료되지 않았습니다.</p><p>' . strtoupper($od_pg) . '를 이용한 전자결제(신용카드, 계좌이체, 가상계좌 등)은 자동 취소되었습니다.');
        }
        // echo "여기까진왔나?";
// 장바구니 상태변경
// 신용카드로 주문하면서 신용카드 포인트 사용하지 않는다면 포인트 부여하지 않음
        $cart_status = $od_status;
        $sql_card_point = "";
        if ($od_receipt_price > 0 && !$default['de_card_point']) {
            $sql_card_point = " , ct_point = '0' ";
        }
        $sql = "update {$g5['g5_shop_cart_table']}
           set od_id = '$od_id',
               ct_status = '$cart_status'
               $sql_card_point
         where od_id = '$tmp_cart_id'
           and ct_select = '1' ";
        $result = sql_query($sql, false);

// 주문정보 입력 오류시 결제 취소
        if (!$result) {
            if ($tno) {
                $cancel_msg = '주문상태 변경 오류';
                include G5_SHOP_PATH . '/ssqpg/ssq_cancel.php';
                exit;
            }

            // 관리자에게 오류 알림 메일발송
            $error = 'status';
            include G5_SHOP_PATH . '/ordererrormail.php';

            // 주문삭제
            sql_query(" delete from {$g5['g5_shop_order_table']} where od_id = '$tmp_cart_id' ");

            LogToApache('<p>고객님의 주문 정보를 처리하는 중 오류가 발생해서 주문이 완료되지 않았습니다.</p><p>' . strtoupper($od_pg) . '를 이용한 전자결제(신용카드, 계좌이체, 가상계좌 등)은 자동 취소되었습니다.');
        }


        $od_memo = nl2br(htmlspecialchars2(stripslashes($od_memo))) . "&nbsp;";


        include_once(G5_SHOP_PATH . '/ordermail1.inc.php');
        include_once(G5_SHOP_PATH . '/ordermail2.inc.php');



// SMS BEGIN --------------------------------------------------------
// 주문고객과 쇼핑몰관리자에게 SMS 전송
        if ($config['cf_sms_use'] && ($default['de_sms_use2'] || $default['de_sms_use3'])) {
            $is_sms_send = false;

            // 충전식일 경우 잔액이 있는지 체크
            if ($config['cf_icode_id'] && $config['cf_icode_pw']) {
                $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);

                if ($userinfo['code'] == 0) {
                    if ($userinfo['payment'] == 'C') { // 정액제
                        $is_sms_send = true;
                    } else {
                        $minimum_coin = 100;
                        if (defined('G5_ICODE_COIN'))
                            $minimum_coin = intval(G5_ICODE_COIN);

                        if ((int)$userinfo['coin'] >= $minimum_coin)
                            $is_sms_send = true;
                    }
                }
            }

            if ($is_sms_send) {
                $sms_contents = array($default['de_sms_cont2'], $default['de_sms_cont3']);
                $recv_numbers = array($od_hp, $default['de_sms_hp']);
                $send_numbers = array($default['de_admin_company_tel'], $default['de_admin_company_tel']);

                $sms_count = 0;
                $sms_messages = array();

                for ($s = 0; $s < count($sms_contents); $s++) {
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

                    $idx = 'de_sms_use' . ($s + 2);

                    if ($default[$idx] && $recv_number) {
                        $sms_messages[] = array('recv' => $recv_number, 'send' => $send_number, 'cont' => $sms_content);
                        $sms_count++;
                    }
                }

                // 무통장 입금 때 고객에게 계좌정보 보냄
                if ($od_settle_case == '무통장' && $default['de_sms_use2'] && $od_misu > 0) {
                    $sms_content = $od_name . "님의 입금계좌입니다.\n금액:" . number_format($od_misu) . "원\n계좌:" . $od_bank_account . "\n" . $default['de_admin_company_name'];

                    $recv_number = preg_replace("/[^0-9]/", "", $od_hp);
                    $send_number = preg_replace("/[^0-9]/", "", $default['de_admin_company_tel']);

                    $sms_messages[] = array('recv' => $recv_number, 'send' => $send_number, 'cont' => $sms_content);
                    $sms_count++;
                }

                // SMS 전송
                if ($sms_count > 0) {
                    if ($config['cf_sms_type'] == 'LMS') {
                        include_once(G5_LIB_PATH . '/icode.lms.lib.php');

                        $port_setting = get_icode_port_type($config['cf_icode_id'], $config['cf_icode_pw']);

                        // SMS 모듈 클래스 생성
                        if ($port_setting !== false) {
                            $SMS = new LMS;
                            $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $port_setting);

                            for ($s = 0; $s < count($sms_messages); $s++) {
                                $strDest = array();
                                $strDest[] = $sms_messages[$s]['recv'];
                                $strCallBack = $sms_messages[$s]['send'];
                                $strCaller = iconv_euckr(trim($default['de_admin_company_name']));
                                $strSubject = '';
                                $strURL = '';
                                $strData = iconv_euckr($sms_messages[$s]['cont']);
                                $strDate = '';
                                $nCount = count($strDest);

                                $res = $SMS->Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate, $nCount);

                                $SMS->Send();
                                $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
                            }
                        }
                    } else {
                        include_once(G5_LIB_PATH . '/icode.sms.lib.php');

                        $SMS = new SMS; // SMS 연결
                        $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);

                        for ($s = 0; $s < count($sms_messages); $s++) {
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
        $uid = md5($od_id . G5_TIME_YMDHIS . $REMOTE_ADDR);
        set_session('ss_orderview_uid', $uid);

// 주문 정보 임시 데이터 삭제
        if ($od_pg == 'inicis') {
            $sql = " delete from {$g5['g5_shop_order_data_table']} where od_id = '$od_id' and dt_pg = '$od_pg' ";
            sql_query($sql);
        }

// 주문번호제거
        set_session('ss_order_id', '');

// 기존자료 세션에서 제거
        if (get_session('ss_direct'))
            set_session('ss_cart_direct', '');

// 배송지처리
        if ($is_member) {
            $sql = " select * from {$g5['g5_shop_order_address_table']}
                where mb_id = '{$member['mb_id']}'
                  and ad_name = '$od_b_name'
                  and ad_tel = '$od_b_tel'
                  and ad_hp = '$od_b_hp'
                  and ad_zip1 = '$od_b_zip1'
                  and ad_zip2 = '$od_b_zip2'
                  and ad_addr1 = '$od_b_addr1'
                  and ad_addr2 = '$od_b_addr2'
                  and ad_addr3 = '$od_b_addr3' ";
            $row = sql_fetch($sql);

            // 기본배송지 체크
            if ($ad_default) {
                $sql = " update {$g5['g5_shop_order_address_table']}
                    set ad_default = '0'
                    where mb_id = '{$member['mb_id']}' ";
                sql_query($sql);
            }

            $ad_subject = clean_xss_tags($ad_subject);

            if ($row['ad_id']) {
                $sql = " update {$g5['g5_shop_order_address_table']}
                      set ad_default = '$ad_default',
                          ad_subject = '$ad_subject',
                          ad_jibeon  = '$od_b_addr_jibeon'
                    where mb_id = '{$member['mb_id']}'
                      and ad_id = '{$row['ad_id']}' ";
            } else {
                $sql = " insert into {$g5['g5_shop_order_address_table']}
                    set mb_id       = '{$member['mb_id']}',
                        ad_subject  = '$ad_subject',
                        ad_default  = '$ad_default',
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


//  여기까지 결제
        if ($member['mb_id']) {
            alert("결제가 완료되었습니다.", "/shop/orderinquiryview.php?od_id=".$od_id);
        } else {
            alert("결제가 완료되었습니다.", "/shop/");
        }


    } catch (Exception $e) {
        // 실패처리
    }

} else {
    // 인증 실패처리
}



//Post api call
function reqPost(array $data, $url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);                    //connection timeout 15
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));    //POST data
    curl_setopt($ch, CURLOPT_POST, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

?>
