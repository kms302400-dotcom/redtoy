<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가;
// gnuwiz
if(!isset($default['it_price2'])) {
    sql_query(" ALTER TABLE `{$g5['g5_shop_item_table']}`
					ADD `it_price2` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price3` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price4` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price5` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price6` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price7` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price8` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price9` INT(11) NOT NULL DEFAULT '0',
					ADD `it_price10` INT(11) NOT NULL DEFAULT '0' ; ", false);
}