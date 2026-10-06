<?php
// Synthetic detached-process test: never loads DB settings or sends messages.
define('_GNUBOARD_', true);
require dirname(__DIR__).'/www/lib/redtoy_telegram.lib.php';
$dir = sys_get_temp_dir().'/redtoy-dispatch-'.bin2hex(random_bytes(6));
mkdir($dir, 0700);
$php = $dir.'/fake-php';
$key = getenv('REDTOY_TELEGRAM_KEY');
$old_php = getenv('REDTOY_TELEGRAM_PHP');
try {
    $script = '#!/bin/sh'."\n".'test "${#REDTOY_TELEGRAM_KEY}" -eq 44 || exit 1'."\n";
    $script .= 'printf started > '.escapeshellarg($dir.'/started')."\n";
    $script .= "sleep 2\n".'printf done > '.escapeshellarg($dir.'/done')."\n";
    file_put_contents($php, $script); chmod($php, 0700);
    putenv('REDTOY_TELEGRAM_KEY='.base64_encode(random_bytes(32)));
    putenv('REDTOY_TELEGRAM_PHP='.$php);
    $start = microtime(true);
    if (!redtoy_tg_dispatch() || microtime(true)-$start >= 1.5) throw new RuntimeException('Launcher waited for transport');
    $deadline = microtime(true)+5;
    while (!is_file($dir.'/done') && microtime(true)<$deadline) usleep(50000);
    if (!is_file($dir.'/started') || !is_file($dir.'/done')) throw new RuntimeException('Detached child or key environment missing');
    putenv('REDTOY_TELEGRAM_PHP='.$dir.'/missing');
    if (redtoy_tg_dispatch()) throw new RuntimeException('Unavailable launcher accepted');
    echo "PASS: detached launch, key environment, nonblocking return, launch failure containment; no DB or Telegram used\n";
} finally {
    putenv($key === false ? 'REDTOY_TELEGRAM_KEY' : 'REDTOY_TELEGRAM_KEY='.$key);
    putenv($old_php === false ? 'REDTOY_TELEGRAM_PHP' : 'REDTOY_TELEGRAM_PHP='.$old_php);
    foreach (array('started','done','fake-php') as $file) if (is_file($dir.'/'.$file)) unlink($dir.'/'.$file);
    rmdir($dir);
}
