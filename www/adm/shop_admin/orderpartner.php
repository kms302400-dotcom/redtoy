<?php
$sub_menu = '400410';
include_once('./_common.php');
check_demo();
auth_check_menu($auth, $sub_menu, 'd');


$od_id = $_GET['od_id'];
$od_partner_status = $_GET['od_partner_status'];

if($od_partner_status ==0 ){
    $od_partner_status = 1;
}else{
    $od_partner_status = 0;
}

$sql = "update {$g5['g5_shop_order_table']} set od_partner_status = {$od_partner_status} where od_id ={$od_id}";
sql_query($sql);

alert('정산처리를 완료하였습니다.');
?>