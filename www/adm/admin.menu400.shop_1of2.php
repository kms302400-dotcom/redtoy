<?php
if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) {
    return;
}

// 기간 설정 함수
$left_today = date("Y-m-d"); //오늘날짜

// 오늘의 모든주문(현재상태에상관없이 오늘 주문건만 계산)/금액(관리자모드)
$sql = " select count(od_id) as cnt, sum(od_cart_price + od_send_cost + od_send_cost2 - od_cancel_price) as price from {$g5['g5_shop_order_table']} where od_time between '$left_today 00:00:00' and '$left_today 23:59:59' ";
$row = sql_fetch($sql);
$left_infotoday = array();
$left_infotoday['count'] = (int)$row['cnt'];
$left_infotoday['price'] = (int)$row['price'];
$left_infotoday['href'] = G5_ADMIN_URL . '/shop_admin/orderlist.php?od_term=od_time&fr_date=' . urlencode($left_today) . '&to_date=' . urlencode($left_today);

// 오늘 발생한 주문서 건수 표시
$linfotoday = ($left_infotoday['count'] > 0) ? ' <span class="round_cnt_black">' . number_format($left_infotoday['count']) . '</span>' : ''; //오늘의주문개수
$linfotoday_depth = ($left_infotoday['count'] > 0) ? ' <span class="round_cnt_lightpink">' . number_format($left_infotoday['count']) . '</span>' : ''; //오늘의주문개수


$menu['menu400'] = array(
    array('400000', '쇼핑몰관리' . $linfotoday, G5_ADMIN_URL . '/shop_admin/', 'shop_config'),
    array('400010', '쇼핑몰현황', G5_ADMIN_URL . '/shop_admin/', 'shop_index'),
    array('400100', '쇼핑몰설정', G5_ADMIN_URL . '/shop_admin/configform.php', 'scf_config'),
    array('400400', '주문내역' . $linfotoday_depth, G5_ADMIN_URL . '/shop_admin/orderlist.php', 'scf_order', 1),
    array('400410', '파트너시스템', G5_ADMIN_URL . '/shop_admin/partner_list.php', 'scf_order', 1),
    array('400440', '개인결제관리', G5_ADMIN_URL . '/shop_admin/personalpaylist.php', 'scf_personalpay', 1),
    array('400200', '분류관리', G5_ADMIN_URL . '/shop_admin/categorylist.php', 'scf_cate'),
    array('400300', '상품관리', G5_ADMIN_URL . '/shop_admin/itemlist.php', 'scf_item'),
    array('400660', '상품문의', G5_ADMIN_URL . '/shop_admin/itemqalist.php', 'scf_item_qna'),
    array('400650', '사용후기', G5_ADMIN_URL . '/shop_admin/itemuselist.php', 'scf_ps'),
    array('400620', '상품재고관리', G5_ADMIN_URL . '/shop_admin/itemstocklist.php', 'scf_item_stock'),
    array('400610', '상품유형관리', G5_ADMIN_URL . '/shop_admin/itemtypelist.php', 'scf_item_type'),
    array('400500', '상품옵션재고관리', G5_ADMIN_URL . '/shop_admin/optionstocklist.php', 'scf_item_option'),
    array('400800', '쿠폰관리', G5_ADMIN_URL . '/shop_admin/couponlist.php', 'scf_coupon'),
    array('400810', '쿠폰존관리', G5_ADMIN_URL . '/shop_admin/couponzonelist.php', 'scf_coupon_zone'),
    array('400750', '추가배송비관리', G5_ADMIN_URL . '/shop_admin/sendcostlist.php', 'scf_sendcost', 1),
    array('400410', '미완료주문', G5_ADMIN_URL . '/shop_admin/inorderlist.php', 'scf_inorder', 1),
    /*array('400410', '파트너테이블처리', G5_ADMIN_URL . '/shop_admin/partner_db.php', 'scf_inorder', 1),*/
);
