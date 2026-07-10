<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>

<script src="<?php echo G5_JS_URL ?>/jquery.fancylist.js"></script>
<?php if($config['cf_kakao_js_apikey']) { ?>
<script src="https://developers.kakao.com/sdk/js/kakao.min.js"></script>
<script src="<?php echo G5_JS_URL; ?>/kakaolink.js"></script>
<script>
    // 사용할 앱의 Javascript 키를 설정해 주세요.
    Kakao.init("<?php echo $config['cf_kakao_js_apikey']; ?>");
</script>
<?php } ?>

<!-- 상품진열 10 시작 { -->
<div class="relation_wr swiper">
    <?php
    $li_width = intval(100 / $this->list_mod);
    $li_width_style = ' style="width:'.$li_width.'%;"';

    for ($i=0; $row=sql_fetch_array($result); $i++) {

        $item_link_href = shop_item_url($row['it_id']);
        if ($i == 0) {
            if ($this->css) {
                echo "<ul id=\"sct_wrap\" class=\"swiper-wrapper {$this->css}\">\n";
            } else {
                echo "<ul id=\"sct_wrap\" class=\"srl_10 swiper-wrapper\">\n";
            }
        }

        if($i % $this->list_mod == 0)
            $li_clear = ' sct_clear';
        else
            $li_clear = '';

        echo "<li class=\"sct_li{$li_clear} swiper-slide\"$li_width_style>\n";

        if ($this->href) {
            echo "<div class=\"sct_img\"><a href=\"{$item_link_href}\">\n";
        }

        if ($this->view_it_img) {
            echo get_it_image($row['it_id'], $this->img_width, $this->img_height, '', '', stripslashes($row['it_name']))."\n";
        }

        if ($this->href) {
            echo "</a></div>\n";
        }


        if ($this->view_it_id) {
            echo "<div class=\"sct_id\">&lt;".stripslashes($row['it_id'])."&gt;</div>\n";
        }

        if ($this->href) {
            echo "<a href=\"{$item_link_href}\" class=\"sct_txt\">\n";
        }

        if ($this->view_it_name) {
            echo stripslashes($row['it_name'])."\n";
        }

        if ($this->href) {
            echo "</a>\n";
        }

        echo "<div class=\"sct_basic\">".cut_str(get_text($row['it_basic']), 55)."</div>\n";

        if ($this->view_it_cust_price || $this->view_it_price) {

            echo "<div class=\"sct_cost\">\n";
            /* 시중가가 등록안되어있을 경우 0이 아니라면 %계산, 0이라면 pass */
            if($row['it_cust_price']!=0){
                $sale_per=ceil((($row['it_cust_price']-get_price($row))/$row['it_cust_price'])*100).'% Sale';
            }
            if ($this->view_it_cust_price && $row['it_cust_price']) {
                echo "<strike><span class='sct_cust_price'>".display_price($row['it_cust_price'])."</span></strike>"."<span class='sct_sale_per'> $sale_per </span></br>\n"; }


            if ($this->view_it_price) {
                echo display_price(get_price($row), $row['it_tel_inq'])."\n";
            }

            if($total_count > 0 && $rows > 0) {
                $total_page = ceil($total_count / $rows);
            } else {
                $total_page = 0;
            }

            echo "</div>\n";

        }

        /* 아이콘 */
        echo "<div class=\"sct_icon_wr\" style='text-align: left;'>".item_icon2($row)."</div>\n";
        if ($this->view_it_icon) {
            // 품절
            if ($is_soldout) {
                echo '<span class="shop_icon_soldout h160"><span class="soldout_txt">SOLD OUT</span></span>';
            }
        }

        echo "</li>\n";
    }

    if ($i > 0) echo "</ul>\n";

    if($i == 0) echo "<p class=\"sct_noitem\">등록된 관련상품이 없습니다.</p>\n";
    ?>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
</div>

<script>
    var swiper = new Swiper(".relation_wr", {
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        pagination: {
            el: ".swiper-pagination",
        },
        slidesPerView: 2,  //브라우저가 768보다 클 때
        slidesPerGroup : 2, // 그룹으로 묶을 수
        breakpoints: {

            768: {
                slidesPerView: 3,  //브라우저가 768보다 클 때
                spaceBetween: 10,
                slidesPerGroup : 3, // 그룹으로 묶을 수
            },
            1024: {
                slidesPerView: 4,  //브라우저가 1024보다 클 때
                spaceBetween: 10,
                slidesPerGroup : 4, // 그룹으로 묶을 수
            },
        },
    });
</script>
<!-- } 상품진열 10 끝 -->
