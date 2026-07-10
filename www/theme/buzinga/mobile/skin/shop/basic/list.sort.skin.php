<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$sct_sort_href = $_SERVER['SCRIPT_NAME'].'?';

if($ca_id) {
    $shop_category_url = shop_category_url($ca_id);
    $sct_sort_href = (strpos($shop_category_url, '?') === false) ? $shop_category_url.'?1=1' : $shop_category_url;
} else if($ev_id) {
    $sct_sort_href .= 'ev_id='.$ev_id;
}

if ($costf)
    $sct_sort_href .= '&amp;costf='.$costf;
if ($costt)
    $sct_sort_href .= '&amp;costt='.$costt;

if($skin)
    $sct_sort_href .= '&amp;skin='.$skin;
$sct_sort_href .= '&amp;sort=';

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);

function getSortActive($type, $odr) {
    global $sort;
    global $sortodr;
    
    if ($sort == $type && $sortodr == $odr) {
        return ' class="active"';
    }
}

function getSortText($type) {
    global $sortodr;
    switch($type) {
        case "it_price desc" :
            return "높은가격순";
            break;
        case "it_price asc" :
            return "낮은가격순";
            break;
        case "it_use_cnt desc" :
            return "리뷰많은순";
            break;
        default :
            $sortodr = "desc";
            return "인기순";
            break;
    }
}

function getSortUpDown($odr) {
    switch($odr) {
        case "desc" :
            return "down";
            break;
        default :
            return "up";
            break;
    }
}
?>

<!-- 상품 정렬 선택 시작 { -->
<section id="sct_sort">
    <h2>상품 정렬</h2>
    <span class="item_cnt">전체 <span id="totalCount"></span>개</span>
    <button type="button" class="btn_sort"><?=getSortText($sort . " " . $sortodr);?> <i class="fas fa-caret-<?=getSortUpDown($sortodr)?>"></i></button>
    <ul>
        <li <?=getSortActive('', 'desc')?>><a href="<?php echo $sct_sort_href; ?>">인기순</a></li>
        <li <?=getSortActive('it_price', 'desc')?>><a href="<?php echo $sct_sort_href; ?>it_price&amp;sortodr=desc">높은가격순</a></li>
        <li <?=getSortActive('it_price', 'asc')?>><a href="<?php echo $sct_sort_href; ?>it_price&amp;sortodr=asc" >낮은가격순</a></li>
        <li <?=getSortActive('it_use_cnt', 'desc')?>><a href="<?php echo $sct_sort_href; ?>it_use_cnt&amp;sortodr=desc">리뷰많은순</a></li>
    </ul>
</section>
<!-- } 상품 정렬 선택 끝 -->

<script>
    $(".btn_sort").click(function(){
        $("#sct_sort ul").toggle();
    });
    $(document).mouseup(function (e){
        var container = $("#sct_sort ul");
        var button = $(".btn_sort");
    
        if (!container.is(e.target) && container.has(e.target).length === 0 &&
            !button.is(e.target) && button.has(e.target).length === 0) {
            container.hide();
        }
    });
</script>