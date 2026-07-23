<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_statistics_cancel_event_table()
{
    return G5_TABLE_PREFIX.'redtoy_order_cancel_event';
}

function redtoy_statistics_cancel_event_table_exists()
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $table = redtoy_statistics_cancel_event_table();
    $result = sql_query(" select 1 from {$table} limit 1 ", false);
    $exists = ($result !== false);

    return $exists;
}

/**
 * 취소 처리 전후의 주문 금액 차이를 기록한다.
 * 결제/환불 성공 여부를 바꾸지 않도록 기록 실패는 false로만 반환한다.
 */
function redtoy_statistics_record_cancel_event($before, $after, $cancel_type, $source, $actor_id='')
{
    if (empty($before['od_id']) || empty($after['od_id']) || $before['od_id'] !== $after['od_id']) {
        return false;
    }

    $cancel_amount = max(0, (int)$after['od_cancel_price'] - (int)$before['od_cancel_price']);
    $refund_delta = max(0, (int)$after['od_refund_price'] - (int)$before['od_refund_price']);
    $receipt_reversal = max(0, (int)$before['od_receipt_price'] - (int)$after['od_receipt_price']);
    $refund_amount = max($refund_delta, $receipt_reversal);

    if ($cancel_amount === 0 && $refund_amount === 0) {
        return true;
    }

    if (!redtoy_statistics_cancel_event_table_exists()) {
        error_log('redtoy statistics: cancel event table is missing');
        return false;
    }

    $table = redtoy_statistics_cancel_event_table();
    $od_id = sql_real_escape_string($after['od_id']);
    $mb_id = sql_real_escape_string(isset($after['mb_id']) ? $after['mb_id'] : '');
    $cancel_type = sql_real_escape_string($cancel_type);
    $source = sql_real_escape_string($source);
    $status_before = sql_real_escape_string(isset($before['od_status']) ? $before['od_status'] : '');
    $status_after = sql_real_escape_string(isset($after['od_status']) ? $after['od_status'] : '');
    $actor_id = sql_real_escape_string($actor_id);

    $sql = " insert into {$table}
                set od_id = '{$od_id}',
                    mb_id = '{$mb_id}',
                    cancel_type = '{$cancel_type}',
                    cancel_amount = '{$cancel_amount}',
                    refund_amount = '{$refund_amount}',
                    order_status_before = '{$status_before}',
                    order_status_after = '{$status_after}',
                    source = '{$source}',
                    admin_id = '{$actor_id}',
                    created_at = '".G5_TIME_YMDHIS."' ";

    $result = sql_query($sql, false);
    if ($result === false) {
        error_log('redtoy statistics: cancel event insert failed');
        return false;
    }

    return true;
}

function redtoy_statistics_empty_row($date)
{
    return array(
        'date' => $date,
        'visits' => 0,
        'joins' => 0,
        'first_count' => 0,
        'repeat_count' => 0,
        'guest_count' => 0,
        'payment_count' => 0,
        'first_amount' => 0,
        'repeat_amount' => 0,
        'guest_amount' => 0,
        'payment_amount' => 0,
        'coupon_amount' => 0,
        'point_amount' => 0,
        'cancel_count' => 0,
        'cancel_amount' => 0,
        'leaves' => 0,
    );
}

function redtoy_statistics_daily($from_date, $to_date)
{
    global $g5;

    $rows = array();
    $cursor = new DateTime($from_date, new DateTimeZone('Asia/Seoul'));
    $last = new DateTime($to_date, new DateTimeZone('Asia/Seoul'));
    while ($cursor <= $last) {
        $date = $cursor->format('Y-m-d');
        $rows[$date] = redtoy_statistics_empty_row($date);
        $cursor->modify('+1 day');
    }

    $from_sql = sql_real_escape_string($from_date);
    $to_sql = sql_real_escape_string($to_date);
    $from_time = $from_sql.' 00:00:00';
    $to_exclusive = date('Y-m-d 00:00:00', strtotime($to_date.' +1 day'));
    $to_exclusive_sql = sql_real_escape_string($to_exclusive);

    $result = sql_query(" select vs_date, vs_count
                            from {$g5['visit_sum_table']}
                           where vs_date between '{$from_sql}' and '{$to_sql}' ");
    while ($row = sql_fetch_array($result)) {
        if (isset($rows[$row['vs_date']])) {
            $rows[$row['vs_date']]['visits'] = (int)$row['vs_count'];
        }
    }

    $result = sql_query(" select substring(mb_datetime, 1, 10) as stat_date, count(*) as cnt
                            from {$g5['member_table']}
                           where mb_datetime >= '{$from_time}'
                             and mb_datetime < '{$to_exclusive_sql}'
                           group by substring(mb_datetime, 1, 10) ");
    while ($row = sql_fetch_array($result)) {
        if (isset($rows[$row['stat_date']])) {
            $rows[$row['stat_date']]['joins'] = (int)$row['cnt'];
        }
    }

    $from_leave = str_replace('-', '', $from_date);
    $to_leave = str_replace('-', '', $to_date);
    $result = sql_query(" select mb_leave_date, count(*) as cnt
                            from {$g5['member_table']}
                           where mb_leave_date between '{$from_leave}' and '{$to_leave}'
                           group by mb_leave_date ");
    while ($row = sql_fetch_array($result)) {
        $date = preg_replace('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', '$1-$2-$3', $row['mb_leave_date']);
        if (isset($rows[$date])) {
            $rows[$date]['leaves'] = (int)$row['cnt'];
        }
    }

    // 결제일 기준 유효 주문: 운영 주문, 정상 상태, 순 실결제금액이 남아 있는 주문.
    $valid_status = "'입금', '준비', '배송', '완료'";
    $sql = " select od.od_id,
                    od.mb_id,
                    substring(od.od_receipt_time, 1, 10) as stat_date,
                    greatest(0, od.od_receipt_price - od.od_refund_price) as payment_amount,
                    (od.od_cart_coupon + od.od_coupon + od.od_send_coupon) as coupon_amount,
                    od.od_receipt_point as point_amount,
                    case
                        when coalesce(od.mb_id, '') = '' then 'guest'
                        when exists (
                            select 1
                              from {$g5['g5_shop_order_table']} previous_order
                             where previous_order.mb_id = od.mb_id
                               and previous_order.mb_id <> ''
                               and previous_order.od_test = '0'
                               and previous_order.od_status in ({$valid_status})
                               and (
                                   previous_order.od_receipt_price > previous_order.od_refund_price
                                   or (
                                       previous_order.od_receipt_price = 0
                                       and previous_order.od_refund_price = 0
                                       and previous_order.od_receipt_point > 0
                                   )
                               )
                               and previous_order.od_receipt_time <> ''
                               and previous_order.od_receipt_time <> '0000-00-00 00:00:00'
                               and (
                                   previous_order.od_receipt_time < od.od_receipt_time
                                   or (previous_order.od_receipt_time = od.od_receipt_time and previous_order.od_id < od.od_id)
                               )
                             limit 1
                        ) then 'repeat'
                        else 'first'
                    end as customer_type
               from {$g5['g5_shop_order_table']} od
              where od.od_receipt_time >= '{$from_time}'
                and od.od_receipt_time < '{$to_exclusive_sql}'
                and od.od_receipt_time <> '0000-00-00 00:00:00'
                and od.od_test = '0'
                and od.od_status in ({$valid_status})
                and (
                    od.od_receipt_price > od.od_refund_price
                    or (
                        od.od_receipt_price = 0
                        and od.od_refund_price = 0
                        and od.od_receipt_point > 0
                    )
                )
              order by od.od_receipt_time, od.od_id ";
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $date = $row['stat_date'];
        if (!isset($rows[$date])) {
            continue;
        }

        $type = $row['customer_type'];
        $amount = (int)$row['payment_amount'];
        $rows[$date][$type.'_count']++;
        $rows[$date][$type.'_amount'] += $amount;
        $rows[$date]['payment_count']++;
        $rows[$date]['payment_amount'] += $amount;
        $rows[$date]['coupon_amount'] += (int)$row['coupon_amount'];
        $rows[$date]['point_amount'] += (int)$row['point_amount'];
    }

    $event_table_ready = redtoy_statistics_cancel_event_table_exists();
    if ($event_table_ready) {
        $event_table = redtoy_statistics_cancel_event_table();
        $result = sql_query(" select substring(created_at, 1, 10) as stat_date,
                                    count(*) as cancel_count,
                                    sum(case when refund_amount > 0 then refund_amount else cancel_amount end) as cancel_amount
                               from {$event_table}
                              where created_at >= '{$from_time}'
                                and created_at < '{$to_exclusive_sql}'
                              group by substring(created_at, 1, 10) ");
        while ($row = sql_fetch_array($result)) {
            if (isset($rows[$row['stat_date']])) {
                $rows[$row['stat_date']]['cancel_count'] = (int)$row['cancel_count'];
                $rows[$row['stat_date']]['cancel_amount'] = (int)$row['cancel_amount'];
            }
        }
    }

    return array(
        'rows' => $rows,
        'cancel_event_table_ready' => $event_table_ready,
    );
}
