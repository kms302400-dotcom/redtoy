<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 0);
if ($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp']))
    add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);
    
$member_name = get_session("ss_cert_user_name");
$member_phone = get_session("ss_cert_phone_no");
if (!$member_name) exit;
$hp_readonly = "";
if ($member_phone) $hp_readonly = "readonly";
$_SESSION["register_secure"] = time();
?>

<style>
    #container_title {display: none;}
</style>

<div id="mb_register" class="mbskin">
    <form name="fregisterform" id="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="url" value="<?php echo $urlencode ?>">
    <input type="hidden" name="agree" value="<?php echo $agree ?>">
    <input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
    <input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
    <input type="hidden" name="cert_no" value="">
    <?php if (isset($member['mb_sex'])) { ?><input type="hidden" name="mb_sex" value="<?php echo $member['mb_sex'] ?>"><?php } ?>
    <?php if (isset($member['mb_nick_date']) && $member['mb_nick_date'] > date("Y-m-d", G5_SERVER_TIME - ($config['cf_nick_modify'] * 86400))) { // 닉네임수정일이 지나지 않았다면 ?>
    <input type="hidden" name="mb_nick_default" value="<?php echo get_text($member['mb_nick']) ?>">
    <input type="hidden" name="mb_nick" value="<?php echo get_text($member['mb_nick']) ?>">
    <?php } ?>

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
                    <input type="text" onchange="checkId(this.value)" onkeypress="this.onchange();" onpaste="this.onchange();" oninput="this.onchange();" name="mb_id" value="<?php echo $member['mb_id'] ?>" id="reg_mb_id" class="frm_input full_input <?php echo $readonly ?>" minlength="3" maxlength="20" <?php echo $required ?> <?php echo $readonly ?> placeholder="아이디를 입력해 주세요.">
                    <span class="warn_msg warning_icon" style="display:none" id="reg_mb_id_text"></span>
                </div>
	        </li>
	        <li class="password">
                <div class="item">
                    <label for="reg_mb_password" class="required">비밀번호</label>
                </div>
                <div class="write">                    
                    <input type="password" onchange="checkPass(this.value)" onkeypress="this.onchange();" onpaste="this.onchange();" oninput="this.onchange();" name="mb_password" id="reg_mb_password" class="frm_input full_input" minlength="8" maxlength="20" <?php echo $required ?> placeholder="8글자 이상 비밀번호를 입력하세요.">
                    <span class="warn_msg warning_icon" style="display:none" id="reg_mb_pass_text"></span>
                </div>
	        </li>
	        <li>
                <div class="item">
                    <label for="reg_mb_password_re" class="required">비밀번호 확인</label>
                </div>
                <div class="write">                    
                    <input type="password" onchange="checkPass2(this.value)" onkeypress="this.onchange();" onpaste="this.onchange();" oninput="this.onchange();" name="mb_password_re" id="reg_mb_password_re" class="frm_input full_input" minlength="8" maxlength="20" <?php echo $required ?>  placeholder="비밀번호를 확인해 주세요.">
                    <span class="warn_msg warning_icon" style="display:none" id="reg_mb_pass2_text"></span>
                </div>
	        </li>
            <li class="rgs_name_li">
                <div class="item">
                    <label for="reg_mb_name" class="required"> 이름</label>
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
                    <input type="text" name="mb_hp" value="<?php echo get_text($member_phone) ?>" id="reg_mb_hp" <?php echo $hp_required; ?> <?php echo $hp_readonly; ?> class="frm_input full_input <?php echo $hp_readonly; ?>" maxlength="20" placeholder="휴대폰번호<?php if (!empty($hp_required)) { ?> (필수)<?php } ?><?php echo $desc_phone ?>">
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
                    <input type="hidden" name="old_email" value="<?php echo $member['mb_email'] ?>">
                    <input type="email" name="mb_email" value="<?php echo isset($member['mb_email'])?$member['mb_email']:''; ?>" id="reg_mb_email" class="frm_input full_input email" size="50" maxlength="100" placeholder="주문조회시 사용할 이메일을 입력하세요.">
                    
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
    function checkId(userid) {
        if (userid.length > 3) {
            var regExp = new RegExp(/^[0-9a-zA-Z]+$/, 'g');
            if (regExp.test(userid)) {
                $.ajax({
                    url : "<?php echo G5_BBS_URL; ?>/register_ajax.php"
                    , type : "POST"
                    , data : { "userid" : userid }
                }).done((res) => {
                    $("#reg_mb_id_text").show();
                    $("#reg_mb_id_text").html(res)
                    if (!res) $("#reg_mb_id_text").hide();
                })
            } else {
                $("#reg_mb_id_text").show();
                $("#reg_mb_id_text").html("영문,숫자 4~12로 입력해 주세요")
            }
        } else {
            $("#reg_mb_id_text").show();
            $("#reg_mb_id_text").html("영문,숫자 4~12로 입력해 주세요")
        }
    }
    
    function checkPass(userpass) {
        if (userpass.length > 0 && userpass.length < 8) {
            $("#reg_mb_pass_text").show();
            $("#reg_mb_pass_text").html("비밀번호는 8글자 이상 입력해 주세요.")
        } else {
            $("#reg_mb_pass_text").hide();
        }
    }

    function checkPass2(userpass) {
        if (userpass.length > 0 && userpass != $("#reg_mb_password").val()) {
            $("#reg_mb_pass2_text").show();
            $("#reg_mb_pass2_text").html("비밀번호가 일치하지 않습니다.")
        } else {
            $("#reg_mb_pass2_text").hide();
        }
    } 

    // submit 최종 폼체크
    function fregisterform_submit(f)
    {
        // 회원아이디 검사
        if (f.w.value == "") {
            var msg = reg_mb_id_check();
            if (msg) {
                alert(msg);
                f.mb_id.select();
                return false;
            }
        }

        if (f.w.value == '') {
            if (f.mb_password.value.length < 3) {
                alert('비밀번호를 3글자 이상 입력하십시오.');
                f.mb_password.focus();
                return false;
            }
        }

        if (f.mb_password.value != f.mb_password_re.value) {
            alert('비밀번호가 같지 않습니다.');
            f.mb_password_re.focus();
            return false;
        }

        if (f.mb_password.value.length > 0) {
            if (f.mb_password_re.value.length < 3) {
                alert('비밀번호를 3글자 이상 입력하십시오.');
                f.mb_password_re.focus();
                return false;
            }
        }

        // 이름 검사
        if (f.w.value=='') {
            if (f.mb_name.value.length < 1) {
                alert('이름을 입력하십시오.');
                f.mb_name.focus();
                return false;
            }
        }

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