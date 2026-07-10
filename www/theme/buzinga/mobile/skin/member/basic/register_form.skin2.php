<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 0);
if ($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp']))
    add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);

if( ! $config['cf_social_login_use'] ){
    alert('소셜 로그인을 사용하지 않습니다.');
}

if( $is_member ){
    alert('이미 회원가입 하였습니다.', G5_URL);
}

$provider_name = social_get_request_provider();
$user_profile = social_session_exists_check();
if( ! $user_profile ){
    alert( "소셜로그인을 하신 분만 접근할 수 있습니다.", G5_URL);
}

// 소셜 가입된 내역이 있는지 확인 상수 G5_SOCIAL_DELETE_DAY 관련
$is_exists_social_account = social_before_join_check($url);

$member_email = isset($user_profile->emailVerified) ? $user_profile->emailVerified : $user_profile->email;
$user_id = $user_profile->sid ? preg_replace("/[^0-9a-z_]+/i", "", $user_profile->sid) : get_social_convert_id($user_profile->identifier, $provider_name);

$register_action_url = https_url(G5_PLUGIN_DIR.'/'.G5_SOCIAL_LOGIN_DIR, true).'/register_member_update.php';    
$member_name = get_session("ss_cert_user_name");
$member_phone = get_session("ss_cert_phone_no");
?>

<style>
    #container_title {display: none;}
</style>

<div id="mb_register" class="mbskin">
    <form name="fregisterform" id="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo $w; ?>">
    <input type="hidden" name="url" value="<?php echo $urlencode; ?>">
    <input type="hidden" name="provider" value="<?php echo $provider_name; ?>">
    <input type="hidden" name="action" value="register">
    <input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
    <input type="hidden" name="cert_no" value="">
    <input type="hidden" name="mb_id" value="<?php echo $user_id; ?>" id="reg_mb_id">

    <h1>
        <P><span class="r_txt">레드토이</span> 회원가입</P>
        <span><span class="required"></span>표시는 필수 입력항목 입니다.</span>
    </h1>

    <div class="form_01">
        <ul>
            <li>
                <div class="item">
                    <label for="reg_mb_id" class="required">아이디</label>
                </div>
                <div class="write">
                    <div class="sns-id">
                        <span class="naver-icon"></span>
                        <span>네이버 로그인</span>
                    </div>
                </div>
	        </li>
            <li class="rgs_name_li">
                <div class="item">
                    <label for="reg_mb_name"> 이름</label>
                </div>
                <div class="write">                    
                    <input type="text" id="reg_mb_name" name="mb_name" value="<?php echo get_text($member_name) ?>" <?php echo $required ?> <?php echo $name_readonly; ?> class="frm_input full_input <?php echo $name_readonly ?>" placeholder="이름<?php echo $desc_name ?>">
                </div>
            </li>
	        <li>
                <div class="item">
                    <label for="reg_mb_hp" class="required">휴대폰 번호<?php if (!empty($hp_required)) { ?><?php } ?></label>
                </div>
                <div class="write">                    
                    <input type="text" name="mb_hp" value="<?php echo get_text($member_phone) ?>" id="reg_mb_hp" <?php echo $hp_required; ?> class="frm_input full_input" maxlength="20" placeholder="휴대폰번호<?php if (!empty($hp_required)) { ?> (필수)<?php } ?><?php echo $desc_phone ?>">
                    <div class="chk_box">
                        <input type="checkbox" name="mb_sms" value="1" id="reg_mb_sms" <?php echo ($w=='' || $member['mb_sms'])?'checked':''; ?> class="selec_chk">
                        <label for="reg_mb_sms">
                            <span></span>
                            SMS 수신 동의
                            <b class="sound_only">SMS 수신여부</b>
                        </label>        
                    </div>
                </div>
	        </li>
            <li class="formemail">
                <div class="item">
                    <label for="reg_mb_email" class="required">이메일</label>
                </div>
                <div class="write">                    
                    <input type="hidden" name="old_email" value="<?php echo $member_email ?>">
                    <input type="email" name="mb_email" value="<?php echo isset($member_email)?$member_email:''; ?>" id="reg_mb_email" class="frm_input full_input email" readonly size="50" maxlength="100" placeholder="주문조회시 사용할 이메일을 입력하세요.">
                    
                    <div class="chk_box">
                        <input type="checkbox" name="mb_mailling" value="1" id="reg_mb_mailling" class="selec_chk">
                        <label for="reg_mb_mailling">
                            <span></span>
                            이메일 수신 동의
                            <b class="sound_only">메일링서비스</b>
                        </label>
                    </div>
                    <!-- span class="warn_msg warning_icon">이메일을 입력해 주세요. (주문조회 시 필요)</span //-->
                </div>
			</li>
        </ul>
    </div>

    <div class="btn_confirm">
        <button type="submit" id="btn_submit" class="btn_submit" accesskey="s"><?php echo $w==''?'회원가입':'정보수정'; ?></button>
    </div>
    </form>

    <script>
    // submit 최종 폼체크
    function fregisterform_submit(f)
    {
        // E-mail 검사
        if (f.mb_email.value != "") {
            var msg = reg_mb_email_check();
            if (msg) {
                alert(msg);
                f.reg_mb_email.select();
                return false;
            }
        }

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
</div>