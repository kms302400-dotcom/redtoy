<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

$provider_name = $_REQUEST["provider"];
?>

<style>
    #container_title {display: none;}
    .policy1, .tbl_head01 {display:none;}
</style>

<div id="mb_register" class="mbskin">
    <h1>
        회원가입
        <span>약관 동의 및 본인 인증을 진행해 주세요.</span>
    </h1>
    <form name="fregister" id="fregister" action="<?php echo $register_action_url ?>" onsubmit="return fregister_submit(this);" method="POST" autocomplete="off">
        <div id="fregister_chkall" class="chk_all fregister_agree">
            <input type="checkbox" name="chk_all" id="chk_all" onclick="checkAll()" class="selec_chk">
            <label for="chk_all"><span></span>전체동의</label>
        </div>
        
        <section id="fregister_term" class="fregister_agree">
            <input type="checkbox" name="agree" value="1" id="agree11" class="selec_chk check_select">
            <label for="agree11"><span></span>(필수) 레드토이 이용약관<b class="sound_only">회원가입약관의 내용에 동의합니다.</b></label>
            <span onclick="openPolicy(1)">[ 전체보기 ]</span>
            <div class="policy1">
                <textarea readonly><?php echo get_text($config['cf_stipulation']) ?></textarea>
            </div>
        </section>

        <section id="fregister_private" class="fregister_agree">
            <input type="checkbox" name="agree2" value="1" id="agree21" class="selec_chk check_select">
            <label for="agree21"><span></span>(필수) 개인정보처리방침안내<b class="sound_only">개인정보처리방침안내의 내용에 동의합니다.</b></label>
            <span onclick="openPolicy(2)">[ 전체보기 ]</span>
            <div class="tbl_head01 tbl_wrap policy2">
                <p>목적 - 이용자 식별 및 본인여부 확인, 고객서비스 이용에 관한 통지, CS대응을 위한 이용자 식별<br>
                    수집항목 - 아이디, 이름, 비밀번호, 연락처, 이메일<br>보유기간 - 회원 탈퇴 시까지</p>
            </div>
        </section>

        <div id="register_certi">
            <h2>본인 인증</h2>
            <section>
                <div>
                    <input type="radio" id="certi1" name="regi_certi" value="1" checked>
                    <span></span>
                    <label for="certi1">휴대폰 본인 인증</label>
                </div>
                <div>
                    <input type="radio" id="certi2" name="regi_certi" value="2">
                    <span></span>
                    <label for="certi2">아이핀 본인 인증</label>                   
                </div>
            </section>
        </div>
    </form>
    <div class="btn_confirm">
        <button class="btn_submit" onclick="popup_cert_select()">다음 단계</button>
    </div>
    <?php
    // 소셜로그인 사용시 소셜로그인 버튼
    //@include_once(get_social_skin_path().'/social_register.skin.php');
    ?>
    <form name="form1"></form>

    <script>
    $(document).ready(() => {
        $(".check_select").on("click", () => {
            check_check();
        })
    });
    
    function popup_cert_select() {
        var checkval = $("input[name='regi_certi']:checked").val()
        popup_cert(checkval);
    }
    
    function check_check() {
        if (!$("#chk_all").prop("checked") && $("#agree11").prop("checked") && $("#agree21").prop("checked")) $("#chk_all").prop("checked", true)
        if (!$("#agree11").prop("checked") || !$("#agree21").prop("checked")) $("#chk_all").prop("checked", false)
    }
    
    function checkAll() {
        if (!$("#chk_all").prop("checked")) {
             $("#agree11").prop("checked", false)
             $("#agree21").prop("checked", false)
        } else {
             $("#agree11").prop("checked", true)
             $("#agree21").prop("checked", true)
        }
    }
    
    function fregister_submit(f)
    {
        return true;
    }
    
    function popup_cert(provider) {
        var f = document.fregister;
        if (!f.agree11.checked) {
            if (confirm("회원가입약관의 내용에 동의가 필요합니다. 동의하시겠습니까?")) {
                $("#chk_all").prop("checked", true)
                checkAll();
                popup_cert_select();
                return;
            } else {
                f.agree.focus();
                return;
            }
        }

        if (!f.agree21.checked) {
            if (confirm("개인정보처리방침안내의 내용에 동의가 필요합니다. 동의하시겠습니까?")) {
                $("#chk_all").prop("checked", true)
                checkAll();
                popup_cert_select();
                return;
            } else {
                f.agree2.focus();
                return;
            }
        }
        
        if (provider == "1") {
            <? if (is_mobile()) { ?>
            location.href = "/plugin/kcpcert_v2/kcpcert_form.php?pageType=register&provider=<?php echo $provider_name; ?>";
            <? } else { ?>
            var popupWindow = window.open("", "auth_popup", "width=430,height=590,scrollbar=yes");
            var form1 = document.form1;
            form1.action = "/plugin/kcpcert_v2/kcpcert_form.php?pageType=register&provider=<?php echo $provider_name; ?>";
            form1.method = "post";
            form1.target = "auth_popup";
            form1.submit();
            
            popupWindow.focus();
            <? } ?>
        } else {
            <? if (is_mobile()) { ?>
            location.href = "/plugin/okname/ipin3.php?provider=<?php echo $provider_name; ?>";
            <? } else { ?>
            var popupWindow = window.open("", "kcbPop", "left=200, top=100, status=0, width=450, height=550");
            var form1 = document.form1;
            form1.action = "/plugin/okname/ipin3.php?provider=<?php echo $provider_name; ?>";
            form1.method = "post";
            form1.target = "kcbPop";
            form1.submit();

            popupWindow.focus();
            <? } ?>
        }
        
        return false
    }

    function openPolicy(e){
        $(".policy"+e).slideToggle();
    }
    </script>

</div>
