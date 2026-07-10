<?php
$sub_menu = '700300';
include_once('./_common.php');

check_demo();

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {


    auth_check($auth[$sub_menu], 'w');

    for ($i=0; $i<count($_POST['chk']); $i++) {
         // 실제 번호를 넘김
        $k = $_POST['chk'][$i];

        $p_it_name = is_array($_POST['it_name']) ? strip_tags($_POST['it_name'][$k]) : '';
        $p_it_title = is_array($_POST['it_8']) ? strip_tags($_POST['it_8'][$k]) : '';
        $p_it_description = is_array($_POST['it_9']) ? strip_tags($_POST['it_9'][$k]) : '';
        $p_it_keywords = is_array($_POST['it_10']) ? strip_tags($_POST['it_10'][$k]) : '';

        $sql = "update {$g5['g5_shop_item_table']} 
                set    it_name        = '".$p_it_name."',
                       it_8        = '".$p_it_title."',
                       it_9        = '".$p_it_description."',
                       it_10        = '".$p_it_keywords."',
                       it_update_time = '".G5_TIME_YMDHIS."'
                 where it_id   = '".preg_replace('/[^a-z0-9_\-]/i', '', $_POST['it_id'][$k])."' ";
        sql_query($sql);

    }
}
goto_url("./redcomm_itemlist.php?sca=$sca&amp;sst=$sst&amp;sod=$sod&amp;sfl=$sfl&amp;stx=$stx&amp;page=$page");
?>
