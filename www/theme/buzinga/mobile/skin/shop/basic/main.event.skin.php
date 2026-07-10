<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨

add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>

<!-- 쇼핑몰 이벤트 시작 { -->
<aside id="sev" class="swiper event">
    <ul class="sev_ev swiper-wrapper">
        <?php
        $hsql = " select ev_id, ev_subject, ev_subject_strong from {$g5['g5_shop_event_table']} where ev_use = '1' order by ev_id desc  limit 50";
        $hresult = sql_query($hsql);
        for ($i=0; $row=sql_fetch_array($hresult); $i++)
        {
            echo '<li class="sev_li swiper-slide">';
            echo '<div class="sev_wr">';
            $href = G5_SHOP_URL.'/event.php?ev_id='.$row['ev_id'];
            echo '<a href="'.$href.'" class="sev_img"><img src="'.G5_DATA_URL.'/event/'.$row['ev_id'].'_m" alt="'.$row['ev_subject'].'"></a>'.PHP_EOL;

            $event_img = G5_DATA_PATH.'/event/'.$row['ev_id'].'_m'; // 이벤트 이미지
            echo '<a href="'.$href.'" class="sev_text">';
            if ($row['ev_subject_strong']) echo '<strong>';
            echo '<span class="event">EVENT</span>';
            echo $row['ev_subject'];
            if ($row['ev_subject_strong']) echo '</strong>';
            echo '</a>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
            // 이벤트 상품
            $sql2 = " select b.*
                                from `{$g5['g5_shop_event_item_table']}` a left join `{$g5['g5_shop_item_table']}` b on (a.it_id = b.it_id)
                                where a.ev_id = '{$row['ev_id']}'
                                order by it_id desc
                                limit 0, 4 ";
            $result2 = sql_query($sql2);
            for($k=1; $row2=sql_fetch_array($result2); $k++) {
                if($k == 1) {
                    echo '<ul class="ev_prd">'.PHP_EOL;
                }

                $item_href = G5_SHOP_URL.'/item.php?it_id='.$row2['it_id'];

                echo '<li class="ev_prd_'.$k.'"><div class="ev_li_wr">'.PHP_EOL;
                echo '<div class="ev_prd_img">'.get_it_image($row2['it_id'], 100, 100, get_text($row2['it_name'])).'</div>'.PHP_EOL;
                echo '<div class="ev_txt_wr"><div class="ev_li_box">'.PHP_EOL;

                echo '<a href="'.$item_href.'" class="ev_name">'.get_text(cut_str($row2['it_name'], 30)).'</a>'.PHP_EOL;
                /* echo '<span class="ev_basic">'.$row2['it_basic'].'</span>'.PHP_EOL; */

                /*echo '<span class="ev_discount">'.display_price($row2['it_cust_price']).'</span>'.PHP_EOL;*/
                echo '<span class="ev_prd_price">'.display_price(get_price($row2), $row2['it_tel_inq']).'</span></div>'.PHP_EOL;
                echo '</div></li>'.PHP_EOL;

            }

            if($k > 1) {
                echo '</ul>'.PHP_EOL;
            }

            if($k == 1) {
                echo '<ul class="ev_prd">'.PHP_EOL;
                echo '<li class="no_prd">등록된 상품이 없습니다.</li>'.PHP_EOL;
                echo '</ul>'.PHP_EOL;
            }
            echo '<a href="'.$href.'" class="sev_link">이벤트 상품 더보기</a>'.PHP_EOL;
            echo '</li>'.PHP_EOL;

        }

        ?>
    </ul>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    </div>
</aside>

<script>
    var swiper = new Swiper(".event", {
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        pagination: {
            el: ".swiper-pagination",
        },
        slidesPerView: 1,  //브라우저가 768보다 클 때
        breakpoints: {

            768: {
                slidesPerView: 1,  //브라우저가 768보다 클 때
                spaceBetween: 10,
            },
            1024: {
                slidesPerView: 3,  //브라우저가 1024보다 클 때
                spaceBetween: 10,
            },
        },
    });
</script>