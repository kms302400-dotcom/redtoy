<?php
$sub_menu = '700100';
include_once('./_common.php');

check_demo();

auth_check($auth[$sub_menu], "w");

check_admin_token();

for ($i=0; $i<count($_POST['ca_id']); $i++)
{
    $str_ca_mb_id = isset($_POST['ca_mb_id'][$i]) ? strip_tags($_POST['ca_mb_id'][$i]) : '';

    if ($str_ca_mb_id)
    {
        $sql = " select mb_id from {$g5['member_table']} where mb_id = '".sql_real_escape_string($str_ca_mb_id)."' ";
        $row = sql_fetch($sql);
        if (!$row['mb_id'])
            alert("\'{$str_ca_mb_id}\' 은(는) 존재하는 회원아이디가 아닙니다.", "./categorylist.php?$qstr");
    }
    
    $p_ca_name = is_array($_POST['ca_name']) ? strip_tags($_POST['ca_name'][$i]) : '';
    $p_ca_title = is_array($_POST['ca_8']) ? strip_tags($_POST['ca_8'][$i]) : '';
    $p_ca_description = is_array($_POST['ca_9']) ? strip_tags($_POST['ca_9'][$i]) : '';
    $p_ca_keywords = is_array($_POST['ca_10']) ? strip_tags($_POST['ca_10'][$i]) : '';

    $sql = " update {$g5['g5_shop_category_table']}
                set ca_name             = '".$p_ca_name."',
                    ca_8  = '".sql_real_escape_string($p_ca_title)."',
                    ca_9  = '".sql_real_escape_string($p_ca_description)."',
                    ca_10  = '".sql_real_escape_string($p_ca_keywords)."'
              where ca_id = '".sql_real_escape_string($_POST['ca_id'][$i])."' ";

              sql_query($sql);

}

goto_url("./redcomm_categorylist.php?$qstr");
?>
