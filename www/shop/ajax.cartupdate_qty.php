<?php
include_once('./_common.php');

// print_r2($_POST); exit;

// 보관기간이 지난 상품 삭제
cart_item_clean();

// cart id 설정
set_cart_id($sw_direct);

if($sw_direct)
    $tmp_cart_id = get_session('ss_cart_direct');
else
    $tmp_cart_id = get_session('ss_cart_id');

// 브라우저에서 쿠키를 허용하지 않은 경우라고 볼 수 있음.
if (!$tmp_cart_id)
{
    alert('더 이상 작업을 진행할 수 없습니다.\\n\\n브라우저의 쿠키 허용을 사용하지 않음으로 설정한것 같습니다.\\n\\n브라우저의 인터넷 옵션에서 쿠키 허용을 사용으로 설정해 주십시오.\\n\\n그래도 진행이 되지 않는다면 쇼핑몰 운영자에게 문의 바랍니다.');
}

$tmp_cart_id = preg_replace('/[^a-z0-9_\-]/i', '', $tmp_cart_id);
$ct_id = isset($_POST['ct_id']) ? $_POST['ct_id'] : '';
$qty = isset($_POST['qty']) ? $_POST['qty'] : 0;

if (!$ct_id || !is_numeric($ct_id)) alert("잘못된 접근입니다1");
if (!$qty || !is_numeric($qty)) alert("잘못된 접근입니다2");

$sql = " update {$g5['g5_shop_cart_table']} set ct_qty = '$qty' where ct_id = '$ct_id' and od_id = '$tmp_cart_id' ";
sql_query($sql);

$sql = " select sum(if (a.io_type = 0, (a.ct_price + a.io_price) * a.ct_qty, (a.io_price * a.ct_qty))) as tot_price,
                sum(a.ct_send_cost) as tot_send_cost,
                sum(a.ct_point * a.ct_qty) as tot_point
           from {$g5['g5_shop_cart_table']} a
          where a.od_id = '$tmp_cart_id' ";
$row = sql_fetch($sql);
echo json_encode($row);
