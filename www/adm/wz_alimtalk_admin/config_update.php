<?php
$sub_menu = '102850';
include_once('./_common.php');

check_demo();

auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

check_admin_token();

$cf_userid = isset($_POST['cf_userid']) ? trim($_POST['cf_userid']) : '';
$cf_userkey_key = isset($_POST['cf_userkey_key']) ? trim($_POST['cf_userkey_key']) : '';
$cf_profile_key = isset($_POST['cf_profile_key']) ? trim($_POST['cf_profile_key']) : '';
$cf_smssender = isset($_POST['cf_smssender']) ? trim($_POST['cf_smssender']) : '';
$cf_receiver = isset($_POST['cf_receiver']) ? trim($_POST['cf_receiver']) : '';
$cf_sms_use = isset($_POST['cf_sms_use']) ? trim($_POST['cf_sms_use']) : '';
$cf_sms_type = isset($_POST['cf_sms_type']) ? clean_xss_tags($_POST['cf_sms_type'], 1, 1) : '';

$cf_userid = clean_xss_tags($cf_userid);
$cf_userkey_key = trim(clean_xss_tags($cf_userkey_key));
$cf_profile_key = preg_replace('/[^0-9a-zA-Z]/', '', clean_xss_tags($cf_profile_key));
$cf_smssender = clean_xss_tags($cf_smssender);
$cf_receiver = clean_xss_tags($cf_receiver);
$cf_sms_use = clean_xss_tags($cf_sms_use);

if ($cf_sms_use) { // 문자발송사용에 체크했을경우 제대로 설치가 되었는지 확인
    include_once(G5_SMS5_PATH.'/sms5.lib.php');
    if (!method_exists('SMS5','getMsg')) {
        alert("비즈엠 기반 문자발송 파일이 정상적으로 설치되지 않았습니다.");
    }

    if(!sql_num_rows(sql_query(" show tables like '{$g5['sms5_config_table']}' "))) {
        alert("SMS5 설치가 되어있지 않습니다. SMS관리 > SMS 기본설정 메뉴에 접속하시면 자동 설치 됩니다.");
    }
}

$query = " update {$g5['wz_alimtalk_config_table']}
            set cf_userid = '".$cf_userid."',
                cf_userkey_key = '".$cf_userkey_key."',
                cf_profile_key = '".$cf_profile_key."',
                cf_smssender = '".$cf_smssender."',
                cf_receiver = '".$cf_receiver."',
                cf_sms_use = '".$cf_sms_use."'
            ";
sql_query($query, true);

if ($cf_sms_use) { // 문자발송에 체크할경우 기본문자발송서비스를 비즈엠으로 변경, 소스변경 최소화를 위해 icode 로 적용.

    $sql_common = '';
    if (!$config['cf_icode_id']) { // 값이 존재하지 않으면 발송되지 않음, cf_icode_token_key 값까지 존재해야만 shop\orderformupdate.php 파일에서도 정상발송됨 (is_sms_send)
        $sql_common = ", cf_icode_id = '".$cf_userid."', cf_icode_pw = '".$cf_userid."', cf_icode_token_key = '".$cf_userid."'";
    }

    $query = " update {$g5['config_table']}
            set cf_sms_use = 'icode',
                cf_sms_type = '".$cf_sms_type."' ".$sql_common."
            ";
    sql_query($query, true);

    $res = sql_fetch("select * from ".$g5['sms5_config_table']." limit 1");
    if (!$res)
        $sql = "insert into ";
    else
        $sql = "update ";

    $sql .= $g5['sms5_config_table']." set cf_phone='".$cf_smssender."' ";
    sql_query($sql, true);
}

goto_url('./config.php', false);