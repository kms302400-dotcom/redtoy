<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');
?>

    <style>
        #container_title {display: none;}
    </style>

<div id="mb_login" class="mbskin">
    <!-- 비회원 주문조회 시 로그인/이벤트 배너 안보이게 -->
	<?php if (preg_match("/orderinquiry.php$/", $url)) { ?>
		<h1>비회원 주문조회 </h1>
		
		<div id="mb_login_od_wr">
			<fieldset id="mb_login_od">
				<legend>비회원 주문조회</legend>
		
				<form name="forderinquiry" method="post" action="<?php echo urldecode($url); ?>" autocomplete="off">
		
				<label for="od_id" class="od_id">주문번호</label>
				<input type="text" name="od_id" value="<?php echo $od_id ?>" id="od_id" placeholder="문자로 발송된 주문번호 (#을 제외한 번호)" required class="frm_input" size="20">
				
				<label for="od_name" class="od_name">주문자명</label>
				<input type="text" name="od_name" size="20" id="od_name" placeholder="주문 시 입력한 주문자명" required class="frm_input">
				<button type="submit" class="btn_submit">주문조회</button>
		
				</form>
			</fieldset>
		
			<section id="mb_login_odinfo">
				<p>문자로 발송된 <strong>주문번호</strong>와 <strong>주문자명</strong>을 입력해 주세요.</p>
			</section>
		</div>
	<?php } else { ?>
		<h1>로그인</h1>
	
		<form name="flogin" action="<?php echo $login_action_url ?>" onsubmit="return flogin_submit(this);" method="post" id="flogin">
			<input type="hidden" name="url" value="<?php echo $login_url ?>">
	
			<div id="login_frm">
				<div class="login_area">
					<div>
						<label for="login_id" class="sound_only">아이디<strong class="sound_only"> 필수</strong></label>
						<input type="text" name="mb_id" id="login_id" placeholder="ID" required class="frm_input required" maxLength="20">
						<label for="login_pw" class="sound_only">비밀번호<strong class="sound_only"> 필수</strong></label>
						<input type="password" name="mb_password" id="login_pw" placeholder="PW" required class="frm_input required" maxLength="20">
					</div>
					<button type="submit" class="btn_submit">로그인</button>
				</div>
				<div style="display: flex; justify-content: space-between; margin:0;">
					<div id="login_info" class="chk_box" style="display:flex;">
						<input type="checkbox" name="auto_login" id="login_auto_login" class="selec_chk">
						<label for="login_auto_login"><span></span>자동 로그인</label>
					</div>
					<div style="text-align:right"><a href="<?php echo G5_BBS_URL ?>/password_lost.php" id="login_password_lost" style="color: #676e70;">아이디/비밀번호 찾기</a></div>
				</div>
				<?php
				// 소셜로그인 사용시 소셜로그인 버튼
				@include_once(get_social_skin_path().'/social_login.skin.php');
				?>
				<!-- <div class="join_wrap naver"><a href="#"><i></i> 네이버 로그인</a></div> -->
				<div class="join_wrap"><a href="./register.php">10초 안에 회원가입하기</a></div>
			</div>
	
		</form>

		
		
		<div id="intro_footer">
			<div class="intro_banner">
				<h2>레드토이에서만 드리는 신규회원 혜택!</h2>
				<!-- PC 배너 -->
				<img class="pc_banner" src="<?php echo G5_THEME_IMG_URL ?>/mobile/join_event_banner_l.png">
				<!-- 모바일 배너 -->
				<img class="mo_banner" src="<?php echo G5_THEME_IMG_URL ?>/mobile/join_event_banner_s.png">
			</div>
		</div>
		
	<?php } ?>
    <?php // 쇼핑몰 사용시 여기부터 ?>
    <?php if ($default['de_level_sell'] == 1) { // 상품구입 권한 ?>

	<!-- 주문하기, 신청하기 -->
	<?php if (preg_match("/orderform.php/", $url)) { ?>
	<section id="mb_login_notmb">
	    <h2>비회원 구매</h2>
	    <p>비회원으로 주문하시는 경우 포인트는 지급하지 않습니다.</p>
	    
	    <div id="guest_privacy">
	        <?php echo $default['de_guest_privacy']; ?>
	    </div>
		
		<div class="chk_box">
			<input type="checkbox" id="agree" value="1" class="selec_chk">
		    <label for="agree"><span></span> 개인정보수집에 대한 내용을 읽었으며 이에 동의합니다.</label>
		</div>
		
	    <div class="btn_confirm">
	        <a href="javascript:guest_submit(document.flogin);" class="btn_submit">비회원으로 구매하기</a>
	    </div>
	
	    <script>
	    function guest_submit(f)
	    {
	        if (document.getElementById('agree')) {
	            if (!document.getElementById('agree').checked) {
	                alert("개인정보수집에 대한 내용을 읽고 이에 동의하셔야 합니다.");
	                return;
	            }
	        }
	
	        f.url.value = "<?php echo $url; ?>";
	        f.action = "<?php echo $url; ?>";
	        f.submit();
	    }
	    </script>
	</section>

	<?php } ?>
	

	<?php } ?>
	<?php // 쇼핑몰 사용시 여기까지 반드시 복사해 넣으세요 ?>
</div>

<script>
$(function(){
    $("#login_auto_login").click(function(){
        if (this.checked) {
            this.checked = confirm("자동로그인을 사용하시면 다음부터 회원아이디와 비밀번호를 입력하실 필요가 없습니다.\n\n공공장소에서는 개인정보가 유출될 수 있으니 사용을 자제하여 주십시오.\n\n자동로그인을 사용하시겠습니까?");
        }
    });
});

function flogin_submit(f)
{
    if( $( document.body ).triggerHandler( 'login_sumit', [f, 'flogin'] ) !== false ){
        return true;
    }
    return false;
}
</script>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>