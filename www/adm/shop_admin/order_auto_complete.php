<?php
$sub_menu = '400400';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only'.PHP_EOL);
}

function redtoy_order_auto_complete_usage()
{
    return 'Usage: php order_auto_complete.php --from=YYYY-MM-DDTHH:MM:SS [--days=N] [--limit=N] [--dry-run]'.PHP_EOL;
}

$from_input = '';
$days = 7;
$limit = 100;
$dry_run = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dry_run = true;
    } else if ($arg === '--help') {
        fwrite(STDOUT, redtoy_order_auto_complete_usage());
        exit(0);
    } else if (preg_match('/^--from=(.+)$/', $arg, $match)) {
        $from_input = trim($match[1]);
    } else if (preg_match('/^--days=([1-9][0-9]*)$/', $arg, $match)) {
        $days = (int)$match[1];
    } else if (preg_match('/^--limit=([1-9][0-9]*)$/', $arg, $match)) {
        $limit = (int)$match[1];
    } else {
        fwrite(STDERR, redtoy_order_auto_complete_usage());
        exit(2);
    }
}

if ($from_input === '' || $limit > 1000) {
    fwrite(STDERR, redtoy_order_auto_complete_usage());
    fwrite(STDERR, '--from is required and --limit must be between 1 and 1000.'.PHP_EOL);
    exit(2);
}

$from_time = str_replace('T', ' ', $from_input);
$from_date = DateTime::createFromFormat('!Y-m-d H:i:s', $from_time);
$date_errors = DateTime::getLastErrors();
if (!$from_date
    || ($date_errors !== false && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0))
    || $from_date->format('Y-m-d H:i:s') !== $from_time) {
    fwrite(STDERR, redtoy_order_auto_complete_usage());
    fwrite(STDERR, 'Invalid --from value.'.PHP_EOL);
    exit(2);
}

chdir(__DIR__);
@set_time_limit(0);

$cron_host = getenv('REDTOY_CRON_HOST');
if (!$cron_host) {
    $cron_host = 'www.redtoy.co.kr';
}

$_SERVER['HTTP_HOST'] = $cron_host;
$_SERVER['SERVER_NAME'] = $cron_host;
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTPS'] = 'on';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['SCRIPT_NAME'] = '/adm/shop_admin/order_auto_complete.php';
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];

define('G5_IS_ADMIN', true);
define('G5_IS_SHOP_ADMIN_PAGE', true);
include_once(__DIR__.'/../../common.php');

if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) {
    fwrite(STDERR, '쇼핑몰 설치 후 이용해 주십시오.'.PHP_EOL);
    exit(1);
}

include_once(__DIR__.'/admin.shop.lib.php');

$lock_file = G5_DATA_PATH.'/order_auto_complete.lock';
$lock_fp = @fopen($lock_file, 'c');
if (!$lock_fp) {
    fwrite(STDERR, 'order_auto_complete: lock file open failed'.PHP_EOL);
    exit(1);
}

if (!flock($lock_fp, LOCK_EX | LOCK_NB)) {
    fclose($lock_fp);
    fwrite(STDERR, 'order_auto_complete: already running'.PHP_EOL);
    exit(1);
}

$from_sql = sql_real_escape_string($from_time);
$base_time = date('Y-m-d H:i:s', G5_SERVER_TIME - (86400 * $days));
$base_sql = sql_real_escape_string($base_time);
$targets = 0;
$completed = 0;
$skipped = 0;
$skipped_lock = 0;
$failed = 0;

// 주문의 전체 cart가 배송 상태인 경우만 대상으로 삼는다.
$sql = " select od.od_id
            from {$g5['g5_shop_order_table']} od
           where od.od_status = '배송'
             and od.od_time >= '{$from_sql}'
             and od.od_invoice_time <> ''
             and od.od_invoice_time <> '0000-00-00 00:00:00'
             and od.od_invoice_time <= '{$base_sql}'
             and exists (
                 select 1
                   from {$g5['g5_shop_cart_table']} shipping_cart
                  where shipping_cart.od_id = od.od_id
                    and shipping_cart.ct_status = '배송'
             )
             and not exists (
                 select 1
                   from {$g5['g5_shop_cart_table']} other_cart
                  where other_cart.od_id = od.od_id
                    and (other_cart.ct_status <> '배송' or other_cart.ct_status is null)
             )
           order by od.od_invoice_time asc, od.od_id asc
           limit {$limit} ";
$result = sql_query($sql);

while ($row = sql_fetch_array($result)) {
    $targets++;

    if ($dry_run) {
        continue;
    }

    $od_id = sql_real_escape_string($row['od_id']);
    $order_lock_name = 'redtoy_complete_order_'.$od_id;
    $order_lock = sql_fetch(" select GET_LOCK('{$order_lock_name}', 5) as locked ", false);
    if (!isset($order_lock['locked']) || (int)$order_lock['locked'] !== 1) {
        $skipped_lock++;
        continue;
    }

    // 후보 조회 후 주문이나 cart 상태가 바뀐 경우를 제외한다.
    $order = sql_fetch(" select od_id
                           from {$g5['g5_shop_order_table']}
                          where od_id = '{$od_id}'
                            and od_status = '배송'
                            and od_time >= '{$from_sql}'
                            and od_invoice_time <> ''
                            and od_invoice_time <> '0000-00-00 00:00:00'
                            and od_invoice_time <= '{$base_sql}' ");

    $cart_count = sql_fetch(" select count(*) as total_count,
                                    sum(case when ct_status = '배송' then 1 else 0 end) as shipping_count
                               from {$g5['g5_shop_cart_table']}
                              where od_id = '{$od_id}' ");

    if (empty($order['od_id'])
        || empty($cart_count['total_count'])
        || (int)$cart_count['total_count'] !== (int)$cart_count['shipping_count']) {
        $skipped++;
        sql_query(" select RELEASE_LOCK('{$order_lock_name}') ", false);
        continue;
    }

    // change_status()는 주문과 배송 상태 cart를 함께 완료로 변경한다.
    change_status($od_id, '배송', '완료');

    $changed = sql_fetch(" select od.od_status,
                                  sum(case when ct.ct_status <> '완료' then 1 else 0 end) as incomplete_count
                             from {$g5['g5_shop_order_table']} od
                             join {$g5['g5_shop_cart_table']} ct
                               on ct.od_id = od.od_id
                            where od.od_id = '{$od_id}'
                            group by od.od_status ");

    if (isset($changed['od_status'])
        && $changed['od_status'] === '완료'
        && (int)$changed['incomplete_count'] === 0) {
        // 기존 관리자 배송완료 처리와 동일하게 상품 판매수량을 재집계한다.
        $item_result = sql_query(" select distinct it_id
                                     from {$g5['g5_shop_cart_table']}
                                    where od_id = '{$od_id}' ");

        while ($item = sql_fetch_array($item_result)) {
            $it_id = sql_real_escape_string($item['it_id']);
            $sum = sql_fetch(" select sum(ct_qty) as sum_qty
                                 from {$g5['g5_shop_cart_table']}
                                where it_id = '{$it_id}'
                                  and ct_status = '완료' ");

            sql_query(" update {$g5['g5_shop_item_table']}
                           set it_sum_qty = '{$sum['sum_qty']}'
                         where it_id = '{$it_id}' ");
        }

        $completed++;
    } else {
        $failed++;
    }

    sql_query(" select RELEASE_LOCK('{$order_lock_name}') ", false);
}

$message = array();
$message[] = 'order_auto_complete';
$message[] = 'from='.$from_time;
$message[] = 'base_time='.$base_time;
$message[] = 'days='.$days;
$message[] = 'limit='.$limit;
$message[] = 'dry_run='.($dry_run ? 'Y' : 'N');
$message[] = 'targets='.$targets;
$message[] = 'completed='.$completed;
$message[] = 'skipped='.$skipped;
$message[] = 'skipped_lock='.$skipped_lock;
$message[] = 'failed='.$failed;

$output = implode(PHP_EOL, $message).PHP_EOL;
$log_file = G5_DATA_PATH.'/order_auto_complete.log';
$log_result = @file_put_contents(
    $log_file,
    '['.date('Y-m-d H:i:s').']'.PHP_EOL.$output.PHP_EOL,
    FILE_APPEND | LOCK_EX
);

flock($lock_fp, LOCK_UN);
fclose($lock_fp);

fwrite(STDOUT, $output);

if ($log_result === false) {
    fwrite(STDERR, 'order_auto_complete: log write failed'.PHP_EOL);
    exit(1);
}

if ($failed > 0 || $skipped_lock > 0) {
    exit(1);
}
