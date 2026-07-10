<?php
$sub_menu = '102810';
include_once('./_common.php');

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

$qstr .= "&sch_frdate=".$sch_frdate."&sch_todate=".$sch_todate."&sch_success=".$sch_success."&sch_tmplId=".$sch_tmplId."&sch_phn=".$sch_phn."&sch_reserved=".$sch_reserved;

if ($_POST['act_button'] == "선택삭제") {

    auth_check_menu($auth, $sub_menu, 'w');

    $bizmsg = new bizmsg();

    for ($i=0; $i<count($_POST['chk']); $i++) {

        // 실제 번호를 넘김
        $k = $_POST['chk'][$i];
        $ao_id = trim($_POST['ao_id'][$k]);

        $ao = sql_fetch("select * from {$g5['wz_alimtalk_log_table']} where ao_id = '".$ao_id."'");
        if ($ao['ao_id']) {

            if ($ao['ao_is_success'] == '0' && $ao['ao_reserveDt'] <> '0000-00-00 00:00:00' && $ao['ao_reserveDt'] > G5_TIME_YMDHIS) {
                $data = $bizmsg->cancel_reserved($ao['ao_msgid']); // 예약발송건 일경우 예약발송 취소처리.
            }

            sql_query("delete from {$g5['wz_alimtalk_log_table']} where ao_id = '".$ao_id."'");
        }
    }
}

goto_url('./send_list.php?'.$qstr);