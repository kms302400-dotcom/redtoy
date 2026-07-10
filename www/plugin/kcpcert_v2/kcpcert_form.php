<?php
include_once('./_common.php');

certify_count_check($member['mb_id'], 'hp');

$page_type = isset($_GET['pageType']) ? preg_replace('/[^a-z]/i', '', $_GET['pageType']) : '';
$provider = isset($_REQUEST['provider']) ? clean_xss_tags($_REQUEST['provider'], 1, 1) : '';
$sw_direct = isset($_REQUEST['sw_direct']) ? clean_xss_tags($_REQUEST['sw_direct'], 1, 1) : '';

if (!in_array($page_type, array('register', 'find', 'main', 'pay'))) {
    alert_close('잘못된 접근입니다.');
}

include_once(G5_KCPCERT_V2_PATH.'/kcpcert_config.php');

$ordr_idxx = get_session('ss_uniqid');
if (!$ordr_idxx) {
    $ordr_idxx = get_uniqid();
}

$req_data = array(
    'site_cd' => $g_conf_site_cd,
    'ordr_idxx' => $ordr_idxx,
    'Ret_URL' => $g_conf_Ret_URL,
    'web_siteid' => '',
    'param_opt_1' => $page_type,
    'param_opt_2' => $provider,
    'param_opt_3' => $sw_direct
);

$req_json = json_encode($req_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$enc_result = Crypto::encryptJson($req_json, $g_conf_ENC_KEY, $g_conf_site_cd);

$headers = array(
    'Content-Type: application/json',
    'site_cd: '.$g_conf_site_cd,
    'rv: '.$enc_result['rv']
);

$ch = curl_init($g_conf_cert_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $enc_result['encData']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$res_data = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($res_data === false || $res_data === '') {
    alert_close('KCP 본인확인 V2 거래등록 통신에 실패했습니다. '.$curl_error);
}

$reg_result = json_decode($res_data, true);
$res_cd = isset($reg_result['res_cd']) ? $reg_result['res_cd'] : '';
$res_msg = isset($reg_result['res_msg']) ? $reg_result['res_msg'] : '';
$call_url = isset($reg_result['call_url']) ? $reg_result['call_url'] : '';
$reg_cert_key = isset($reg_result['reg_cert_key']) ? $reg_result['reg_cert_key'] : '';

if ($res_cd !== '0000' || !$call_url || !$reg_cert_key) {
    alert_close('KCP 본인확인 V2 거래등록에 실패했습니다. 코드 : '.$res_cd.' '.$res_msg);
}

set_session('ss_kcpcert_v2_reg_cert_key', $reg_cert_key);
set_session('ss_kcpcert_v2_ordr_idxx', $ordr_idxx);
set_session('ss_kcpcert_v2_page_type', $page_type);
set_session('ss_kcpcert_v2_provider', $provider);
set_session('ss_kcpcert_v2_sw_direct', $sw_direct);

$g5['title'] = '휴대폰 본인확인';
include_once(G5_PATH.'/head.sub.php');
?>

<form name="form_auth" method="post" action="<?php echo htmlspecialchars($call_url, ENT_QUOTES); ?>">
    <input type="hidden" name="reg_cert_key" value="<?php echo htmlspecialchars($reg_cert_key, ENT_QUOTES); ?>">
    <input type="hidden" name="kcp_page_submit_yn" value="<?php echo is_mobile() ? 'Y' : 'N'; ?>">
</form>

<script>
(function() {
    var frm = document.form_auth;

    if (frm.kcp_page_submit_yn.value === "N") {
        frm.target = "auth_popup";
    } else {
        frm.target = "_self";
    }

    frm.submit();
})();
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
