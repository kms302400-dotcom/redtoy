<?php
if (!defined('_GNUBOARD_')) exit;
require_once __DIR__.'/redtoy_danal_cancel.lib.php';

// Pure eligibility check shared by the read-only preview and the actual runner.
function redtoy_completion_evaluate($order, $carts, $now, $days = 7)
{
    if ($order['od_status'] !== '배송') return array('reason'=>'not_shipping');
    $normal = 0; $last = '';
    foreach ($carts as $cart) {
        if (in_array($cart['ct_status'], array('취소','반품','품절'), true)) continue;
        $normal++;
        if (!in_array($cart['ct_status'], array('배송','완료'), true)) return array('reason'=>'unshipped_item');
        $date = isset($cart['ct_invoice_time']) ? $cart['ct_invoice_time'] : '';
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string)$date, new DateTimeZone('Asia/Seoul'));
        $errors = DateTimeImmutable::getLastErrors();
        if (!$parsed || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))
            || $parsed->format('Y-m-d H:i:s') !== $date || substr($date, 0, 4) < '1000') {
            return array('reason'=>'invalid_item_shipping_time');
        }
        if ($date > $now) return array('reason'=>'future_item_shipping_time');
        if ($date > $last) $last = $date;
    }
    if (!$normal) return array('reason'=>'no_normal_items');
    $cutoff = (new DateTimeImmutable($now, new DateTimeZone('Asia/Seoul')))->modify('-'.(int)$days.' days')->format('Y-m-d H:i:s');
    return array('reason'=>$last <= $cutoff ? 'eligible' : 'waiting_7_days', 'last_shipping_time'=>$last);
}

function redtoy_completion_table($key)
{
    global $g5;
    if (empty($g5[$key]) || !preg_match('/^[a-zA-Z0-9_]+$/D', $g5[$key])) throw new RuntimeException('table_mapping');
    return '`'.$g5[$key].'`';
}

function redtoy_completion_query($sql)
{
    global $redtoy_completion_db;
    $result = $redtoy_completion_db->query($sql);
    if ($result === false) throw new RuntimeException('storage_failed');
    return $result;
}

function redtoy_completion_escape($value)
{
    global $redtoy_completion_db;
    return $redtoy_completion_db->real_escape_string((string)$value);
}

function redtoy_completion_order($id)
{
    $id = redtoy_completion_escape($id);
    return redtoy_completion_query('select od_id,mb_id,od_status,od_time,od_mod_history from '.redtoy_completion_table('g5_shop_order_table')." where od_id='$id'")->fetch_assoc();
}

function redtoy_completion_carts($id)
{
    $id = redtoy_completion_escape($id);
    return redtoy_completion_query('select ct_id,od_id,it_id,ct_status,ct_invoice_time,ct_point,ct_qty,ct_point_use from '.redtoy_completion_table('g5_shop_cart_table')." where od_id='$id' order by ct_id")->fetch_all(MYSQLI_ASSOC);
}

// Match both historical relations; ct_point_use alone is not reliable evidence.
function redtoy_completion_point_plan($order, $carts)
{
    $plan = array('reason'=>'no_points', 'points'=>0, 'rows'=>array());
    global $redtoy_completion_db;
    if (redtoy_cancel_hold($order['od_id'],$redtoy_completion_db)) { $plan['reason']='cancel_pending'; return $plan; }
    $mb = redtoy_completion_escape($order['mb_id']);
    $id = redtoy_completion_escape($order['od_id']);
    $member = $mb === '' ? null : redtoy_completion_query('select mb_id,mb_leave_date from '.redtoy_completion_table('member_table')." where mb_id='$mb'")->fetch_assoc();
    if (!$member) {
        $plan['reason']='no_member'; return $plan;
    }
    if (trim((string)$member['mb_leave_date']) !== '') {
        $plan['reason']='withdrawn_member'; return $plan;
    }
    $points = redtoy_completion_table('point_table');
    if (redtoy_completion_query("select po_id from $points where mb_id='$mb' and po_rel_table='@member' and po_rel_id='$id' and po_rel_action='상품구입' limit 1")->fetch_assoc()) {
        $plan['reason']='already_paid_at_checkout'; return $plan;
    }
    foreach ($carts as $cart) {
        if (!in_array($cart['ct_status'], array('배송','완료'), true) || $cart['ct_point'] <= 0 || $cart['ct_qty'] <= 0) continue;
        $relation = redtoy_completion_escape($order['od_id'].','.$cart['ct_id']);
        if (redtoy_completion_query("select po_id from $points where mb_id='$mb' and po_rel_table='@delivery' and po_rel_id='$mb' and po_rel_action='$relation' limit 1")->fetch_assoc()) continue;
        $plan['rows'][]=$cart;
        $plan['points'] += (int)$cart['ct_point'] * (int)$cart['ct_qty'];
    }
    if ($plan['rows']) $plan['reason']='payable';
    else $plan['reason']='no_unpaid_items';
    return $plan;
}

function redtoy_completion_lock($name)
{
    $name=redtoy_completion_escape($name);
    $row=redtoy_completion_query("select GET_LOCK('$name', 0) as acquired")->fetch_assoc();
    return !empty($row['acquired']);
}

function redtoy_completion_unlock($name)
{
    $name=redtoy_completion_escape($name);
    redtoy_completion_query("select RELEASE_LOCK('$name')");
}

function redtoy_completion_marker()
{
    return '[redtoy-auto-complete:v2]';
}

// The marker is written with the order transition, allowing recovery after a crash.
function redtoy_completion_apply($id, $now, $days)
{
    $lock='redtoy_complete_order_'.$id;
    if (!redtoy_completion_lock($lock)) return 'locked';
    try {
        global $redtoy_completion_db;
        if (redtoy_cancel_hold($id,$redtoy_completion_db)) return 'changed';
        $order=redtoy_completion_order($id);
        if (!$order) return 'changed';
        $carts=redtoy_completion_carts($id);
        if (redtoy_completion_evaluate($order, $carts, $now, $days)['reason'] !== 'eligible') return 'changed';
        $oid=redtoy_completion_escape($id);
        $cart_table=redtoy_completion_table('g5_shop_cart_table');
        $order_table=redtoy_completion_table('g5_shop_order_table');
        // Preserve cancelled/returned/out-of-stock and already completed lines.
        redtoy_completion_query("update $cart_table set ct_status='완료' where od_id='$oid' and ct_status='배송'");
        $fresh=redtoy_completion_carts($id);
        foreach ($fresh as $cart) {
            if (!in_array($cart['ct_status'], array('완료','취소','반품','품절'), true)) throw new RuntimeException('cart_changed');
        }
        if (redtoy_completion_evaluate($order, $fresh, $now, $days)['reason'] !== 'eligible') throw new RuntimeException('shipping_time_changed');
        $marker=redtoy_completion_escape("\n".redtoy_completion_marker().' '.$now);
        redtoy_completion_query("update $order_table set od_status='완료', od_mod_history=concat(coalesce(od_mod_history,''),'$marker') where od_id='$oid' and od_status='배송'");
        $changed=redtoy_completion_order($id);
        if (!$changed || $changed['od_status'] !== '완료' || strpos((string)$changed['od_mod_history'],redtoy_completion_marker()) === false) throw new RuntimeException('transition_failed');
        // Retain the existing product sales-count calculation.
        $items=array();
        foreach ($fresh as $cart) $items[$cart['it_id']]=true;
        foreach (array_keys($items) as $item) {
            $item=redtoy_completion_escape($item);
            $sum=redtoy_completion_query("select coalesce(sum(ct_qty),0) as qty from $cart_table where it_id='$item' and ct_status='완료'")->fetch_assoc();
            redtoy_completion_query('update '.redtoy_completion_table('g5_shop_item_table')." set it_sum_qty=".(int)$sum['qty']." where it_id='$item'");
        }
        return 'completed';
    } finally { redtoy_completion_unlock($lock); }
}

function redtoy_completion_pay($id)
{
    global $config;
    if (empty($config['cf_use_point'])) return array('reason'=>'points_disabled','points'=>0,'items'=>0);
    // Same mutex used by save_order_point and the existing point batch.
    if (!redtoy_completion_lock('redtoy_save_order_point')) return array('reason'=>'locked','points'=>0,'items'=>0);
    try {
        $order=redtoy_completion_order($id);
        if (!$order || $order['od_status'] !== '완료' || strpos((string)$order['od_mod_history'],redtoy_completion_marker()) === false) return array('reason'=>'not_auto_completed','points'=>0,'items'=>0);
        $carts=redtoy_completion_carts($id);
        foreach ($carts as $cart) {
            if (!in_array($cart['ct_status'],array('완료','취소','반품','품절'),true)) return array('reason'=>'cart_changed','points'=>0,'items'=>0);
        }
        $plan=redtoy_completion_point_plan($order,$carts);
        $paid=0; $items=0;
        foreach ($plan['rows'] as $cart) {
            $amount=(int)$cart['ct_point']*(int)$cart['ct_qty'];
            $relation=$order['od_id'].','.$cart['ct_id'];
            $result=insert_point($order['mb_id'],$amount,'주문번호 '.$order['od_id'].' ('.$cart['ct_id'].') 배송완료','@delivery',$order['mb_id'],$relation);
            if ($result !== 1 && $result !== -1) throw new RuntimeException('point_insert_failed');
            $mb=redtoy_completion_escape($order['mb_id']); $rel=redtoy_completion_escape($relation);
            $saved=redtoy_completion_query('select po_point from '.redtoy_completion_table('point_table')." where mb_id='$mb' and po_rel_table='@delivery' and po_rel_id='$mb' and po_rel_action='$rel'")->fetch_assoc();
            if (!$saved || (int)$saved['po_point'] !== $amount) throw new RuntimeException('point_verification_failed');
            redtoy_completion_query('update '.redtoy_completion_table('g5_shop_cart_table').' set ct_point_use=1 where ct_id='.(int)$cart['ct_id']);
            if ($result === 1) { $paid+=$amount; $items++; }
        }
        return array('reason'=>$plan['reason'],'points'=>$paid,'items'=>$items);
    } finally { redtoy_completion_unlock('redtoy_save_order_point'); }
}

function redtoy_completion_recovery_ids($limit)
{
    $o=redtoy_completion_table('g5_shop_order_table'); $c=redtoy_completion_table('g5_shop_cart_table');
    $m=redtoy_completion_table('member_table'); $p=redtoy_completion_table('point_table');
    global $redtoy_completion_db;
    $cancelTable=redtoy_cancel_hold_table($redtoy_completion_db);
    $cancelWhere=$cancelTable ? " and not exists(select 1 from $cancelTable rc where rc.od_id=od.od_id and rc.state<>'done')" : '';
    $marker=redtoy_completion_escape(redtoy_completion_marker());
    $rows=redtoy_completion_query("select od.od_id from $o od join $m mb on mb.mb_id=od.mb_id
        where trim(coalesce(mb.mb_leave_date,''))='' $cancelWhere
        and od.od_status='완료' and locate('$marker',coalesce(od.od_mod_history,''))>0
        and not exists(select 1 from $p po where po.mb_id=od.mb_id and po.po_rel_table='@member' and po.po_rel_id=od.od_id and po.po_rel_action='상품구입')
        and exists(select 1 from $c ct where ct.od_id=od.od_id and ct.ct_status='완료' and ct.ct_point>0 and ct.ct_qty>0
          and not exists(select 1 from $p po where po.mb_id=od.mb_id and po.po_rel_table='@delivery' and po.po_rel_id=od.mb_id and po.po_rel_action=concat(od.od_id,',',ct.ct_id)))
        order by od.od_id limit ".(int)$limit);
    return array_column($rows->fetch_all(MYSQLI_ASSOC),'od_id');
}
