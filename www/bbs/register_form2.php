<?php
include_once('./_common.php');
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');
include_once(G5_LIB_PATH.'/register.lib.php');

run_event('register_form_before');

include_once('./_head.php');

include_once($member_skin_path.'/register_form.skin2.php');

run_event('register_form_after', $w, $agree, $agree2);

include_once('./_tail.php');