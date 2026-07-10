<?php
if (!defined('_GNUBOARD_')) exit;

define('KCP_CERT_V2_ENC_KEY', '97acb5be87029a34d505fb38c8477f68e1cecd4f9933ede153f0d8ec5bc97b86');

function kcp_cert_v2_site_cd()
{
    global $config;

    $site_cd = isset($config['cf_cert_kcp_cd']) ? trim($config['cf_cert_kcp_cd']) : '';
    if ($site_cd && !preg_match('/^[A-Z]{2}/', $site_cd)) {
        $site_cd = 'SM'.$site_cd;
    }

    return $site_cd;
}

$g_conf_site_cd = kcp_cert_v2_site_cd();

if ($config['cf_cert_use'] == 2) {
    $g_conf_cert_url = 'https://cert.kcp.co.kr/api/reg/certDataReg.do';
    $g_conf_dec_url = 'https://cert.kcp.co.kr/api/query/getCertData.do';
} else if ($config['cf_cert_use'] == 1) {
    $g_conf_site_cd = 'AO7F3';
    $g_conf_cert_url = 'https://testcert.kcp.co.kr/api/reg/certDataReg.do';
    $g_conf_dec_url = 'https://testcert.kcp.co.kr/api/query/getCertData.do';
} else {
    $g_conf_cert_url = '';
    $g_conf_dec_url = '';
}

$g_conf_Ret_URL = G5_KCPCERT_V2_URL.'/kcpcert_result.php';
$g_conf_ENC_KEY = KCP_CERT_V2_ENC_KEY;

if (!$g_conf_site_cd || !$g_conf_cert_url || !$g_conf_dec_url) {
    alert('KCP 휴대폰 본인확인 V2 설정이 올바르지 않습니다. 관리자 > 기본환경설정의 KCP 설정을 확인해 주십시오.', G5_URL);
}

include_once(G5_KCPCERT_V2_PATH.'/utils/Crypto.php');
