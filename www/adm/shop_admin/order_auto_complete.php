<?php
$sub_menu = '400400';
chdir(__DIR__);
$is_cli = (PHP_SAPI === 'cli');

if ($is_cli) {
    $_SERVER['HTTP_HOST'] = 'www.redtoy.co.kr';
    $_SERVER['SERVER_NAME'] = 'www.redtoy.co.kr';
    $_SERVER['SERVER_PORT'] = 443;
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;
    $_SERVER['SCRIPT_NAME'] = '/adm/shop_admin/order_auto_complete.php';
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
}

define('G5_IS_ADMIN', true);
define('G5_IS_SHOP_ADMIN_PAGE', true);
include_once(__DIR__.'/../../common.php');

if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) {
    exit('쇼핑몰 설치 후 이용해 주십시오.'.PHP_EOL);
}

include_once(__DIR__.'/admin.shop.lib.php');

$secret = '';

if (!$is_cli) {
    $run_key = isset($_GET['key']) ? preg_replace('/[^a-zA-Z0-9_\\-]/', '', $_GET['key']) : '';
    if (!$secret || $run_key !== $secret) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$days = 7;
$dry_run = false;

if ($is_cli && isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if ($arg === '--dry-run') {
            $dry_run = true;
        } else if (preg_match('/^--days=([0-9]+)$/', $arg, $match)) {
            $days = max(1, (int)$match[1]);
        }
    }
} else {
    if (isset($_GET['dry_run']) && $_GET['dry_run']) {
        $dry_run = true;
    }
    if (isset($_GET['days'])) {
        $days = max(1, (int)$_GET['days']);
    }
}

$base_time = date('Y-m-d H:i:s', G5_SERVER_TIME - (86400 * $days));
$completed = 0;
$targets = array();

$sql = " select od_id
            from {$g5['g5_shop_order_table']}
           where od_status = '배송'
             and od_invoice_time <> ''
             and od_invoice_time <> '0000-00-00 00:00:00'
             and od_invoice_time <= '{$base_time}'
           order by od_invoice_time asc ";
$result = sql_query($sql);

while ($row = sql_fetch_array($result)) {
    $od_id = $row['od_id'];
    $targets[] = $od_id;

    if ($dry_run) {
        continue;
    }

    change_status($od_id, '배송', '완료');

    $sql2 = " select it_id
                from {$g5['g5_shop_cart_table']}
               where od_id = '{$od_id}'
                 and ct_status = '완료'
               group by it_id ";
    $result2 = sql_query($sql2);

    for ($i=0; $row2=sql_fetch_array($result2); $i++) {
        $sql3 = " select sum(ct_qty) as sum_qty
                    from {$g5['g5_shop_cart_table']}
                   where it_id = '{$row2['it_id']}'
                     and ct_status = '완료' ";
        $row3 = sql_fetch($sql3);

        $sql4 = " update {$g5['g5_shop_item_table']}
                     set it_sum_qty = '{$row3['sum_qty']}'
                   where it_id = '{$row2['it_id']}' ";
        sql_query($sql4);
    }

    $completed++;
}

$message = array();
$message[] = 'order_auto_complete';
$message[] = 'base_time='.$base_time;
$message[] = 'days='.$days;
$message[] = 'dry_run='.($dry_run ? 'Y' : 'N');
$message[] = 'targets='.count($targets);
$message[] = 'completed='.$completed;

if ($targets) {
    $message[] = 'od_id='.implode(',', $targets);
}

$output = implode(PHP_EOL, $message).PHP_EOL;
$log_file = dirname(G5_PATH).'/order_auto_complete.log';
@file_put_contents($log_file, '['.date('Y-m-d H:i:s').']'.PHP_EOL.$output.PHP_EOL, FILE_APPEND);

if ($is_cli && defined('STDOUT')) {
    fwrite(STDOUT, $output);
} else {
    echo $output;
}
