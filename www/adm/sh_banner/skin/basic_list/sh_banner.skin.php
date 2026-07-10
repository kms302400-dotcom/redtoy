<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$banner_name = $info_group['bn_gr_skin'].'_'.$bn_gr_id;
?>
<style>
    #sh_banner_<?php echo $banner_name; ?> {justify-content: space-between;}
    #sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap {margin-bottom:0.8%}

    @media (max-width: 500px) {
        #sh_banner_<?php echo $banner_name; ?> {justify-content: center;}
        #sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap img {width: 240px;}
    }

    @media (max-width: 499px) {
        #sh_banner_<?php echo $banner_name; ?> {justify-content: center;}
        #sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap img {width: 195px;}
    }

</style>
<div id="sh_banner_<?php echo $banner_name; ?>" style="display: flex;  flex-wrap: wrap;">
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
