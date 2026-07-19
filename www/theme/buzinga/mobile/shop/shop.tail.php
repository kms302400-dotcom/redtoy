<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$admin = get_admin("super");

$pg_review_company = function_exists('redtoy_pg_review_get_company_info') ? redtoy_pg_review_get_company_info() : array();
$display_company_name = $pg_review_company ? $pg_review_company['company_name'] : '(주)인센스글로벌';
$display_company_owner = $pg_review_company ? $pg_review_company['company_owner'] : $default['de_admin_company_owner'];
$display_company_address = $pg_review_company ? $pg_review_company['company_address'] : $default['de_admin_company_addr'];
$display_business_number = $pg_review_company ? $pg_review_company['business_number'] : $default['de_admin_company_saupja_no'];
$display_business_number_digits = $pg_review_company ? $pg_review_company['business_number_digits'] : '1768600636';
$display_online_sales_number = $pg_review_company ? $pg_review_company['online_sales_number'] : $default['de_admin_tongsin_no'];
$display_privacy_officer = $pg_review_company ? $pg_review_company['privacy_officer'] : $default['de_admin_info_name'];
$display_email = $pg_review_company ? $pg_review_company['email'] : 'incense0523@gmail.com';
$display_customer_service = $pg_review_company ? $pg_review_company['customer_service'] : '02-6101-9272';
$display_fax = $pg_review_company ? $pg_review_company['fax'] : $default['de_admin_company_fax'];
$display_depositor = $pg_review_company ? $pg_review_company['depositor'] : '(주)인센스글로벌';
$display_copyright = $pg_review_company ? $pg_review_company['copyright'] : 'Copyright © 2024 REDCommerce. All Rights Reserved.';

// 사용자 화면 우측과 하단을 담당하는 페이지입니다.
// 우측, 하단 화면을 꾸미려면 이 파일을 수정합니다.
?>
	</div><!-- container End -->
</div><!-- wrapper End -->

<!-- 하단 고정 탭 -->
<div class="ft_fix">
    <ul>
        <li><div id="btn_hdcate"><span class="icon cate" aria-hidden="true"></span><div class="ft_txt">카테고리</div></div></li>
        <li><a href="<?php echo G5_SHOP_URL; ?>/couponzone.php"><span class="icon couponz"></span><div class="ft_txt">쿠폰존</div></a></li>
        <li><a href="<?php echo G5_SHOP_URL; ?>/"><span class="icon home" aria-hidden="true"></span><div class="ft_txt">홈</div></a></li>
        <?php if ($is_member) { ?>
            <?php if ($is_admin) {  ?>
            <?php }  ?>
            <li><a href="<?php echo G5_BBS_URL; ?>/logout.php?url=shop"><i class="fas fa-sign-out-alt"></i><div class="ft_txt">로그아웃</div></a></li>
            <li><a href="<?php echo G5_SHOP_URL; ?>/mypage.php"><span class="icon mypage"></span><div class="ft_txt">마이페이지</div></a></li>
        <?php } else { ?>
            <li><a href="<?php echo G5_BBS_URL; ?>/login.php?url=<?php echo $urlencode; ?>"><i class="fas fa-sign-in-alt"></i><div class="ft_txt">로그인</div></a></li>
            <li><a href="<?php echo G5_BBS_URL; ?>/register.php"><i class="fas fa-user-plus"></i><div class="ft_txt">회원가입</div></a></li>
        <?php } ?>
    </ul>
</div>
<!-- 하단 고정 탭 end -->

<div id="sidr">
<?php include_once(G5_THEME_MSHOP_PATH.'/category.php'); // 분류 ?>
</div>

<div id="ft">
    <h2><?php echo $config['cf_title']; ?> 정보</h2>
    <div class="ft_wrap">
        <div class="ft_wr">
            <div id="ft_cs" class="ft_con">
                <h3><a href="<?php echo G5_BBS_URL; ?>/faq.php">고객센터</a></h3>
                <div>
                    <strong class="cs_tel"><?php echo get_text($display_customer_service); ?></strong>
                    <p class="cs_info">※ 평일 10:00 - 18:00 (주말, 공휴일 휴무)</p>
                    <a href="<?php echo G5_BBS_URL; ?>/qalist.php" class="qa_link">문의게시판</a>
                    <a href="/bbs/faq.php?fm_id=3" class="qa_link">교환 및 환불</a>
                </div>
            </div>

            <div id="ft_bank" class="ft_con">
                <h3>계좌 정보</h3>
                <p class="name">예금주 : <?php echo get_text($display_depositor); ?> <br><?php echo $default['de_bank_account']; ?></p>
            </div>

            <div id="ft_if" class="ft_con">
                <h3><?php echo $config['cf_title']; ?></h3>
                <span><?php echo get_text($display_company_name); ?></span>
                <br>
                <span>대표자 : <?php echo get_text($display_company_owner); ?> | E-mail : <?php echo get_text($display_email); ?></span>
                <!-- <span>Email : <?php echo $default['de_admin_company_owner']; ?></span> -->
                <br>
                <span>주소 : <?php echo get_text($display_company_address); ?></span><br>
                <span>사업자등록번호 : <?php echo get_text($display_business_number); ?></span><br>
                <span>통신판매업신고번호 : <?php echo get_text($display_online_sales_number); ?></span><br>
                <span>개인정보 보호책임자 : <?php echo get_text($display_privacy_officer); ?></span><br>
                <span>팩스 : <?php echo get_text($display_fax); ?></span>
            </div>
        </div>
    </div>
    <ul id="ft_link">
        <li>
            <form name="frm1">
                <input class="ftc_value" name="wrkr_no" type="text" value="<?php echo get_text($display_business_number_digits); ?>">
                <input class="ftc_btn" type="button" value="사업자 정보확인" onclick="onopen();">
            </form>
        </li>
        <li><a href="<?php echo get_pretty_url('content', 'company'); ?>">회사소개</a></li>
        <li><a href="<?php echo get_pretty_url('content', 'privacy'); ?>">개인정보처리방침</a></li>
        <li><a href="<?php echo get_pretty_url('content', 'provision'); ?>">이용약관</a></li>
    </ul>
		<p class="ft_copy"><?php echo get_text($display_copyright); ?></p>
	<a href="http://pf.kakao.com/_rJDxeG/chat" id="ft_cs_kakao2"><span class="sound_only">카톡상담</span></a>
	<!-- <a href="#" id="ft_to_top"><i class="fa fa-arrow-up" aria-hidden="true"></i><span class="sound_only">상단으로</span></a> -->

   <script>
    $(function() {
        $("#ft_to_top").on("click", function() {
            $("html, body").animate({scrollTop:0}, '500');
            return false;
        });
    });

    function onopen()
    {
        var url =
            "http://www.ftc.go.kr/bizCommPop.do?wrkr_no="+frm1.wrkr_no.value;
        window.open(url, "bizCommPop", "width=750, height=700;");
    }
    </script>
</div>

<?php
$sec = get_microtime() - $begin_time;
$file = $_SERVER['SCRIPT_NAME'];

if ($config['cf_analytics']) {
    echo $config['cf_analytics'];
}
?>

<script src="<?php echo G5_JS_URL; ?>/sns.js"></script>
<script src="<?php echo G5_THEME_JS_URL ?>/css3-animate-it.js"></script>
<link rel="stylesheet" href="<?php echo G5_THEME_CSS_URL ?>/animate.css">

<?php
include_once(G5_THEME_PATH.'/tail.sub.php');
?>
