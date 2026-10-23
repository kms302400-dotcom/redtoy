<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
function redtoy_order_auto_complete_usage()
{
    return "Usage: php order_auto_complete.php [--days=N] [--limit=N] [--from=YYYY-MM-DDTHH:MM:SS] [--dry-run]\nDefault: all currently shipping orders; no order-date cutoff.\n";
}
$from_time=''; $days=7; $limit=100; $dry_run=false;
foreach (array_slice($argv,1) as $arg) {
    if ($arg==='--help') { echo redtoy_order_auto_complete_usage(); exit; }
    if ($arg==='--dry-run') $dry_run=true;
    elseif (preg_match('/^--days=([1-9][0-9]*)$/D',$arg,$match)) $days=(int)$match[1];
    elseif (preg_match('/^--limit=([1-9][0-9]*)$/D',$arg,$match)) $limit=(int)$match[1];
    elseif (preg_match('/^--from=(.+)$/D',$arg,$match)) $from_time=str_replace('T',' ',$match[1]);
    else { fwrite(STDERR,redtoy_order_auto_complete_usage()); exit(2); }
}
if ($limit>1000 || $days>3650) { fwrite(STDERR,"Invalid limit/days\n"); exit(2); }
if ($from_time!=='') {
    $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$from_time);
    $errors=DateTimeImmutable::getLastErrors();
    if (!$date || ($errors!==false && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d H:i:s')!==$from_time) { fwrite(STDERR,"Invalid --from\n"); exit(2); }
}
$lock_fp=null;
try {
    // Config/constants and DB only: no sessions, visit writes, cron hooks or PG code.
    $g5_path=array('path'=>dirname(__DIR__,2),'url'=>'');
    require $g5_path['path'].'/config.php';
    require G5_DATA_PATH.'/'.G5_DBCONFIG_FILE;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $redtoy_completion_db=new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);
    $redtoy_completion_db->set_charset('utf8mb4');
    require G5_LIB_PATH.'/redtoy_order_completion.lib.php';
    if ($dry_run) {
        // Enforce read-only SQL on transactional tables, too. No lock/log files are created.
        redtoy_completion_query('SET SESSION TRANSACTION READ ONLY');
    } else {
        $lock_fp=fopen(G5_DATA_PATH.'/order_auto_complete.lock','c');
        if (!$lock_fp || !flock($lock_fp,LOCK_EX|LOCK_NB)) throw new RuntimeException('batch_locked');
        $g5['connect_db']=$redtoy_completion_db;
        $is_admin='';
        $_SERVER['HTTP_HOST']=getenv('REDTOY_CRON_HOST') ?: 'www.redtoy.co.kr';
        $_SERVER['SCRIPT_NAME']='/adm/shop_admin/order_auto_complete.php';
        require_once G5_LIB_PATH.'/hook.lib.php';
        require_once G5_LIB_PATH.'/get_data.lib.php';
        add_replace('get_permission_debug_show', function () { return false; });
        require_once G5_LIB_PATH.'/common.lib.php';
    }
    $config=redtoy_completion_query('select cf_use_point,cf_point_term from '.redtoy_completion_table('config_table').' limit 1')->fetch_assoc();
    if (!$config) throw new RuntimeException('configuration_missing');
    $now=G5_TIME_YMDHIS;
    $where=" where od_status='배송'";
    if ($from_time!=='') $where.=" and od_time>='".redtoy_completion_escape($from_time)."'";
    $orders=redtoy_completion_query('select od_id,mb_id,od_status,od_time,od_mod_history from '.redtoy_completion_table('g5_shop_order_table').$where.' order by od_id')->fetch_all(MYSQLI_ASSOC);
    $stats=array('shipping_orders'=>count($orders),'eligible'=>0,'planned_completions'=>0,'completed'=>0,'failed'=>0,'skipped_lock'=>0,'skipped_changed'=>0,'already_paid_at_checkout'=>0,'no_member'=>0,'withdrawn_member'=>0,'preview_payable_orders'=>0,'preview_payable_items'=>0,'preview_points'=>0,'paid_items'=>0,'paid_points'=>0,'recovery_pending'=>0,'preview_recovery_points'=>0,'preview_recovery_items'=>0);
    $reasons=array();
    foreach ($orders as $order) {
        $carts=redtoy_completion_carts($order['od_id']);
        $evaluation=redtoy_completion_evaluate($order,$carts,$now,$days);
        $reason=$evaluation['reason'];
        if ($reason!=='eligible') { $reasons[$reason]=isset($reasons[$reason])?$reasons[$reason]+1:1; continue; }
        $stats['eligible']++;
        $plan=redtoy_completion_point_plan($order,$carts);
        if ($plan['reason']==='already_paid_at_checkout') $stats['already_paid_at_checkout']++;
        if ($plan['reason']==='no_member') $stats['no_member']++;
        if ($plan['reason']==='withdrawn_member') $stats['withdrawn_member']++;
        if ($plan['reason']==='payable' && !empty($config['cf_use_point'])) {
            $stats['preview_payable_orders']++; $stats['preview_payable_items']+=count($plan['rows']); $stats['preview_points']+=$plan['points'];
        }
        if ($stats['planned_completions'] >= $limit) continue;
        $stats['planned_completions']++;
        if ($dry_run) continue;
        try {
            $result=redtoy_completion_apply($order['od_id'],$now,$days);
            if ($result==='completed') {
                $stats['completed']++;
                $paid=redtoy_completion_pay($order['od_id']);
                $stats['paid_points']+=$paid['points']; $stats['paid_items']+=$paid['items'];
                if ($paid['reason']==='locked') $stats['skipped_lock']++;
            } elseif ($result==='locked') $stats['skipped_lock']++;
            else $stats['skipped_changed']++;
        } catch (Throwable $e) { $stats['failed']++; }
    }
    // Retry only orders completed by this version, never arbitrary historical completions.
    $pending=redtoy_completion_recovery_ids($limit);
    $stats['recovery_pending']=count($pending);
    foreach ($pending as $id) {
        try {
            if (!empty($config['cf_use_point'])) {
                $plan=redtoy_completion_point_plan(redtoy_completion_order($id),redtoy_completion_carts($id));
                $stats['preview_recovery_points']+=$plan['points'];
                $stats['preview_recovery_items']+=count($plan['rows']);
            }
            if ($dry_run) continue;
            $paid=redtoy_completion_pay($id);
            $stats['paid_points']+=$paid['points']; $stats['paid_items']+=$paid['items'];
            if ($paid['reason']==='locked') $stats['skipped_lock']++;
        } catch (Throwable $e) { $stats['failed']++; }
    }
    $output="order_auto_complete_v2\ntime=$now\nfrom=".($from_time===''?'ALL':$from_time)."\ndays=$days\nlimit=$limit\ndry_run=".($dry_run?'Y':'N')."\npoint_enabled=".(!empty($config['cf_use_point'])?'Y':'N')."\n";
    foreach ($stats as $key=>$value) $output.="$key=$value\n";
    foreach ($reasons as $key=>$value) $output.="held_$key=$value\n";
    echo $output;
    if (!$dry_run && file_put_contents(G5_DATA_PATH.'/order_auto_complete.log',"[$now]\n".$output."\n",FILE_APPEND|LOCK_EX)===false) throw new RuntimeException('log_write_failed');
    if ($stats['failed'] || $stats['skipped_lock']) exit(1);
} catch (Throwable $e) {
    fwrite(STDERR,"order_auto_complete: failed; no SQL, credentials or customer data logged\n");
    exit(1);
} finally {
    if (is_resource($lock_fp)) { flock($lock_fp,LOCK_UN); fclose($lock_fp); }
}
