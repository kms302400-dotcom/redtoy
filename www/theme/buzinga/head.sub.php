<?php
// 이 파일은 새로운 파일 생성시 반드시 포함되어야 함
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5_debug['php']['begin_time'] = $begin_time = get_microtime();

if (!isset($g5['title'])) {
    $g5['title'] = $config['cf_title'];
    $g5_head_title = $g5['title'];
}
else {
    $g5_head_title = $g5['title']; // 상태바에 표시될 제목
    $g5_head_title .= " | ".$config['cf_title'];
}

$g5['title'] = strip_tags($g5['title']);
$g5_head_title = strip_tags($g5_head_title);

// 현재 접속자
// 게시판 제목에 ' 포함되면 오류 발생
$g5['lo_location'] = addslashes($g5['title']);
if (!$g5['lo_location'])
    $g5['lo_location'] = addslashes(clean_xss_tags($_SERVER['REQUEST_URI']));
$g5['lo_url'] = addslashes(clean_xss_tags($_SERVER['REQUEST_URI']));
if (strstr($g5['lo_url'], '/'.G5_ADMIN_DIR.'/') || $is_admin == 'super') $g5['lo_url'] = '';

/*
// 만료된 페이지로 사용하시는 경우
header("Cache-Control: no-cache"); // HTTP/1.1
header("Expires: 0"); // rfc2616 - Section 14.21
header("Pragma: no-cache"); // HTTP/1.0
*/
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<?php
if (G5_IS_MOBILE) {
    echo '<meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0,minimum-scale=0,maximum-scale=1.0,user-scalable=no">'.PHP_EOL;
    echo '<meta name="HandheldFriendly" content="true">'.PHP_EOL;
    echo '<meta name="format-detection" content="telephone=no">'.PHP_EOL;
	echo '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'.PHP_EOL;

    /*<!-- 🔹 파비콘 및 앱 아이콘 -->*/
    echo '<link rel="icon" href="/img/favicon.ico" type="image/x-icon">'.PHP_EOL;
    echo '<link rel="apple-touch-icon" href="/img/apple-touch-icon.png">'.PHP_EOL;
    echo '<link rel="icon" sizes="192x192" href="/img/redtoy_logo_192x192.png">'.PHP_EOL;
    echo '<link rel="icon" sizes="512x512" href="/img/redtoy_logo_512x512.png">'.PHP_EOL;
} else {
    echo '<meta http-equiv="imagetoolbar" content="no">'.PHP_EOL;
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
}

if($config['cf_add_meta'])
    echo $config['cf_add_meta'].PHP_EOL;
?>

    <?php

    /***************** redcomm_seo start*************/
    $it_id=trim($_GET['it_id']);
    $ca_id=trim($_GET['ca_id']);

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
    $row_table = sql_fetch($sql);

    if(!empty($row_table[1])){
        $sql = " select * from g5_redcomm_seo ";
        $redcomm_seo =  sql_fetch($sql);
        $google = $redcomm_seo['google'];
        $naver = $redcomm_seo['naver'];
        $seo_keyword = $redcomm_seo['seo_keyword'];

        if($redcomm_seo['seo_title'] != "") {
            $meta_title = $redcomm_seo['seo_title'];

            if(empty($it_id) && empty($ca_id)) {
                $g5_head_title = $redcomm_seo['seo_title'];
            }
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

    }

    if(!empty($it_id)){
        $sql="select it_name, it_8 , it_9, it_10  from g5_shop_item  where it_id='$it_id'";
        $row_item = sql_fetch($sql);
        if(!$row_item['it_8']) {
            $meta_title = $g5_head_title;
        } else {
            $meta_title = $row_item['it_8'];
            $g5_head_title = $row_item['it_8'];
        }

        if(!$row_item['it_9']) {
            // $meta_description = $g5_head_title;
        } else {
            $meta_description = $row_item['it_9'];
        }

        if(!$row_item['it_10']) {
            $meta_keyword = "";
        } else {
            $meta_keyword = $row_item['it_10'];
        }

    } else if(!empty($ca_id)){
        $sql="select ca_name, ca_8 , ca_9, ca_10  from g5_shop_category  where ca_id='$ca_id'";
        $row_category = sql_fetch($sql);

        if(!$row_category['ca_8']) {
            $meta_title = $g5_head_title;
        } else {
            $meta_title = $row_category['ca_8'];
            $g5_head_title = $row_category['ca_8'];
        }

        if(!$row_category['ca_9']) {
            // $meta_description = $g5_head_title;
        } else {
            $meta_description = $row_category['ca_9'];
        }

        if(!$row_category['ca_10']) {
            $meta_keyword = "";
        } else {
            $meta_keyword = $row_category['ca_10'];
        }
    }

    if($meta_description != "") {
        /* <!-- 🔹 기본 메타 --> */
        echo '<title>'.$g5_head_title.'</title>'.PHP_EOL;
        echo '<meta name="description" content="'.$meta_description.'">'.PHP_EOL;
        echo '<meta name="naver-site-verification" content="86c4ccb74ac094c5192dec4bbafd311fb7b8a1d8">'.PHP_EOL;
        echo '<link rel="canonical" href="https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] .'"/>'.PHP_EOL;
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
        echo '<meta property="og:image" content="'.$facebook_img.'">'.PHP_EOL;
        echo '<meta property="og:image:width" content="1200">'.PHP_EOL;
        echo '<meta property="og:image:height" content="630">'.PHP_EOL;
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
    //     echo '<meta name="twitter:image" content="'.$twitter_img.'">'.PHP_EOL;
    //     echo '<meta name="twitter:image:width" content="1024" />'.PHP_EOL;
    //     echo '<meta name="twitter:image:height" content="512" /'.PHP_EOL;
    // }

    // if ($seo_keyword != "") {
    //     echo "<meta name=\"keywords\" content=\"{$seo_keyword}\">".PHP_EOL;
    // }

    // if ($naver != "") {
    //     echo "<meta name=\"naver-site-verification\" content=\"{$naver}\">".PHP_EOL;
    // }

    // if ($google != "") {
    //     echo "<meta name=\"google-site-verification\" content=\"{$google}\">".PHP_EOL;
    // }
    // if ($canonical != "") {
    //     echo "<link rel=\"canonical\" href=\"{$canonical}\" />".PHP_EOL;
    // }
    /***************** redcomm_seo end *************/

    ?>


<?php
$shop_css = '';
if (defined('_SHOP_')) $shop_css = '_shop';
echo '<link rel="stylesheet" href="'.run_replace('head_css_url', G5_THEME_CSS_URL.'/'.(G5_IS_MOBILE?'mobile':'default').$shop_css.'.css?ver='.G5_CSS_VER, G5_THEME_URL).'">'.PHP_EOL;
?>
<!--[if lte IE 8]>
<script src="<?php echo G5_JS_URL ?>/html5.js"></script>
<![endif]-->
<script>
// 자바스크립트에서 사용하는 전역변수 선언
var g5_url       = "<?php echo G5_URL ?>";
var g5_bbs_url   = "<?php echo G5_BBS_URL ?>";
var g5_is_member = "<?php echo isset($is_member)?$is_member:''; ?>";
var g5_is_admin  = "<?php echo isset($is_admin)?$is_admin:''; ?>";
var g5_is_mobile = "<?php echo G5_IS_MOBILE ?>";
var g5_bo_table  = "<?php echo isset($bo_table)?$bo_table:''; ?>";
var g5_sca       = "<?php echo isset($sca)?$sca:''; ?>";
var g5_editor    = "<?php echo ($config['cf_editor'] && $board['bo_use_dhtml_editor'])?$config['cf_editor']:''; ?>";
var g5_cookie_domain = "<?php echo G5_COOKIE_DOMAIN ?>";
var g5_theme_shop_url = "<?php echo G5_THEME_SHOP_URL; ?>";
var g5_shop_url = "<?php echo G5_SHOP_URL; ?>";

<?php if(defined('G5_IS_ADMIN')) { ?>
var g5_admin_url = "<?php echo G5_ADMIN_URL; ?>";
<?php } ?>
</script>
<?php
add_javascript('<script src="'.G5_JS_URL.'/jquery-1.12.4.min.js"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery-migrate-1.4.1.min.js"></script>', 0);
if (defined('_SHOP_')) {
    if(!G5_IS_MOBILE) {
        add_javascript('<script src="'.G5_JS_URL.'/jquery.shop.menu.js?ver='.G5_JS_VER.'"></script>', 0);
    }
} else {
    add_javascript('<script src="'.G5_JS_URL.'/jquery.menu.js?ver='.G5_JS_VER.'"></script>', 0);
}
add_javascript('<script src="'.G5_JS_URL.'/common.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/wrest.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/placeholders.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/font-awesome/css/font-awesome.min.css">', 0);

if(G5_IS_MOBILE) {
    add_javascript('<script src="'.G5_JS_URL.'/modernizr.custom.70111.js"></script>', 1); // overflow scroll 감지
}

echo '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">';


if(!defined('G5_IS_ADMIN'))
    echo $config['cf_add_script'];
?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>

    <script>
        function WebpIsSupported(callback){
            // If the browser doesn't has the method createImageBitmap, you can't display webp format
            if(!window.createImageBitmap){
                callback(false);
                return;
            }

            // Base64 representation of a white point image
            var webpdata = 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoCAAEAAQAcJaQAA3AA/v3AgAA=';

            // Retrieve the Image in Blob Format
            fetch(webpdata).then(function(response){
                return response.blob();
            }).then(function(blob){
                // If the createImageBitmap method succeeds, return true, otherwise false
                createImageBitmap(blob).then(function(){
                    callback(true);
                }, function(){
                    callback(false);
                });
            });
        }

        window.onload = () => {
            WebpIsSupported((isSupportWebP) => {
                if (!isSupportWebP) {
                    const container = document.querySelector('.container');
                    container.classList.remove('support-webp');
                }
            });
        }
    </script>

</head>
<body<?php echo isset($g5['body_script']) ? $g5['body_script'] : ''; ?>>
<?php
if ($is_member) { // 회원이라면 로그인 중이라는 메세지를 출력해준다.
    $sr_admin_msg = '';
    if ($is_admin == 'super') $sr_admin_msg = "최고관리자 ";
    else if ($is_admin == 'group') $sr_admin_msg = "그룹관리자 ";
    else if ($is_admin == 'board') $sr_admin_msg = "게시판관리자 ";

    echo '<div id="hd_login_msg">'.$sr_admin_msg.get_text($member['mb_nick']).'님 로그인 중 ';
    echo '<a href="'.G5_BBS_URL.'/logout.php">로그아웃</a></div>';
}
?>