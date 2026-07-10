<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_THEME_LIB_PATH.'/theme.shop.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
add_javascript('<script src="'.G5_THEME_JS_URL.'/jquery.shop.list.js"></script>', 10);
?>
<style>
    /* #wrapper {background:#fff;border-bottom: 1px solid #eee;} */
</style>

<!-- 상품진열 10 시작 { -->
<?php
for ($i=1; $row=sql_fetch_array($result); $i++) {
    if ($this->list_mod >= 2) { // 1줄 이미지 : 2개 이상
        if ($i%$this->list_mod == 0) $sct_last = ' sct_last'; // 줄 마지막
        else if ($i%$this->list_mod == 1) $sct_last = ' sct_clear'; // 줄 첫번째
        else $sct_last = '';
    } else { // 1줄 이미지 : 1개
        $sct_last = ' sct_clear';
    }

    if ($i == 1) {
        if ($this->css) {
            echo "<ul class=\"{$this->css}\">\n";
        } else {
            echo "<ul class=\"sct sct_30\">\n";
        }
    }

    // 브랜드를 위한 상품 출력
    $it = get_shop_item($row['it_id']);

    echo "<li class=\"sct_li{$sct_last}\" style=\"width:{$this->img_width}px\">\n";

    if ($this->href) {
        echo "<div class=\"sct_img\"><a href=\"{$this->href}{$row['it_id']}\" class=\"sct_a\">\n";
    }

    if ($this->view_it_img) {
        //echo get_it_image($row['it_id'], $this->img_width, $this->img_height, '', '', stripslashes($row['it_name']))."\n";
        //get it imag 변경
        echo get_it_image($row['it_id'], $this->img_width, $this->img_height, '', '', stripslashes($row['it_name']))."\n";
    }

    if ($this->href) {
        echo "</a></div>\n";
    }
    ?>

    <div class="list_wr">
    <div class="it_brand">
        <?php
            echo $it['it_brand'];
        ?>
    </div>

    <?php
    if ($this->view_it_id) {
        echo "<div class=\"sct_id\">&lt;".stripslashes($row['it_id'])."&gt;</div>\n";
    }

    if ($this->href) {
        echo "<h3 class=\"sct_txt\"><a href=\"{$this->href}{$row['it_id']}\" class=\"sct_a\">\n";
    }

    if ($this->view_it_name) {
        echo stripslashes($row['it_name'])."\n";
    }

    if ($this->href) {
        echo "</a></h3>\n";
    }

    echo "<div class=\"sct_cost\">\n";
    /* 시중가가 등록안되어있을 경우 0이 아니라면 %계산, 0이라면 pass */
    if($row['it_cust_price']!=0){
        $sale_per=ceil((($row['it_cust_price']-get_price($row))/$row['it_cust_price'])*100).'%';
    }
    if ($row['it_cust_price']) {
        // echo "<strike><span class='sct_cust_price'>".display_price($row['it_cust_price'])."</span></strike>"."<br>\n";
        echo "<span class='sct_sale_per'> $sale_per </span>"; }

    echo display_price(get_price($row), $row['it_tel_inq'])."\n";

    if($total_count > 0 && $rows > 0) {
        $total_page = ceil($total_count / $rows);
    } else {
        $total_page = 0;
    }
    echo "</div>\n";

    /* 평점 및 리뷰 표시 */
    if(get_use_count($row['it_id']) > 0 ){
        echo "<div class='avg_rv'>";

        $sns_title = get_text($it['it_name']).' | '.get_text($config['cf_title']);
        $sns_url  = shop_item_url($it['it_id']);

        if ($score = get_star_image($it['it_id'])) {
        ?>
        <img src="<?php echo G5_SHOP_URL; ?>/img/s_star.png" alt="고객평점 <?php echo $score?>개" class="sit_star" width="12">
        <?php } ?>
        <?php
        // 리뷰 별점 소수점 첫째자리까지
        echo "<span class='score'>" . get_star_image_float($it['it_id']) . "</span>";
        echo "<span>리뷰 ".number_format($row['it_use_cnt'])."</span>";
        echo "</div>";
    }

    /* 아이콘 */
    echo "<div class=\"sct_icon_wr\">".item_icon2($row)."</div>\n";
    if ($this->view_it_icon) {
        // 품절
        if ($is_soldout) {
            echo '<span class="shop_icon_soldout h160"><span class="soldout_txt">SOLD OUT</span></span>';
        }
    }

    echo "</div></li>\n";
}
if ($i > 1) echo "</ul>\n";

if($i == 1) echo "<p class=\"sct_noitem\">등록된 상품이 없습니다.</p>\n";
?>


<!-- 제품 이미지 마우스 오버 처리  -->
<script type="text/javascript">
    jQuery(document).ready(function(){
        /*fade
        jQuery(".image_change").on("mouseenter",function(){
            var src = jQuery(this).attr("data-val2");
            jQuery(this).fadeOut('fast' , function(){jQuery(this).attr("src", src)});
            jQuery(this).fadeIn('fast');

        });
        jQuery(".image_change").on("mouseleave",function(){
            var src = jQuery(this).attr("data-val1");
            jQuery(this).fadeOut('fast' , function(){jQuery(this).attr("src", src)});
            jQuery(this).fadeIn('fast');
        });*/

        /*바로 변경 주석 을 제거하시고 위의 fade 를 주석처리하세요 */

        jQuery(".image_change").on("mouseenter",function(){
            jQuery(this).attr("src", jQuery(this).attr("data-val2"));
        });
        jQuery(".image_change").on("mouseleave",function(){
            jQuery(this).attr("src", jQuery(this).attr("data-val1"));
        });

    });

    //-->
</script>
<!-- 제품 이미지 마우스 오버 처리 완료  -->

<!-- } 상품진열 10 끝 -->
