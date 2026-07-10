<?php
$sub_menu = '102840';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

if(!$_POST['at_tmplId'])
    alert('템플릿 코드를 입력해 주십시오.');

if(!$_POST['at_title'])
    alert('템플릿명을 입력해 주십시오.');

if(!$_POST['at_msg'])
    alert('템플릿 메시지를 입력해 주십시오.');

$at_id = isset($_POST['at_id']) ? clean_xss_tags($_POST['at_id']) : '';
$at_tmplId = isset($_POST['at_tmplId']) ? clean_xss_tags($_POST['at_tmplId']) : '';
$at_title = isset($_POST['at_title']) ? clean_xss_tags($_POST['at_title']) : '';

$at_msg = '';
if (isset($_POST['at_msg'])) {
    $at_msg = substr(trim($_POST['at_msg']),0,65536);
    $at_msg = preg_replace("#[\\\]+$#", "", $at_msg);
}

$at_use = isset($_POST['at_use']) ? clean_xss_tags($_POST['at_use']) : '';
$at_accent_title = isset($_POST['at_accent_title']) ? clean_xss_tags($_POST['at_accent_title']) : '';
$at_emp_type = isset($_POST['at_emp_type']) ? clean_xss_tags($_POST['at_emp_type']) : '';
if ($at_emp_type == '') {
    $at_accent_title = '';
}
$at_header = isset($_POST['at_header']) ? clean_xss_tags($_POST['at_header']) : '';
$at_itemHighlight_title = isset($_POST['at_itemHighlight_title']) ? clean_xss_tags($_POST['at_itemHighlight_title']) : '';
$at_itemHighlight_description = isset($_POST['at_itemHighlight_description']) ? clean_xss_tags($_POST['at_itemHighlight_description']) : '';
if ($at_emp_type != 'ITEMLIST') {
    $at_header = $at_itemHighlight_title = $at_itemHighlight_description = '';
}

$sql_common =  "at_tmplId = '".$at_tmplId."',
                at_emp_type = '".$at_emp_type."',
                at_title = '".$at_title."',
                at_type = '".$at_type."',
                at_accent_title = '".$at_accent_title."',
                at_msg = '".$at_msg."',
                at_use = '".$at_use."',
                at_header = '".$at_header."',
                at_itemHighlight_title = '".$at_itemHighlight_title."',
                at_itemHighlight_description = '".$at_itemHighlight_description."'
                ";

if ($w == '') {

    $sql = " insert into {$g5['wz_alimtalk_template_table']} set ".$sql_common;
    sql_query($sql);

    $at_id = (!defined('G5_MYSQLI_USE') ? mysql_insert_id() : sql_insert_id());

}
else if ($w == 'u') {

    $sql = " select at_tmplId from {$g5['wz_alimtalk_template_table']} where at_id = '".$at_id."' ";
    $at = sql_fetch($sql, true);
    $at_tmplId_before = $at['at_tmplId'];

    $sql = " update {$g5['wz_alimtalk_template_table']} set ".$sql_common." where at_id = '".$at_id."' ";
    sql_query($sql);

    // 템플릿코드를 수정해도 발송방법이 변경되지 않도록 수정
    if ($at_tmplId_before <> $at_tmplId) {
        $sql = " update {$g5['wz_alimtalk_template_cate_table']} set at_tmplId = '".$at_tmplId."' where at_tmplId = '".$at_tmplId_before."' ";
        sql_query($sql, true);
    }
}

$query = "delete from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$at_tmplId."'";
sql_query($query);

$z = 0;
$buttons = array();
foreach ($_POST['at_button_type'] as $k => $v) {

    $atb_name = trim($_POST['at_button_name'][$z]);
    $atb_name_select = trim($_POST['at_button_name_select'][$z]);
    $atb_type = $v;
    $atb_url_pc = $atb_url_mobile = $atb_scheme_android = $atb_scheme_ios = $atb_plugin_id = '';

    $atb_gubun = clean_xss_tags($_POST['atb_gubun'][$z]);

    switch ($v) {
        case 'WL':
            $atb_url_mobile = trim($_POST['at_button_url_1'][$z]);
            if ($_POST['at_button_url_2'][$z]) {
                $atb_url_pc = trim($_POST['at_button_url_2'][$z]);
            }
            break;
        case 'AL':
            $atb_scheme_android = trim($_POST['at_button_url_1'][$z]);
            $atb_scheme_ios = trim($_POST['at_button_url_2'][$z]);
            $atb_url_mobile = trim($_POST['at_button_url_3'][$z]);
            $atb_url_pc = trim($_POST['at_button_url_4'][$z]);
            break;
        case 'P1':
        case 'P2':
        case 'P3':
            $atb_plugin_id = trim($_POST['at_button_url_1'][$z]);
            break;
        case 'BF':
            $atb_name = $atb_name_select;
            $atb_plugin_id = trim($_POST['at_button_url_1'][$z]);
            break;
    }

    $query = "insert into {$g5['wz_alimtalk_template_button_table']} set at_tmplId = '".$at_tmplId."', atb_gubun = '".$atb_gubun."', atb_name = '".$atb_name."', atb_type = '".$atb_type."', atb_url_mobile = '".$atb_url_mobile."', atb_url_pc = '".$atb_url_pc."', atb_scheme_android = '".$atb_scheme_android."', atb_scheme_ios = '".$atb_scheme_ios."', atb_plugin_id = '".$atb_plugin_id."'";
    sql_query($query, true);

    $z++;
}

// 아이템정보 선택 삭제
foreach ($_POST['del_ati_id'] as $key => $value) {
    $ati_id = (int)trim($value);
    if ($ati_id) {
        $query = "delete from {$g5['wz_alimtalk_template_items_table']} where ati_id = '".$ati_id."'";
        sql_query($query);
    }
}

// 아이템정보 등록/수정
foreach ($_POST['ati_title'] as $key => $value) {

    $ati_title = clean_xss_tags($value);
    $ati_id = (int)($_POST['ati_id'][$key]);
    $ati_summary = (int)($_POST['ati_summary'][$key]); // 아이템요약정보 여부
    $ati_description = clean_xss_tags($_POST['ati_description'][$key]);

    $sql_common = "ati_title = '".$ati_title."', ati_description = '".$ati_description."'";

    if ($ati_id) { // 이미 등록이 된것은 수정
        if (!$ati_title) {
            $sql = "delete from {$g5['wz_alimtalk_template_items_table']} where ati_id = '".$ati_id."' ";
            sql_query($sql);
        }
        else {
            $sql = "update {$g5['wz_alimtalk_template_items_table']} set ".$sql_common." where ati_id = '".$ati_id."' ";
            sql_query($sql);
        }
    }
    else {
        if (!$ati_title) {
            continue;
        }
        $sql = "insert into {$g5['wz_alimtalk_template_items_table']} set at_id = '".$at_id."', ati_summary = '".$ati_summary."', ".$sql_common;
        sql_query($sql);
    }
}

if ($w == '') {
    goto_url('./template_list.php?'.$qstr);
}
else {
    goto_url('./template_form.php?w=u&at_id='.$at_id.'&'.$qstr);
}