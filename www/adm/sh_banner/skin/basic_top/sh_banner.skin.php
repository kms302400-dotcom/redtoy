<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$banner_name = $info_group['bn_gr_skin'].'_'.$bn_gr_id;

$banner_img_set = '';
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
    $banner_img_set .= $img_result;
}
$banner = '
<div id="sh_banner_'.$banner_name.'">
    <div class="sh-banner-img-wrap">
        <div class="banner_top_close"><img src="'.$g5['sh_banner_skin_url'].'/'.$info_group['bn_gr_skin'].'/img/btn_close.png"></div>
        '.$banner_img_set.'
    </div>
</div>
';
$banner = str_replace(PHP_EOL, '', $banner);
?>
<style>
#sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap {margin-bottom:0.8%}
<?php if($bn_gr_skin_set['bg_color']) { ?>
#sh_banner_<?php echo $banner_name; ?> {background:<?php echo $bn_gr_skin_set['bg_color']; ?>}
<?php } ?>
#sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap {position:relative;margin:0px auto;text-align:center}
#sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap .banner_top_close {position:absolute;top:15px;right:20px;cursor:pointer}
</style>
<script>
$(document).ready(function() {
	var is_top_banner_<?php echo $banner_name ?> = get_cookie('banner_<?php echo $banner_name ?>');
	if(is_top_banner_<?php echo $banner_name ?> != 'no') {
		var sh_top_banner_<?php echo $banner_name ?> = '<?php echo $banner ?>';
		$("body").prepend(sh_top_banner_<?php echo $banner_name ?>);
		$("#sh_banner_<?php echo $banner_name; ?> .banner_top_close").click(function () {
			$(this).hide();
			$("#sh_banner_<?php echo $banner_name; ?>").slideUp();
			set_cookie('banner_<?php echo $banner_name ?>','no',<?php echo $bn_gr_skin_set['close_hour'] ?>); // close버튼 클릭시 배너 재게시 1시간
		});
	}
});
</script>
