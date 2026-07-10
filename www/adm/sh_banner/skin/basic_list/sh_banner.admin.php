<?php
if (!defined('_GNUBOARD_')) exit;
if(!is_array($bn_gr_skin_set)) {
    $bn_gr_skin_set = array();
}
if(!isset($bn_gr_skin_set['img_width']))    $bn_gr_skin_set['img_width'] = 500;
if(!isset($bn_gr_skin_set['img_height']))   $bn_gr_skin_set['img_height'] = 300;
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
    </tbody>
    </table>