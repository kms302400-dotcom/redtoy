<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>
<?php
if($this->total_count > 0) {
    $li_width = intval(100 / $this->list_mod);
    $li_width_style = ' style="width:'.$li_width.'%;"';
    $k = 1;
    $slide_btn = '<button type="button" class="bst_sl">'.$k.'번째 리스트</button>';

    for ($i=0; $row=sql_fetch_array($result); $i++) {
// print_r( $row);
		$item_link_href = shop_item_url($row['it_id']);

        if($i == 0) {
            echo '<script src="'.G5_JS_URL.'/swipe.js"></script>'.PHP_EOL;
            echo '<section id="best_item">'.PHP_EOL;
            echo '<h2>인기세트 <span>신상품</span></h2>'.PHP_EOL;
            echo '<div id="sbest_list" class="">'.PHP_EOL;
            echo '<div id="sbest_slide" class="swiper">'.PHP_EOL;
            echo '<ul class="sct_best swiper-wrapper">'.PHP_EOL;
        }

        // if($i > 0 && ($i % $this->list_mod == 0)) {
        //     echo '</ul>'.PHP_EOL;
        //     echo '<ul class="sct_best">'.PHP_EOL;
        //     $k++;
        //     $slide_btn .= '<button type="button">'.$k.'번째 리스트</button>';
        // }

        echo '<li class="sct_li swiper-slide">'.PHP_EOL;

        if ($this->href) {
            echo '<div class="sct_img"><a href="'.$item_link_href.'" class="sct_a">'.PHP_EOL;
        }

        if ($this->view_it_img) {
            echo get_it_image($row['it_id'], $this->img_width, $this->img_height, '', '', stripslashes($row['it_name'])).PHP_EOL;
        }
		
		if ($this->href) {
            echo '</a></div>'.PHP_EOL;
        }

        if ($this->view_it_id) {
            echo '<div class="sct_id">&lt;'.stripslashes($row['it_id']).'&gt;</div>'.PHP_EOL;
        }

        if ($this->href) {
            echo '<div class="list_wr"><div class="it_brand">'.$row['it_brand'].'</div>'.PHP_EOL;
            echo '<div class="sct_txt"><a href="'.$item_link_href.'" class="sct_a">'.PHP_EOL;
        }

        if ($this->view_it_name) {
            echo stripslashes($row['it_name']).PHP_EOL;
        }

        if ($this->href) {
            echo '</a></div>'.PHP_EOL;
        }

        if ($this->view_it_price) {
            echo '<div class="sct_cost">'.display_price(get_price($row), $row['it_tel_inq']).'</div></div>'.PHP_EOL;
        }

        echo '</li>'.PHP_EOL;
    }

    if($i > 0) {
        echo '</ul>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
        echo '<div class="slide_btn swiper-button-prev"></div>'.PHP_EOL;
        echo '<div class="slide_btn swiper-button-next"></div>'.PHP_EOL;
        //  echo '<div class="bst_silde_btn">'.$slide_btn.'</div>'.PHP_EOL; 슬라이드 네비게이션 삭제
        echo '</div>'.PHP_EOL;
        echo '</section>'.PHP_EOL;
    }
?>

<script>

const mySwiper  = new Swiper('#sbest_slide',{
    loop: false,
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
    slidesPerView: 3.2,
    slidesPerGroup: 3,
    breakpoints: {        
        890: {
          slidesPerView: 4.3,
          slidesPerGroup: 4,
        },
    },

})

// (function($) {
//     $.fn.BestSlide = function(option)
//     {
//         var cfg = {
//                 wrap: ".slide-wrap",
//                 slides: ".sct_best",
//                 buttons: ".bst_silde_btn > button",
//                 btnActive: "bst_sl",
//                 startSlide: 0,
//                 auto: 0,
//                 continuous: true,
//                 disableScroll: false,
//                 stopPropagation: false,
//                 callback: function(index, element) {
//                   button_change(index);
//                 },
//                 transitionEnd: function(index, element) {
//                     idx = index;
//                 }
//             };

//         if(typeof option == "object")
//             cfg = $.extend( cfg, option );

//         var $wrap = this.find(""+cfg.wrap+"");
//         var $slides = this.find(""+cfg.slides+"");
//         var $btns = this.find(""+cfg.buttons+"");

//         var idx = cfg.startSlide;
//         var count = $slides.size();
//         var width, outerW;

//         if(count < 1)
//             return;

//         function button_change(idx)
//         {
//             if(count < 2)
//                 return;

//             $btns.removeClass(cfg.btnActive)
//                  .eq(idx).addClass(cfg.btnActive);
//         }

//         function init()
//         {
//             width  = $slides.eq(0).width();
//             outerW = $slides.eq(0).outerWidth(true);

//             $slides.width(width);
//         }

//         init();

//         window.mySwipe = Swipe(this[0], {
//             startSlide: cfg.startSlide,
//             auto: cfg.auto,
//             continuous: cfg.continuous,
//             disableScroll: cfg.disableScroll,
//             stopPropagation: cfg.stopPropagation,
//             callback: cfg.callback,
//             transitionEnd: cfg.transitionEnd
//         });

//         $(window).on("resize", function() {
//             init();
//         });

//         if(count > 0 && mySwipe) {
//             $btns.on("click", function() {
//                 if($(this).hasClass(""+cfg.btnActive+""))
//                     return false;

//                 idx = $btns.index($(this));
//                 mySwipe.slide(idx);
//             });
//         }
//     }
// // }(jQuery));

// $(function() {
    // $("#sbest_list").BestSlide({
    //     wrap: ".slide-wrap",
    //     slides: ".slide-wrap > ul",
    //     buttons: ".bst_silde_btn > button",
    //     btnActive: "bst_sl",
    //     startSlide: 0,
    //     auto: 0
    // });
// });

    
    
            

</script>

<?php
}
?>