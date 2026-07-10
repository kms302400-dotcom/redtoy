<?php
$sub_menu = '600100';
include_once('./_common.php');

auth_check($auth[$sub_menu], "d");

// check_admin_token();

if ($ca_mb_id)
{
    $sql = " select mb_id from {$g5['member_table']} where mb_id = '$ca_mb_id' ";
    $row = sql_fetch($sql);
    if (!$row['mb_id'])
        alert("\'$ca_mb_id\' 은(는) 존재하는 회원아이디가 아닙니다.");
}

$sql_common = " ca_8                    = '$ca_8',
                ca_9                    = '$ca_9',
                ca_10                   = '$ca_10' ";

$sql = " update {$g5['g5_shop_category_table']}
            set ca_name = '$ca_name',
                $sql_common
            where ca_id = '$ca_id' ";
sql_query($sql);

// 하위분류를 똑같은 설정으로 반영
if ($sub_category) {
    $len = strlen($ca_id);
    $sql = " update {$g5['g5_shop_category_table']}
                set $sql_common
                where SUBSTRING(ca_id,1,$len) = '$ca_id' ";
    if ($is_admin != 'super')
        $sql .= " and ca_mb_id = '{$member['mb_id']}' ";
    sql_query($sql);
}

goto_url("./redcomm_categorylist.php?$qstr");

?>
