<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$swiper_url = $g5['sh_banner_url'].'/js/swiper';
$banner_name = $info_group['bn_gr_skin'].'_'.$bn_gr_id.'_'.rand(1,10000);
$chk_height = strpos($bn_gr_skin_set['img_height'], 'px');
if($chk_height) {
    $bn_gr_skin_set['slide_height'] = ($bn_gr_skin_set['img_height'] - 1).'px';
} else {
    $bn_gr_skin_set['slide_height'] = $bn_gr_skin_set['img_height'];
}
?>
<link rel="stylesheet" href="<?php echo $swiper_url ?>/swiper.min.css">
<script type="text/javascript" src="<?php echo $swiper_url ?>/swiper.min.js"></script>
<style>
<?php if($bn_gr_skin_set['img_width']) { ?>
#sh_banner_<?php echo $banner_name; ?> {width:<?php echo $bn_gr_skin_set['img_width'] ?>}
#sh_banner_<?php echo $banner_name; ?> .swiper-slide img {width:<?php echo $bn_gr_skin_set['img_width'] ?>}
<?php } ?>
<?php if($bn_gr_skin_set['img_height']) { ?>
#sh_banner_<?php echo $banner_name; ?> {height:<?php echo $bn_gr_skin_set['slide_height'] ?>}
#sh_banner_<?php echo $banner_name; ?> .swiper-slide img {height:<?php echo $bn_gr_skin_set['img_height'] ?>}
<?php } ?>
</style>

<!-- Slider main container -->
<div class="swiper <?php echo $banner_class ?>" id="sh_banner_<?php echo $banner_name; ?>" style="--swiper-theme-color:#fff;--swiper-navigation-size:35px">
    <div class="swiper-wrapper">
    <!-- 이미지 -->
    <?php
    foreach($list as $item) {
        $tname = false;
        if( strpos($bn_gr_skin_set['img_width'],'px') && strpos($bn_gr_skin_set['img_height'], 'px') ) {
            $tname = thumbnail($item['bn_filename'], $g5['sh_banner_file_path'], $g5['sh_banner_file_path'].'/thumb', $bn_gr_skin_set['img_width']*1, $bn_gr_skin_set['img_height']*1, false, true);
        }
        if($tname) {
            $banner_img = $g5['sh_banner_file_url'].'/thumb/'.$tname;
        } else {
            $banner_img = $g5['sh_banner_file_url'].'/'.$item['bn_filename'];
        }
        if($item['bn_href']) {
            $img_result = '<a  class="sh_banner_item_href" href="'.$item['bn_href'].'" target="'.$item['bn_target'].'"><img  class="sh_banner_item_img" src="'.$banner_img.'"></a>';
        } else {
            $img_result = '<img class="sh_banner_item_img" src="'.$banner_img.'">';
        }
    ?>
        <div class="swiper-slide">
            <?php echo $img_result ?>
        </div>
    <?php } ?>
    </div>
    <?php if($bn_gr_skin_set['dots'] == 'true') { ?>
    <!-- 하단 불릿 -->
    <div class="swiper-pagination"></div>
    <?php } ?>
    <?php if($bn_gr_skin_set['arrows'] == 'true') { ?>
    <!-- 좌우 버튼 -->
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
    <?php } ?>
</div>

<script>
const sh_banner_<?php echo $banner_name; ?> = new Swiper('#sh_banner_<?php echo $banner_name; ?>', {
    <?php if($bn_gr_skin_set['autoplay']) { ?>
    autoplay: {
        delay: <?php echo $bn_gr_skin_set['autoplaySpeed'] ?>
    },
    <?php } ?>

    // Optional parameters
    loop: true,
    
    <?php if($bn_gr_skin_set['dots'] == 'true') { ?>
    // If we need pagination
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    <?php } ?>
    
    <?php if($bn_gr_skin_set['arrows'] == 'true') { ?>
    // Navigation arrows
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
    <?php } ?>
});
</script>