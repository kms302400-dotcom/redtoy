<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>
<?php
$merchantKey = "hesukWZ0i4ui+wPzDjanvJK9KYXnpdm5M7/JNv/0f2p9HMrSYc+WwP/jGPpjV7i3gFh8FEgt9fqJfvAl9ycA6A==";                        // 상점키
$merchantID = "nf0000003m";                    // 상점아이디
$goodsNm = $goods;                        // 결제상품명
$goodsAmt = $tot_price;                            // 결제상품금액
if($member['mb_id']) {
    $ordNm = $member['mb_id'];                            // 구매자명
}else{
    $ordNm = $od_id;
}
$ordNo = $od_id;                    // 상품주문번호

if($_SERVER['HTTPS'] != 'on'){
    $HTT = "http";
}else{
    $HTT = "https";
}
$returnUrl = "".$HTT."://".$_SERVER['HTTP_HOST']."/plugin/payster/payResult.php";            // 결과페이지(절대경로)
$ediDate = date("YmdHis");
$encData = bin2hex(hash('sha256', $merchantID . $ediDate . $goodsAmt . $merchantKey, true));
?>

<input type="hidden" name="payMethod" value="card">
<input type="hidden" name="mid" value="<?php echo($merchantID) ?>">
<input type="hidden" name="goodsNm" value="<?php echo($goodsNm) ?>">
<input type="hidden" name="ordNo" value="<?php echo($od_id) ?>">
<input type="hidden" name="goodsAmt" value="<?php echo($goodsAmt) ?>">
<input type="hidden" name="ordNm" value="<?php echo($ordNm) ?>">
<input type="hidden" name="ordTel" value="">
<input type="hidden" name="od_id" value="<?php echo($od_id) ?>">
<input type="hidden" name="ordEmail" value="">
<input type="hidden" name="returnUrl" value="<?php echo($returnUrl) ?>">
<input type="hidden" name="mb_id" value="<?php echo($member["mb_id"]) ?>">
<input type="hidden" name="notiUrl" value="">
<input type="hidden" name="userIp" value="<?= $_SERVER['REMOTE_ADDR'] ?>">
<input type="hidden" name="trxCd" value="0">
<input type="hidden" name="mbsUsrId" value="<?php echo $member['mb_id'] ?>">
<input type="hidden" name="cart_id" value="<?php echo $s_cart_id ?>">
<input type="hidden" name="mbsReserved" value=""><!-- 상점 예약필드 -->
<input type="hidden" name="charSet" value="UTF-8">
<input type="hidden" name="appMode" value="1">
<input type="hidden" name="ediDate" value="<?php echo($ediDate) ?>"><!-- 전문 생성일시 -->
<input type="hidden" name="encData" value="<?php echo($encData) ?>"><!-- 해쉬값 -->
<div id="display_pay_button" class="btn_confirm" style="display:none">
    <input type="button" value="주문하기" class="btn_submit" onclick="forderform_check(this.form);" class="btn_submit"/>
    <a href="javascript:history.go(-1);" class="btn01">취소</a>
</div>
<div id="display_pay_process" style="display:none">
    <img src="<?php echo G5_URL; ?>/shop/img/loading.gif" alt="">
    <span>주문완료 중입니다. 잠시만 기다려 주십시오.</span>
</div>

<script>
    document.getElementById("display_pay_button").style.display = "" ;
</script>
