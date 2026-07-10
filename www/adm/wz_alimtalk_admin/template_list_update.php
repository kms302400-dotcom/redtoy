<?php
$sub_menu = '102840';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

$qstr .= "&sch_cate=".$sch_cate."&sch_tmplId=".$sch_tmplId."&sch_title=".$sch_title."&sch_msg=".$sch_msg;
$atc_code = isset($_POST['atc_code']) ? clean_xss_tags($_POST['atc_code']) : '';

if ($_POST['act_button'] == "선택삭제") {

    auth_check_menu($auth, $sub_menu, "d");

    for ($i=0; $i<count($_POST['chk']); $i++) {

        // 실제 번호를 넘김
        $k = $_POST['chk'][$i];
        $at_tmplId = $_POST['at_tmplId'][$k];

        if (!$at_tmplId) {
            continue;
        }

        $sql = "select at_id from {$g5['wz_alimtalk_template_table']} where at_tmplId = '".$at_tmplId."'";
        $at = sql_fetch($sql);

        // 알림톡 템플릿 분류 삭제
        $sql = " delete from {$g5['wz_alimtalk_template_cate_table']} where at_tmplId = '".$at_tmplId."' ";
        sql_query($sql);

        // 알림톡 템플릿 버튼 삭제
        $sql = " delete from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$at_tmplId."' ";
        sql_query($sql);

        // 알림톡 템플릿 아이템 삭제
        $sql = " delete from {$g5['wz_alimtalk_template_items_table']} where at_id = '".$at['at_id']."' ";
        sql_query($sql);

        // 알림톡 템플릿 삭제
        $sql = " delete from {$g5['wz_alimtalk_template_table']} where at_id = '".$at['at_id']."' ";
        sql_query($sql);
    }

}
else if ($_POST['act_button'] == "선택적용") {

    auth_check_menu($auth, $sub_menu, 'w');

    $bk_status = '완료';

    for ($i=0; $i<count($_POST['chk']); $i++) {

        // 실제 번호를 넘김
        $k = $_POST['chk'][$i];
        $at_tmplId = $_POST['at_tmplId'][$k];

        // 알림톡 템플릿 분류 삭제
        $sql = " delete from {$g5['wz_alimtalk_template_cate_table']} where atc_code = '".$atc_code."' or at_tmplId = '".$at_tmplId."' ";
        sql_query($sql);

        // 알림톡 템플릿 분류 변경
        $sql = " insert into {$g5['wz_alimtalk_template_cate_table']} set at_tmplId = '".$at_tmplId."', atc_code = '".$atc_code."' ";
        sql_query($sql, true);
    }

}

goto_url('./template_list.php?'.$qstr);