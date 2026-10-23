<?php
$sub_menu = '400400';
include_once('./_common.php');
include_once('./admin.shop.lib.php');
include_once('./redtoy_statistics.lib.php');
include_once(G5_LIB_PATH.'/mailer.lib.php');

auth_check_menu($auth, $sub_menu, "w");

check_admin_token();

$od_id = isset($_POST['od_id']) ? safe_replace_regex($_POST['od_id'], 'od_id') : '';

$search = isset($_REQUEST['search']) ? get_search_string($_REQUEST['search']) : '';
$sort1 = isset($_REQUEST['sort1']) ? clean_xss_tags($_REQUEST['sort1'], 1, 1) : '';
$sort2 = isset($_REQUEST['sort2']) ? clean_xss_tags($_REQUEST['sort2'], 1, 1) : '';
$sel_field = isset($_REQUEST['sel_field']) ? clean_xss_tags($_REQUEST['sel_field'], 1, 1) : '';

// The dedicated action never trusts edited financial/shipping fields from this form.
if (isset($_POST['receipt_action'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_POST['receipt_action'] !== 'confirm_bank')
        alert('잘못된 입금 확인 요청입니다.');
    require_once G5_LIB_PATH.'/redtoy_bank_receipt.lib.php';
    try {
        redtoy_bank_receipt_confirm($od_id);
    } catch (Throwable $e) {
        $messages = array(
            'busy' => '다른 작업이 처리 중입니다. 잠시 후 주문 상태를 다시 확인해 주세요.',
            'bank_not_eligible' => '입금 대기 중인 무통장 주문만 처리할 수 있습니다. 주문 상태와 취소·환불 내역을 확인해 주세요.',
            'bank_amount_mismatch' => '주문 금액 또는 상품 상태 확인이 필요합니다. 기존 수동 수정 화면에서 확인해 주세요.',
            'reconciliation_required' => '확인 필요 취소 기록이 있습니다. 취소·환불 내역을 확인해 주세요.'
        );
        alert(isset($messages[$e->getMessage()]) ? $messages[$e->getMessage()]
            : '입금 확인을 완료하지 못했습니다. 주문·상품 상태를 확인한 뒤 수동 처리해 주세요.');
    }
    // Financial changes are complete before either notification is attempted.
    try {
        if (function_exists('redtoy_tg_order')) redtoy_tg_order($od_id);
    } catch (Throwable $e) { error_log('redtoy_bank_receipt: telegram_failed'); }
    $od_send_alimtalk = 1;
    try {
        if (!defined('_ORDERALIMTALK_')) define('_ORDERALIMTALK_', true);
        include './orderalimtalk.inc.php';
    } catch (Throwable $e) {
        error_log('redtoy_bank_receipt: notification_failed');
        alert('입금 확인은 저장됐지만 알림톡 전송 중 오류가 발생했습니다. 발송 내역을 확인해 주세요.', './orderform.php?od_id='.rawurlencode($od_id));
    }
    goto_url('./orderform.php?od_id='.rawurlencode($od_id));
    exit;
}

$check_keys = array(
'od_deposit_name',
'od_bank_account',
'od_receipt_time',
'od_receipt_price',
'od_receipt_point',
'od_refund_price',
'od_delivery_company',
'od_invoice',
'od_invoice_time',
'od_send_cost',
'od_send_cost2',
'od_tno',
'od_escrow',
'od_send_mail'
);

$posts = array();

foreach($check_keys as $key){
    $posts[$key] = isset($_POST[$key]) ? clean_xss_tags($_POST[$key], 1, 1) : '';
}

$od_send_mail = $posts['od_send_mail'];

$sql = " select * from {$g5['g5_shop_order_table']} where od_id = '$od_id' ";
$od  = sql_fetch($sql);
if(! (isset($od['od_id']) && $od['od_id']))
    alert('주문자료가 존재하지 않습니다.');
if ($od['od_pg'] === 'danal') {
    require_once G5_LIB_PATH.'/redtoy_danal_cancel.lib.php';
    try { redtoy_cancel_guard_order($od_id); }
    catch (Throwable $e) { alert('다날 취소 처리 중이거나 확인 필요 기록이 있습니다. 취소·환불 화면에서 확인해 주세요.'); }
    $od = sql_fetch(" select * from {$g5['g5_shop_order_table']} where od_id = '$od_id' ");
}
$redtoy_statistics_before_order = $od;

if ($posts['od_receipt_time']) {
    if (check_datetime($posts['od_receipt_time']) == false)
        alert('결제일시 오류입니다.');
}

// 결제정보 반영
$sql = " update {$g5['g5_shop_order_table']}
            set od_deposit_name    = '{$posts['od_deposit_name']}',
                od_bank_account    = '{$posts['od_bank_account']}',
                od_receipt_time    = '{$posts['od_receipt_time']}',
                od_receipt_price   = '{$posts['od_receipt_price']}',
                od_receipt_point   = '{$posts['od_receipt_point']}',
                od_refund_price    = '{$posts['od_refund_price']}',
                od_delivery_company= '{$posts['od_delivery_company']}',
                od_invoice         = '{$posts['od_invoice']}',
                od_invoice_time    = '{$posts['od_invoice_time']}',
                od_send_cost       = '{$posts['od_send_cost']}',
                od_send_cost2      = '{$posts['od_send_cost2']}'
            where od_id = '$od_id' ";
sql_query($sql);

if ($posts['od_receipt_time'] && $posts['od_receipt_price']) {
    $od = sql_fetch("SELECT * FROM {$g5['g5_shop_order_table']} where od_id = '$od_id' ");
//    insert_point($od['mb_id'], $od['od_cart_price'] * 0.05, "주문번호 $od_id 결제 (입금확인)", '@member', $od_id, '상품구입');
}

// 주문정보
$info = get_order_info($od_id);
if(!$info)
    alert('주문자료가 존재하지 않습니다.');

$od_status = $od['od_status'];
$cart_status = false;

// 미수가 0이고 상태가 주문이었다면 입금으로 변경
if($info['od_misu'] == 0 && $od['od_status'] == '주문')
{
    $od_status = '입금';
    $cart_status = true;
}

// 배송정보가 있으면 주문상태 배송으로 변경
$order_status = array('입금', '준비');
if($posts['od_delivery_company'] && $posts['od_invoice'] && in_array($od['od_status'], $order_status))
{
    $od_status = '배송';
    $cart_status = true;
}

// 미수금액
$od_misu = ( $od['od_cart_price'] - $od['od_cancel_price'] + (int) $posts['od_send_cost'] + (int) $posts['od_send_cost2'] )
           - ( $od['od_cart_coupon'] + $od['od_coupon'] + $od['od_send_coupon'] )
           - ( (int) $posts['od_receipt_price'] + (int) $posts['od_receipt_point'] - (int) $posts['od_refund_price'] );

// 미수금 정보 등 반영
$sql = " update {$g5['g5_shop_order_table']}
            set od_misu         = '$od_misu',
                od_tax_mny      = '{$info['od_tax_mny']}',
                od_vat_mny      = '{$info['od_vat_mny']}',
                od_free_mny     = '{$info['od_free_mny']}',
                od_status       = '$od_status'
            where od_id = '$od_id' ";
sql_query($sql);

$redtoy_statistics_after_order = sql_fetch(" select * from {$g5['g5_shop_order_table']} where od_id = '$od_id' ");
redtoy_statistics_record_cancel_event(
    $redtoy_statistics_before_order,
    $redtoy_statistics_after_order,
    '환불금액수정',
    'admin_orderformreceiptupdate',
    $member['mb_id']
);

// 장바구니 상태 변경
if($cart_status) {
    $sql = " update {$g5['g5_shop_cart_table']}
                set ct_status = '$od_status'
                where od_id = '$od_id' ";

    switch($od_status) {
        case '입금':
            $sql .= " and ct_status = '주문' ";
            break;
        case '배송':
            $sql .= " and ct_status IN ('".implode("', '", $order_status)."') ";
            break;
        default:
            ;
    }

    sql_query($sql);
}


// 배송때 재고반영
if($info['od_misu'] == 0 && $od_status == '배송') {
    $sql = " select * from {$g5['g5_shop_cart_table']} where od_id = '$od_id' ";
    $result = sql_query($sql);

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        // 재고를 사용하지 않았다면
        $stock_use = $row['ct_stock_use'];

        if(!$row['ct_stock_use'])
        {
            // 재고에서 뺀다.
            subtract_io_stock($row['it_id'], $row['ct_qty'], $row['io_id'], $row['io_type']);
            $stock_use = 1;

            $sql = " update {$g5['g5_shop_cart_table']} set ct_stock_use  = '$stock_use' where ct_id = '{$row['ct_id']}' ";
            sql_query($sql);
        }
    }

    unset($sql);
    unset($result);
    unset($row);
}


if (function_exists('redtoy_tg_order')) redtoy_tg_order($od_id);

// 메일발송
define("_ORDERMAIL_", true);
include "./ordermail.inc.php";


// SMS 문자전송
define("_ORDERSMS_", true);
include "./ordersms.inc.php";

// wetoz : 입금정보 알림톡전송
define("_ORDERALIMTALK_", true);
include "./orderalimtalk.inc.php";
include "./orderalimtalk2.inc.php";

// 에스크로 배송처리
if($posts['od_tno'] && $posts['od_escrow'] == 1)
{
    $escrow_tno  = $posts['od_tno'];
    $escrow_corp = $posts['od_delivery_company'];
    $escrow_numb = $posts['od_invoice'];

    include(G5_SHOP_PATH.'/'.$od['od_pg'].'/escrow.register.php');
}


$qstr = "sort1=$sort1&amp;sort2=$sort2&amp;sel_field=$sel_field&amp;search=$search&amp;page=$page";

goto_url("./orderform.php?od_id=$od_id&amp;$qstr");
