<?php
define('G5_CERT_IN_PROG', true);
include_once('../../common.php');

if (!defined('G5_KCPCERT_V2_DIR')) {
    define('G5_KCPCERT_V2_DIR', 'kcpcert_v2');
}

if (!defined('G5_KCPCERT_V2_PATH')) {
    define('G5_KCPCERT_V2_PATH', G5_PLUGIN_PATH.'/'.G5_KCPCERT_V2_DIR);
}

if (!defined('G5_KCPCERT_V2_URL')) {
    define('G5_KCPCERT_V2_URL', G5_PLUGIN_URL.'/'.G5_KCPCERT_V2_DIR);
}
