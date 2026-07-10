<?php
$sub_menu = "100200";
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

sql_query(" ALTER TABLE `{$g5['g5_shop_order_table']}`
                    ADD `od_partner` varchar(20) NOT NULL DEFAULT '' AFTER `od_pwd`,
                    ADD `od_partner_status` tinyint(2) NOT NULL DEFAULT '0' AFTER `od_partner`", true);

alert('처리하였습니다.');