<?php
header("Content-Type:text/html; charset=utf-8;"); 
/*
*******************************************************
* <결제요청 파라미터>
* 결제시 Form 에 보내는 결제요청 파라미터입니다.
*******************************************************
*/  

$merchantKey	= "UYvTCe0yLNHfpvSopCefaYu3Bqo0CHOj7HS9gq8VIRv8v0VsGvV1H7ruVrJj/m4wVSsj6dxCXchxKzVlhiFMPg=="; 						// 상점키
$merchantID		= "gxtest001m";					// 상점아이디
$goodsNm		= "PGTEST"; 						// 결제상품명
$goodsAmt		= "1004";							// 결제상품금액
$ordNm  		= "PGTEST";							// 구매자명 
$ordTel			= "01000000000"; 					// 구매자연락처
$ordEmail		= "abcd@zxcv.com"; 					// 구매자메일주소        
$ordNo			= "test1234567890";					// 상품주문번호                     
$returnUrl		= "/plugin/payster/payResult.php"; 			// 결과페이지(절대경로)

/*
*******************************************************
* <해쉬암호화> (수정하지 마세요)
* SHA-256 해쉬암호화는 거래 위변조를 막기위한 방법입니다. 
*******************************************************
*/ 
$ediDate = date("YmdHis");
$encData = bin2hex(hash('sha256', $merchantID.$ediDate.$goodsAmt.$merchantKey, true));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Cache-Control" content="no-cache" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=3.0">
<title>결제 TEST</title>
<script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
<script src="https://api.payster.co.kr/js/pgAsistant.js"></script>
</head>
<body>
<script type="text/javascript">

function doPaySubmit(){
	// 결제창 호출 함수
	SendPay(document.payInit);
}
// 결제창 return 함수(pay_result_submit 이름 변경 불가능)
function pay_result_submit(){
	payResultSubmit();
}
// 결제창 종료 함수(pay_result_close 이름 변경 불가능)
function pay_result_close(){
	//alert('결제를 취소하였습니다.');
}
</script>
<div style="text-align:center;">
<div id="sampleInput" class="paypop_con" style="padding:20px 15px 35px 15px;display: inline-block;float: inherit;">
<p class="square_tit mt0" style="text-align:left;"><strong>결제정보</strong></p>
<form name="payInit" method="post" action="<?php echo($returnUrl)?>">
	<table class="tbl_sty02">
		<tr>
			<td>결제수단</td>
			<td><input type="text" name="payMethod" value="card"></td>
		</tr>
        <tr>
            <td>테스트 페이지</td>
            <td><input type="text" name="paytest" value="card"></td>
        </tr>
		<tr>
			<td>결제방법선택</td>
			<td><input type="text" name="mid" value="<?php echo($merchantID)?>"></td>
		</tr>
		<tr>
			<td>상품명</td>
			<td><input type="text" name="goodsNm" value="<?php echo($goodsNm)?>"></td>
		</tr>
		<tr>
			<td>주문번호</td>
			<td><input type="text" name="ordNo" value="<?php echo($ordNo)?>"></td>
		</tr>
		<tr>
			<td>결제금액</td>
			<td><input type="text" name="goodsAmt" value="<?php echo($goodsAmt)?>"></td>
		</tr>
		<tr>
			<td>구매자명</td>
			<td><input type="text" name="ordNm" value="<?php echo($ordNm)?>"></td>
		</tr>
		<tr>
			<td>구매자연락처</td>
			<td><input type="text" name="ordTel" value="<?php echo($ordTel)?>"></td>
		</tr>
		<tr>
			<td>구매자이메일</td>
			<td><input type="text" name="ordEmail" value="<?php echo($ordEmail)?>"></td>
		</tr>
		<tr>
			<td>returnUrl</td>
			<td><input type="text" name="returnUrl" value="<?php echo($returnUrl)?>"></td>
		</tr>
	
		<tr>
			<td>notiUrl</td>
			<td><input type="text" name="notiUrl" value=""></td>
		</tr>
	</table>
	<!-- 옵션 --> 
	<input type="hidden" name="userIp"	value="183.96.140.86">
	<input type="hidden" name="trxCd"	value="0">
		
	<input type="hidden" name="mbsUsrId" value="user1234">
	<input type="hidden" name="mbsReserved" value="MallReserved"><!-- 상점 예약필드 -->
	
	<!-- <input type="hidden" name="goodsSplAmt" value="0"> -->
	<!-- <input type="hidden" name="goodsVat" value="0"> -->
	<!-- <input type="hidden" name="goodsSvsAmt" value="0"> -->
	
	<input type="hidden" name="charSet" value="UTF-8">
	<input type="hidden" name="appMode" value="1">
	<!-- <input type="hidden" name="period" value="별도 제공기간없음"> -->
	
	<!-- 변경 불가능 -->
	<input type="hidden" name="ediDate" value="<?php echo($ediDate)?>"><!-- 전문 생성일시 -->
	<input type="hidden" name="encData" value="<?php echo($encData)?>"><!-- 해쉬값 -->

</form>	
	<a href="#;" id="payBtn" class="btn_sty01 bg01" style="margin:15px;" onClick="doPaySubmit();">결제하기</a>
	</div>
</div>
</body>
</html>