<?php
if (!defined('_GNUBOARD_')) exit;
require_once __DIR__.'/redtoy_danal_cancel.lib.php';

// Only the stored order and cart data determine the cumulative cash receipt.
function redtoy_bank_receipt_plan($order, $carts)
{
    if (!$order || $order['od_settle_case'] !== '무통장' || $order['od_status'] !== '주문'
        || redtoy_cancel_money($order['od_cancel_price']) !== 0
        || redtoy_cancel_money($order['od_refund_price']) !== 0)
        throw new RuntimeException('bank_not_eligible');
    $money = array();
    foreach (array('od_cart_price','od_send_cost','od_send_cost2','od_cart_coupon',
        'od_coupon','od_send_coupon','od_receipt_point','od_receipt_price','od_misu') as $field)
        $money[$field] = redtoy_cancel_money($order[$field]);
    $gross = 0;
    $coupons = 0;
    if (!$carts) throw new RuntimeException('bank_amount_mismatch');
    foreach ($carts as $cart) {
        if ($cart['ct_status'] !== '주문' || !preg_match('/^[01]$/D', (string)$cart['io_type'])
            || !preg_match('/^-?[0-9]+$/D', (string)$cart['io_price'])
            || abs((float)$cart['io_price']) > 2000000000)
            throw new RuntimeException('bank_amount_mismatch');
        $qty = redtoy_cancel_money($cart['ct_qty']);
        $unit = (int)$cart['io_type'] === 1 ? (int)$cart['io_price']
            : redtoy_cancel_money($cart['ct_price']) + (int)$cart['io_price'];
        if ($qty < 1 || $unit < 0 || $unit * $qty > 2000000000)
            throw new RuntimeException('bank_amount_mismatch');
        $gross += $unit * $qty;
        $coupons += redtoy_cancel_money($cart['cp_price']);
    }
    $total = $money['od_cart_price'] + $money['od_send_cost'] + $money['od_send_cost2']
        - $money['od_cart_coupon'] - $money['od_coupon'] - $money['od_send_coupon'] - $money['od_receipt_point'];
    if ($coupons > $gross || $money['od_coupon'] > $gross - $coupons
        || $money['od_send_coupon'] > $money['od_send_cost'] + $money['od_send_cost2']
        || $gross !== $money['od_cart_price'] || $coupons !== $money['od_cart_coupon']
        || $total <= $money['od_receipt_price'] || $total > 2000000000
        || $money['od_misu'] !== $total - $money['od_receipt_price'])
        throw new RuntimeException('bank_amount_mismatch');
    return $total;
}

function redtoy_bank_receipt_confirm($id)
{
    global $g5;
    if (!is_scalar($id) || !preg_match('/^[0-9]{1,32}$/D', (string)$id))
        throw new RuntimeException('bank_not_eligible');
    $id = redtoy_cancel_escape($id);
    $orders = redtoy_cancel_table('g5_shop_order_table');
    $carts = redtoy_cancel_table('g5_shop_cart_table');
    $held = array();
    $tables_locked = false;
    $changed = false;
    $order = null;
    try {
        // Same order lock used by cancellation/completion. No network calls while locked.
        $name = 'redtoy_complete_order_'.$id;
        $lock = redtoy_cancel_one("select GET_LOCK('$name',0) as ok");
        if (empty($lock['ok'])) throw new RuntimeException('busy');
        $held[] = $name;
        if (redtoy_cancel_pending($id)) throw new RuntimeException('reconciliation_required');
        // Works for both MyISAM and InnoDB; also serializes the legacy manual form's writes.
        redtoy_cancel_query("LOCK TABLES $orders WRITE, $carts WRITE");
        $tables_locked = true;
        $order = redtoy_cancel_one("select * from $orders where od_id='$id'");
        $items = redtoy_cancel_rows("select * from $carts where od_id='$id' order by ct_id");
        $total = redtoy_bank_receipt_plan($order, $items);
        $now = redtoy_cancel_escape(date('Y-m-d H:i:s')); // App bootstrap sets Asia/Seoul.
        $changed = true;
        redtoy_cancel_query("update $carts set ct_status='입금' where od_id='$id' and ct_status='주문'");
        if (mysqli_affected_rows($g5['connect_db']) !== count($items)) throw new RuntimeException('database_failure');
        redtoy_cancel_query("update $orders set od_receipt_price='$total', od_receipt_time='$now', od_misu=0, od_status='입금' where od_id='$id' and od_status='주문'");
        if (mysqli_affected_rows($g5['connect_db']) !== 1) throw new RuntimeException('database_failure');
    } catch (Throwable $e) {
        // Compensate statement failures while both tables are still exclusively locked.
        // A process/server crash can still require manual reconciliation on nontransactional tables.
        if ($changed && $tables_locked) {
            try {
                $price = redtoy_cancel_money($order['od_receipt_price']);
                $misu = redtoy_cancel_money($order['od_misu']);
                $time = redtoy_cancel_escape($order['od_receipt_time']);
                redtoy_cancel_query("update $orders set od_receipt_price='$price', od_receipt_time='$time', od_misu='$misu', od_status='주문' where od_id='$id'");
                redtoy_cancel_query("update $carts set ct_status='주문' where od_id='$id' and ct_status='입금'");
            } catch (Throwable $restore_error) {
                error_log('redtoy_bank_receipt: manual_reconciliation_required');
            }
        }
        throw $e;
    } finally {
        try { if ($tables_locked) redtoy_cancel_query('UNLOCK TABLES'); }
        finally { redtoy_cancel_unlock($held); }
    }
}
