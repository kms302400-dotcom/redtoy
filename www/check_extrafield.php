<?php

include "./_common.php";


$notice = "이 안내가 출력이 되면 해당 필드를 사용하는 데이타가 없는 경우입니다. 플러그인 사용이 가능합니다";
$sql = "SELECT it_id, it_name   FROM g5_shop_item WHERE it_8 <> '' or it_9 <> '' or it_10 <> ''  ";
$rows = sql_query($sql);


if($rows->num_rows> 0) {
	$notice = "";
	echo "상품정보에서 여분필드(it_8, it_9,it_10)가 사용중입니다.\r\n";
	for ($i=0; $row=sql_fetch_array($rows); $i++) {
		echo $row['it_id'] . " - " .$row['it_name'] . " \r\n";
	}
} 

$sql = "SELECT ca_id, ca_name  FROM g5_shop_category WHERE ca_8 <> '' or ca_9 <> '' or ca_10 <> ''  ";
$rows = sql_query($sql);

if($rows->num_rows > 0) {
	$notice = "";
	echo "상품카테고리에서 여분필드(ca_8, ca_9,ca_10)가 사용중입니다.\r\n";
	for ($i=0; $row=sql_fetch_array($rows); $i++) {
		echo $row['ca_id'] . " - " .$row['ca_name'] . " \r\n";
	}
} 

echo $notice;

?>
