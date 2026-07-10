<?php
$sub_menu = '102834';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, "w");

$g5['title'] = '친구톡 이미지 등록';

$fi_id = isset($_GET['fi_id']) ? clean_xss_tags(preg_replace('/[^0-9]/', '', $_GET['fi_id'])) : '';

if ($w == 'u') {
    $html_title = '친구톡 이미지 수정';

    $sql = " select * from {$g5['wz_alimtalk_fl_image_table']} where fi_id = '".$fi_id."' ";
    $db = sql_fetch($sql);
    if (!$db['fi_id']) alert('등록된 자료가 없습니다.');
}
else {
    $html_title = '친구톡 이미지 입력';
}

$qstr .= "&sch_filename=".$sch_filename;

include_once (G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_desc01 local_desc">
    일반 이미지 사이즈 및 허용 범위
    <ol>
        <li>권장 사이즈 : 720px * 720px</li>
        <li>제한 사이즈 : 가로 500px 미만 또는 가로:세로 비율이 2:1 미만 또는 3:4 초과시 업로드 불가</li>
        <li>파일 형식 및 크기 : jpg, png / 최대 500KB</li>
    </ol>
</div>

<form name="frm" action="./attch_image_form_update.php" method="post" onsubmit="return getAction(this);" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="fi_id" value="<?php echo $fi_id; ?>">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="page" value="<?php echo $page;?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">등록방식</th>
        <td>
            <?php echo help("비즈엠사이트에서 이미 등록이 된경우 '파일명 및 URL 만 등록' 을 선택해주세요."); ?>
            <input type="radio" name="upload_type" value="1" id="upload_type1" checked>
            <label for="upload_type1"> API업로드</label>&nbsp;
            <input type="radio" name="upload_type" value="0" id="upload_type2">
            <label for="upload_type2"> 파일명 및 URL 만 등록</label>
        </td>
    </tr>
    <tr class="upload_type1">
        <th scope="row">이미지파일</th>
        <td>
           <input type="file" name="fi_img" value="" id="fi_img" class="frm_input" size="50">
        </td>
    </tr>
    <tr class="upload_type0">
        <th scope="row">이미지 파일명</th>
        <td>
            <input type="text" name="fi_img_name" id="fi_img_name" class="frm_input" size="50">
        </td>
    </tr>
    <tr class="upload_type0">
        <th scope="row">이미지 URL</th>
        <td>
            <?php echo help("http:// 또는 https:// 포함해서 등록해주세요."); ?>
            <input type="text" name="fi_img_url" id="fi_img_url" class="frm_input" size="50">
        </td>
    </tr>
    <tr>
        <th scope="row">와이드형 여부</th>
        <td>
            <?php echo help("와이드형 이미지 제한 사이즈 : 800px * 600px<br />파일형식 및 크기 : jpg, png / 최대 2M"); ?>
            <input type="radio" name="fi_wide" value="Y" id="fi_wide1">
            <label for="fi_wide1"> Y</label>&nbsp;
            <input type="radio" name="fi_wide" value="N" id="fi_wide2" checked>
            <label for="fi_wide2"> N</label>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" value="확인" class="btn_submi btn btn_01" accesskey="s" >
    <a href="./attch_image_list.php?<?php echo $qstr; ?>" class="btn_02 btn">목록</a>
</div>

</form>

<script type="text/javascript">
<!--
    function getAction(f) {

        var upload_type = $(":input:radio[name=upload_type]:checked").val();
        if (upload_type == '1') { // API업로드
            if (!f.fi_img.value) {
                alert("이미지파일을 선택해주세요.");
                return false;
            }
        }
        else {
            if (!f.fi_img_url.value) {
                alert("이미지URL을 반드시 입력해주세요.");
                f.fi_img_url.focus();
                return false;
            }
        }

        return true;
    }
    $(function() {
        $(document).on('click', ':input:radio[name=upload_type]', function() {
            display_form_type();
        });
        display_form_type();

    });
    function display_form_type() {
        var upload_type = $(":input:radio[name=upload_type]:checked").val();
        if (upload_type == '1') {
            $('.upload_type1').show();
            $('.upload_type0').hide();
        }
        else {
            $('.upload_type1').hide();
            $('.upload_type0').show();
        }
    }
//-->
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>