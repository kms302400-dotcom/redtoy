<?php
include_once("../../../common.php");
require('utils.php');                // 유틸리티 포함
if ($_SERVER['HTTPS'] != 'on') {
    $HTT = "http";
} else {
    $HTT = "https";
}
$mbrNo = "114515";
$_host = $_SERVER['HTTP_HOST'];
$site_domain = $HTT . "://" . $_host;
$logPath = $_SERVER['DOCUMENT_ROOT'] . "/data/mainpay/log/app.log";         //디버그 로그위치 (windows)
$paymethod = $_POST['od_settle_case'];
$goodsName = $_POST["od_id"];
$od_id = $_POST['od_id'];
$mb_id = $_POST['mb_id'];
$od_pwd = $_POST['od_pwd'];
$od_send_cost = $_POST['send_cost'];
$od_send_cost2 = $_POST['send_cost2'];
$buy_name = $_POST['buyerName'];
$buy_email = $_POST['buyerEmail'];
if(empty($od_send_cost2)){
    $od_send_cost2 = 0 ;
}
$od_price = $_POST['salesPrice']+$od_send_cost2;
$od_name = $_POST['od_name'];
$od_tel = $_POST['od_tel'];
$od_hp = $_POST['od_hp'];
$od_zip = $_POST['od_zip'];
$od_addr1 = $_POST['od_addr1'];
$od_addr2 = $_POST['od_addr2'];
$od_addr3 = $_POST['od_addr3'];
$od_addr_jibeon = $_POST['od_addr_jibeon'];
$od_email = $_POST['od_email'];
$od_b_name = $_POST['od_b_name'];
$od_b_tel = $_POST['od_b_tel'];
$od_b_hp = $_POST['od_b_hp'];
$od_b_zip = $_POST['od_b_zip'];
$od_b_addr1 = $_POST['od_b_addr1'];
$od_b_addr2 = $_POST['od_b_addr2'];
$od_b_addr3 = $_POST['od_b_addr3'];
$od_b_addr_jibeon = $_POST['od_b_addr_jibeon'];
$od_memo = $_POST['$od_memo'];
$s_cart_id = $_POST['tmp_cart_id'];
$merchantData = time();
$mbrRefNo = makeMbrRefNo($mbrNo);
$put_data = array(
    "buy_name" => "$buy_name",
    "buy_email" => "$buy_email",
    "od_id" => "$od_id",
    "mb_id" => "$mb_id",
    "od_pwd" => "$od_pwd",
    "mbrRefNo"=>"$mbrRefNo",
    "od_send_cost" => "$od_send_cost",
    "od_send_cost2" => "$od_send_cost2",
    "s_cart_id" => "$s_cart_id",
    "goodsName" => "$goodsName",
    "paymethod" => "$paymethod",
    "od_price" => "$od_price",
    "od_name" => "$od_name",
    "od_tel" => "$od_tel",
    "od_hp" => "$od_hp",
    "od_zip" => "$od_zip",
    "od_addr1" => "$od_addr1",
    "od_addr2" => "$od_addr2",
    "od_addr3" => "$od_addr3",
    "od_addr_jibeon" => "$od_addr_jibeon",
    "od_email" => "$od_email",
    "od_b_name" => "$od_b_name",
    "od_b_tel" => "$od_b_tel",
    "od_b_hp" => "$od_b_hp",
    "od_b_zip" => "$od_b_zip",
    "od_b_addr1" => "$od_b_addr1",
    "od_b_addr2" => "$od_b_addr2",
    "od_b_addr3" => "$od_b_addr3",
    "od_b_addr_jibeon" => "$od_b_addr_jibeon",
    "od_memo" => "$od_memo",
);
$put_data = serialize($put_data);
$put_time = date("Y-m-d H:m:s");
$sql = "insert into g5_temp_cart(temp_id,temp_value,temp_regdate) values($merchantData,'{$put_data}','{$put_time}')";
sql_query($sql);


/*****************************************************************************************
 * READY API  (결제창 호출 전처리)
 ******************************************************************************************
 * - API 호출 도메인
 * - ## 테스트 완료후 real 서비스용 URL로 변경  ##
 * - 리얼-URL : https://api-std.mainpay.co.kr
 * - 개발-URL : https://test-api-std.mainpay.co.kr
 */

$API_BASE = "https://api-std.mainpay.co.kr";

/*
  API KEY (비밀키)
 - 생성 : http://biz.mainpay.co.kr 고객지원>기술지원>암호화키관리
 - 가맹점번호(mbrNo) 생성시 함께 만들어지는 key (테스트 완료후 real 서비스용 발급필요) */
$apiKey = "dWoxljWTqPYYljEg3GTxE6Zh8ScAsTD7d40AZ3LYRXmF"; // <===테스트용 API_KEY입니다. 100011

/*****************************************************************************************
 *    필수 파라미터
 ******************************************************************************************/
$version = "V001";
/* 가맹점 아이디(테스트 완료후 real 서비스용 발급필요)*/
//<===테스트용 가맹점아이디입니다.
/* 가맹점 주문번호 (가맹점 고유ID 대체가능) 6byte~20byte*/

/*$mbrRefNo = $_POST["mbrRefNo"];
/* 결제수단 */
$paymethod = $_POST['od_settle_case'];
/* 결제금액 (공급가+부가세)
(#주의#) 페이지에서 전달 받은 값을 그대로 사용할 경우 금액위변조 시도가 가능합니다.
DB에서 조회한 값을 사용 바랍니다. */
$amount = (integer)$_POST['salesPrice'] + $_POST['send_cost2'];
/* 상품명 max 30byte, 특수문자 사용금지*/
//$goodsName = urlencode("테스트상품명");
$goodsName = $_POST["od_id"];
/* 상품코드 max 8byte*/
$goodsCode = substr($_POST["goodsCode"], 0, 8);
/*인증완료 시 호출되는 상점 URL (PG->가맹점)*/
$approvalUrl = $site_domain . "/plugin/mainpay/pc/_3_approval.php";
/*결제창 close시 호출되는 상점URL (PG->가맹점)*/
$closeUrl = $site_domain . "/plugin/mainpay/pc/_3_close.php";
$customerName = $_POST['buyerName'];
$customerEmail = $_POST['buyerEmail'];
/* timestamp max 20byte*/
$timestamp = makeTimestamp();
/* signature 64byte*/
$signature = makeSignature($mbrNo, $mbrRefNo, $amount, $apiKey, $timestamp);

/*****************************************************************************************
 *    옵션 파라미터
 ******************************************************************************************/

$parameters = array(
    'version' => $version,
    'mbrNo' => $mbrNo,
    'mbrRefNo' => $mbrRefNo,
    'paymethod' => $paymethod,
    'amount' => $amount,
    'goodsName' => $_POST["goodsCode"],
    'goodsCode' => $goodsCode,
    'approvalUrl' => $approvalUrl,
    'closeUrl' => $closeUrl,
    'customerName' => $od_name,
    'merchantData' => $merchantData,
    'customerEmail' => $od_email,
    'timestamp' => $timestamp,
    'signature' => $signature
);
//pintLog("READY-API: ".var_dump($parameters), $logPath);
/*****************************************************************************************
 * READY API 호출
 *****************************************************************************************/
$READY_API_URL = $API_BASE . "/v1/payment/ready";
$result = "";
$errorMessage = "";
try {
    pintLog("READY-API: " . $READY_API_URL, $logPath);
    pintLog("PARAM: " . print_r($parameters, TRUE), $logPath);
    $result = httpPost($READY_API_URL, $parameters);
} catch (Exception $e) {
    $errorMessage = "결제준비API 호출실패: " . $READY_API_URL;
    pintLog("ERROR: " . $errorMessage, $logPath);
    throw new Exception($e);
    return;
}

pintLog("RESPONSE: " . $result, $logPath);
$obj = json_decode($result);
$resultCode = $obj->{'resultCode'};
$resultMessage = $obj->{'resultMessage'};
$aid = "";
if ($resultCode = "200") {
    $data = $obj->{'data'};
    $aid = $data->{'aid'};
}

/******************************************************************************************
 * 요청정보 DB에 저장 (parameters, apiKey, aid, API_BASE, amount 등)
 * 브라우저 cross-domain session, cookie 정책 강화로 session 사용 지양
 * PG로부터 인증결과 수신후 결제승인 요청시에 필요
 ******************************************************************************************/

// JSON TYPE RESPONSE
header('Content-Type: application/json');
echo $result;
?>
