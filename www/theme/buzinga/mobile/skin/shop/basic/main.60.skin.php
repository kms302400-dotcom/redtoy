<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_THEME_LIB_PATH.'/theme.shop.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
add_javascript('<script src="'.G5_THEME_JS_URL.'/jquery.shop.list.js"></script>', 10);

?>

<?php

?>
<!-- 메인상품진열 30 시작 { -->
<?php
$li_width = intval(100 / $this->list_mod);
$li_width_style = ' style="width:'.$li_width.'%;"';

$j = $page;

for ($i=1; $row=sql_fetch_array($result); $i++) {
    if ($i == 1) {
        if ($this->css) {
            echo "<ul id=\"sct_wrap\" class=\"{$this->css}\">\n";
        } else {
            echo "<ul id=\"sct_wrap\" class=\"sct lst_30\">\n";
        }
    }


    if($i % $this->list_mod == 1)
        $li_clear = ' sct_clear';
    else
        $li_clear = '';

    $k = $i + $j;

    echo "<li class=\"sct_li{$li_clear}\"$li_width_style><span class=\"sct_rank\">{$k}</span>\n";

    if ($this->href) {
        echo "<a href=\"{$this->href}{$row['it_id']}\" class=\"sct_a\">\n";
    }

    if ($this->view_it_img) {
        echo get_it_image($row['it_id'], $this->img_width, $this->img_height, '', '', stripslashes($row['it_name']))."\n";
    }

    if ($this->href) {
        echo "</a>\n";
    }


    echo "<div class=\"sct_cont\">\n";

    // 브랜드를 위한 상품 출력
    $it = get_shop_item($row['it_id']);
    ?>

    <div class="it_brand">
        <?php
        echo $it['it_brand'];
        ?>
    </div>

    <?php
    echo "<div class=\"sct_txt_wr\">\n";

    if ($this->view_it_id) {
        echo "<div class=\"sct_id\">&lt;".stripslashes($row['it_id'])."&gt;</div>\n";
    }

    if ($this->href) {
        echo "<div class=\"sct_txt\"><a href=\"{$this->href}{$row['it_id']}\" class=\"sct_a\">\n";
    }

    if ($this->view_it_name) {
        echo stripslashes($row['it_name'])."\n";
    }

    if ($this->href) {
        echo "</a></div>\n";
    }

    if ($this->view_it_cust_price || $this->view_it_price) {

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

    }

    /* 평점 및 리뷰 표시 */
    if(get_use_count($row['it_id']) > 0 ){
        echo "<div class='avg_rv' style='margin-bottom: 10px;'>";

        $sns_title = get_text($it['it_name']).' | '.get_text($config['cf_title']);
        $sns_url  = shop_item_url($it['it_id']);

        if ($score = get_star_image($it['it_id'])) {
            ?>
            <img src="<?php echo G5_SHOP_URL; ?>/img/s_star.png" alt="고객평점 <?php echo $score?>개" class="sit_star" style="margin-top: 2px; padding: 0 !important; width: 12px !important;">
        <?php } ?>
        <?php
        echo "<span class='score'>" . get_star_image_float($it['it_id']) . "</span>";
        echo "<span>리뷰 ".number_format($row['it_use_cnt'])."</span>";
        echo "</div>";
    }

    echo "<div class=\"sct_icon_wr\">".item_icon2($row)."</div>\n";
    if ($this->view_it_icon) {
        // 품절
        if ($is_soldout) {
            echo '<span class="shop_icon_soldout h160"><span class="soldout_txt">SOLD OUT</span></span>';
        }
    }
    
    echo "<div class=\"sct_rvws\" style='margin: 10px 0 0;'>\n";

    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";

    echo "</li>\n";
}

if ($i > 0) echo "</ul>\n";

if($i == 0) echo "<p class=\"sct_noitem\">등록된 상품이 없습니다.</p>\n";
?>
<!-- } 상품진열 30 끝 -->