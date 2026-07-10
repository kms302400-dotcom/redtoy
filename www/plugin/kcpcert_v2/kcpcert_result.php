<?php
include_once('./_common.php');
include_once(G5_KCPCERT_V2_PATH.'/kcpcert_config.php');

$res_cd = isset($_POST['res_cd']) ? clean_xss_tags($_POST['res_cd'], 1, 1) : '';
$res_msg = isset($_POST['res_msg']) ? clean_xss_tags($_POST['res_msg'], 1, 1) : '';
$cb_reg_cert_key = isset($_POST['reg_cert_key']) ? clean_xss_tags($_POST['reg_cert_key'], 1, 1) : '';

$reg_cert_key = get_session('ss_kcpcert_v2_reg_cert_key');
$ordr_idxx = get_session('ss_kcpcert_v2_ordr_idxx');
$page_type = get_session('ss_kcpcert_v2_page_type');
$provider = get_session('ss_kcpcert_v2_provider');
$sw_direct = get_session('ss_kcpcert_v2_sw_direct');

function kcpcert_v2_clear_session()
{
    set_session('ss_kcpcert_v2_reg_cert_key', '');
    set_session('ss_kcpcert_v2_ordr_idxx', '');
    set_session('ss_kcpcert_v2_page_type', '');
    set_session('ss_kcpcert_v2_provider', '');
    set_session('ss_kcpcert_v2_sw_direct', '');
}

if (!$reg_cert_key || !$ordr_idxx || $cb_reg_cert_key !== $reg_cert_key) {
    kcpcert_v2_clear_session();
    alert_close('KCP 본인확인 V2 거래등록키가 일치하지 않습니다.');
}

if ($res_cd !== '0000') {
    kcpcert_v2_clear_session();
    alert_close('코드 : '.$res_cd.' '.$res_msg);
}

$headers = array(
    'Content-Type: application/json',
    'site_cd: '.$g_conf_site_cd
);

$body = json_encode(array(
    'reg_cert_key' => $reg_cert_key,
    'ordr_idxx' => $ordr_idxx
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init($g_conf_dec_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$res_data = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($res_data === false || $res_data === '') {
    kcpcert_v2_clear_session();
    alert_close('KCP 본인확인 V2 결과조회 통신에 실패했습니다. '.$curl_error);
}

$query_result = json_decode($res_data, true);
$query_res_cd = isset($query_result['res_cd']) ? $query_result['res_cd'] : '';

if ($query_res_cd !== '0000') {
    $query_res_msg = isset($query_result['res_msg']) ? $query_result['res_msg'] : '';
    kcpcert_v2_clear_session();
    alert_close('KCP 본인확인 V2 결과조회에 실패했습니다. 코드 : '.$query_res_cd.' '.$query_res_msg);
}

$enc_cert_data = isset($query_result['enc_cert_data']) ? $query_result['enc_cert_data'] : '';
$rv = isset($query_result['rv']) ? $query_result['rv'] : '';
$cert_data_json = Crypto::decryptJson($enc_cert_data, $rv, $g_conf_ENC_KEY, $g_conf_site_cd);
$cert_data = json_decode($cert_data_json, true);

if (!$cert_data || !is_array($cert_data)) {
    kcpcert_v2_clear_session();
    alert_close('KCP 본인확인 V2 결과 복호화에 실패했습니다.');
}

$phone_no = isset($cert_data['phone_no']) ? $cert_data['phone_no'] : (isset($cert_data['PHONE_NO']) ? $cert_data['PHONE_NO'] : '');
$user_name = isset($cert_data['user_name']) ? $cert_data['user_name'] : (isset($cert_data['USER_NAME']) ? $cert_data['USER_NAME'] : '');
$birth_day = isset($cert_data['birth_day']) ? $cert_data['birth_day'] : (isset($cert_data['BIRTH_DAY']) ? $cert_data['BIRTH_DAY'] : '');
$sex_code = isset($cert_data['sex_code']) ? $cert_data['sex_code'] : (isset($cert_data['SEX_CODE']) ? $cert_data['SEX_CODE'] : '');
$ci = isset($cert_data['ci']) ? $cert_data['ci'] : (isset($cert_data['CI']) ? $cert_data['CI'] : '');
$di = isset($cert_data['di']) ? $cert_data['di'] : (isset($cert_data['DI']) ? $cert_data['DI'] : '');

if (!$phone_no || !$user_name || !$birth_day) {
    kcpcert_v2_clear_session();
    alert_close('정상적인 인증 결과가 아닙니다. 올바른 방법으로 이용해 주세요.');
}

$phone_no = hyphen_hp_number($phone_no);
$mb_dupinfo = str_replace(' ', '+', $ci ? $ci : $di);

if ($page_type === 'register') {
    if (!empty($member['mb_certify']) && !empty($member['mb_dupinfo']) && $member['mb_dupinfo'] != $mb_dupinfo) {
        kcpcert_v2_clear_session();
        if (is_mobile()) {
            alert('해당 계정은 이미 다른명의로 본인인증 되어있는 계정입니다.', '/19m.php');
        } else {
            alert_close('해당 계정은 이미 다른명의로 본인인증 되어있는 계정입니다.');
        }
    }

    $sql = " select mb_id from {$g5['member_table']} where mb_id <> '{$member['mb_id']}' and mb_dupinfo = '{$mb_dupinfo}' ";
    $row = sql_fetch($sql);
    if (!empty($row['mb_id'])) {
        kcpcert_v2_clear_session();
        if (is_mobile()) {
            alert('입력하신 본인확인 정보로 가입된 내역이 존재합니다.\\n회원아이디 : '.$row['mb_id'], '/19m.php');
        } else {
            alert_close('입력하신 본인확인 정보로 가입된 내역이 존재합니다.\\n회원아이디 : '.$row['mb_id']);
        }
    }
}

$find_mb_id = '';
if ($page_type === 'find') {
    $md5_ci = md5($ci.$ci);
    $row = array();

    if ($ci) {
        $row = sql_fetch(" select mb_id from {$g5['member_table']} where mb_id <> '{$member['mb_id']}' and mb_dupinfo = '{$md5_ci}' ");
    }

    if (empty($row['mb_id']) && $di) {
        $row = sql_fetch(" select mb_id from {$g5['member_table']} where mb_id <> '{$member['mb_id']}' and mb_dupinfo = '{$di}' ");
        if (!empty($row['mb_id'])) {
            $mb_dupinfo = $di;
        }
    } else if (!empty($row['mb_id'])) {
        $mb_dupinfo = $md5_ci;
    }

    if (empty($row['mb_id'])) {
        kcpcert_v2_clear_session();
        alert_close('인증하신 정보로 가입된 회원정보가 없습니다.');
    }

    $find_mb_id = $row['mb_id'];
}

$cert_type = 'hp';
$md5_cert_no = md5($reg_cert_key);
$hash_data = md5($user_name.$cert_type.$birth_day.$phone_no.$md5_cert_no);
$adult_day = date('Ymd', strtotime('-19 years', G5_SERVER_TIME));
$adult = ((int)$birth_day <= (int)$adult_day) ? 'OK' : 0;

set_session('ss_cert_type', $cert_type);
set_session('ss_cert_no', $md5_cert_no);
set_session('ss_cert_hash', $hash_data);
set_session('ss_cert_adult', $adult);
set_session('ss_cert_birth', $birth_day);
set_session('ss_cert_sex', ($sex_code == '01' || strtoupper($sex_code) == 'M') ? 'M' : 'F');
set_session('ss_cert_dupinfo', $mb_dupinfo);
set_session('ss_cert_user_name', $user_name);
set_session('ss_cert_phone_no', $phone_no);

if ($page_type === 'find') {
    set_session('ss_cert_mb_id', $find_mb_id);
}

if ($page_type === 'main' || $page_type === 'pay') {
    set_session('ss_cert_adult', 'OK');
    set_cookie('ss_cert_adult', 'OK', 86400 * 365);
}

kcpcert_v2_clear_session();

$g5['title'] = '휴대폰 본인확인 결과';
include_once(G5_PATH.'/head.sub.php');
?>

<script>
$(function() {
    var pageType = "<?php echo $page_type; ?>";
    var provider = "<?php echo $provider; ?>";
    var swDirect = "<?php echo $sw_direct; ?>";
    var isMobile = /Android|iPhone/i.test(navigator.userAgent);
    var openerWindow = isMobile ? window : window.opener;

    if (pageType === "main") {
        if (isMobile) {
            location.href = "/19_ok.php";
        } else if (openerWindow) {
            openerWindow.location.href = "/19_ok.php";
            window.close();
        } else {
            location.href = "/19_ok.php";
        }
        return;
    }

    if (pageType === "pay") {
        alert("본인의 휴대폰번호로 확인 되었습니다.");
        if (isMobile) {
            location.href = "/shop/orderform.php?sw_direct=" + encodeURIComponent(swDirect);
        } else if (openerWindow && openerWindow.document.MAINPAY_FORM) {
            openerWindow.document.MAINPAY_FORM.adult_check.value = "OK";
            window.close();
        } else {
            window.close();
        }
        return;
    }

    if (pageType === "find") {
        var form = document.createElement("form");
        form.method = "post";
        form.action = "<?php echo G5_BBS_URL; ?>/password_reset.php";
        form.target = isMobile ? "_self" : "parentPage";

        var input = document.createElement("input");
        input.type = "hidden";
        input.name = "mb_id";
        input.value = "<?php echo $find_mb_id; ?>";
        form.appendChild(input);

        document.body.appendChild(form);
        if (!isMobile && openerWindow) {
            openerWindow.name = "parentPage";
        }
        form.submit();
        window.close();
        return;
    }

    if (openerWindow && openerWindow.$) {
        openerWindow.$("input[name=cert_type]").val("<?php echo $cert_type; ?>");
        openerWindow.$("input[name=mb_name]").val("<?php echo $user_name; ?>").attr("readonly", true);
        openerWindow.$("input[name=mb_hp]").val("<?php echo $phone_no; ?>").attr("readonly", true);
        openerWindow.$("input[name=cert_no]").val("<?php echo $md5_cert_no; ?>");
    }

    if (openerWindow && openerWindow.$ && openerWindow.$("form[name=fregister]").length) {
        if (provider === "Naver") {
            openerWindow.$("form[name=fregister]").attr("action", "/bbs/register_form2.php?provider=Naver");
        }
        openerWindow.$("form[name=fregister]").submit();
        window.close();
    } else {
        if (provider === "Naver") {
            location.href = "/bbs/register_form2.php?provider=Naver";
        } else {
            location.href = "/bbs/register_form.php";
        }
    }
});
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
