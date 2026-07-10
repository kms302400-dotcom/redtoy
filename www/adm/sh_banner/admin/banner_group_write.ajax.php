<?php
include_once('./_common.php');
if(!$is_admin) exit;
if(!$skin_name) $skin_name = 'basic';
include_once($g5['sh_banner_skin_path'].'/'.$skin_name.'/sh_banner.admin.php');