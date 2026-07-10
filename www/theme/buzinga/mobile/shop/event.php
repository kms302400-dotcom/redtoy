<?php
include_once('./_common.php');

if (!defined('_INDEX_')) define('_INDEX_', true);

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');
?>

<section class="idx_only">
    <h1 id="container_title">진행중인 이벤트</h1>

    <?php include_once(G5_MSHOP_SKIN_PATH.'/main.event.skin.php'); // 이벤트 ?>

</section>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>
