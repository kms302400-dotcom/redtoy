<?php
$sub_menu = '102834';
include_once('./_common.php');

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

$qstr .= '&sch_filename='.$sch_filename;

if ($_POST['act_button'] == '선택삭제') {

    auth_check_menu($auth, $sub_menu, 'd');

    $bizmsg = new bizmsg();

    for ($i=0; $i<count($_POST['chk']); $i++) {

        // 실제 번호를 넘김
        $k = $_POST['chk'][$i];
        $fi_id = preg_replace('/[^0-9]/', '', trim($_POST['fi_id'][$k]));

        $query = sprintf(" select * from {$g5['wz_alimtalk_fl_image_table']} where fi_id = '%s' ", $fi_id);
        $db = sql_fetch($query);
        if (!$db['fi_id']) {
            continue;
        }

        $result = $bizmsg->delete_image($db['fi_img_url']); // 삭제

        $query = sprintf(" delete from {$g5['wz_alimtalk_fl_image_table']} where fi_id = '%s' ", $fi_id);
        sql_query($query);
    }
}

goto_url('./attch_image_list.php?'.$qstr);