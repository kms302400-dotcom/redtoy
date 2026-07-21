<?php
$sub_menu = '400400';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only'.PHP_EOL);
}

function redtoy_order_complete_point_usage()
{
    return 'Usage: php order_complete_point.php --from=YYYY-MM-DD[THH:MM:SS] [--dry-run] [--limit=N]'.PHP_EOL;
}

$from_input = '';
$limit = 100;
$dry_run = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dry_run = true;
    } else if ($arg === '--help') {
        fwrite(STDOUT, redtoy_order_complete_point_usage());
        exit(0);
    } else if (preg_match('/^--from=(.+)$/', $arg, $match)) {
        $from_input = trim($match[1]);
    } else if (preg_match('/^--limit=([1-9][0-9]*)$/', $arg, $match)) {
        $limit = (int)$match[1];
    } else {
        fwrite(STDERR, redtoy_order_complete_point_usage());
        exit(2);
    }
}

if ($from_input === '' || $limit > 1000) {
    fwrite(STDERR, redtoy_order_complete_point_usage());
    fwrite(STDERR, '--from is required and --limit must be between 1 and 1000.'.PHP_EOL);
    exit(2);
}

$from_time = str_replace('T', ' ', $from_input);
if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $from_time)) {
    $from_time .= ' 00:00:00';
}

$from_date = DateTime::createFromFormat('!Y-m-d H:i:s', $from_time);
$date_errors = DateTime::getLastErrors();
if (!$from_date
    || ($date_errors !== false && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0))
    || $from_date->format('Y-m-d H:i:s') !== $from_time) {
    fwrite(STDERR, redtoy_order_complete_point_usage());
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
$_SERVER['SCRIPT_NAME'] = '/adm/shop_admin/order_complete_point.php';
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];

define('G5_IS_ADMIN', true);
define('G5_IS_SHOP_ADMIN_PAGE', true);
include_once(__DIR__.'/../../common.php');

if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) {
    fwrite(STDERR, '쇼핑몰 설치 후 이용해 주십시오.'.PHP_EOL);
    exit(1);
}

$lock_file = G5_DATA_PATH.'/order_complete_point.lock';
$lock_fp = @fopen($lock_file, 'c');
if (!$lock_fp) {
    fwrite(STDERR, 'order_complete_point: lock file open failed'.PHP_EOL);
    exit(1);
}

if (!flock($lock_fp, LOCK_EX | LOCK_NB)) {
    fclose($lock_fp);
    fwrite(STDERR, 'order_complete_point: already running'.PHP_EOL);
    exit(1);
}

$from_sql = sql_real_escape_string($from_time);
$candidates = 0;
$inserted = 0;
$skipped_history = 0;
$skipped_legacy = 0;
$skipped_lock = 0;
$failed = 0;
$point_disabled = empty($config['cf_use_point']);

if (!$point_disabled) {
    // ct_point_use는 후보 선정에 사용하지 않고 @delivery relation 이력을 기준으로 한다.
    $sql = " select od.od_id,
                    od.mb_id,
                    ct.ct_id,
                    ct.ct_point,
                    ct.ct_qty
               from {$g5['g5_shop_order_table']} od
               join {$g5['g5_shop_cart_table']} ct
                 on ct.od_id = od.od_id
               join {$g5['member_table']} mb
                 on mb.mb_id = od.mb_id
              where od.od_status = '완료'
                and ct.ct_status = '완료'
                and od.od_time >= '{$from_sql}'
                and ct.ct_point > 0
                and ct.ct_qty > 0
                and not exists (
                    select 1
                      from {$g5['point_table']} delivery
                     where delivery.mb_id = od.mb_id
                       and delivery.po_rel_table = '@delivery'
                       and delivery.po_rel_id = od.mb_id
                       and delivery.po_rel_action = concat(od.od_id, ',', ct.ct_id)
                )
                and not exists (
                    select 1
                      from {$g5['point_table']} immediate
                     where immediate.mb_id = od.mb_id
                       and immediate.po_rel_table = '@member'
                       and immediate.po_rel_id = od.od_id
                       and immediate.po_rel_action = '상품구입'
                )
              order by od.od_time asc, od.od_id asc, ct.ct_id asc
              limit {$limit} ";
    $result = sql_query($sql);

    while ($row = sql_fetch_array($result)) {
        $candidates++;

        if ($dry_run) {
            continue;
        }

        // 관리자 수동 지급과 동시 실행되어도 같은 relation을 중복 지급하지 않도록 한다.
        $point_lock_name = 'redtoy_save_order_point';
        $point_lock = sql_fetch(" select GET_LOCK('{$point_lock_name}', 5) as locked ", false);
        if (!isset($point_lock['locked']) || (int)$point_lock['locked'] !== 1) {
            $skipped_lock++;
            continue;
        }

        $od_id = sql_real_escape_string($row['od_id']);
        $ct_id = (int)$row['ct_id'];

        // 후보 조회 후 상태가 바뀐 경우를 제외한다.
        $fresh = sql_fetch(" select od.od_id,
                                    od.mb_id,
                                    od.od_status,
                                    od.od_time,
                                    ct.ct_id,
                                    ct.ct_status,
                                    ct.ct_point,
                                    ct.ct_qty
                               from {$g5['g5_shop_order_table']} od
                               join {$g5['g5_shop_cart_table']} ct
                                 on ct.od_id = od.od_id
                               join {$g5['member_table']} mb
                                 on mb.mb_id = od.mb_id
                              where od.od_id = '{$od_id}'
                                and ct.ct_id = '{$ct_id}'
                                and od.od_status = '완료'
                                and ct.ct_status = '완료'
                                and od.od_time >= '{$from_sql}'
                                and ct.ct_point > 0
                                and ct.ct_qty > 0 ");

        if (empty($fresh['od_id'])) {
            $failed++;
            sql_query(" select RELEASE_LOCK('{$point_lock_name}') ", false);
            continue;
        }

        $mb_id = sql_real_escape_string($fresh['mb_id']);
        $relation = $fresh['od_id'].','.$fresh['ct_id'];
        $relation_sql = sql_real_escape_string($relation);

        // insert_point() 호출 직전에 relation 이력을 다시 확인한다.
        $history = sql_fetch(" select count(*) as cnt
                                from {$g5['point_table']}
                               where mb_id = '{$mb_id}'
                                 and po_rel_table = '@delivery'
                                 and po_rel_id = '{$mb_id}'
                                 and po_rel_action = '{$relation_sql}' ");
        if (!empty($history['cnt'])) {
            $skipped_history++;
            sql_query(" select RELEASE_LOCK('{$point_lock_name}') ", false);
            continue;
        }

        // 정책 전환 이후에도 구 즉시 적립 이력이 생긴 예외 주문은 중복 지급하지 않는다.
        $legacy = sql_fetch(" select count(*) as cnt
                               from {$g5['point_table']}
                              where mb_id = '{$mb_id}'
                                and po_rel_table = '@member'
                                and po_rel_id = '{$od_id}'
                                and po_rel_action = '상품구입' ");
        if (!empty($legacy['cnt'])) {
            $skipped_legacy++;
            sql_query(" select RELEASE_LOCK('{$point_lock_name}') ", false);
            continue;
        }

        $po_point = (int)$fresh['ct_point'] * (int)$fresh['ct_qty'];
        $po_content = "주문번호 {$fresh['od_id']} ({$fresh['ct_id']}) 배송완료";
        $point_result = insert_point($fresh['mb_id'], $po_point, $po_content, '@delivery', $fresh['mb_id'], $relation);

        if ($point_result === 1) {
            sql_query(" update {$g5['g5_shop_cart_table']}
                           set ct_point_use = '1'
                         where ct_id = '{$ct_id}' ");
            $inserted++;
        } else if ($point_result === -1) {
            // 협력하지 않는 다른 경로에서 relation을 먼저 생성한 예외 경우.
            $skipped_history++;
        } else {
            $failed++;
        }

        sql_query(" select RELEASE_LOCK('{$point_lock_name}') ", false);
    }
}

$message = array();
$message[] = 'order_complete_point';
$message[] = 'from='.$from_time;
$message[] = 'limit='.$limit;
$message[] = 'dry_run='.($dry_run ? 'Y' : 'N');
$message[] = 'point_enabled='.($point_disabled ? 'N' : 'Y');
$message[] = 'candidates='.$candidates;
$message[] = 'inserted='.$inserted;
$message[] = 'skipped_history='.$skipped_history;
$message[] = 'skipped_legacy='.$skipped_legacy;
$message[] = 'skipped_lock='.$skipped_lock;
$message[] = 'failed='.$failed;

$output = implode(PHP_EOL, $message).PHP_EOL;
$log_file = G5_DATA_PATH.'/order_complete_point.log';
$log_result = @file_put_contents(
    $log_file,
    '['.date('Y-m-d H:i:s').']'.PHP_EOL.$output.PHP_EOL,
    FILE_APPEND | LOCK_EX
);

flock($lock_fp, LOCK_UN);
fclose($lock_fp);

fwrite(STDOUT, $output);

if ($log_result === false) {
    fwrite(STDERR, 'order_complete_point: log write failed'.PHP_EOL);
    exit(1);
}

if ($point_disabled || $failed > 0 || $skipped_lock > 0) {
    exit(1);
}
