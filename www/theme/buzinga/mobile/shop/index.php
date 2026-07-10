<?php
include_once('./_common.php');

if (!defined('_INDEX_')) define('_INDEX_', true);

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');
?>

    <style>
        #hd_wr {border: none;}
    </style>

    <div class="banner_main">
        <div class="main_banner_wrap">
            <?php echo display_banner('mainbanner.10.skin.php'); ?></li>
            <!-- 배너 좌우로 나뉜거 하나로 합침 -->
            <!-- <?php echo display_banner('메인', 'mainbanner.10.skin.php'); ?> -->
        </div>
    </div>

    <div class="gap"></div>

    <section class="idx_only">
        <div class="main_tit_wrap">
            <h2 class="title"><a href="<?php echo shop_type_url('4'); ?>">레드토이에서 <span class="c_pick">가장 인기있는</span> 제품</a></h2>
            <div class="sub_title">이번 주 가장 <span class="sub_name">잘나가는 토이</span>를 지금 바로 만나보세요.</div>
        </div>
        <!-- 카테고리 -->
        <div class="category_line tab_title" id="category_best">
            <ul class="bo_top cate_box">
                <li id="cate_1" class="on">최근 가장 핫한 토이</li>
                <li id="cate_2">나홀로 오나홀</li>
                <li id="cate_3">매혹적인 란제리</li>
                <li id="cate_4">새롭고 짜릿하게</li>
                <li id="cate_5">알차게 활용하는 Tip</li>
            </ul>
        </div>
        <!-- 카테고리 end -->

        <div class="tab_cont">
            <?php if ($default['de_mobile_type1_list_use']) { ?>
                <div class="sct_wrap" id="hit_list_1">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(2);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_2" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(4);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(40);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_3" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(4);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(70);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_4" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(4);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(50);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_5" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(4);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(80);
                    echo $list->run();
                    ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            $(".tab_title li").click(function() {
                var idx = $(this).index();
                $(".tab_title li").removeClass("on");
                $(".tab_title li").eq(idx).addClass("on");
                $(".tab_cont > div").hide();
                $(".tab_cont > div").eq(idx).show();
            })
        });
    </script>
    <!-- 베스트셀러 -->

    <div class="gap"></div>

    <section class="idx_only">
        <div class="main_tit_wrap">
            <h2 class="title"><a href="<?php echo shop_type_url('3'); ?>">레드토이 <span class="c_pick">인기 신상품</span></a></h2>
            <div class="sub_title">따끈따끈한 신상품을 <span class="sub_name">할인된 가격으로 구매</span>하세요.</div>
        </div>
        <!-- 카테고리 -->
        <div class="category_line tab_title_new" id="category_best">
            <ul class="bo_top cate_box">
                <li id="cate_1" class="on">남성토이</li>
                <li id="cate_2">여성토이</li>
                <li id="cate_3">애널토이</li>
                <li id="cate_4">BDSM</li>
            </ul>
        </div>
        <!-- 카테고리 end -->

        <div class="tab_cont_new">
            <?php if ($default['de_mobile_type1_list_use']) { ?>
                <div class="sct_wrap" id="hit_list_1">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(3);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(40);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_2" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(3);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(30);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_3" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(3);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(60);
                    echo $list->run();
                    ?>
                </div>
                <div class="sct_wrap" id="hit_list_4" style="display:none">
                    <?php
                    $list = new item_list();
                    $list->set_mobile(true);
                    $list->set_type(3);
                    $list->set_view('it_id', false);
                    $list->set_view('it_name', true);
                    $list->set_view('it_basic', true);
                    $list->set_view('it_cust_price', true);
                    $list->set_view('it_price', true);
                    $list->set_view('it_icon', true);
                    $list->set_category(50);
                    echo $list->run();
                    ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            $(".tab_title_new li").click(function() {
                var idx = $(this).index();
                $(".tab_title_new li").removeClass("on");
                $(".tab_title_new li").eq(idx).addClass("on");
                $(".tab_cont_new > div").hide();
                $(".tab_cont_new > div").eq(idx).show();
            })
        });
    </script>

    <div class="gap"></div>

    <section class="idx_only">
        <div class="main_tit_wrap">
            <h2 class="title"><a href="<?php echo shop_type_url('5'); ?>">놓치기 아쉬운 <span class="c_pick">할인특가</span></a></h2>
            <div class="sub_title">지금 가장 <span class="sub_name">주목받는 상품</span>을 만나보세요.</div>
        </div>

        <?php if($default['de_mobile_type3_list_use']) { ?>
            <div class="sct_wrap">
                <?php

                $list = new item_list();
                $list->set_mobile(true);
                $list->set_type(5);
                $list->set_view('it_id', false);
                $list->set_view('it_name', true);
                $list->set_view('it_cust_price', true);
                $list->set_view('it_price', true);
                $list->set_view('it_icon', true);
                $list->set_view('sns', true);
                echo $list->run();
                ?>
            </div>
        <?php } ?>
    </section>

    <div class="gap"></div>

    <!-- 구매후기 -->
    <!-- <section class="idx_only">
        <div class="main_tit_wrap">
            <h2 class="title"><a href="/shop/itemuselist.php">실시간 <span class="c_pick">구매후기</span></a></h2>
            <div class="sub_title">구매고객의 <span class="sub_name">생생한 사용후기</span>를 만나보세요!</div>
        </div>

        <div class="sct_wrap">
            <div id="idx_review_sec" class="idx_section">
                <div class="pc">
                    <?php
                    // 상품리뷰
                    $sql = " select a.is_id, a.is_subject, a.is_content, a.it_id, b.it_name
                from `{$g5['g5_shop_item_use_table']}` a join `{$g5['g5_shop_item_table']}` b on (a.it_id=b.it_id)
                where a.is_confirm = '1'
                order by a.is_id desc
                limit 0, 10 ";
                    $result = sql_query($sql);

                    for($i=0; $row=sql_fetch_array($result); $i++) {
                        if($i == 0) {
                            echo '<div id="idx_review" class="review_box">'.PHP_EOL;
                            echo '<ul>'.PHP_EOL;
                        }

                        $review_href = G5_SHOP_URL.'/item.php?it_id='.$row['it_id'];
                        ?>
                        <li class="rv_<?php echo $i;?>">
                            <div class="rv_wr">
                                <a href="<?php echo $review_href; ?>" class="rv_img"><?php echo get_itemuselist_thumbnail($row['it_id'], $row['is_content'], 300, 300); ?></a>
                                <div class="rv_txt">
                                    <span class="rv_tit"><?php echo get_text(cut_str($row['is_subject'], 15)); ?></span>
                                    <p><?php echo get_text(cut_str(strip_tags($row['is_content']), 70), 1); ?></p>
                                    <a href="<?php echo $review_href; ?>" class="prd_view">상품보기 +</a>
                                </div>
                            </div>
                        </li>
                        <?php
                    }

                    if($i > 0) {
                        echo '</ul>'.PHP_EOL;
                        echo '</div>'.PHP_EOL;
                    }
                    ?>
                </div>

                <div class="mobile">
                    <?php
                    // 상품리뷰
                    $sql = " select a.is_id, a.is_subject, a.is_content, a.it_id, b.it_name
                from `{$g5['g5_shop_item_use_table']}` a join `{$g5['g5_shop_item_table']}` b on (a.it_id=b.it_id)
                where a.is_confirm = '1'
                order by a.is_id desc
                limit 0, 7 ";
                    $result = sql_query($sql);

                    for($i=0; $row=sql_fetch_array($result); $i++) {
                        if($i == 0) {
                            echo '<div id="idx_review" class="review_box">'.PHP_EOL;
                            echo '<ul>'.PHP_EOL;
                        }

                        $review_href = G5_SHOP_URL.'/item.php?it_id='.$row['it_id'];
                        ?>
                        <li class="rv_<?php echo $i;?>">
                            <div class="rv_wr">
                                <a href="<?php echo $review_href; ?>" class="rv_img"><?php echo get_itemuselist_thumbnail($row['it_id'], $row['is_content'], 300, 300); ?></a>
                                <div class="rv_txt">
                                    <span class="rv_tit"><?php echo get_text(cut_str($row['is_subject'], 15)); ?></span>
                                    <p><?php echo get_text(cut_str(strip_tags($row['is_content']), 70), 1); ?></p>
                                    <a href="<?php echo $review_href; ?>" class="prd_view">상품보기 +</a>
                                </div>
                            </div>
                        </li>
                        <?php
                    }

                    if($i > 0) {
                        echo '</ul>'.PHP_EOL;
                        echo '</div>'.PHP_EOL;
                    }
                    ?>
                </div>
            </div>
        </div>
    </section> -->

    <!-- <div class="gap"></div> -->

    <!-- 하단 메뉴 -->
    <!-- <section class="idx_only">
        <div class="board_wrap">
            <div>
                <div class="main_tit_wrap">
                    <h2 class="title"><a href="/notice">공지사항</a></h2>
                </div>

                <?php
                echo latest('theme/notice', 'notice', 5, 45, 1, $options);
                ?>
            </div>

            <div>
                <div class="main_tit_wrap">
                    <h2 class="title"><a href="/magazine">매거진</a></h2>
                </div>

                <?php
                echo latest('theme/notice', 'magazine', 5, 45, 1, $options);
                ?>
            </div>

            <div>
                <div class="main_tit_wrap">
                    <h2 class="title"><a href="/event">이벤트</a></h2>
                </div>
                <?php
                echo latest('theme/notice', 'event', 5, 45, 1, $options);
                ?>
            </div>
        </div>
    </section> -->

    <!-- <div class="gap pc"></div> -->

    <script>
        $("#container").removeClass("container").addClass("idx-container");
    </script>



<!-- JSON-LD 구조화 데이터: 사이트의 조직(Organization) 및 매장(Store) 정보를 검색엔진에 제공  -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [
                {
                "@type": "Organization",
                "@id": "https://www.redtoy.co.kr/#organization",
                "name": "레드토이",
                "url": "https://www.redtoy.co.kr",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://www.redtoy.co.kr/img/redtoy_logo_192x192.png"
                }
                },
                {
                "@type": "Store",
                "@id": "https://www.redtoy.co.kr/#store",
                "name": "레드토이",
                "url": "https://www.redtoy.co.kr",
                "description": "정품 성인용품 전문 쇼핑몰 레드토이! 오나홀, 바이브레이터, 콘돔, 커플토이까지 다양한 상품을 안전하고 빠르게 배송해드립니다.",

                "address": {
                    "@type": "PostalAddress",
                    "addressCountry": "KR",
                    "addressLocality": "서울시",
                    "addressRegion": "송파구",
                    "streetAddress": "백제고분로 509, 7289호"
                },
                "email": "incense0523@gmail.com",
                "telephone": "02-6101-9272",
                "openingHours": "Monday,Tuesday,Wednesday,Thursday,Friday 09:00-18:00"
                }
            ]
        }
    </script>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>