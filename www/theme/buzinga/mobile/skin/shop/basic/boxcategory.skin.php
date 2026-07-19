<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
$is_pg_review_test_member = function_exists('redtoy_pg_review_is_test_member') && redtoy_pg_review_is_test_member();
?>

<!-- 쇼핑몰 카테고리 시작 { -->
<nav id="gnb">
    <div class="gnb_box">
        <ul id="gnb_1dul">
            <? /*
            <li class="gnb_1dli" style="z-index:991">
                <a href="/theme/buzinga/mobile/shop/top100.php" class="gnb_1da" style="font-weight:600;">Top100</a>
                <div class="gnb_2dul gnb_2dli" style="z-index:998">
                    <div class="gnb_2dul_left">
                        <ul>
                            <li><a href="/theme/buzinga/mobile/shop/topman.php" class="gnb_2da">남성토이</a></li>
                            <li><a href="/theme/buzinga/mobile/shop/topwoman.php" class="gnb_2da">여성토이</a></li>
                            <li><a href="/theme/buzinga/mobile/shop/topanal.php" class="gnb_2da">애널토이</a></li>
                            <li><a href="/theme/buzinga/mobile/shop/topcouple.php" class="gnb_2da">커플토이</a></li>
                        </ul>
                    </div>
                </div>
            </li>
            */
            $ca_style = array();
            if ($PHP_SELF == "/theme/buzinga/mobile/shop/top100.php") {
                $top100 = ' gnb_1dam';
            } else {
                if ($_REQUEST["ca_id"]) $ca_style[substr($_REQUEST["ca_id"], 0, 2)] = ' gnb_1dam';
            }
            ?>
            <li class="gnb_1dli" style="z-index:999">
                <a href="/theme/buzinga/mobile/shop/top100.php" class="gnb_1da<?=$top100?>" style="font-weight:600;">Top100</a>
            </li>
            <?php
            // 1단계 분류 판매 가능한 것만
            $hsql = " select ca_id, ca_name from {$g5['g5_shop_category_table']} where length(ca_id) = '2' and ca_use = '1' order by ca_order, ca_id ";
            $hresult = sql_query($hsql);
            $gnb_zindex = 999; // gnb_1dli z-index 값 설정용
            for ($i=0; $row=sql_fetch_array($hresult); $i++)
            {
                if ($is_pg_review_test_member && redtoy_pg_review_is_hidden_category($row['ca_id'])) {
                    continue;
                }

                $gnb_zindex -= 1; // html 구조에서 앞선 gnb_1dli 에 더 높은 z-index 값 부여
                // 2단계 분류 판매 가능한 것만
                $sql2 = " select ca_id, ca_name from {$g5['g5_shop_category_table']} where LENGTH(ca_id) = '4' and SUBSTRING(ca_id,1,2) = '{$row['ca_id']}' and ca_use = '1' order by ca_order, ca_id ";
                $result2 = sql_query($sql2);
                $count = sql_num_rows($result2);
                ?>
            <li class="gnb_1dli" style="z-index:<?php echo $gnb_zindex; ?>">
                <a href="<?php echo G5_SHOP_URL.'/list.php?ca_id='.$row['ca_id']; ?>" class="gnb_1da<?php echo $ca_style[$row['ca_id']]; ?>"><?php echo $row['ca_name']; ?></a>
                <?php
                for ($j=0; $row2=sql_fetch_array($result2); $j++)
                {
                    if ($j==0) echo '<div class="gnb_2dul gnb_2dli" style="z-index:'.$gnb_zindex.'"><div class="gnb_2dul_left"><ul>';
                    ?>
                    <li><a href="<?php echo G5_SHOP_URL; ?>/list.php?ca_id=<?php echo $row2['ca_id']; ?>" class="gnb_2da"><?php echo $row2['ca_name']; ?></a></li>
                <?php }
                if ($j>0) echo '</ul>';
                ?>
                </li>
            <?php } ?>
            <!-- <li class="gnb_1dli" style="z-index:991"><a href="<?php echo G5_SHOP_URL; ?>/couponzone.php" class="gnb_1da" style="font-weight:600; color: #fd5c63">쿠폰존</a></li>
            <li class="gnb_1dli" style="z-index:991"><a href="/notice" class="gnb_1da" style="font-weight:600; color: #fd5c63">공지사항</a></li>
            <li class="gnb_1dli" style="z-index:991"><a href="/magazine" class="gnb_1da" style="font-weight:600; color: #fd5c63;">매거진</a></li>
            <li class="gnb_1dli" style="z-index:991"><a href="/shop/itemuselist.php" class="gnb_1da" style="font-weight:600; color: #fd5c63;">구매후기</a></li> -->
        </ul>
    </div>
</nav>
<!-- } 쇼핑몰 카테고리 끝 -->
