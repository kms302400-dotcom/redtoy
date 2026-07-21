<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('redtoy_add_admin_statistics_menu')) {
    add_replace('admin_menu', 'redtoy_add_admin_statistics_menu', 8, 1);

    function redtoy_add_admin_statistics_menu($admin_menu)
    {
        if (!isset($admin_menu['menu500']) || !is_array($admin_menu['menu500'])) {
            return $admin_menu;
        }

        foreach ($admin_menu['menu500'] as $menu_item) {
            if (isset($menu_item[0]) && $menu_item[0] === '500130') {
                return $admin_menu;
            }
        }

        $statistics_menu = array(
            '500130',
            '종합통계',
            G5_ADMIN_URL.'/shop_admin/redtoy_statistics.php',
            'sst_redtoy_statistics'
        );

        $insert_at = count($admin_menu['menu500']);
        foreach ($admin_menu['menu500'] as $index => $menu_item) {
            if (isset($menu_item[0]) && $menu_item[0] === '500110') {
                $insert_at = $index + 1;
                break;
            }
        }

        array_splice($admin_menu['menu500'], $insert_at, 0, array($statistics_menu));

        return $admin_menu;
    }
}
