<?php
include_once('./_common.php');

$mb_id = trim($_POST['mb_id']);
$coupon1 = trim($_POST['coupon1']);
$coupon2 = trim($_POST['coupon2']);
$coupon3 = trim($_POST['coupon3']);
$coupon4 = trim($_POST['coupon4']);

$coupon = $coupon1.'-'.$coupon2.'-'.$coupon3.'-'.$coupon4;

// 회원만 쿠폰 등록 가능
if (!$is_member || !$mb_id)
    alert_close('회원만 접근이 가능합니다.');

if (!$coupon1 || !$coupon2 || !$coupon3 || !$coupon4)
    alert_close('쿠폰번호가 제대로 넘어오지 않았습니다.');

// 쿠폰번호 검증
$sql = " select count(*) as cnt from {$g5['g5_shop_coupon_table']} where cp_id = '$coupon' ";
$row = sql_fetch($sql);
if ($row['cnt'] == 0)
    alert_close('사용할 수 없는 쿠폰번호 입니다.\\n\\n관리자에게 문의하여 주십시오.');

$sql = " select * from {$g5['g5_shop_coupon_table']} where cp_id = '$coupon' ";
$row = sql_fetch($sql);
if ($row['mb_id'])
    alert_close('해당 쿠폰번호를 등록한 다른 회원이 존재합니다.\\n\\n관리자에게 문의하여 주십시오.');

// 사용가능한 쿠폰이라면 해당 회원을 사용자로 등록
$sql = " update {$g5['g5_shop_coupon_table']} set mb_id = '$mb_id' where cp_id = '{$coupon}' ";
sql_query($sql);

alert_close($coupon.' 쿠폰번호가 등록 되었습니다.\\n\\n마이페이지의 보유쿠폰을 확인하여 주십시오.');
?>