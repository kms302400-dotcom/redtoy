<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
$banner_name = $info_group['bn_gr_skin'].'_'.$bn_gr_id;
?>
<style>
#sh_banner_<?php echo $banner_name; ?> .sh-banner-img-wrap {margin-bottom: 10px;}
</style>
<div id="sh_banner_<?php echo $banner_name; ?>" style="display: flex; flex-wrap: wrap; justify-content: space-between;">
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
        $img_result = $img_result."<div class='cnt_wrap'><div class='cnt_tit'>".$item['bn_title']."</div>";
        $img_result = $img_result."<div class='cntsub_tit'>".$item['bn_memo']."</div>";
        $img_result = $img_result."<div class='cnt_more_btn'><a href='".$item['bn_href']."'>MORE</a></div></div>";
    } else {
        $img_result = '<img src="'.$banner_img.'">';
        $img_result = $img_result."<div class='cnt_wrap'><div class='cnt_tit'>".$item['bn_title']."</div>";
        $img_result = $img_result."<div class='cntsub_title'>".$item['bn_memo']."</div></div>";

    }



?>
    <?php


    $date1 = new DateTime( date('Y-m-d') );
    $date2 = new DateTime( $item['bn_date_end'] );
    $interval = $date2->diff($date1);
        $interval->format('%R%a DAY');


    ?>
    <div class="sh-banner-img-wrap">
        <div class="drop_box">
            <?php echo $img_result ?>
            <div class="cnt_dday">이벤트 종료 D <?php  echo $interval->format('%R%a');?></div>
        </div>
    </div>
<?php } ?>
</div>
