<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$outlogin_skin_url.'/style.css">', 0);

// 쿠폰
$cp_count = 0;
$sql = " select cp_id
            from {$g5['g5_shop_coupon_table']}
            where mb_id IN ( '{$member['mb_id']}', '전체회원' )
            and cp_start <= '".G5_TIME_YMD."'
            and cp_end >= '".G5_TIME_YMD."' ";
$res = sql_query($sql);

for($k=0; $cp=sql_fetch_array($res); $k++) {
    if(!is_used_coupon($member['mb_id'], $cp['cp_id']))
        $cp_count++;
}
?>

<!-- 로그인 후 아웃로그인 시작 -->
<section id="ol_after" class="ol tnb_con" style="right: 75px;">
    <h2>나의 회원정보</h2>
    <button type="button" class="btn_close"><i class="fa fa-times"></i><span class="sound_only">닫기</span></button>
    <div class="ol_wr">
        <div class="ol_hd">
            <span class="profile_img">
                <?php echo get_member_profile_img($member['mb_id']); ?>
                <a href="<?php echo G5_BBS_URL ?>/member_confirm.php?url=register_form.php" id="ol_after_info"><i class="fa fa-cog" aria-hidden="true"></i><span class="sound_only">정보수정</span></a>
            </span>
            <strong><?php echo $nick ?>님</strong>

            <div class="out_point"><a href="<?php echo G5_BBS_URL; ?>/point.php" target="_blank" class="win_point"><span>포인트</span><?php echo number_format($member['mb_point']); ?>점</a></div>

            <div class="ol_hd_btn">
                <a href="<?php echo G5_BBS_URL ?>/logout.php" id="ol_after_logout" class="btn_b01 btn_s">로그아웃</a>
                <?php if($is_admin) { ?><a href="<?php echo G5_THEME_ADM_URL ?>" target="_blank" class="btn_admin">테마관리</a><?php } ?>
            </div>
        </div>
        <ul id="ol_after_private">
            <li><a href="<?php echo G5_SHOP_URL; ?>/mypage.php"><i class="fas fa-user"></i>마이페이지</a></li>
            <li><a href="<?php echo G5_SHOP_URL; ?>/couponzone.php"><i class="fas fa-gift"></i>쿠폰존</a></li>
            <li><a href="<?php echo G5_SHOP_URL; ?>/itemuselist.php"><i class="fas fa-camera-retro"></i>사용후기</a></li>
            <li><a href="<?php echo G5_BBS_URL; ?>/faq.php"><i class="far fa-question-circle"></i>고객센터</a></li>
            <li><a href="<?php echo G5_BBS_URL; ?>/qalist.php"><i class="fas fa-comments"></i>1:1문의</a></li>
            <?php if ($is_admin) {  ?>
            <li><a href="<?php echo G5_ADMIN_URL ?>/shop_admin/"><i class="fas fa-user-cog"></i>관리자</a></li>
            <?php } ?>
        </ul>
    </div>
</section>

<script>
// 탈퇴의 경우 아래 코드를 연동하시면 됩니다.
function member_leave()
{
    if (confirm("정말 회원에서 탈퇴 하시겠습니까?"))
        location.href = "<?php echo G5_BBS_URL ?>/member_confirm.php?url=member_leave.php";
}
</script>
<!-- 로그인 후 외부로그인 끝 -->
