<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_cert_statistics_table()
{
    $table = G5_TABLE_PREFIX.'redtoy_cert_success';
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $table)) throw new RuntimeException('invalid_cert_table');
    return '`'.$table.'`';
}

// Store neither identity data nor the original provider transaction key.
// A failed statistics write must not change the authentication result.
function redtoy_cert_record_success($transaction_key)
{
    global $g5;
    if (!is_string($transaction_key) || $transaction_key === '' || strlen($transaction_key) > 4096) return false;
    try {
        $table = redtoy_cert_statistics_table();
        $key = hash('sha256', "kcp-v2\0".$transaction_key);
        $time = (new DateTimeImmutable('now', new DateTimeZone('Asia/Seoul')))->format('Y-m-d H:i:s');
        $statement = @mysqli_prepare($g5['connect_db'], "INSERT INTO $table (event_key, succeeded_at) VALUES (?, ?) ON DUPLICATE KEY UPDATE event_key=event_key");
        if (!$statement) throw new RuntimeException('cert_prepare_failed');
        try {
            mysqli_stmt_bind_param($statement, 'ss', $key, $time);
            if (!@mysqli_stmt_execute($statement)) throw new RuntimeException('cert_insert_failed');
        } finally {
            mysqli_stmt_close($statement);
        }
        return true;
    } catch (Throwable $e) {
        error_log('redtoy statistics: certification success recording failed');
        return false;
    }
}

function redtoy_cert_success_daily($from_time, $to_exclusive)
{
    global $g5;
    try {
        $table = redtoy_cert_statistics_table();
        $statement = @mysqli_prepare($g5['connect_db'], "SELECT DATE(succeeded_at) AS stat_date, COUNT(*) AS cnt FROM $table WHERE succeeded_at >= ? AND succeeded_at < ? GROUP BY DATE(succeeded_at)");
        if (!$statement) return null;
        try {
            mysqli_stmt_bind_param($statement, 'ss', $from_time, $to_exclusive);
            if (!@mysqli_stmt_execute($statement)) return null;
            mysqli_stmt_bind_result($statement, $date, $count);
            $rows = array();
            while (mysqli_stmt_fetch($statement)) $rows[$date] = (int)$count;
            return $rows;
        } finally {
            mysqli_stmt_close($statement);
        }
    } catch (Throwable $e) {
        return null;
    }
}
