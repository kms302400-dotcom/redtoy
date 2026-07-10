<?php
$sub_menu = "400800";
include_once("./_common.php");

if ( ! function_exists('utf2euc')) {
    function utf2euc($str) {
        return iconv("UTF-8","cp949//IGNORE", $str);
    }
}

if ( ! function_exists('is_ie')) {
    function is_ie() {
        return isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
    }
}

auth_check($auth[$sub_menu], "r");

$sql_common = " from {$g5['g5_shop_coupon_table']} ";

$sql_search = " where (1) ";

$sql_search = " where (1) ";
if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case 'mb_id' :
            $sql_search .= " ({$sfl} = '{$stx}') ";
            break;
        default :
            $sql_search .= " ({$sfl} like '%{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

if (!$sst) {
    $sst  = "cp_no";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
            {$sql_common}
            {$sql_search}
            {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

if (!$total_count) alert_just('데이터가 없습니다.');

//$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
//$result = sql_query($sql);

$qry = sql_query(" select * {$sql_common} {$sql_search} {$sql_order} ");

/*================================================================================
php_writeexcel http://www.bettina-attack.de/jonny/view.php/projects/php_writeexcel/
=================================================================================*/

include_once(G5_LIB_PATH.'/Excel/php_writeexcel/class.writeexcel_workbook.inc.php');
include_once(G5_LIB_PATH.'/Excel/php_writeexcel/class.writeexcel_worksheet.inc.php');

$fname = tempnam(G5_DATA_PATH, "tmp.xls");
$workbook = new writeexcel_workbook($fname);
$worksheet = $workbook->addworksheet();
$worksheet->set_column(1, 1, 20); // 부터, 까지, 가로길이
$worksheet->set_column(2, 2, 25); // 부터, 까지, 가로길이
$worksheet->set_column(3, 4, 15); // 부터, 까지, 가로길이
$worksheet->set_column(5, 5, 10); // 부터, 까지, 가로길이
$worksheet->set_column(6, 6, 20); // 부터, 까지, 가로길이

$num2_format =& $workbook->addformat(array(num_format => '\0#'));

// Put Excel data
$data = array(
	'번호',
	'쿠폰종류',
	'쿠폰코드',
	'쿠폰이름',
	'적용대상',
	'회원아이디',
	'사용기한',
	'사용회수'
);

$data = array_map('iconv_euckr', $data);

$col = 0;
foreach($data as $cell) {
    $worksheet->write(0, $col++, $cell);
}

for($i=1; $res=sql_fetch_array($qry); $i++)
{

	switch($res['cp_method']) {
		case '0':
			$sql3 = " select it_name from {$g5['g5_shop_item_table']} where it_id = '{$res['cp_target']}' ";
			$row3 = sql_fetch($sql3);
			$res['cp_method'] = '개별상품할인';
			$res['cp_target'] = get_text($row3['it_name']);
			break;
		case '1':
			$sql3 = " select ca_name from {$g5['g5_shop_category_table']} where ca_id = '{$res['cp_target']}' ";
			$row3 = sql_fetch($sql3);
			$res['cp_method'] = '카테고리할인';
			$res['cp_target'] = get_text($row3['ca_name']);
			break;
		case '2':
			$res['cp_method'] = '주문금액할인';
			$res['cp_target'] = '주문금액';
			break;
		case '3':
			$res['cp_method'] = '배송비할인';
			$res['cp_target'] = '배송비';
			break;
	}

	// 쿠폰사용회수
	$sql = " select count(*) as cnt from {$g5['g5_shop_coupon_log_table']} where cp_id = '{$res['cp_id']}' ";
	$tmp = sql_fetch($sql);
	$res['used_count'] = $tmp['cnt'];

	// 사용기한
	$res['cp_date'] = substr($res['cp_start'], 2, 8).' ~ '.substr($res['cp_end'], 2, 8);

    $res = array_map('iconv_euckr', $res);

	$worksheet->write($i, 0, $i); // 번호
	$worksheet->write($i, 1, $res['cp_method']); // 쿠폰종류
	$worksheet->write($i, 2, $res['cp_id']); // 쿠폰코드
	$worksheet->write($i, 3, $res['cp_subject']); // 쿠폰이름
	$worksheet->write($i, 4, $res['cp_target']); // 적용대상
	$worksheet->write($i, 5, $res['mb_id']); // 회원아이디
	$worksheet->write($i, 6, $res['cp_date']); // 사용기한
	$worksheet->write($i, 7, $res['used_count']); // 사용회수
}

$workbook->close();

$filename = "쿠폰목록-".date("ymd", time()).".xls";
if( is_ie() ) $filename = utf2euc($filename);

header("Content-Type: application/x-msexcel; name=".$filename);
header("Content-Disposition: inline; filename=".$filename);
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

exit;
?>