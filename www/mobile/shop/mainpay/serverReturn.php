<?php
include_once('../../common.php');
include_once(g5_lib_path.'/mailer.lib.php');


header('Content-Type: text/html; charset=utf-8');
require('utils.php'); // 유틸리티 포함
$logPath = $_SERVER['DOCUMENT_ROOT'] . "/data/log/app.log";         //디버그 로그위치 (windows)
if ($_SERVER['HTTPS'] != 'on') {
    $HTT = "http";
} else {
    $HTT = "https";
}
$_host = $_SERVER['HTTP_HOST'];
$site_domain = $HTT . "://" . $_host;

/********************************************************************************
 * 인증이 완료될 경우 PG사에서 호출하는 페이지 입니다.
 * PG로 부터 전달 받은 인증값을 받아 PG로 승인요청을 합니다.
 ********************************************************************************/

$aid = $_REQUEST["aid"];
$authToken = $_REQUEST["authToken"];
$merchantData = $_REQUEST["merchantData"];
$payType = $_REQUEST["payType"];
$receiveParam = "## 인증결과수신 ## aid:" . $aid . ", authToken:" . $authToken;
pintLog("RECEIVE-PARAM: " . $receiveParam, $logPath);
echo $receiveParam; // 실운영시 제거

/********************************************************************************
 *    ready에서 DB에 저장한 요청정보 값 조회해서 사용하세요.
 ********************************************************************************/
$API_BASE = "https://test-api-std.mainpay.co.kr";
$apiKey = "U1FVQVJFLTEwMDAxMTIwMTgwNDA2MDkyNTMyMTA1MjM0";

$version = "V001";
$mbrNo = "100011";
/*$mbrRefNo = makeMbrRefNo($mbrNo);*/
$mbrRefNo = "P000010001";
$paymethod = "CARD";
$amount = "1004";
$goodsName = "카약-슬라이더406";
$goodsCode = "GOOD0001";
$approvalUrl = $site_domain . "/plugin/mainpay/pc/_3_approval.php";
$closeUrl = $site_domain . "/plugin/mainpay/pc/_3_close.php";
$customerName = "고객명";
$customerEmail = "test@spc.co.kr";
$timestamp = makeTimestamp();
$signature = makeSignature($mbrNo, $mbrRefNo, $amount, $apiKey, $timestamp);

$parameters = array(
    'version' => $version,
    'mbrNo' => $mbrNo,
    'mbrRefNo' => $mbrRefNo,
    'paymethod' => $paymethod,
    'amount' => $amount,
    'goodsName' => $goodsName,
    'goodsCode' => $goodsCode,
    'approvalUrl' => $approvalUrl,
    'closeUrl' => $closeUrl,
    'customerName' => $customerName,
    'customerEmail' => $customerEmail,
    'timestamp' => $timestamp,
    'signature' => $signature
);

$parameters["aid"] = $aid;
$parameters["authToken"] = $authToken;
$parameters["merchantData"] = $merchantData;
$parameters["payType"] = $payType;

/********************************************************************************
 * 승인요청 API 호출
 *********************************************************************************/
$PAY_API_URL = $API_BASE . "/v1/payment/pay";
$result = "";
$errorMessage = "";

try {
    pintLog("PAY-API: " . $PAY_API_URL, $logPath);
    pintLog("PARAM: " . print_r($parameters, TRUE), $logPath);
    $result = httpPost($PAY_API_URL, $parameters);
} catch (Exception $e) {
    $errorMessage = "승인요청API 호출실패: " . $e;
    pintLog($errorMessage, $logPath);

    /*********************************************************************************
     * 망취소 처리(승인API 호출 도중 응답수신에 실패한 경우)
     *********************************************************************************/
    $NET_CANCEL_URL = $API_BASE . "/v1/payment/net-cancel";
    $result = httpPost($NET_CANCEL_URL, $parameters);
    return;
}
echo("<br>## 승인요청API 호출 결과 ##<br>" . $result);
pintLog("RESPONSE: " . $result, $logPath);

$obj = json_decode($result);
$resultCode = $obj->{'resultCode'};
$resultMessage = $obj->{'resultMessage'};
$data = $obj->{'data'};
/* 추가 항목은 연동매뉴얼 참조*/
$refNo = "";      // 거래번호
$tranDate = "";      // 거래일자
$mbrRefNo = "";   // 주문번호
$applNo = "";     // 승인번호
$payType = "";    // 인증타입

/*********************************************************************************
 * 승인결과 처리 (결과에 따라 상점 DB처리 및 화면 전환 처리)
 *********************************************************************************/
if ($resultCode != "200") {
    /*호출실패*/
    $errorMessage = "<br>## 승인요청API 호출 결과 ##<br>resultCode = " . $resultCode . ", resultMessage = " . $resultMessage;
    echo $errorMessage; // 실운영시 제거
    return;
} else {
    /*승인요청API 호출 성공*/
    $refNo = $data->{'refNo'};
    $tranDate = $data->{'tranDate'};
    $mbrRefNo = $data->{'mbrRefNo'};
    $applNo = $data->{'applNo'};
    $payType = $data->{'payType'};

    /*== 가맹점 DB 주문처리 ==*/
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>상점 도착페이지</title>
</head>
<body>
<script>
    /* 결제 완료 페이지 호출 */
    var resultCode = "<?=$resultCode ?>";
    var resultMessage = "<?=$resultMessage ?>";
    alert("resultCode = " + resultCode + ", resultMessage = " + resultMessage);

    /* 현재 팝업 닫기*/
    //Mainpay.close(true);
</script>
</body>
</html>















//넘어온 값들 정리

$od_price  = $_get[od_price];// 79000;
$org_od_price  = $_get[org_od_price];// 79000;
$od_send_cost  = $_get[od_send_cost];// 3000;
$od_send_cost2  = $_get[od_send_cost2];// 0;
$od_coupon  = $_get[od_coupon];// 0;
$od_send_coupon  = $_get[od_send_coupon];// 0;
$item_coupon  = $_get[item_coupon];// 0;
$od_temp_point = 0;
$od_name  = $_get[od_name];// 탁동영;
$od_email  = $_get[od_email];// takty74@naver.com;
$od_tel  = $_get[od_tel];// 11111;
$od_hp  = $_get[od_hp];// 010-7297-7895;
$od_b_name  = $_get[od_b_name];// 탁동영;
$od_b_tel  = $_get[od_b_tel];// 11111;
$od_b_hp  = $_get[od_b_hp];// 010-7297-7895;
$od_zip  = $_get[od_zip];// 48093;
$od_zip1          = substr($od_zip, 0, 3);
$od_zip2          = substr($od_zip, 3);
$od_addr1  = base64_decode($_get[od_addr1]);// 67aa7ikwio2vtoyatoumgoq1rcdtlbtsmrtrjidtlbtrs4drozwgmju3;
$od_addr2  = base64_decode($_get[od_addr2]);// mtmwn 2yua==;

$od_b_zip  = $_get[od_b_zip];// 48093;
$od_b_zip1          = substr($od_b_zip, 0, 3);
$od_b_zip2          = substr($od_b_zip, 3);
$od_b_addr1  = base64_decode($_get[od_b_addr1]);// 67aa7ikwio2vtoyatoumgoq1rcdtlbtsmrtrjidtlbtrs4drozwgmju3;
$od_b_addr2  = base64_decode($_get[od_b_addr2]);// mtmwn 2yua==;


$rescode  = $_post[rescode];// 0000;
$resmsg  = $_post[resmsg];// 정상;

if($rescode != '0000'){
  logtoapache('오류가 발생하였습니다.', $resmsg . ' 입니다.');
  exit;
}

$rstcode  = $_post[rstcode];// 0000;
$rstmsg  = $_post[rstmsg];// 정상;
$amount  = $_post[salesprice];// 82000;
$od_id  = $_post[oid];// 2017071407151984;
$hashvalue  = $_post[hashvalue];// 2017071407151900238caba0978b37bc07a7eef5b9a0d4154c9fc8d7dfaf9d9cd754fbbe2203da;
$installno  = $_post[installno];// 02;

$paytype = $_post[paytype];// 3d신용카드, 3:계좌이체 4:휴대폰결제;
$paykind = $_post[paykind];
switch($paykind) {
    case '3':
// 계좌이체시 넘어오는 값
        $od_settle_case = "계좌이체";
        $accounttradeno = $_post[accounttradeno];// 2017071308051879<br />
        $tno = $accounttradeno;
        $accountapprov = $_post[accountapprov];// mainp2017071308075986663<br />
        $ccountapprovdate = $_post[ccountapprovdate];// 170713<br />
        $ccountapod_settle_caseprovtime = $_post[ccountapprovtime];// 080605<br />
        $app_time = "20".$ccountapprovdate.$ccountapod_settle_caseprovtime;
        $ccountbankname = $_post[ccountbankname];// 신한<br />
        $bank_name = $ccountbankname;
        $accountbankcode = $_post[accountbankcode];// 88<br />
        $od_bank_account = $accountapprov;
        $paytype = $_post[paytype];// account<br /
        break;
    case '1':
// 신용카드일경우
        $od_settle_case = "신용카드";
        $authtype = $_post[authtype];// auth;
        $cardtradeno = $_post[cardtradeno];// 0714c0398706;
        $tno = $cardtradeno;
        $cardapprovdate = $_post[cardapprovdate];// 170714;
        $cardapprovtime = $_post[cardapprovtime];// 071437;
        $app_time = "20".$cardapprovdate.$cardapprovtime;
        $cardname = $_post[cardname];// 현대카드;
        $od_bank_account = $cardname;
        $cardcode = $_post[cardcode];// 10;
        $cardapprovno = $_post[cardapprovno];// 90398706;
        $app_no =$cardapprovno;
        break;
//휴대폰 결제의 경우
    case '4':
        $od_settle_case ="휴대폰";

        $mobiletradeno = $_post[mobiletradeno]; // 0715p0398760<br />
        $od_bank_account = "휴대폰결제".$mobiletradeno;
        $tno = $mobiletradeno;
        $mobileapprovdate = $_post[mobileapprovdate]; // 170715<br />
        $mobileapprovtime = $_post[mobileapprovtime]; // 073310<br />
        $app_time = "20".$mobileapprovdate.$mobileapprovtime;
        break;
}



header('content-type: application/json; charset=utf-8');




// 장바구니가 비어있는가?
    $tmp_cart_id = $_get[cart_id];
    $mb_id = $_get[mb_id];
    $member['mb_id']=$mb_id;
    $od_pwd = $_get['od_pwd'];

if (get_cart_count($tmp_cart_id) == 0)// 장바구니에 담기
    logtoapache('장바구니가 비어 있습니다.\\n\\n이미 주문하셨거나 장바구니에 담긴 상품이 없는 경우입니다.', g5_shop_url.'/cart2.php');

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
    logtoapache('장바구니가 비어 있습니다.\\n\\n이미 주문하셨거나 장바구니에 담긴 상품이 없는 경우입니다.', g5_shop_url . '/cart.php');
    exit;
}

if ($error != "")
{
    $error .= "다른 고객님께서 {$od_name}님 보다 먼저 주문하신 경우입니다. 불편을 끼쳐 죄송합니다.";
    logtoapache($error);
    exit;
}

$i_price     = $od_price;
$i_send_cost  = $od_send_cost;
$i_send_cost2  = $od_send_cost2;
$i_send_coupon  = $od_send_coupon;
$i_temp_point = $od_temp_point;


// 주문금액이 상이함
$sql = " select sum(if(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as od_price,
              count(distinct it_id) as cart_count
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

$od_status = '주문';
$od_tno    = '';
if ($od_settle_case == "무통장")
{
    $od_receipt_point   = $i_temp_point;
    $od_receipt_price   = 0;
    $od_misu            = $i_price - $od_receipt_price;
    if($od_misu == 0) {
        $od_status      = '입금';
        $od_receipt_time = g5_time_ymdhis;
    }
}
else if ($od_settle_case == "계좌이체")
{
    $od_tno             = $tno;
    $od_receipt_price   = $amount;
    $od_receipt_point   = 0;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $od_settle_case;
    $od_deposit_name    = $od_name;
    $od_bank_account    = $bank_name;
    $pg_price           = $amount;
    $od_misu            = $i_price - $od_receipt_price;
    if($od_misu == 0)
        $od_status      = '입금';
}
else if ($od_settle_case == "가상계좌")
{


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

    $od_tno             = $tno;
    $od_receipt_price   = $amount;
    $od_receipt_point   = $i_temp_point;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $commid . ($commid ? ' ' : '').$mobile_no;
    $pg_price           = $amount;
    $od_misu            = $i_price - $od_receipt_price;
    $od_status      = '입금';
}
else if ($od_settle_case == "신용카드")
{

    $od_tno             = $tno;
    $od_app_no          = $app_no;
    $od_receipt_price   = $amount;
    $od_receipt_point   = $i_temp_point;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $cardname;
    $pg_price           = $amount;
    $od_misu            = $i_price - $od_receipt_price;
    $od_status      = '입금';
}
else if ($od_settle_case == "간편결제")
{

    $od_tno             = $tno;
    $od_app_no          = $app_no;
    $od_receipt_price   = $amount;
    $od_receipt_point   = $i_temp_point;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $card_name;
    $pg_price           = $amount;
    $od_misu            = $i_price - $od_receipt_price;
        $od_status      = '입금';
}

else
{
    logtoapache("od_settle_case error!!!");
}

$od_pg = $default['de_pg_service'];
if($od_settle_case == 'kakaopay')
    $od_pg = 'kakaopay';

// 주문금액과 결제금액이 일치하는지 체크
if($tno) {
    if((int)$order_price !== (int)$pg_price) {
        $cancel_msg = '결제금액 불일치';
        include g5_shop_path.'/ssqpg/ssq_cancel.php';
        logtoapache("receipt amount error");
    }
}


    $od_pwd = get_encrypt_string($_post['od_pwd']);

// 주문번호를 얻는다.
$od_id = $oid;

$od_escrow = 0;
if($escw_yn == 'y')
    $od_escrow = 1;

// 복합과세 금액
$od_tax_mny = round($i_price / 1.1);
$od_vat_mny = $i_price - $od_tax_mny;
$od_free_mny = 0;
if($default['de_tax_flag_use']) {
    $od_tax_mny = (int)$_post['comm_tax_mny'];
    $od_vat_mny = (int)$_post['comm_vat_mny'];
    $od_free_mny = (int)$_post['comm_free_mny'];
}

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
$od_addr_jibeon   = preg_match("/^(n|r)$/", $od_addr_jibeon) ? $od_addr_jibeon : '';
$od_b_name        = clean_xss_tags($od_b_name);
$od_b_tel         = clean_xss_tags($od_b_tel);
$od_b_hp          = clean_xss_tags($od_b_hp);
$od_b_addr1       = clean_xss_tags($od_b_addr1);
$od_b_addr2       = clean_xss_tags($od_b_addr2);
$od_b_addr3       = clean_xss_tags($od_b_addr3);
$od_b_addr_jibeon = preg_match("/^(n|r)$/", $od_b_addr_jibeon) ? $od_b_addr_jibeon : '';
$od_memo          = clean_xss_tags($od_memo);
$od_deposit_name  = clean_xss_tags($od_deposit_name);
$od_tax_flag      = $default['de_tax_flag_use'];

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
                od_receipt_time   = '".g5_time_ymdhis."',
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
                od_shop_memo      = '',
                od_hope_date      = '$od_hope_date',
                od_time           = '".g5_time_ymdhis."',
                od_ip             = '$remote_addr',
                od_settle_case    = '$od_settle_case',
                od_test           = '{$default['de_card_test']}'
                ";
$result = sql_query($sql, false);

// 주문정보 입력 오류시 결제 취소
if(!$result) {
    if($tno) {
        $cancel_msg = '주문정보 입력 오류';
        include g5_shop_path.'/ssqpg/ssq_cancel.php';
        exit;
    }

    // 관리자에게 오류 알림 메일발송
    $error = 'order';
    include g5_shop_path.'/ordererrormail.php';

    logtoapache('<p>고객님의 주문 정보를 처리하는 중 오류가 발생해서 주문이 완료되지 않았습니다.</p><p>'.strtoupper($od_pg).'를 이용한 전자결제(신용카드, 계좌이체, 가상계좌 등)은 자동 취소되었습니다.');
}

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
if(!$result) {
    if($tno) {
        $cancel_msg = '주문상태 변경 오류';
        include g5_shop_path.'/ssqpg/ssq_cancel.php';
        exit;
    }

    // 관리자에게 오류 알림 메일발송
    $error = 'status';
    include g5_shop_path.'/ordererrormail.php';

    // 주문삭제
    sql_query(" delete from {$g5['g5_shop_order_table']} where od_id = '$od_id' ");

    logtoapache('<p>고객님의 주문 정보를 처리하는 중 오류가 발생해서 주문이 완료되지 않았습니다.</p><p>'.strtoupper($od_pg).'를 이용한 전자결제(신용카드, 계좌이체, 가상계좌 등)은 자동 취소되었습니다.');
}


$od_memo = nl2br(htmlspecialchars2(stripslashes($od_memo))) . "&nbsp;";


include_once(g5_shop_path.'/ordermail1.inc.php');
include_once(g5_shop_path.'/ordermail2.inc.php');

// sms begin --------------------------------------------------------
// 주문고객과 쇼핑몰관리자에게 sms 전송
if($config['cf_sms_use'] && ($default['de_sms_use2'] || $default['de_sms_use3'])) {
    $is_sms_send = false;

    // 충전식일 경우 잔액이 있는지 체크
    if($config['cf_icode_id'] && $config['cf_icode_pw']) {
        $userinfo = get_icode_userinfo($config['cf_icode_id'], $config['cf_icode_pw']);

        if($userinfo['code'] == 0) {
            if($userinfo['payment'] == 'c') { // 정액제
                $is_sms_send = true;
            } else {
                $minimum_coin = 100;
                if(defined('g5_icode_coin'))
                    $minimum_coin = intval(g5_icode_coin);

                if((int)$userinfo['coin'] >= $minimum_coin)
                    $is_sms_send = true;
            }
        }
    }

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

        // sms 전송
        if($sms_count > 0) {
            if($config['cf_sms_type'] == 'lms') {
                include_once(g5_lib_path.'/icode.lms.lib.php');

                $port_setting = get_icode_port_type($config['cf_icode_id'], $config['cf_icode_pw']);

                // sms 모듈 클래스 생성
                if($port_setting !== false) {
                    $sms = new lms;
                    $sms->sms_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $port_setting);

                    for($s=0; $s<count($sms_messages); $s++) {
                        $strdest     = array();
                        $strdest[]   = $sms_messages[$s]['recv'];
                        $strcallback = $sms_messages[$s]['send'];
                        $strcaller   = iconv_euckr(trim($default['de_admin_company_name']));
                        $strsubject  = '';
                        $strurl      = '';
                        $strdata     = iconv_euckr($sms_messages[$s]['cont']);
                        $strdate     = '';
                        $ncount      = count($strdest);

                        $res = $sms->add($strdest, $strcallback, $strcaller, $strsubject, $strurl, $strdata, $strdate, $ncount);

                        $sms->send();
                        $sms->init(); // 보관하고 있던 결과값을 지웁니다.
                    }
                }
            } else {
                include_once(g5_lib_path.'/icode.sms.lib.php');

                $sms = new sms; // sms 연결
                $sms->sms_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);

                for($s=0; $s<count($sms_messages); $s++) {
                    $recv_number = $sms_messages[$s]['recv'];
                    $send_number = $sms_messages[$s]['send'];
                    $sms_content = iconv_euckr($sms_messages[$s]['cont']);

                    $sms->add($recv_number, $send_number, $config['cf_icode_id'], $sms_content, "");
                }

                $sms->send();
                $sms->init(); // 보관하고 있던 결과값을 지웁니다.
            }
        }
    }
}
// sms end   --------------------------------------------------------


// orderview 에서 사용하기 위해 session에 넣고
$uid = md5($od_id.g5_time_ymdhis.$remote_addr);
set_session('ss_orderview_uid', $uid);

// 주문 정보 임시 데이터 삭제
if($od_pg == 'inicis') {
    $sql = " delete from {$g5['g5_shop_order_data_table']} where od_id = '$od_id' and dt_pg = '$od_pg' ";
    sql_query($sql);
}

// 주문번호제거
set_session('ss_order_id', '');

// 기존자료 세션에서 제거
if (get_session('ss_direct'))
    set_session('ss_cart_direct', '');

// 배송지처리
if($is_member) {
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
    if($ad_default) {
        $sql = " update {$g5['g5_shop_order_address_table']}
                    set ad_default = '0'
                    where mb_id = '{$member['mb_id']}' ";
        sql_query($sql);
    }

    $ad_subject = clean_xss_tags($ad_subject);

    if($row['ad_id']){
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


$result = array('rescode' => '00');
echo json_encode($result, json_unescaped_unicode);


function logtoapache($message)
{
    $log_dir = $_server['document_root']."/data/log/log.txt";
    $stderr = fopen($log_dir, 'a');
    fwrite($stderr, $message);
    fclose($stderr);
}
$result = array('rescode' => '00');
echo json_encode($result, json_unescaped_unicode);

$json_result = json_encode($result, json_unescaped_unicode);
logtoapache($json_result);


foreach($_post as $key => $value){
    logtoapache($key . " : " . $value . "<br />\r\n");
}
foreach($_get as $key => $value){
    logtoapache($key . " : " . $value . "<br />\r\n");
}

logtoapache('========================================================================');
?>