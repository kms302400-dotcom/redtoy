<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$slick_url = $g5['sh_banner_url'].'/js/slick-1.8.1';
$banner_name = $info_group['bn_gr_skin'].'_'.$bn_gr_id;
?>
<link rel="stylesheet" href="<?php echo $slick_url ?>/slick.css">
<link rel="stylesheet" href="<?php echo $slick_url ?>/slick-theme.css">
<script type="text/javascript" src="<?php echo $slick_url ?>/slick.min.js"></script>
<style>
<?php echo ($bn_gr_skin_set['arrows']) ? '#sh_banner_'.$banner_name.' {margin:0 20px; padding:0 20px}'.PHP_EOL : '' ?>
#sh_banner_<?php echo $banner_name; ?> .slick-prev:before, #sh_banner_<?php echo $banner_name; ?> .slick-next:before {color: black;}
#sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap img {width:100%}
#sh_banner_<?php echo $banner_name; ?> .slick-slide {margin:0 10px}
</style>

<div id="sh_banner_<?php echo $banner_name; ?>">
<?php
foreach($list as $item) {
    $tname = thumbnail($item['bn_filename'], $g5['sh_banner_file_path'], $g5['sh_banner_file_path'].'/thumb', $bn_gr_skin_set['img_width'], $bn_gr_skin_set['img_height'], false, true);
    if($tname) {
        $banner_img = $g5['sh_banner_file_url'].'/thumb/'.$tname;
    } else {
        $banner_img = $g5['sh_banner_file_url'].'/'.$item['bn_filename'];
    }
    if($item['bn_href']) {
        $img_result = '<a href="'.$item['bn_href'].'" target="'.$item['bn_target'].'"><img src="'.$banner_img.'"></a>';
    } else {
        $img_result = '<img src="'.$banner_img.'">';
    }
?>
    <div class="sh-banner-img-wrap">
        <?php echo $img_result ?>
    </div>
<?php } ?>
</div>

<script>
$('#sh_banner_<?php echo $banner_name; ?>').slick({
    slidesToShow: <?php echo $bn_gr_skin_set['slidesToShow'] ?>,
    slidesToScroll: <?php echo $bn_gr_skin_set['slidesToScroll'] ?>,
    arrows: <?php echo $bn_gr_skin_set['arrows'] ?>,
    dots: <?php echo $bn_gr_skin_set['dots'] ?>,
    vertical: <?php echo $bn_gr_skin_set['vertical'] ?>,
    autoplay: <?php echo $bn_gr_skin_set['autoplay'] ?>,
    centerMode: false,
    infinite: true,
    responsive: <?php echo $bn_gr_skin_set['responsive'] ?>,
    autoplaySpeed: <?php echo $bn_gr_skin_set['autoplaySpeed'] ?>,
});
</script>
