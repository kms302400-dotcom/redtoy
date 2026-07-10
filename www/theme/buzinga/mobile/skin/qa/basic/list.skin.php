<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$qa_skin_url.'/style.css">', 0);
?>

<style>
    #container {max-width:none;padding:0;}
    #container_title {display:none}
</style>
<!-- 1:1 문의하기 -->
<div id="mp_top">
    <h1>마이 페이지</h1>
    <!-- 마이페이지 상단 공통 -->
    <ul class="mp_info">
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/couponzone.php">            
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_coupon_icon.png" alt="">
                <p>쿠폰 <span><?=number_format(get_coupon_count($member["mb_id"]))?></span> 장</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage3.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_point_icon.png" alt="">
                <p>포인트 <span><?=number_format(get_point_sum($member["mb_id"]))?></span> P</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/wishlist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_wish_icon.png" alt="">
                <p>찜한 상품 <span><?=number_format(get_wishlist_datas_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
        <li>
            <a href="/bbs/qalist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_qa_icon.png" alt="">
                <p>1:1 문의 <span><?=number_format(get_qna_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
    </ul>
</div>
</div>

<div id="bo_list">
    <div id="tab_menu">
        <ul>
            <li><a href="/shop/mypage.php">주문 배송 조회</a></li>
            <li><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage2.php">회원 정보 수정</a></li>
            <li><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage4.php">리뷰 관리</a></li>
            <li class="active"><a href="/bbs/qalist.php">1:1 문의</a></li>
        </ul>
    </div>
    <div id="qa_wrap">

        <?php if ($category_option) { ?>
        <!-- 카테고리 사용 안함 { -->
        <!-- <nav id="bo_cate">
            <h2><?php echo $qaconfig['qa_title'] ?> 카테고리</h2>
            <ul id="bo_cate_ul">
                <?php echo $category_option ?>
            </ul>
        </nav> -->
        <!-- } 카테고리 끝 -->
        <?php } ?>
        
        <!-- 게시판 페이지 정보 및 버튼 시작 { -->
        <div class="bo_top_option">    
            <!-- <div id="bo_list_total">
                <span>전체 <?php echo number_format($total_count) ?>건</span>
                <span style="margin: 0 3px;">/</span>
                <?php echo $page ?> 페이지
            </div> -->
            <?php if ($admin_href || $write_href) { ?>
            <ul class="btn_top top">
                <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin">관리자</a></li><?php } ?>
                <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b02">1:1 문의하기</a></li><?php } ?>
            </ul>
            <?php } ?>
        </div>
        <!-- } 게시판 페이지 정보 및 버튼 끝 -->
    
        <form name="fqalist" id="fqalist" action="./qadelete.php" onsubmit="return fqalist_submit(this);" method="post">
        <input type="hidden" name="stx" value="<?php echo $stx; ?>">
        <input type="hidden" name="sca" value="<?php echo $sca; ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
        
        <ul class="list_tit">
            <li class="tit_date">일시</li>
            <li class="tit_cate">분류</li>
            <li class="tit_title">제목</li>
            <li class="tit_stat">문의상태</li>
        </ul>

        <div class="list_01">
            <ul>
                <!--<li class="li_all_chk">
                    제목
                    <?php /*if ($is_checkbox) { ?>
                    <div>
                        <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);">
                        <label for="chkall"><span class="sound_only">현재 페이지 게시물 </span>전체선택</label>
                    </div>
                    <?php }*/ ?>
                </li>-->
    
                <!-- 게시판 검색 사용안함 { -->
                <!-- <fieldset id="bo_sch">
                    <legend>게시물 검색</legend>
                    <form name="fsearch" method="get">
                        <input type="hidden" name="sca" value="<?php echo $sca ?>">
                        <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
                        <div class="sch_area">
                            <input type="text" name="stx" value="<?php echo stripslashes($stx) ?>" required id="stx" class="sch_input" placeholder="검색어를 입력하세요" size="15" maxlength="15">
                            <button type="submit" value="검색" class="sch_btn"><i class="fa fa-search" aria-hidden="true"></i> <span class="sound_only">검색</span></button>
                        </div>
                    </form>
                </fieldset> -->
                <!-- } 게시판 검색 끝 -->
    
                <?php
                for ($i=0; $i<count($list); $i++) {
                ?>
                <li class="bo_li<?php if ($is_checkbox) echo ' bo_adm'; ?>">
                    <div class="li_date"><?php echo date("Y.m.d",strtotime($list[$i]['date'])) ?></div>
                    <div class="li_cate">[&#8201;<?php echo $list[$i]['category']; ?>&#8201;]</div>
                    <div class="li_title">
                        <a href="<?php echo $list[$i]['view_href']; ?>" class="li_sbj">
                            <?php echo $list[$i]['subject']; ?>
                            <?php if ($list[$i]['icon_file']) echo " <i class=\"fa fa-download\" aria-hidden=\"true\"></i>" ; ?>
                        </a>
                    </div>
                    <div class="li_stat <?php echo ($list[$i]['qa_status'] ? 'txt_done' : 'txt_rdy'); ?>"><?php echo ($list[$i]['qa_status'] ? '답변 완료' : '답변 대기'); ?></div>
                    
                    <!-- <div class="li_info">
                        <span><?php echo $list[$i]['name']; ?></span>
                    </div> -->
                </li>
                <?php
                }
                ?>
                <?php if ($i == 0) { echo '<li class="empty_list">게시물이 없습니다.</li>'; } ?>
            </ul>
        </div>
        
        <div class="bo_fx">
            <ul class="btn_bo_adm">
                <li class="page_nav">
                    <?php echo $list_pages;  ?>
                </li>
            </ul>
        </div>
        </form>

    </div>
</div>

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
    var f = document.fqalist;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_qa_id[]")
            f.elements[i].checked = sw;
    }
}

function fqalist_submit(f) {
    var chk_count = 0;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_qa_id[]" && f.elements[i].checked)
            chk_count++;
    }

    if (!chk_count) {
        alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택삭제") {
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다"))
            return false;
    }

    return true;
}
</script>
<?php } ?>
<!-- } 게시판 목록 끝 -->