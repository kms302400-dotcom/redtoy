<?php
include_once("_common.php");
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

if ($is_member) {
	alert_close('이미 로그인중입니다.', G5_URL);
}

if (!chk_captcha()) {
//	alert('자동등록방지 숫자가 틀렸습니다.');
}

$row = sql_fetch(" select mb_id from `{$g5['member_table']}` where mb_name = '{$name}' and mb_email = '{$email}' ");

if ($row['mb_id']) {
	echo $row['mb_id'];
} else {
	echo "000";
}