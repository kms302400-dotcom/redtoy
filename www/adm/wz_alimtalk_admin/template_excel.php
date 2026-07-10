<?php
$sub_menu = '102840';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '템플릿 엑셀 일괄등록';
include_once(G5_PATH.'/head.sub.php');
?>

<script>
    var g5_admin_csrf_token_key = "<?php echo (function_exists('admin_csrf_token_key')) ? admin_csrf_token_key() : ''; ?>";
</script>
<script src="<?php echo G5_ADMIN_URL ?>/admin.js?ver=<?php echo G5_JS_VER; ?>"></script>

<style>
.local_desc01 {min-width:auto;margin:0 0 10px}
#excelfile_upload {padding:20px 0;width:100%;text-align:center;}
.excel-linker {font-weight:bold;font-size:14px;}
</style>

<div class="ifram-win">
    <h2><?php echo $g5['title']; ?></h2>

    <div class="local_desc01 local_desc">
        <p>
            <a href="./sample.xls" class="excel-linker">템플릿 일괄등록용 엑셀파일 필수 다운로드</a>
        </p>

        <p>
            엑셀파일을 이용하여 템플릿을 일괄등록할 수 있습니다.<br />
            <a href="./sample.xls">"템플릿 일괄등록용 엑셀파일"</a>을 다운로드 받으신 다음 수정 후 등록바랍니다.<br />
            @채널아이디 -> 업체에서 사용중인 카카오톡 채널 아이디로 반드시 수정<br />
            등록하신 엑셀파일을 <a href="https://www.bizmsg.kr/template/add/" target="_blank">비즈엠 사이트 로그인 > 템플릿 > 템플릿 등록 > 엑셀대량입력</a> 에서 동일하게 등록하시면 됩니다.<br />
            업로드가 안될경우 파일 <strong>확장자명이 xls 인지 확인</strong>해주세요.<br />
            <img src="./img/test_sample.png" border=0 />
        </p>
    </div>

    <form name="fitemexcel" method="post" action="./template_excel_update.php" enctype="multipart/form-data" autocomplete="off">

    <div id="excelfile_upload">
        <label for="excelfile">파일선택</label>&nbsp;
        <input type="file" name="excelfile" id="excelfile">
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="템플릿 엑셀파일 등록" class="btn_submit">
        <button type="button" onclick="parent.$.magnificPopup.close();">닫기</button>
    </div>

    </form>

</div>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>