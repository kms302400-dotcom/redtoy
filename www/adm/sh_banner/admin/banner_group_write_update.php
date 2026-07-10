<?php
$sub_menu = "800400";
include_once("./_common.php");
auth_check($auth[$sub_menu], 'w');
check_admin_token();

$g5['title'] = '배너 그룹 저장';

$bn_gr_level_start = $bn_gr_level_start + 0;
$bn_gr_level_end = $bn_gr_level_end + 0;
if(!$bn_gr_level_start) $bn_gr_level_start = 1;
if(!$bn_gr_level_end) $bn_gr_level_end = 10;

$bn_gr_order = $bn_gr_order + 0;
$bn_gr_pc_use = $bn_gr_pc_use + 0;
$bn_gr_mobile_use = $bn_gr_mobile_use + 0;
$bn_order_opt = $bn_order_opt + 0; 
$bn_count_limit = $bn_count_limit + 0; 

$bn_gr_name = substr(trim($_POST['bn_gr_name']),0,50);
$bn_gr_name = addslashes(preg_replace("#[\\\]+$#", "", $bn_gr_name));
$bn_gr_memo = substr(trim($_POST['bn_gr_memo']),0,255);
$bn_gr_memo = addslashes(preg_replace("#[\\\]+$#", "", $bn_gr_memo));

if(is_array($bn_skin_set)) {
    $bn_gr_skin_set = json_encode($bn_skin_set);
} else {
    $bn_gr_skin_set = '';
}
if(!$bn_gr_skin) $bn_gr_skin = 'basic';

if($w == 'u' && $bn_gr_id) {
    $bn_gr_id = $bn_gr_id + 0;
    $sql = "
    update {$g5['sh_banner_group_table']}
      SET
        bn_gr_name = '{$bn_gr_name}',
        bn_gr_memo = '{$bn_gr_memo}',
        bn_gr_level_start = '{$bn_gr_level_start}',
        bn_gr_level_end = '{$bn_gr_level_end}',
        bn_gr_order = '{$bn_gr_order}',
        bn_order_opt = '{$bn_order_opt}', 
        bn_count_limit = '{$bn_count_limit}',
        bn_gr_pc_use = '{$bn_gr_pc_use}',
        bn_gr_mobile_use = '{$bn_gr_mobile_use}',
        bn_gr_skin = '{$bn_gr_skin}',
        bn_gr_skin_set = '{$bn_gr_skin_set}'
      WHERE
        bn_gr_id = '{$bn_gr_id}'
    ";
    sql_query($sql);
} else {
    $sql = "
    insert into {$g5['sh_banner_group_table']}
      SET
        bn_gr_name = '{$bn_gr_name}',
        bn_gr_memo = '{$bn_gr_memo}',
        bn_gr_level_start = '{$bn_gr_level_start}',
        bn_gr_level_end = '{$bn_gr_level_end}',
        bn_gr_order = '{$bn_gr_order}',
        bn_order_opt = '{$bn_order_opt}', 
        bn_count_limit = '{$bn_count_limit}',
        bn_gr_pc_use = '{$bn_gr_pc_use}',
        bn_gr_mobile_use = '{$bn_gr_mobile_use}',
        bn_gr_skin = '{$bn_gr_skin}',
        bn_gr_skin_set = '{$bn_gr_skin_set}'
    ";
    sql_query($sql);
}

goto_url('./banner_group_list.php?'.$qstr);
?>
