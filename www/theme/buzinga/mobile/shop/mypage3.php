<?php
include_once('./_common.php');

$g5['title'] = '마이 페이지';

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');

///////////////////////////////////
//                               //
//                               //
//     포인트 내역 임시 페이지     //
//                               //
//                               //
///////////////////////////////////

?>
<link rel="stylesheet" href="https://www.redtoy.co.kr/theme/buzinga/mobile/skin/member/basic/style.css">

<style>
    #container {max-width:none;padding:0;}
    #container_title {display:none}
</style>

<!-- 마이페이지 상단 공통 -->
<div id="mp_top">
    <h1>마이 페이지</h1>
    <ul class="mp_info">
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/couponzone.php">            
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_coupon_icon.png" alt="">
                <p>쿠폰 <span><?=number_format(get_coupon_count($member["mb_id"]))?></span> 장</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage3.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_point_icon.png" alt="">
                <p>포인트 <span><?=number_format(get_point_sum($member["mb_id"]))?></span> P</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/wishlist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_wish_icon.png" alt="">
                <p>찜한 상품 <span><?=number_format(get_wishlist_datas_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
        <li>
            <a href="/bbs/qalist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_qa_icon.png" alt="">
                <p>1:1 문의 <span><?=number_format(get_qna_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
    </ul>
</div>

<?
    $page = $_REQUEST["page"];
    if (!is_numeric($page)) $page = 1;
    $block = 20;

    $sql = "select count(po_id) as total_cnt from g5_point where mb_id = '{$member["mb_id"]}' ";
    $row = sql_fetch($sql);
    $total_count = $row["total_cnt"];
    
    $sql = "select po_datetime, po_content, po_point, po_expire_date from g5_point where mb_id = '{$member["mb_id"]}' order by po_id desc limit " . (($page - 1) * $block) . "," . $block;
    $res = sql_query($sql);
    $rows = sql_num_rows($res);
?>

<div id="mypage_wrap" style="background:none">
    <div id="smb_my" class="my_point">
        <h2>포인트 내역</h2>
        <p class="have_point">보유 포인트 : <span class="r_txt"><?=number_format(get_point_sum($member["mb_id"]))?></span>P</p>
        <ul>
            <?php
            for($i=0;$i<$rows;$i++) {
                $row = sql_fetch_array($res);
                if ($row["po_point"] > 0) {
                    $stand = "적립";
                } else {
                    $stand = "차감";
                }
            ?>
            <li class="point_list">
                <div class="left">
                    <div class="date"><?=date("Y.m.d", strtotime($row["po_datetime"]))?></div>
                    <!-- span>구매한 제품 이름</span //-->
                </div>
                <div class="center">
                    <div>
                        <span class="status <?=$stand == "차감"? "earned" : ""?>"><?=$stand?></span>
                        <?=$row["po_content"]?>
                    </div>
                    <!-- div><span>주문번호</span>2024090617083428</div -->
                    <div><span>유효기간</span><?=date("Y.m.d", strtotime($row["po_expire_date"]))?> 까지</div>
                </div>
                <div class="right "><span class="earned"><?=$row["po_point"] > 0 ? "+" . $row["po_point"] : $row["po_point"] ?></span> P</div>
            </li>
            <? } ?>
        </ul>
        
        <?
        echo PageNumber($page, $block, $total_count, "");
        ?>
    </div>    
</div>

<script>
    
</script>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>