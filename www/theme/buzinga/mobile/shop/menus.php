<?php
    $is_pg_review_test_member = function_exists('redtoy_pg_review_is_test_member') && redtoy_pg_review_is_test_member();

    function path_check($path) {
        if ($path == $_SERVER["PHP_SELF"]) {
            return ' style="padding: 5px 10px; font-weight:600; color: #fff; background: #fd5c63; border: none;"';
        }
    }
    ?>
<ul>
    <li><a href="/theme/buzinga/mobile/shop/top100.php" <?=path_check("/theme/buzinga/mobile/shop/top100.php")?>>전체</a></li>
    <?php if (!$is_pg_review_test_member) { ?>
    <li><a href="/theme/buzinga/mobile/shop/topman.php" <?=path_check("/theme/buzinga/mobile/shop/topman.php")?>>남성토이</a></li>
    <li><a href="/theme/buzinga/mobile/shop/topwoman.php" <?=path_check("/theme/buzinga/mobile/shop/topwoman.php")?>>여성토이</a></li>
    <li><a href="/theme/buzinga/mobile/shop/topanal.php" <?=path_check("/theme/buzinga/mobile/shop/topanal.php")?>>애널토이</a></li>
    <li><a href="/theme/buzinga/mobile/shop/topcouple.php" <?=path_check("/theme/buzinga/mobile/shop/topcouple.php")?>>커플토이</a></li>
    <li><a href="/theme/buzinga/mobile/shop/topbdsm.php" <?=path_check("/theme/buzinga/mobile/shop/topbdsm.php")?>>BDSM</a></li>
    <li><a href="/theme/buzinga/mobile/shop/topunder.php" <?=path_check("/theme/buzinga/mobile/shop/topunder.php")?>>속옷</a></li>
    <?php } ?>
    <li><a href="/theme/buzinga/mobile/shop/topcondom.php" <?=path_check("/theme/buzinga/mobile/shop/topcondom.php")?>>콘돔</a></li>
    <li><a href="/theme/buzinga/mobile/shop/topoils.php" <?=path_check("/theme/buzinga/mobile/shop/topoils.php")?>>윤활제</a></li>
    <?php if (!$is_pg_review_test_member) { ?>
    <li><a href="/theme/buzinga/mobile/shop/topwashtools.php" <?=path_check("/theme/buzinga/mobile/shop/topwashtools.php")?>>세척/관리도구</a></li>
    <?php } ?>
</ul>
