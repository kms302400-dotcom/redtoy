<?php
$sub_menu = '600300';
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

$sql_common = " it_8                    = '$it_8',
                it_9                    = '$it_9',
                it_10                   = '$it_10' ";

$sql = " update {$g5['g5_shop_item_table']}
            set it_name = '$it_name',
                $sql_common
            where it_id = '$it_id' ";

echo $sql;

sql_query($sql);
goto_url("./redcomm_itemlist.php?$qstr");

?>
