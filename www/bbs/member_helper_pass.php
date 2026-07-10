<?php
include_once("./_common.php");

if ($is_member) {
	alert_close('이미 로그인중입니다.', G5_URL);
}

$id = trim($id);
if (!$id) {
	echo "정보가 없습니다.";
	exit;
}

$mb = sql_fetch(" select mb_no, mb_id, mb_name, mb_nick, mb_email, mb_datetime, mb_leave_date, mb_lost_certify from `{$g5['member_table']}` where mb_name = '{$name}' and mb_id = '{$id}' and mb_email = '{$email}' ");
if (!$mb['mb_id'] || $mb['mb_leave_date']){ 
	echo "정보가 없습니다.";
	exit;
} else if (is_admin($id)){ 
	echo "관리자 아이디는 접근 불가합니다.";
	exit;
}

// 어떠한 회원정보도 포함되지 않은 일회용 난수를 생성하여 인증에 사용
$mb_nonce = md5(pack('V*', rand(), rand(), rand(), rand()));

// 임시비밀번호 발급
$change_password = rand(100000, 999999);
$mb_lost_certify = get_encrypt_string($change_password);

// 회원테이블에 필드를 추가
if (!isset($mb['mb_lost_certify'])) {
	sql_query(" ALTER TABLE `{$g5['member_table']}` ADD `mb_lost_certify` VARCHAR( 255 ) NOT NULL AFTER `mb_memo` ", false);
}

// 임시비밀번호와 난수를 mb_lost_certify 필드에 저장
sql_query(" update `{$g5['member_table']}` set mb_lost_certify = '{$mb_nonce} {$mb_lost_certify}' where mb_id = '{$mb['mb_id']}' ");

// 인증 링크 생성
$href = G5_BBS_URL."/password_lost_certify.php?mb_no={$mb['mb_no']}&amp;mb_nonce={$mb_nonce}";

echo "새 비밀번호 <span style='color:#ff3300; font:13px Verdana;'><strong>{$change_password}</span></strong><br /><a href='{$href}'>여기를 <span style='color:#ff3300;font-size:1.2em; font-weight:bold;'>클릭</span>하면 비밀번호가 변경됩니다.</a>";