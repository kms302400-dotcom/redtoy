<?php
if (!defined('_GNUBOARD_')) exit;
if(!is_array($bn_gr_skin_set)) {
    $bn_gr_skin_set = array();
}
if(!isset($bn_gr_skin_set['img_width']))        $bn_gr_skin_set['img_width'] = 'auto';
if(!isset($bn_gr_skin_set['img_height']))       $bn_gr_skin_set['img_height'] = '60px';
if(!isset($bn_gr_skin_set['arrows']))           $bn_gr_skin_set['arrows'] = 'true';
if(!isset($bn_gr_skin_set['arrowsColor']))      $bn_gr_skin_set['arrowsColor'] = '#333';
if(!isset($bn_gr_skin_set['autoplay']))         $bn_gr_skin_set['autoplay'] = 'true';
if(!isset($bn_gr_skin_set['autoplaySpeed']))    $bn_gr_skin_set['autoplaySpeed'] = '5000';
if(!isset($bn_gr_skin_set['spaceBetween']))     $bn_gr_skin_set['spaceBetween'] = '15';
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
            * 0으로 입력시 별도의 너비 지정을 하지 않음, 너비 지정시 px까지 입력해주세요.(예 : 200px)
        </td>
    </tr>
    <tr>
        <th scope="row">이미지 높이</th>
        <td>
            <input type="text" name="bn_skin_set[img_height]" value="<?php echo $bn_gr_skin_set['img_height'] ?>" id="bn_skin_set_img_height" class="frm_input" style="width:60px">
            * 0으로 입력시 별도의 높이 지정을 하지 않음, 높이 지정시 px까지 입력해주세요.(예 : 60px)
        </td>
    </tr>
    <tr>
        <th scope="row">화살표표시</th>
        <td>
            <label for="bn_skin_set_arrows_1">
                <input type="radio" name="bn_skin_set[arrows]" value="true" id="bn_skin_set_arrows_1" class="frm_input"<?php echo ($bn_gr_skin_set['arrows'] == 'true') ? ' checked' : '' ?>>표시 O
            </label>
            &nbsp;&nbsp;
            <label for="bn_skin_set_arrows_2">
                <input type="radio" name="bn_skin_set[arrows]" value="false" id="bn_skin_set_arrows_2" class="frm_input"<?php echo ($bn_gr_skin_set['arrows'] == 'false') ? ' checked' : '' ?>>표시 X
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">화살표 색상</th>
        <td>
            <label for="bn_skin_set_arrowsColor">
                <input type="text" name="bn_skin_set[arrowsColor]" value="<?php echo $bn_gr_skin_set['arrowsColor'] ?>" id="bn_skin_set_arrowsColor" class="frm_input">
                css 색상코드로 입력
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">자동재생</th>
        <td>
            <label for="bn_skin_set_autoplay_1">
                <input type="radio" name="bn_skin_set[autoplay]" value="true" id="bn_skin_set_autoplay_1" class="frm_input"<?php echo ($bn_gr_skin_set['autoplay'] == 'true') ? ' checked' : '' ?>>자동재생 O
            </label>
            &nbsp;&nbsp;
            <label for="bn_skin_set_autoplay_2">
                <input type="radio" name="bn_skin_set[autoplay]" value="false" id="bn_skin_set_autoplay_2" class="frm_input"<?php echo ($bn_gr_skin_set['autoplay'] == 'false') ? ' checked' : '' ?>>자동재생 X
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">자동재생 속도</th>
        <td>
            <label for="bn_skin_set_autoplaySpeed">
                <input type="text" name="bn_skin_set[autoplaySpeed]" value="<?php echo $bn_gr_skin_set['autoplaySpeed'] ?>" id="bn_skin_set_autoplaySpeed" class="frm_input">
                3000 = 3초
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">배너간 간격</th>
        <td>
            <label for="bn_skin_set_spaceBetween">
                <input type="text" name="bn_skin_set[spaceBetween]" value="<?php echo $bn_gr_skin_set['spaceBetween'] ?>" id="bn_skin_set_spaceBetween" class="frm_input">
            </label>
        </td>
    </tr>
    </tbody>
    </table>