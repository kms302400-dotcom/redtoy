<?php
if (!defined('_GNUBOARD_')) exit;
require_once G5_LIB_PATH.'/redtoy_telegram.lib.php';
add_event('qawrite_update', 'redtoy_tg_qa', 10, 5);
add_replace('admin_menu', 'redtoy_tg_menu', 10, 1);
function redtoy_tg_menu($menus)
{
    global $is_admin;
    if ($is_admin === 'super') $menus['menu400'][] = array('400990','텔레그램 운영 알림',G5_ADMIN_URL.'/shop_admin/redtoy_telegram.php','redtoy_telegram');
    return $menus;
}
