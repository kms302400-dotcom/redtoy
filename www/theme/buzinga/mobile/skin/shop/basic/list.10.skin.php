<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_THEME_LIB_PATH.'/theme.shop.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
add_javascript('<script src="'.G5_THEME_JS_URL.'/jquery.shop.list.js"></script>', 10);

$pstr = 'ca_id='.$_GET['ca_id'].'&amp;sort='.$sort.'&amp;sortodr='.$sortodr;
?>

<style>
    @media (max-width: 890px){
        #container {padding: 0;}
        .sct {padding:0 20px;background:#fff;}
        .sct_30 .sct_li {padding:0 0 0 10px;}
        .sct_30 .sct_li:nth-child(2n+1) {padding:0 10px 0 0;}
        .list_wr {padding: 0 12px 0; margin: 0;}

        /* .sct {padding:0 10px} */
        .sct_ct ul li {padding:5px 0;}
    }
    #container_title {display:none;}
    .sct_ct ul {padding:0;}
    .sct_ct ul li:first-child {margin:0;}
    #best_item {margin-top:20px}
</style>


<!--div class="sct-tofrom">
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr; ?>" class="a a-all"><span>전체상품</span></a>
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr.'&amp;costf=0&amp;costt=100000'; ?>" class="a"><span>0~10만원</span></a>
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr.'&amp;costf=100001&amp;costt=200000'; ?>" class="a"><span>10~20만원</span></a>
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr.'&amp;costf=200001&amp;costt=300000'; ?>" class="a"><span>20~30만원</span></a>
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr.'&amp;costf=300001&amp;costt=400000'; ?>" class="a"><span>30~40만원</span></a>
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr.'&amp;costf=400001&amp;costt=500000'; ?>" class="a"><span>40~50만원</span></a>
    <a href="<?php echo $_SERVER['PHP_SELF'].'?'.$pstr.'&amp;costf=500001'; ?>" class="a"><span>50만원 이상</span></a>
</div-->

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
        // echo "<strike><span class='sct_cust_price'>".display_price($row['it_cust_price'])."</span></strike>"."</br>\n";
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

        if ($score = get_star_image($it['it_id'])) { ?>
            <img src="<?php echo G5_SHOP_URL; ?>/img/s_star.png" alt="고객평점 <?php echo round($score)?>개" class="sit_star" width="12">
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



<?php
$ca_id = isset($_GET['ca_id']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['ca_id']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$is_search_page = $search_query !== '';

$ca_url = $is_search_page 
    ? G5_SHOP_URL . "/search.php?q=" . urlencode($search_query) 
    : G5_SHOP_URL . "/list.php?ca_id=" . $ca_id;

$depth1_name = '';
$depth2_name = '';
$depth1_url = '';

if ($is_search_page) {
    // 검색 페이지
    $depth1_name = "'" . htmlspecialchars($search_query) . "'검색결과";
    $depth1_url = $ca_url;
    $ca_name = $depth1_name;
} else {
    // 카테고리 페이지
    $ca_id_depth1 = substr($ca_id, 0, 2);

    // 대카테고리 이름 조회
    $sql1 = "SELECT ca_name FROM {$g5['g5_shop_category_table']} WHERE ca_id = '{$ca_id_depth1}'";
    $row1 = sql_fetch($sql1);
    if ($row1 && $row1['ca_name']) {
        $depth1_name = $row1['ca_name'];
    }

    // 현재 카테고리 이름 조회
    $sql2 = "SELECT ca_name FROM {$g5['g5_shop_category_table']} WHERE ca_id = '{$ca_id}'";
    $row2 = sql_fetch($sql2);
    if ($row2 && $row2['ca_name']) {
        $depth2_name = $row2['ca_name'];
    }

    $depth1_url = G5_SHOP_URL . "/list.php?ca_id=" . $ca_id_depth1;
    $ca_name = '카테고리';
}
?>

<!-- JSON-LD: BreadcrumbList 마크업 (구글용 구조화 데이터) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "@id": "<?= $ca_url ?>#breadcrumb",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "item": {
        "@id": "https://www.redtoy.co.kr",
        "name": "홈"
      }
    },
    {
      "@type": "ListItem",
      "position": 2,
      "item": {
        "@id": "<?= $depth1_url ?>",
        "name": "<?= $depth1_name ?>"
      }
    }
    <?php if(strlen($ca_id) > 2): ?>,
    {
      "@type": "ListItem",
      "position": 3,
      "item": {
        "@id": "<?= $ca_url ?>",
        "name": "<?= $depth2_name ?>"
      }
    }
    <?php endif; ?>
  ]
}
</script>

<!-- JSON-LD: CollectionPage 마크업 (페이지 자체 정보 구조화) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "@id": "<?= $ca_url ?>#webpage",
  "url": "<?= $ca_url ?>",
  "name": "<?= $ca_name ?> - 레드토이",
  "inLanguage": "ko-KR",
  "isPartOf": {
    "@id": "https://www.redtoy.co.kr/#website"
  },
  "breadcrumb": {
    "@id": "<?= $ca_url ?>#breadcrumb"
  }
}
</script>