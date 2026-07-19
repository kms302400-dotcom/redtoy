<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
$q = isset($_GET['q']) ? clean_xss_tags($_GET['q'], 1, 1) : '';
$is_pg_review_test_member = function_exists('redtoy_pg_review_is_test_member') && redtoy_pg_review_is_test_member();
function get_mshop_category($ca_id, $len)
{
    global $g5;

    $sql = " select ca_id, ca_name from {$g5['g5_shop_category_table']}
                where ca_use = '1' ";
    if($ca_id)
        $sql .= " and ca_id like '$ca_id%' ";
    $sql .= " and length(ca_id) = '$len' order by ca_order, ca_id ";

    return $sql;
}

$mshop_categories = get_shop_category_array(true);
?>

<div id="menu">
    <button type="button" class="menu_close"><span class="sound_only">카테고리닫기</span></button>
    <div id="category" class="menu">
        <div class="menu_wr">

            <ul class="cate_tab" style="border-bottom:1px solid #ddd;">
                <li class="tab-btn active">카테고리</li>
                <?php if (!$is_pg_review_test_member) { ?>
                <li class="tab-btn">오늘 본 상품</li>
                <?php } ?>
                <li class="tab-btn">게시판</li>
            </ul>

            <ul class="content tab-con show">
                <li id="cate_01" class="con">
                    <?php
                    $i = 0;
                    foreach($mshop_categories as $cate1){
                    if( empty($cate1) ) continue;

                    $mshop_ca_row1 = $cate1['text'];
                    if($i == 0) {
                        echo '<ul class="cate">'.PHP_EOL;
                        echo '<li><a href="/theme/buzinga/mobile/shop/top100.php">Top100</a>'.PHP_EOL;
                        if (!$is_pg_review_test_member) {
                            echo '<button class="sub_ct_toggle ct_op">Top100 하위분류 열기</button>'.PHP_EOL;
                            echo '<ul class="sub_cate sub_cate1">'.PHP_EOL;
                            echo '<li><a href="/theme/buzinga/mobile/shop/topman.php">남성토이</a></li>'.PHP_EOL;
                            echo '<li><a href="/theme/buzinga/mobile/shop/topwoman.php">여성토이</a></li>'.PHP_EOL;
                            echo '<li><a href="/theme/buzinga/mobile/shop/topanal.php">애널토이</a></li>'.PHP_EOL;
                            echo '<li><a href="/theme/buzinga/mobile/shop/topcouple.php">커플토이</a></li>'.PHP_EOL;
                            echo '</ul>'.PHP_EOL;
                        }
                        echo '</li>'.PHP_EOL;
                    }
                    ?>
                <li>
                    <a href="<?php echo $mshop_ca_row1['url']; ?>"><?php echo get_text($mshop_ca_row1['ca_name']); ?></a>
                    <?php
                    if( count($cate1) > 1 )
                        echo '<button class="sub_ct_toggle ct_op">'.get_text($mshop_ca_row1['ca_name']).' 하위분류 열기</button>'.PHP_EOL;

                    $j=0;
                    foreach($cate1 as $key=>$cate2){
                    if( empty($cate2) || $key === 'text' ) continue;

                    $mshop_ca_row2 = $cate2['text'];
                    if($j == 0)
                        echo '<ul class="sub_cate sub_cate1">'.PHP_EOL;
                    ?>
                <li>
                    <a href="<?php echo $mshop_ca_row2['url']; ?>"><?php echo get_text($mshop_ca_row2['ca_name']); ?></a>
                    <?php
                    $mshop_ca_res3 = sql_query(get_mshop_category($mshop_ca_row2['ca_id'], 6));
                    if( count($cate2) > 1 )
                        echo '<button type="button" class="sub_ct_toggle ct_op">'.get_text($mshop_ca_row2['ca_name']).' 하위분류 열기</button>'.PHP_EOL;

                    $k = 0;
                    foreach($cate2 as $cate3_key=>$cate3){
                    if( empty($cate2) || $cate3_key === 'text' ) continue;

                    $mshop_ca_row3 = $cate3['text'];
                    if($k == 0)
                        echo '<ul class="sub_cate sub_cate2">'.PHP_EOL;
                    ?>
                <li>
                    <a href="<?php echo $mshop_ca_row3['url']; ?>"><?php echo get_text($mshop_ca_row3['ca_name']); ?></a>
                    <?php
                    $mshop_ca_res4 = sql_query(get_mshop_category($mshop_ca_row3['ca_id'], 8));
                    if(sql_num_rows($mshop_ca_res4))
                        echo '<button type="button" class="sub_ct_toggle ct_op">'.get_text($mshop_ca_row3['ca_name']).' 하위분류 열기</button>'.PHP_EOL;

                    for($m=0; $mshop_ca_row4=sql_fetch_array($mshop_ca_res4); $m++) {
                    if($m == 0)
                        echo '<ul class="sub_cate sub_cate3">'.PHP_EOL;
                    ?>
                <li>
                    <a href="<?php echo $mshop_ca_href.$mshop_ca_row4['ca_id']; ?>"><?php echo get_text($mshop_ca_row4['ca_name']); ?></a>
                    <?php
                    $mshop_ca_res5 = sql_query(get_mshop_category($mshop_ca_row4['ca_id'], 10));
                    if(sql_num_rows($mshop_ca_res5))
                        echo '<button type="button" class="sub_ct_toggle ct_op">'.get_text($mshop_ca_row4['ca_name']).' 하위분류 열기</button>'.PHP_EOL;

                    for($n=0; $mshop_ca_row5=sql_fetch_array($mshop_ca_res5); $n++) {
                    if($n == 0)
                        echo '<ul class="sub_cate sub_cate4">'.PHP_EOL;
                    ?>
                <li>
                    <a href="<?php echo $mshop_ca_href.$mshop_ca_row5['ca_id']; ?>"><?php echo get_text($mshop_ca_row5['ca_name']); ?></a>
                </li>
                <?php
                }

                if($n > 0)
                    echo '</ul>'.PHP_EOL;
                ?>
                </li>
                <?php
                }

                if($m > 0)
                    echo '</ul>'.PHP_EOL;
                ?>
                </li>
                <?php
                $k++;
                }

                if($k > 0)
                    echo '</ul>'.PHP_EOL;
                ?>
                </li>
                <?php
                $j++;
                }

                if($j > 0)
                    echo '</ul>'.PHP_EOL;
                ?>
                </li>
                <?php
                $i++;
                }   // end for

                if($i > 0) {
                    if (!$is_pg_review_test_member) {
                        echo '<li><a href="/shop/type-5">할인특가</a></li>'.PHP_EOL;
                        echo '<li><a href="/shop/event.php?ev_id=1711342008">AV기획전</a></li>'.PHP_EOL;
                    }
                    echo '</ul>'.PHP_EOL;
                }
                else
                    echo '<p>등록된 분류가 없습니다.</p>'.PHP_EOL;
                ?>
                </li>
            </ul>

            <?php if (!$is_pg_review_test_member) { ?>
            <ul class="tab-con">
                <li class="con"><?php include(G5_MSHOP_SKIN_PATH.'/boxtodayview.skin.php'); // 오늘 본 상품 ?></li>
            </ul>
            <?php } ?>

            <ul class="tab-con">
                <li class="con">
                    <ul id="hd_tnb" class="cate">
                        <?php if ($is_member) {  ?>
                            <li><a href="<?php echo G5_SHOP_URL; ?>/mypage.php">마이페이지</a></li>
                            <li><a href="<?php echo G5_SHOP_URL; ?>/cart.php">장바구니</a></li>
                        <?php } else {  ?>
                            <li><a href="<?php echo G5_SHOP_URL; ?>/cart.php">장바구니</a></li>
                        <?php } ?>
                        <li><a href="<?php echo G5_SHOP_URL; ?>/couponzone.php">쿠폰존</a></li>
                        <li><a href="<?php echo G5_BBS_URL; ?>/qalist.php">1:1문의</a></li>
                        <?php if (!$is_pg_review_test_member) { ?><li><a href="<?php echo G5_SHOP_URL; ?>/itemuselist.php">리얼리뷰</a></li><?php } ?>
                        <li><a href="/magazine">매거진</a></li>
                        <?php if (!$is_pg_review_test_member) { ?><li><a href="/event">이벤트</a></li><?php } ?>
                        <li><a href="/notice">공지사항</a></li>
                        <li><a href="<?php echo G5_BBS_URL; ?>/faq.php">고객센터</a></li>
                        <li><a href="<?php echo G5_SHOP_URL; ?>/orderinquiry.php">배송조회</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
    jQuery(function ($){

        $("button.sub_ct_toggle").on("click", function() {
            var $this = $(this);
            $sub_ul = $(this).closest("li").children("ul.sub_cate");

            if($sub_ul.size() > 0) {
                var txt = $this.text();

                if($sub_ul.is(":visible")) {
                    txt = txt.replace(/닫기$/, "열기");
                    $this
                        .removeClass("ct_cl")
                        .text(txt);
                } else {
                    txt = txt.replace(/열기$/, "닫기");
                    $this
                        .addClass("ct_cl")
                        .text(txt);
                }

                $sub_ul.toggle();
            }
        });
    });

    // 탭메뉴 구성 js
    //함수 호출 반복문
    for(let i = 0; i < $('.tab-btn').length; i++){
        tabOpen(i);
    }

    //함수에 보관
    function tabOpen(e){
        $('.tab-btn').eq(e).click(function(){
            $('.tab-btn').removeClass('active');
            $('.tab-con').removeClass('show');
            $('.tab-btn').eq(e).addClass('active');
            $('.tab-con').eq(e).addClass('show');
        });
    }
</script>
