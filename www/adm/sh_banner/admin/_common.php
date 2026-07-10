<?php
define('G5_IS_ADMIN', true);
include_once ('../../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
include_once(G5_ADMIN_PATH.'/sh_banner/config/sh_banner.config.php');
include_once(G5_ADMIN_PATH.'/sh_banner/admin/sh_banner.admin.lib.php');
if( isset($token) ){
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}
?>