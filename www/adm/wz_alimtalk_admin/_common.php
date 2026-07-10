<?php
define('G5_IS_ADMIN', true);
include_once ('../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/config.php');
include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/bizmsg.class.php');

add_stylesheet('<link rel="stylesheet" href="./style.css?v=210630">', 12);
add_stylesheet('<link rel="stylesheet" href="'.G5_PLUGIN_URL.'/wz.alimtalk.bizm/magnific-popup.css?v=210630">', 12);
add_javascript('<script type="text/javascript" src="'.G5_PLUGIN_URL.'/wz.alimtalk.bizm/jquery.magnific-popup.min.js"></script>', 12);
?>