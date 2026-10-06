<?php
// No browser endpoint, no default send, and no application bootstrap for usage output.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!isset($argv[1]) || !in_array($argv[1], array('--run','--check'), true)) {
    fwrite(STDERR, "Usage: php redtoy_telegram_worker.php --check|--run\n");
    exit(1);
}
// CLI must not load web redirects, sessions, visits, optimization or extend hooks.
define('_GNUBOARD_', true);
try {
    require __DIR__.'/../../data/dbconfig.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $redtoy_worker_db = new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
    $redtoy_worker_db->set_charset('utf8mb4');
} catch (Throwable $e) {
    fwrite(STDERR, "Database configuration/connection unavailable\n");
    exit(1);
}
function sql_query($sql, $error = true) {
    global $redtoy_worker_db;
    return $redtoy_worker_db->query($sql);
}
function sql_fetch_array($result) { return $result->fetch_assoc(); }
function sql_real_escape_string($value) {
    global $redtoy_worker_db;
    return $redtoy_worker_db->real_escape_string((string)$value);
}
require_once __DIR__.'/../../lib/redtoy_telegram.lib.php';
if ($argv[1] === '--check') {
    $config = redtoy_tg_config();
    if (!$config) { fwrite(STDERR, "Schema/configuration unavailable\n"); exit(1); }
    if (!redtoy_tg_query('select id from '.G5_TABLE_PREFIX.'redtoy_telegram_queue limit 1')) {
        fwrite(STDERR, "Queue schema unavailable\n"); exit(1);
    }
    try { redtoy_tg_decrypt($config['token_cipher']); }
    catch (Throwable $e) { fwrite(STDERR, "Encryption key/token unavailable\n"); exit(1); }
    if (!function_exists('curl_init')) { fwrite(STDERR, "cURL unavailable\n"); exit(1); }
    echo "Configuration ready; no messages sent\n";
    exit;
}
echo 'Processed: '.redtoy_tg_worker(10)."\n";
