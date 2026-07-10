<?php

// 기간 설정 함수
$nav_today = date("Y-m-d"); //오늘날짜

// 오늘 기준 회원가입 수
$nav_today_mship_cnt = 0;
$sql = " select count(*) as cnt from {$g5['member_table']} where mb_datetime between '$nav_today 00:00:00' and '$nav_today 23:59:59' ";
$row = sql_fetch($sql);
$nav_today_mship_cnt = (int)$row['cnt'];

// 오늘 회원가입수
$nav_ty_mship = $nav_today_mship_cnt;

// 회원가입수표시
$nav_mship1 = ($nav_ty_mship > 0) ? ' <span class="round_cnt_black">'.number_format($nav_ty_mship) . '</span>' : ''; //오늘+어제 회원가입수
$nav_mship1_depth = ($nav_ty_mship > 0) ? ' <span class="round_cnt_lightpink">'.number_format($nav_ty_mship) . '</span>' : ''; //오늘+어제 회원가입수

$menu['menu200'] = array(
    array('200000', '회원관리' . $nav_mship1, G5_ADMIN_URL . '/member_list.php', 'member'),
    array('200100', '회원관리' . $nav_mship1_depth, G5_ADMIN_URL . '/member_list.php', 'mb_list'),
    array('200300', '회원메일발송', G5_ADMIN_URL . '/mail_list.php', 'mb_mail'),
    array('200800', '접속자집계', G5_ADMIN_URL . '/visit_list.php', 'mb_visit', 1),
    array('200810', '접속자검색', G5_ADMIN_URL . '/visit_search.php', 'mb_search', 1),
    array('200820', '접속자로그삭제', G5_ADMIN_URL . '/visit_delete.php', 'mb_delete', 1),
    array('200200', '포인트관리', G5_ADMIN_URL . '/point_list.php', 'mb_point'),
    array('200900', '투표관리', G5_ADMIN_URL . '/poll_list.php', 'mb_poll')
);
