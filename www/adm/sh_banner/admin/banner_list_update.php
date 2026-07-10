<?php
$sub_menu = "800100";
include_once("./_common.php");
auth_check($auth[$sub_menu], 'd');
include_once('./_common.php');

if (!count($_REQUEST['chk'])) {
    alert($_REQUEST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_REQUEST['act_button'] == "선택수정") {

} else if ($_REQUEST['act_button'] == "선택삭제") {

    if ($is_admin != 'super')
        alert('삭제는 최고관리자만 가능합니다.');

    auth_check($auth[$sub_menu], 'd');

    for ($i=0; $i<count($_REQUEST['chk']); $i++) {
        // 실제 번호를 넘김
        $k = $_REQUEST['chk'][$i];
        $row = sql_fetch(" select bn_filename from {$g5['sh_banner_table']} where bn_id = '{$k}' ");
        if ($row['bn_filename']) {
            @unlink(G5_DATA_PATH.'/sh_banner/'.$row['bn_filename']);
        }
        $sql = " delete from {$g5['sh_banner_table']} where bn_id = '{$k}' ";
        sql_query($sql);
    }

}

goto_url('./banner_list.php?bn_group='.$bn_group.'&'.$qstr);
?>
