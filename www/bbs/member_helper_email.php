<?php
include_once("_common.php");
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

if ($is_member) {
	alert_close('이미 로그인중입니다.', G5_URL);
}

if (!chk_captcha()) {
//	alert('자동등록방지 숫자가 틀렸습니다.');
}

$row = sql_fetch(" select mb_email from `{$g5['member_table']}` where mb_name='{$name}' and (mb_tel='{$phone}' or mb_hp='{$phone}') ");

if ($row['mb_email']) {
	echo $row['mb_email'];
} else {
	echo "000";
}