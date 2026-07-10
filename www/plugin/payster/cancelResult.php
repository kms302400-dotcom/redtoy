<?php
header("Content-Type:text/html; charset=utf-8;"); 
/*
****************************************************************************************
* <취소요청 파라미터>
* 취소시 전달하는 파라미터입니다.
* 샘플페이지에서는 기본(필수) 파라미터만 예시되어 있으며, 
* 추가 가능한 옵션 파라미터는 연동메뉴얼을 참고하세요.
****************************************************************************************
*/
$tid				=	$_POST['tid'];				// 거래 ID
$ordNo				=	'0123456789';				// 주문 번호
$canAmt				=	$_POST['canAmt'];			// 취소 금액
$partCanFlg			=	$_POST['partCanFlg'];		// 부분취소여부
$notiUrl			=	$_POST['notiUrl'];			// NOTI URL
$canMsg				=	'고객요청';						// 취소 사유
$mid				=	tid.substring(0, 10);		// 
$merchantKey		=	'발급받은 상점 Key';				//

/*
*******************************************************
* <해쉬암호화> (수정하지 마세요)
* SHA-256 해쉬암호화는 거래 위변조를 막기위한 방법입니다. 
*******************************************************
*/
$ediDate = date("YmdHis");
$signData = bin2hex(hash('sha256', $mid . $canAmt . $ediDate . $merchantKey, true));

/*
****************************************************************************************
* <취소 요청>
* 취소 사유(CancelMsg) 와 같이 한글 텍스트가 필요한 파라미터는 euc-kr encoding 처리가 필요합니다.
****************************************************************************************
*/

try{
	$data = Array(
		'tid' => $tid,
		'mid' => $mid,
		'ordNo' => $ordNo,
		'canAmt' => $canAmt,
		'canMsg' => iconv("UTF-8", "EUC-KR", $canMsg),
		'partCanFlg' => $partCanFlg,
		'notiUrl' => $notiUrl,
		'ediDate' => $ediDate,
		'charSet' => 'utf-8',
		'encData' => $encData
	);	
	$response = reqPost($data, "https://api.payster.co.kr/payment.cancel");
	jsonRespDump($response);

	
}catch(Exception $e){
	// 실패처리
	$e->getMessage();
	$ResultCode = "9999";
	$ResultMsg = "통신실패";
}

// API CALL foreach 예시
function jsonRespDump($resp){
	$respArr = json_decode($resp);
	foreach ( $respArr as $key => $value ){
		echo "$key=". $value."<br />";
	}
}

//Post api call
function reqPost(Array $data, $url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);					//connection timeout 15 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));	//POST data
	curl_setopt($ch, CURLOPT_POST, true);
	$response = curl_exec($ch);
	curl_close($ch);	 
	return $response;
}
?>