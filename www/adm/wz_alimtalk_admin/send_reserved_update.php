<?php
$sub_menu = '102832';
include_once('./_common.php');

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

$qstr .= "&sch_frdate=".$sch_frdate."&sch_todate=".$sch_todate."&sch_success=".$sch_success."&sch_tmplId=".$sch_tmplId."&sch_phn=".$sch_phn."&sch_reserved=".$sch_reserved;

if ($_POST['act_button'] == "선택예약취소") {

    auth_check_menu($auth, $sub_menu, 'w');

    $bizmsg = new bizmsg();

    for ($i=0; $i<count($_POST['chk']); $i++) {

        // 실제 번호를 넘김
        $k = $_POST['chk'][$i];
        $ao_msgid = trim($_POST['ao_msgid'][$k]);

        $data = $bizmsg->cancel_reserved($ao_msgid);
        if ($data !== false) {
            sql_query("update {$g5['wz_alimtalk_log_table']} set ao_msgid = '".$data['msgid']."', ao_result = '발송 예약 취소' where ao_msgid = '".$ao_msgid."'");
        }
    }
}
else if ($_POST['act_button'] == "선택삭제") {

    auth_check_menu($auth, $sub_menu, 'w');

    $bizmsg = new bizmsg();

    for ($i=0; $i<count($_POST['chk']); $i++) {

        // 실제 번호를 넘김
        $k = $_POST['chk'][$i];
        $ao_msgid = trim($_POST['ao_msgid'][$k]);

        $data = $bizmsg->cancel_reserved($ao_msgid);
        sql_query("delete from {$g5['wz_alimtalk_log_table']} where ao_msgid = '".$ao_msgid."'");
    }
}

goto_url('./send_reserved.php?'.$qstr);