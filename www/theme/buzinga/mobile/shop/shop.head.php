<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

include_once(G5_THEME_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/connect.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');
include_once(G5_LIB_PATH.'/latest.lib.php');

add_javascript('<script src="'.G5_THEME_JS_URL.'/owl.carousel.min.js"></script>', 10);
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_JS_URL.'/owl.carousel.css">', 0);


add_javascript('<script src="'.G5_THEME_JS_URL.'/jquery.sidr.min.js"></script>', 0);
add_javascript('<script src="'.G5_THEME_JS_URL.'/unslider.min.js"></script>', 10);
$q = isset($_GET['q']) ? clean_xss_tags($_GET['q'], 1, 1) : '';
?>

<!-- <div class="swiper top_box">
    <div class="swiper-wrapper">
        <div class="swiper-slide slide_box" style="line-height: 35px;">지금 가입하고 <span class="cy fw600">적립금 1천원<i>!</i></span> 첫구매 혜택 받기</div>
        <div class="swiper-slide slide_box02" style="line-height: 35px;">레드토이 <span class="fw600">REVIEW EVENT</span> 진행중<i>!</i></div>
    </div>
</div>

<script defer>
    var swiper = new Swiper(".top_box", {
        direction: "vertical",
        autoplay: {
            delay: 2500,
        },
    });
</script> -->

<header id="hd">
    <?php if ((!$bo_table || $w == 's' ) && defined('_INDEX_')) { ?><h1><?php echo $config['cf_title'] ?></h1><?php } ?>

    <div id="skip_to_container"><a href="#container">본문 바로가기</a></div>

    <?php if(defined('_INDEX_')) { // index에서만 실행
        include G5_MOBILE_PATH.'/newwin.inc.php'; // 팝업레이어
    } ?>

    <div id="hd_wr">
        <div class="pc_menu_bar pc">
            <div class="menu_bar_wrap">
                <ul>
                    <li><a href="/notice">공지사항</a></li>
                    <li><a href="/magazine">매거진</a></li>
                    <li><a href="<?php echo G5_SHOP_URL; ?>/couponzone.php">쿠폰존</a></li>
                </ul>
    
                <ul class="menu_r">
                    <?php if ($is_member) {  ?>
                        <li><a href=""><span class="b_txt"><?php echo $member['mb_id'] ? $member['mb_name'] : '비회원'; ?></span>님</a></li>
                        <li><a href="<?php echo G5_BBS_URL ?>/logout.php?url=shop">로그아웃</a></li>
                    <?php } else {  ?>
                        <li><a href="/bbs/login.php">로그인</a></li>
                        <li><a href="/bbs/register.php">회원가입</a></li>
                    <?php } ?>
                    <li><a href="<?php echo G5_BBS_URL; ?>/member_online_coupon.php" target="_blank" id="login_password_lost">쿠폰등록</a></li>
                    <li><a href="/bbs/faq.php">고객센터</a></li>
                    <li><a href="<?php echo G5_SHOP_URL; ?>/orderinquiry.php">배송조회</a></li>
                </ul>
            </div>
        </div>
    	<div id="hd_wr_inner">
	        <div id="logo"><a href="<?php echo G5_SHOP_URL; ?>/"><img src="/data/common/mobile_logo_img" alt="<?php echo $config['cf_title']; ?> 메인"></a></div>
			
            <div class="pc_menu">
                <ul>
                    <li><a href="/shop/type-5">할인특가</a></li>
                    <!-- <li><a href="/shop/type-3">신상품</a></li> -->
                    <li><a href="/shop/event.php?ev_id=1711342008">AV기획전</a></li>
                    <li><a href="/shop/itemuselist.php">리얼리뷰</a></li>
                    <li><a href="/event">이벤트</a></li>
                </ul>
            </div>

            <div class="mo_menu">
                <div id="btn_menu"></div>
            </div> 
            
			<div id="hd_btn">
                <div class="hd_right_btn">
                    <!-- <?php if ($is_member) {  ?>
                        <div class="btn_align">
                            <button class="member_toggle tnb_btn" onclick="location.href='<?php echo G5_SHOP_URL; ?>/mypage.php'"><span class="material-symbols-outlined">person</span></button>
                        </div>
                    <?php } else {  ?>
                        <a href="<?php echo G5_BBS_URL ?>/login.php" class="join_btn">
                            <span class="material-symbols-outlined">person</span><span class="sound_only">로그인</span>
                            <div id="animated-example" class="animated2 bounce2">+1000</div>
                        </a>
                    <?php } ?> -->
                    
					<div class="hd_search btn_align">                        
                        <!-- PC용 검색버튼 -->
                        <div class="search_wrap">
                            <form name="frmsearch1" action="<?php echo G5_SHOP_URL; ?>/search.php" onsubmit="return search_submit(this);">
                                <label for="p_sch_str" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
                                <input type="text" name="q" placeholder="검색어를 입력하세요!" value="<?php echo stripslashes(get_text(get_search_string($q))); ?>" id="p_sch_str" required>
                                <button type="submit" id="p_sch_submit"><span class="sound_only">검색</span></button>
                            </form>
                        </div>
                        
                        <!-- 모바일용 검색버튼 -->
                        <button class="search_toggle tnb_btn">
                            <!-- <span class="material-symbols-outlined">search</span> -->
                            <span class="sound_only">검색창 열기</span>
                        </button>
                        <!-- 검색창 -->
			            <div class="tnb_con">
			            	<h3>Search</h3>
				            <form name="frmsearch1" action="<?php echo G5_SHOP_URL; ?>/search.php" onsubmit="return search_submit(this);">
                                <label for="sch_str" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
                                <input type="text" name="q" value="<?php echo stripslashes(get_text(get_search_string($q))); ?>" id="sch_str" required>
                                <button type="submit" id="sch_submit"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">검색</span></button>
				            </form>
			            	<?php echo popular('theme/basic'); ?>
			            	<button type="button" class="btn_close"><i class="fa fa-times"></i><span class="sound_only">쇼핑몰 검색 닫기</span></button>
			            </div>

			            <script>
			            function search_submit(f) {
			                if (f.q.value.length < 2) {
			                    alert("검색어는 두글자 이상 입력하십시오.");
			                    f.q.select();
			                    f.q.focus();
			                    return false;
			                }
			                return true;
			            }
			            </script>
			        </div>

                    <a href="<?php echo G5_SHOP_URL; ?>/mypage.php" class="mypage">
                        <span class="sound_only">마이페이지</span>
                    </a>

                    <a href="<?php echo G5_SHOP_URL; ?>/cart.php" class="cart">
                        <!-- <span class="material-symbols-outlined">shopping_bag</span> -->
                        <span class="sound_only">장바구니</span>
                        <span class="cart-count">
                            <?php echo get_boxcart_datas_count(); ?>
                        </span>
                    </a>

                </div>
	        </div>
        </div>
        <div class="menu_cate"><?php include_once(G5_MSHOP_SKIN_PATH.'/boxcategory.skin.php'); // 상단 카테고리 분류 ?></div>
    </div>



    <?php if ($is_admin) { ?>
        <div class="hd_admin">
            <a href="<?php echo G5_ADMIN_URL; ?>" target="_blank">관리자</a>
            <a href="<?php echo G5_THEME_ADM_URL ?>" target="_blank">테마관리</a>
        </div>
    <?php } ?>


    <script>
    // $( document ).ready( function() {
    //     var jbOffset = $( '.menu_cate' ).offset();
    //     $( window ).scroll( function() {
    //         if ( $( document ).scrollTop() > jbOffset.top ) {
    //             $( '.menu_cate' ).addClass( 'fixed' );
    //         }
    //         else {
    //             $( '.menu_cate' ).removeClass( 'fixed' );
    //         }
    //     });
    // });

    $(document).ready(function() {
      $('#btn_hdcate, #btn_menu, .menu_close').sidr();
    });

	$(".hd_right_btn .btn_close").click(function(e) {
        console.log("검색창 안에서 닫기");
        
		$(".tnb_con").hide();
	});

	$(".hd_right_btn .tnb_btn:not(:only-child)").click(function(e) {
        console.log("검색창 토글버튼");
        
	    $(this).siblings(".tnb_con").toggle();
	
	    $(".tnb_con").not($(this).siblings()).hide();
	    e.stopPropagation();
	
	    $("#wrapper").on("click", function() {
	        $(".tnb_con").hide();
	    });
	});

    $(function(){
        $("#tnb_close").on("click", function() {
            set_cookie("ck_top_banner_close", 1, 24, g5_cookie_domain);
            $(".tnb_wrap").hide();
        });
    });
   </script>
</header>

<div id="wrapper">
	<div id="container">
	    <?php if ((!$bo_table || $w == 's' ) && !defined('_INDEX_')) { ?><h1 id="container_title"><?php echo $g5['title'] ?></h1><?php } ?>
