<?php
// No browser endpoint, no default send, and no application bootstrap for usage output.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!isset($argv[1]) || !in_array($argv[1], array('--run','--check'), true)) {
    fwrite(STDERR, "Usage: php redtoy_telegram_worker.php --check|--run\n");
    exit(1);
}
define('G5_IS_ADMIN', true);
chdir(__DIR__);
include_once __DIR__.'/../../common.php';
require_once G5_LIB_PATH.'/redtoy_telegram.lib.php';
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
