<?php
if (!defined('_GNUBOARD_')) exit;
if(!is_array($bn_gr_skin_set)) {
    $bn_gr_skin_set = array();
}
if(!isset($bn_gr_skin_set['img_width']))    $bn_gr_skin_set['img_width'] = 1200;
if(!isset($bn_gr_skin_set['img_height']))   $bn_gr_skin_set['img_height'] = 100;
if(!isset($bn_gr_skin_set['bg_color']))   $bn_gr_skin_set['bg_color'] = '#ffffff';
if(!isset($bn_gr_skin_set['close_hour']))   $bn_gr_skin_set['close_hour'] = 1;
?>
    <h3 style="margin:25px 10px 10px 5px;font-size:1.15em">스킨 설정</h3>
    <table>
    <caption>스킨 설정</caption>
    <colgroup>
        <col class="grid_2">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">이미지 너비</th>
        <td>
            <input type="text" name="bn_skin_set[img_width]" value="<?php echo $bn_gr_skin_set['img_width'] ?>" id="bn_skin_set_img_width" class="frm_input" style="width:60px">
            ※ 숫자만 입력 (px단위)
        </td>
    </tr>
    <tr>
        <th scope="row">이미지 높이</th>
        <td>
            <input type="text" name="bn_skin_set[img_height]" value="<?php echo $bn_gr_skin_set['img_height'] ?>" id="bn_skin_set_img_height" class="frm_input" style="width:60px">
            ※ 숫자만 입력 (px단위)
        </td>
    </tr>
    <tr>
        <th scope="row">배경색상</th>
        <td>
            <input type="text" name="bn_skin_set[bg_color]" value="<?php echo $bn_gr_skin_set['bg_color'] ?>" id="bn_skin_set_bg_color" class="frm_input" style="width:60px">
            ※ #0011ff 형식
        </td>
    </tr>
    <tr>
        <th scope="row">배경색상</th>
        <td>
            <input type="text" name="bn_skin_set[close_hour]" value="<?php echo $bn_gr_skin_set['close_hour'] ?>" id="bn_skin_set_close_hour" class="frm_input" style="width:60px">
            ※ close버튼 클릭시 배너 재게시 시간
        </td>
    </tr>
    </tbody>
    </table>