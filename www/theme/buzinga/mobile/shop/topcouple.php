<?php
include_once('./_common.php');

if (!defined('_INDEX_')) define('_INDEX_', true);

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');
?>

<section class="idx_only">
    <h1 id="container_title">Top100</h1>

    <aside id="sct_ct_1" class="sct_ct">
        <h2>현재 상품 분류와 관련된 분류</h2>
        <? include $_SERVER["DOCUMENT_ROOT"] . "/theme/buzinga/mobile/shop/menus.php"; ?>
    </aside>

    <?php if($default['de_mobile_type3_list_use']) { ?>
        <div class="sct_wrap">
            <?php
            $list = new item_list();
            $list->set_mobile(true);
            $list->set_type(1);
            $list->set_view('it_id', false);
            $list->set_view('it_name', true);
            $list->set_view('it_cust_price', true);
            $list->set_view('it_price', true);
            $list->set_view('it_icon', true);
            $list->set_view('sns', true);
            $list->set_category(90);
            echo $list->run();
            ?>
        </div>
    <?php } ?>

</section>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>
