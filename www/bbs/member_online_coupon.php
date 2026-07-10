<?php
include_once('./_common.php');

if (!$is_member) {
    alert_close("회원만 이용가능합니다.");
}

$g5['title'] = '회원쿠폰 등록';
include_once(G5_PATH.'/head.sub.php');

$action_url = G5_HTTPS_BBS_URL."/member_online_coupon_update.php";

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<!-- 회원쿠폰 등록 시작 { -->
<div id="find_info" class="new_win">
    <h1 id="win_title">회원쿠폰 등록</h1>
    <div class="new_win_con">
        <form name="fpasswordlost" action="<?php echo $action_url ?>" onsubmit="return fonlinecoupon_submit(this);" method="post" autocomplete="off">
		<input type="hidden" name="mb_id" value="<?php echo $member['mb_id'] ?>">
		
        <fieldset id="info_fs">
            <div class="local_desc01 local_desc">
				<ol>
					<li>쿠폰번호 16자리를 정확히 입력해주세요.</li>
					<li>쿠폰 등록 후 기간, 혜택, 사용조건 등을 꼭 확인하세요.</li>
					<li>쿠폰은 등록 시 바로 사용할 수 있습니다.</li>
					<li>쿠폰번호 등록 시, 하이픈('-')은 넣지 않으셔도 됩니다.</li>
					<li><strong>알파벳 O와 숫자 0</strong>을 명확히 구분하여 입력하세요.</li>
				</ol>
			</div>
            <label for="coupon" class="sound_only">쿠폰번호<strong class="sound_only">필수</strong></label>
            <input type="text" name="coupon1" id="coupon1" required class="required frm_input" size="10" maxlength="4"> -
            <input type="text" name="coupon2" id="coupon2" required class="required frm_input" size="10" maxlength="4"> -
            <input type="text" name="coupon3" id="coupon3" required class="required frm_input" size="10" maxlength="4"> -
            <input type="text" name="coupon4" id="coupon4" required class="required frm_input" size="10" maxlength="4">
        </fieldset>

		<div class="win_btn">
            <input type="submit" class="btn_submit" value="등록">
            <button type="button" onclick="window.close();" class="btn_close">창닫기</button>
        </div>

		</form>
    </div>
</div>

<script>
function fonlinecoupon_submit(f)
{
	if (f.coupon1.value.length < 4) {
		alert('쿠폰번호 4글자를 입력하십시오.');
		f.coupon1.focus();
		return false;
	}

	if (f.coupon2.value.length < 4) {
		alert('쿠폰번호 4글자를 입력하십시오.');
		f.coupon2.focus();
		return false;
	}

	if (f.coupon3.value.length < 4) {
		alert('쿠폰번호 4글자를 입력하십시오.');
		f.coupon3.focus();
		return false;
	}

	if (f.coupon4.value.length < 4) {
		alert('쿠폰번호 4글자를 입력하십시오.');
		f.coupon4.focus();
		return false;
	}

    return true;
}

$(function() {
    var sw = screen.width;
    var sh = screen.height;
    var cw = document.body.clientWidth;
    var ch = document.body.clientHeight;
    var top  = sh / 2 - ch / 2 - 100;
    var left = sw / 2 - cw / 2;
    moveTo(left, top);
});
</script>
<!-- } 회원쿠폰 등록 끝 -->

<?php
include_once(G5_PATH.'/tail.sub.php');
?>