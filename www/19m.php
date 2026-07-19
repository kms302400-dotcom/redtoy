<?php
include("./common.php");

if (!function_exists('redtoy_validate_adult_return_url')) {
    function redtoy_validate_adult_return_url($url)
    {
        if (!is_string($url)) {
            return '/';
        }

        $url = trim($url);
        $decoded_url = rawurldecode($url);
        if ($url === ''
            || preg_match('#^https?://#i', $url)
            || substr($url, 0, 1) !== '/'
            || substr($url, 0, 2) === '//'
            || substr($decoded_url, 0, 2) === '//'
            || strpos($url, '\\') !== false
            || strpos($decoded_url, '\\') !== false
            || preg_match('/[\x00-\x1F\x7F]/', $decoded_url)) {
            return '/';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return '/';
        }

        $normalized_segments = array();
        foreach (explode('/', rawurldecode($path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($normalized_segments);
                continue;
            }
            $normalized_segments[] = $segment;
        }

        $normalized_path = '/'.implode('/', $normalized_segments);
        if (preg_match('#^/(?:19_ok|19m)\.php(?:/|$)#i', $normalized_path)) {
            return '/';
        }

        return $url;
    }
}

if (!isset($g5['title'])) {
    $g5['title'] = $config['cf_title'];
    $g5_head_title = $g5['title'];
}
else {
    $g5_head_title = $g5['title']; // 상태바에 표시될 제목
    $g5_head_title .= " | ".$config['cf_title'];
}

// 이미 로그인했거나 성인인증이 완료된 사용자는 정상 메인으로 진입시킨다.
$reAdult = get_cookie('ss_cert_adult');
if ($is_member || get_session('ss_cert_adult') === 'OK' || $reAdult === 'OK') {
    if (!$is_member && get_session('ss_cert_adult') !== 'OK' && $reAdult === 'OK') {
        set_session('ss_cert_adult', 'OK');
    }
    goto_url(G5_URL.'/');
}

$cert_url = redtoy_validate_adult_return_url(isset($_GET['url']) ? $_GET['url'] : '/');
set_session('ss_cert_url', $cert_url);

$pg_review_company = function_exists('redtoy_pg_review_get_company_info') ? redtoy_pg_review_get_company_info() : array();
$display_company_name = $pg_review_company ? $pg_review_company['company_name'] : '(주)인센스글로벌';
$display_company_owner = $pg_review_company ? $pg_review_company['company_owner'] : $default['de_admin_company_owner'];
$display_company_address = $pg_review_company ? $pg_review_company['company_address'] : $default['de_admin_company_addr'];
$display_business_number = $pg_review_company ? $pg_review_company['business_number'] : $default['de_admin_company_saupja_no'];
$display_online_sales_number = $pg_review_company ? $pg_review_company['online_sales_number'] : $default['de_admin_tongsin_no'];
$display_privacy_officer = $pg_review_company ? $pg_review_company['privacy_officer'] : $default['de_admin_info_name'];
$display_email = $pg_review_company ? $pg_review_company['email'] : 'incense0523@gmail.com';
$display_customer_service = $pg_review_company ? $pg_review_company['customer_service'] : '02-6101-9272';
$display_fax = $pg_review_company ? $pg_review_company['fax'] : $default['de_admin_company_fax'];
$display_copyright = $pg_review_company ? $pg_review_company['copyright'] : '© 2024 REDCommerce. All Rights Reserved.';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">
<head>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=0,maximum-scale=1.0,user-scalable=no">
    <meta name="HandheldFriendly" content="true">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <!-- 🔹 파비콘 및 앱 아이콘 -->
    <link rel="icon" href="/img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
    <link rel="icon" sizes="192x192" href="/img/redtoy_logo_192x192.png">
    <link rel="icon" sizes="512x512" href="/img/redtoy_logo_512x512.png">
    <?php
    /* redcomm_seo_start */
    $meta_title = "";
    $meta_description = "";

    $canonical = G5_URL;
    $meta_keyword = "";
    $google = "";
    $naver = "";
    $facebook_img = "";
    $twitter_img = "";
    $seo_keyword ="";
    $sql= " SELECT 1 FROM information_schema.tables WHERE TABLE_NAME like 'g5_redcomm_seo'" ;
    $row = sql_fetch($sql);

    if(!empty($row[1])){
        $sql = " select * from g5_redcomm_seo ";
        $redcomm_seo =  sql_fetch($sql);
        $google = $redcomm_seo['google'];
        $naver = $redcomm_seo['naver'];
        $seo_keyword = $redcomm_seo['seo_keyword'];

        if($redcomm_seo['seo_title'] != "") {
            $meta_title = $redcomm_seo['seo_title'];
            $g5_head_title = $redcomm_seo['seo_title'];
        }

        if($redcomm_seo['seo_description'] != "") {
            $meta_description = $redcomm_seo['seo_description'];
        }

        if($redcomm_seo['facebook_img'] != "") {
            $facebook_img = G5_DATA_URL . "/common/". $redcomm_seo['facebook_img'] ;
        }

        if($redcomm_seo['twitter_img'] != "") {
            $twitter_img = G5_DATA_URL . "/common/". $redcomm_seo['twitter_img'] ;
        }
        if($redcomm_seo['canonical'] != "") {
            $canonical = $redcomm_seo['canonical'];
        }

        if($meta_description != "") {
            /* <!-- 🔹 기본 메타 --> */
            echo '<title>'.$g5_head_title.'</title>'.PHP_EOL;
            echo '<meta name="description" content="'.$meta_description.'">'.PHP_EOL;
            echo '<meta name="naver-site-verification" content="86c4ccb74ac094c5192dec4bbafd311fb7b8a1d8">'.PHP_EOL;
            echo '<link rel="canonical" href="https://www.redtoy.co.kr" />'.PHP_EOL;
        }


        if($meta_title != "") {
            /* <!-- 🔹 Open Graph (SNS) --> */
            echo '<meta property="og:type" content="website">'.PHP_EOL;
            echo '<meta property="og:locale" content="ko_KR">'.PHP_EOL;
            echo '<meta property="og:url" content="'.$canonical.'">'.PHP_EOL;
            echo '<meta property="og:title" content="'.$meta_title.'">'.PHP_EOL;
            if($meta_description != "") {
                echo '<meta property="og:description" content="'.$meta_description.'">'.PHP_EOL;
            }
            echo '<meta property="og:image" content="https://www.redtoy.co.kr/img/logo-og.png">'.PHP_EOL;
        }

        if ($facebook_img != "") {
            echo '    <meta property="og:image" content="' . $facebook_img.'">'.PHP_EOL;
            echo '    <meta property="og:image:width" content="1200">'.PHP_EOL;
            echo '    <meta property="og:image:height" content="630">'.PHP_EOL;
        }

        if($meta_title != "") {
            /* <!-- 🔹 Twitter --> */
            echo '<meta name="twitter:card" content="summary_large_image">'.PHP_EOL;
            echo '<meta name="twitter:title" content="레드토이 | 성인용품 전문 쇼핑몰">'.PHP_EOL;
            if($meta_description != "") {
                echo '<meta name="twitter:description" content="정품 성인용품 쇼핑몰 레드토이! 오나홀, 바이브레이터, 콘돔 등 다양한 제품을 만나보세요.">'.PHP_EOL;
            }
            echo '<meta name="twitter:image" content="https://www.redtoy.co.kr/img/logo-og.png">'.PHP_EOL;
        }

        /* <!-- 🔹 안드로이드 앱 정보 --> */
        echo '<meta name="theme-color" content="#D8204C">'.PHP_EOL;
        echo '<meta name="mobile-web-app-capable" content="yes">'.PHP_EOL;
        echo '<meta name="application-name" content="레드토이">'.PHP_EOL;
        echo '<link rel="alternate" href="android-app://kr.co.redtoy.app/https/www.redtoy.co.kr/">'.PHP_EOL;

        // if ($twitter_img != "") {
        //     echo '    <meta name="twitter:image" content="' . $twitter_img.'">'.PHP_EOL;
        //     echo '    <meta name="twitter:image:width" content="1024" />'.PHP_EOL;
        //     echo '    <meta name="twitter:image:height" content="512" />'.PHP_EOL;
        // }

        // if ($seo_keyword != "") {
        //     echo "    <meta name=\"keywords\" content=\"{$seo_keyword}\">".PHP_EOL;
        // }

        // if ($naver != "") {
        //     echo "    <meta name=\"naver-site-verification\" content=\"{$naver}\">".PHP_EOL;
        // }

        // if ($google != "") {
        //     echo "    <meta name=\"google-site-verification\" content=\"{$google}\">".PHP_EOL;
        // }
        // if ($canonical != "") {
        //     echo "<link rel=\"canonical\" href=\"{$canonical}\" />".PHP_EOL;
        // }

    }
    /* redcomm_seo_end */
    ?>

    <?php
    if($config['cf_add_meta'])
        echo $config['cf_add_meta'].PHP_EOL;
    ?>
    <link rel="stylesheet" type="text/css" href="/css/m_style.default.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo G5_THEME_CSS_URL?>/mobile_shop.css" />
    <link rel="stylesheet" href="<?php echo $member_skin_url; ?>/style.css">
    <script src="/js/jquery-1.8.3.min.js" type="text/javascript"></script>

    <!--주소창 위로 올리기-->
    <script>
        function hideAddressBar(){
            if(document.documentElement.scrollHeight<window.outerHeight/window.devicePixelRatio)
                document.documentElement.style.height=(window.outerHeight/window.devicePixelRatio)+'px';
            setTimeout(window.scrollTo(1,1),0);
        }
        window.addEventListener("load",function(){hideAddressBar();});
        window.addEventListener("orientationchange",hideAddressBar());
    </script>
    <script type="text/javascript">
        function jsSubmit() {
            <? if (is_mobile()) { ?>
            location.href = "/plugin/okname/ipinadult1.php";
            <? } else { ?>
            var popupWindow = window.open("", "kcbPop", "left=200, top=100, status=0, width=450, height=550");
            var form1 = document.form1;
            form1.action = "/plugin/okname/ipinadult1.php";
            form1.method = "post";
            form1.target = "kcbPop";
            form1.submit();

            popupWindow.focus();
            <? } ?>
        }

        function jsSubmit2() {
            <? if (is_mobile()) { ?>
            location.href = "/plugin/kcpcert_v2/kcpcert_form.php?pageType=main";
            <? } else { ?>
            var form1 = document.form1;
            window.open("", "auth_popup", "width=430,height=590,scrollbar=yes");
            form1.action = "/plugin/kcpcert_v2/kcpcert_form.php?pageType=main";
            form1.method = "post";
            form1.target = "auth_popup";
            form1.submit();
            <? } ?>
        }
    </script>
</head>
<body>

<!-- Adult certification conts -->
<div id="sub_wrap" >    
    <!-- <div id="adult_act"> -->
    <!-- <div id="logo">
        <img src="/data/common/mobile_logo_img">
    </div>                 -->
        
    <div id="adult_certify">
        <!-- PC배너 -->
        <img class="pc_banner" src="<?php echo G5_THEME_IMG_URL ?>/mobile/intro_info.png">
        <!-- 모바일 배너 -->
        <img class="mo_banner" src="<?php echo G5_THEME_IMG_URL ?>/mobile/intro_info_m.png">
        <div class="announcement">
            이 정보내용은 청소년유해매체물로서 정보통신망이용촉진 및 정보보호 등에
            <br>
            관한 법률 및 청소년보호법의 규정에 의하여 19세 미만의 청소년이 이용할 수 없습니다.
        </div>
    </div>

    <div id="certi_login">
        <div class="ad_wr">
            <div class="adult_certi">
                <div class="main_tit">비회원 성인인증</div>
                <!-- <span class="sub_tit">성인인증 후 레드토이 접속이 가능합니다.</span> -->

                <!-- <div class="ad_list">
                    <ul>
                        <li>아래 2가지 방법 중 하나로 <span class="ad_list_span">성인인증을 하신 후 서비스를 이용</span>하실 수 있습니다.</li>
                        <li>나이 및 본인 여부만 확인할 뿐 <span class="ad_list_span">개인정보는 저장되지 않습니다.</span></li>
                        <li>본인 인증 절차는 <span class="ad_list_span">과금이 전혀 청구되지 않습니다.</span></li>
                    </ul>
                </div> -->

                <form name="form1">
                    <div class="certi_frm">
                        <div class="btn_area3">
                            <a  href="javascript:jsSubmit2();" class="certi_phone ">휴대폰 인증</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div id="mb_login" class="certi_box mbskin">
                <div class="main_tit">회원 로그인</div>
                <!-- <span class="sub_tit">성인인증 후 레드토이 접속이 가능합니다.</span> -->

                    <!--<div class="ad_list">
                        <ul>
                            <li>레드토이 <span class="ad_list_span">아이디와 패스워드로 접속</span>하실 수 있습니다.</li>
                            <li>회원이 되시면 <span class="ad_list_span">적립금 · 사은품 · 이벤트 참여 등 다양한 혜택</span>이 있습니다.</li>
                            <li>성인인증은 <span class="ad_list_span">1년에 한 번만</span> 해주시면 됩니다. (레드토이 회원의 경우)</li>
                        </ul>
                    </div> -->

                <form name="flogin" action="/bbs/login_check.php" onsubmit="return flogin_submit(this);" method="post" id="flogin">
                    <input type="hidden" name="url" value="<?php echo get_session('ss_cert_url'); ?>">

                    <div id="login_frm">
                        <div class="login_area">
                            <div>
                                <label for="login_id" class="sound_only">아이디<strong class="sound_only"> 필수</strong></label>
                                <input type="text" name="mb_id" id="login_id" placeholder="ID" required class="frm_input required" maxLength="20">
                                <label for="login_pw" class="sound_only">비밀번호<strong class="sound_only"> 필수</strong></label>
                                <input type="password" name="mb_password" id="login_pw" placeholder="PW" required class="frm_input required" maxLength="20">
                            </div>
                            <button type="submit" class="btn_submit">로그인</button>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin:0;">
                            <div id="login_info" class="chk_box" style="display:flex;">
                                <input type="checkbox" name="auto_login" id="login_auto_login" class="selec_chk">
                                <label for="login_auto_login"><span></span>자동 로그인</label>
                            </div>
                            <div style="text-align:right"><a href="<?php echo G5_BBS_URL ?>/password_lost.php" id="login_password_lost" target="_blank" style="color: #676e70;">아이디/비밀번호 찾기</a></div>
                        </div>
                        <?php
                        // 소셜로그인 사용시 소셜로그인 버튼
                        @include_once(get_social_skin_path().'/social_login.skin.php');
                        ?>
                        <!-- <div class="join_wrap naver"><a href="#"><i></i> 네이버 로그인</a></div> -->
                        <div class="join_wrap"><a href="<?php echo G5_BBS_URL ?>/register.php">10초 안에 회원가입하기</a></div>
                    </div>

                </form>
            </div>

                <!-- <form name="flogin" action="/bbs/login_check.php" onsubmit="return flogin_submit(this);" method="post">
                    <input type="hidden" name="url" value="<?php echo get_session('ss_cert_url'); ?>">
                    <label><input type="text" class="ipt_style loginipt_style" name="mb_id" id="mb_id" placeholder="ID"/> </label>
                    <label><input type="password" class="ipt_style loginipt_style" name="mb_password" id="mb_password" placeholder="PW" /> </label>
                    <div class="btn_area"><a href="#" onclick="send()" class="btn_red90">로그인</a></div>
                </form>
                <div class="login_save">
                    <label class="check">
                        <input type="checkbox" name="id_save"  id="id_save" title="자동 로그인" /><span class="txt"> 자동 로그인</span>
                        <span class="txt"><a href="/bbs/password_lost.php" target="_blank"> 비밀번호 찾기</a></span>
                    </label>
                </div> -->

            
        </div>
    </div>
    
    <div id="intro_footer">
        <div class="intro_banner">
            <h2>레드토이에서만 드리는 신규회원 혜택!</h2>
            <!-- PC 배너 -->
            <img class="pc_banner" src="<?php echo G5_THEME_IMG_URL ?>/mobile/join_event_banner_b.png">
            <!-- 모바일 배너 -->
            <img class="mo_banner" src="<?php echo G5_THEME_IMG_URL ?>/mobile/join_event_banner_s.png">
        </div>
        
        <div class="certi_out_btn">
            <input name="submit22"  type="submit" class="btn certi_out" id="submit2"  value="19세 미만 나가기" onclick="window.open('http://www.daum.net')">
        </div>

        <div id="foot_info">
            <div id="ft_if" class="ft_con">
                <span><?php echo get_text($display_company_name); ?></span>
                <br>
                <span>대표자 : <?php echo get_text($display_company_owner); ?> | E-mail : <?php echo get_text($display_email); ?></span>
                <br>
                <span>주소 : <?php echo get_text($display_company_address); ?></span><br>
                <span>사업자등록번호 : <?php echo get_text($display_business_number); ?></span><br>
                <span>통신판매업신고번호 : <?php echo get_text($display_online_sales_number); ?></span><br>
                <span>개인정보 보호책임자 : <?php echo get_text($display_privacy_officer); ?></span><br>
                <span>팩스 : <?php echo get_text($display_fax); ?></span><br>
                <span>고객센터 <?php echo get_text($display_customer_service); ?> &emsp;※ 평일 10:00 - 18:00 (주말, 공휴일 휴무)</span><br><br>
                <span><?php echo get_text($display_copyright); ?></span>
            </div>        
        </div>
    </div>


</div>

<!-- 인증 -->
<form name="kcbOutForm" method="post">
    <input type="hidden" name="encPsnlInfo" />
    <input type="hidden" name="virtualno" />
    <input type="hidden" name="dupinfo" />
    <input type="hidden" name="realname" />
    <input type="hidden" name="cprequestnumber" />
    <input type="hidden" name="age" />
    <input type="hidden" name="sex" />
    <input type="hidden" name="nationalinfo" />
    <input type="hidden" name="birthdate" />
    <input type="hidden" name="coinfo1" />
    <input type="hidden" name="coinfo2" />
    <input type="hidden" name="ciupdate" />
    <input type="hidden" name="cpcode" />
    <input type="hidden" name="authinfo" />
</form>

<form name="kcbResultForm" method="post" >
    <input type="hidden" name="idcf_mbr_com_cd" 		value="" 	/>
    <input type="hidden" name="hs_cert_svc_tx_seqno" 	value=""	/>
    <input type="hidden" name="hs_cert_rqst_caus_cd" 	value="" 	/>
    <input type="hidden" name="result_cd" 				value="" 	/>
    <input type="hidden" name="result_msg" 				value="" 	/>
    <input type="hidden" name="cert_dt_tm" 				value="" 	/>
    <input type="hidden" name="di" 						value="" 	/>
    <input type="hidden" name="ci" 						value="" 	/>
    <input type="hidden" name="name" 					value="" 	/>
    <input type="hidden" name="birthday" 				value="" 	/>
    <input type="hidden" name="gender" 					value="" 	/>
    <input type="hidden" name="nation" 					value="" 	/>
    <input type="hidden" name="tel_com_cd" 				value="" 	/>
    <input type="hidden" name="tel_no" 					value="" 	/>
    <input type="hidden" name="return_msg" 				value="" 	/>
</form>

<script>
    function send(){
        var f = document.flogin;
        if(f.mb_id.value ==''){
            alert('아이디를 입력해주세요');
            f.mb_id.focus();
            return false;

        }
        if(f.mb_password.value ==''){
            alert('비밀번호를 입력해주세요');
            f.mb_password.focus();
            return false;

        }
        f.submit();
    }
</script>

</body>

</html>
