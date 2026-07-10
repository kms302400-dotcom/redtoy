<?php
if (!defined('_GNUBOARD_')) exit;
if(!is_array($bn_gr_skin_set)) {
    $bn_gr_skin_set = array();
}
if(!isset($bn_gr_skin_set['img_width']))     $bn_gr_skin_set['img_width'] = 1200;
if(!isset($bn_gr_skin_set['img_height']))     $bn_gr_skin_set['img_height'] = 800;
if(!isset($bn_gr_skin_set['slidesToShow']))     $bn_gr_skin_set['slidesToShow'] = 1;
if(!isset($bn_gr_skin_set['slidesToScroll']))   $bn_gr_skin_set['slidesToScroll'] = 1;
if(!isset($bn_gr_skin_set['vertical']))         $bn_gr_skin_set['vertical'] = 'false';
if(!isset($bn_gr_skin_set['dots'] ))            $bn_gr_skin_set['dots'] = 'true';
if(!isset($bn_gr_skin_set['arrows']))           $bn_gr_skin_set['arrows'] = 'true';
if(!isset($bn_gr_skin_set['autoplay']))         $bn_gr_skin_set['autoplay'] = 'true';
if(!isset($bn_gr_skin_set['responsive']))       $bn_gr_skin_set['responsive'] = 'false';
if(!isset($bn_gr_skin_set['autoplaySpeed']))    $bn_gr_skin_set['autoplaySpeed'] = '2000';
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
        <th scope="row">슬라이드숫자</th>
        <td>
            <input type="text" name="bn_skin_set[slidesToShow]" value="<?php echo $bn_gr_skin_set['slidesToShow'] ?>" id="bn_skin_set_slidesToShow" class="frm_input" style="width:30px">
            ※ 한 번에 표시되는 슬라이드
        </td>
    </tr>
    <tr>
        <th scope="row">1회당 스크롤수</th>
        <td>
            <input type="text" name="bn_skin_set[slidesToScroll]" value="<?php echo $bn_gr_skin_set['slidesToScroll'] ?>" id="bn_skin_set_slidesToScroll" class="frm_input" style="width:30px">
            ※ 화살표 클릭시 넘어가는 슬라이드, 3입력시 3개씩 넘어감
        </td>
    </tr>
    <tr>
        <th scope="row">슬라이드방향</th>
        <td>
            <label for="bn_skin_set_vertical_1">
                <input type="radio" name="bn_skin_set[vertical]" value="true" id="bn_skin_set_vertical_1" class="frm_input"<?php echo ($bn_gr_skin_set['vertical'] == 'true') ? ' checked' : '' ?>>수직방향(상하)
            </label>
            &nbsp;&nbsp;
            <label for="bn_skin_set_vertical_2">
                <input type="radio" name="bn_skin_set[vertical]" value="false" id="bn_skin_set_vertical_2" class="frm_input"<?php echo ($bn_gr_skin_set['vertical'] == 'false') ? ' checked' : '' ?>>수평방향(좌우)
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">네비게이션표시</th>
        <td>
            <label for="bn_skin_set_dots_1">
                <input type="radio" name="bn_skin_set[dots]" value="true" id="bn_skin_set_dots_1" class="frm_input"<?php echo ($bn_gr_skin_set['dots'] == 'true') ? ' checked' : '' ?>>표시 O
            </label>
            &nbsp;&nbsp;
            <label for="bn_skin_set_dots_2">
                <input type="radio" name="bn_skin_set[dots]" value="false" id="bn_skin_set_dots_2" class="frm_input"<?php echo ($bn_gr_skin_set['dots'] == 'false') ? ' checked' : '' ?>>표시 X
            </label>
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
                2000 = 2초
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">반응형옵션</th>
        <td>
            <label for="bn_skin_set_responsive_1">
                <input type="radio" name="bn_skin_set[responsive]" value="true" id="bn_skin_set_responsive_1" class="frm_input"<?php echo ($bn_gr_skin_set['responsive'] == 'true') ? ' checked' : '' ?>>반응형 O
            </label>
            &nbsp;&nbsp;
            <label for="bn_skin_set_responsive_2">
                <input type="radio" name="bn_skin_set[responsive]" value="false" id="bn_skin_set_responsive_2" class="frm_input"<?php echo ($bn_gr_skin_set['responsive'] == 'false') ? ' checked' : '' ?>>반응형 X
            </label>
        </td>
    </tr>
    </tbody>
    </table>