<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>

<input type="hidden" name="BILLTYPE" value="00" checked="checked">
<input type="hidden" name="mbrNo" value="114515">
<input type="hidden" name="mbrName" value="이노빅스">
<input type="hidden" name="buyerName" value="<?php echo get_text($member['mb_name']); ?>">
<input type="hidden" name="buyerMobile" value="<?php echo get_text($member['mb_hp']); ?>">
<input type="hidden" name="buyerEmail" value="<?php echo $member['mb_email']; ?>">
<input type="hidden" name="customerEmail" value="<?php echo $member['mb_email']; ?>">
<input type="hidden" name="mb_id" value="<?php echo $member['mb_id']; ?>">
<input type="hidden" name="od_id" value="<?=$od_id?>">
<input type="hidden" name="send_cost" value="<?=$send_cost?>">
<input type="hidden" name="send_cost2" value="0">
<input type="hidden" name="tmp_cart_id" value="<?=$s_cart_id?>">
<input type="hidden" name="goodsName"  value="<?php echo $od_id; ?>">
<input type="hidden" name="goodsCode"  value="<?php echo $goods_it_id; ?>">
<input type="hidden" name="salesPrice" value="<?php echo $tot_price; ?>"> <!-- 실제 결제되는 금액 -->
<input type="hidden" name="productCount" value="1">
<input type="hidden" name="CPCODE" value="">
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
