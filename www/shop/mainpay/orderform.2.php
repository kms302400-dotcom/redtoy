<?
require("utils.php");
/*
hashValue(서명값) 생성 예시

$sign = hash("sha256", mbrId|salesPrice|oid|timestamp);

$hashValue = timestamp . $sign

(timestamp 형식 : YYYYMMDDHHMMSS)


*/


function signHash($mbrId,$salesPrice,$od_id,$timestamp){
    $sign = hash("sha256", $mbrId.'|'.$salesPrice.'|'.$od_id.'|'.$timestamp,false);
    $sign = $timestamp.$sign;
    return $sign;
}
$sign = signHash('114515',$tot_price,$od_id,date('YmdHis'));

?>

    <!-- 공통 파라미터 -->
    <input type="hidden" name="version" value="4">
    <input type="hidden" name="server" value="1">
    <!--
<select name="payKind">
	<option selected value='CARD'>카드</option>
	<option value='VACCT'>가상계좌</option>
	<option value='ACCT'>계좌이체</option>
	<option value='HPP'>휴대폰</option>
</select>
	지불수단 (CARD: 신용카드 | VACCT: 가상계좌 | ACCT: 계좌이체 | HPP: 휴대폰소액)
(*)간편결제는 "CARD"에 포함되어 있음

version*	버전정보 (샘플코드값 사용)	4
mbrNo*	섹타나인에서 부여한 가맹점 번호 (상점 아이디)	6
mbrRefNo*	가맹점주문번호 (가맹점에서 생성한 중복되지 않는 번호)	20
paymethod*	지불수단 (CARD: 신용카드 | VACCT: 가상계좌 | ACCT: 계좌이체 | HPP: 휴대폰소액)
(*)간편결제는 "CARD"에 포함되어 있음	5
amount*	총결제금액	10
goodsName*	상품명 (일부 특수문자는 사용불가 합니다.)	30
approvalUrl*	인증결과 수신페이지
예) https://상점도메인/approval
(주의) URL내에 &,=등의 특수문자 허용안됨)	500
closeUrl*	결제종료 수신페이지 URL
예) https://상점도메인/close
(주의) URL내에 &,=등의 특수문자 허용안됨)	300
timestamp*	타임스탬프 (가맹점 시스템 시각)
signature 생성시 사용	18
signature*	결제 위변조 방지를 위한 파라미터 서명 값	64
goodsCode	상품코드	8
customerTelNo	구매자전화번호	12
customerName	구매자명 (일부 특수문자는 사용불가 합니다.)	30
customerEmail	구매자이메일	50
merchantData	가맹점 전용 필드 (approvalUrl 수신시 응답)
URL인코딩 필수	500
skinType	결제창 테마 색상 (black | blue | indigo | orange | yellow)
미 사용시 기본 색상	10
escrowEmail	에스크로 구매확정 이메일주소
가상계좌, 계좌이체 지불수단의 에스크로를 이용할 시 필수항목	50
availableCards	카드코드 지정 노출
결제창에 표시할 카드코드만 지정 가능(JSON Array 타입)
예) ["01","02","03","11"]
카드코드는 "공통코드 > 요청카드사코드" 참조	100
-->
