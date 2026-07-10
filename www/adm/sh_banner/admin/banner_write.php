<?php
$sub_menu = '800200';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if($bn_id) {
    $bn = sql_fetch(" select * from {$g5['sh_banner_table']} where bn_id = '{$bn_id}'  ");
    if(!$bn['bn_id']) {
        alert('삭제된 배너입니다.');
    }
} else {
    $bn['bn_gr_id'] = $_GET['bn_gr_id'];
}
$sh_banner_group_list = sh_banner_get_group_list();
if(!$bn['bn_target']) {
    $bn['bn_target'] = '_self';
    $bn['bn_status'] = 1;
    $bn['bn_date_start'] = G5_TIME_YMD;
}
$g5['title'] .= '배너 등록 및 수정';
include_once(G5_ADMIN_PATH.'/admin.head.php');
include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');

?>
<style>
    #fbanner label {cursor:pointer}
    button.set_date {padding:3px 5px;border:1px solid #ccc;background:#f1f1f1;}
    #bn_date_start, #bn_date_end {width:6em}
</style>
<form name="fbanner" id="fbanner" action="./banner_write_update.php" onsubmit="return fbanner_submit(this);" method="post" enctype="multipart/form-data">
    <input type="hidden" name="bn_id" value="<?php echo $bn_id ?>">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="selected_bn_gr_id" value="<?php echo $bn_gr_id ?>">
    <input type="hidden" name="token" value="">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <colgroup>
                <col class="grid_3">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th scope="row">배너그룹</th>
                <td>
                    <select name="bn_gr_id" class="frm_input required" title="배너그룹">
                        <?php
                        if(count($sh_banner_group_list) > 1) echo '<option value="">::배너그룹::</option>';
                        foreach($sh_banner_group_list as $key => $gr_item) {
                            ?>
                            <option value="<?php echo $gr_item['bn_gr_id'] ?>"<?php echo get_selected($bn['bn_gr_id'], $gr_item['bn_gr_id']); ?>><?php echo $gr_item['bn_gr_name'] ?></option>
                        <?php } ?>
                        <select>
                </td>
            </tr>
            <tr>
                <th scope="row">배너명</th>
                <td>
                    <input type="text" name="bn_title" value="<?php echo $bn['bn_title'] ?>" id="bn_title" required class="frm_input required" maxlength="50" style="width:80%">
                </td>
            </tr>
            <tr>
                <th scope="row">링크</th>
                <td>
                    <input type="text" name="bn_href" value="<?php echo $bn['bn_href'] ?>" id="bn_href" class="frm_input" maxlength="255" style="width:95%">
                </td>
            </tr>
            <tr>
                <th scope="row">타겟</th>
                <td>
                    <label for="bn_target_self">
                        <input type="radio" name="bn_target" value="_self" id="bn_target_self" class="frm_input"<?php echo ($bn['bn_target'] == '_self') ? ' checked' : ''; ?>> 현재창
                    </label>
                    &nbsp;&nbsp;
                    <label for="bn_target_blank">
                        <input type="radio" name="bn_target" value="_blank" id="bn_target_blank" class="frm_input"<?php echo ($bn['bn_target'] == '_blank') ? ' checked' : ''; ?>> 새창
                    </label>
                    &nbsp;&nbsp;
                    <label for="bn_target_opt">
                        <input type="radio" name="bn_target" value="사용자입력" id="bn_target_opt" class="frm_input"<?php echo ($bn['bn_target'] != '_self' && $bn['bn_target'] != '_blank') ? ' checked' : ''; ?>> 사용자
                    </label>
                    &nbsp;&nbsp;
                    <input type="text" name="bn_target_user" value="<?php echo ($bn['bn_target'] != '_self' && $bn['bn_target'] != '_blank') ? $bn['bn_target'] : ''; ?>" id="bn_target_user" class="frm_input" maxlength="255" style="display:none;width:140px">
                </td>
            </tr>
            <tr>
                <th scope="row">순서</th>
                <td>
                    <input type="text" name="bn_order" value="<?php echo $bn['bn_order'] ?>" id="bn_order" class="frm_input" maxlength="10" style="width:80px"> ※ 숫자가 높을수록 먼저 출력 됩니다.
                </td>
            </tr>
            <tr>
                <th scope="row">노출일</th>
                <td>
                    <input type="text" name="bn_date_start" value="<?php echo $bn['bn_date_start'] ?>" id="bn_date_start" class="frm_input required" required style="width:80px;text-align:center">
                    ~
                    <input type="text" name="bn_date_end" value="<?php echo $bn['bn_date_end'] ?>" id="bn_date_end" class="frm_input required" required style="width:80px;text-align:center">
                    <button type="button" class="set_date" onclick="javascript:set_date('내일');">내일</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('이번주');">이번주</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('이번달');">이번달</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('다음주');">다음주</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('다음달');">다음달</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('1주일');">1주일</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('1개월');">1개월</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('3개월');">3개월</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('6개월');">6개월</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('1년');">1년</button>
                    <button type="button" class="set_date" onclick="javascript:set_date('10년');">10년</button>
                </td>
            </tr>
            <tr>
                <th scope="row">이미지첨부</th>
                <td>
                    <input type="file" name="bn_file[]" id="bf_file" title="이미지첨부" class="frm_file">
                    <?php if($bn_id) echo '<img src="'.G5_DATA_URL.'/file/_sh_banner_/'.$bn['bn_filename'].'" style="display:block;margin:10px 0;max-width:300px">'; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">메모</th>
                <td>
                    <textarea name="bn_memo"><?=$bn['bn_memo']?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">상태</th>
                <td>
                    <label for="bn_status_1">
                        <input type="radio" name="bn_status" value="1" id="bn_status_1" class="frm_input"<?php if($bn['bn_status'] == 1) echo ' checked'; ?>> 노출
                    </label>
                    &nbsp;&nbsp;
                    <label for="bn_status_0">
                        <input type="radio" name="bn_status" value="0" id="bn_status_0" class="frm_input"<?php if(!$bn['bn_status']) echo ' checked'; ?>> 일시정지
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <a href="./banner_list.php?<?php echo $qstr ?>" class="btn btn_02">목록</a>
        <input type="submit" value="확인" id="btn_submit" class="btn_submit btn" accesskey='s'>
    </div>
</form>
<script>
    $("#bn_date_start").datepicker({
        dateFormat: "yy-mm-dd",
        showButtonPanel: true,
        onSelect: function(dateText, inst) {
            var end_date = $("#bn_date_end").val();
            if(end_date && end_date < dateText) {
                alert("시작일은 종료일 보다 늦을 수 없습니다.");
                $(this).val("");
                return false;
            }
        }
    });
    $("#bn_date_end").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: "+0d",
        showButtonPanel: true,
        onSelect: function(dateText, inst) {
            var st_date = $("#bn_date_start").val();
            if(st_date && st_date > dateText) {
                alert("종료일은 시작일 보다 빠를 수 없습니다.");
                $(this).val("");
                return false;
            }
        }
    });
    $("#bn_target_opt").on("click", function() {
        $("#bn_target_user").css("display","inline-block").focus();
    });
    $("#bn_target_self, #bn_target_blank").on("click", function() {
        $("#bn_target_user").css("display","none");
        $("#bn_target_user").val("");
    });


    function set_date(today)
    {
        <?php
        $date_term = date('w', G5_SERVER_TIME);
        $week_term = 7-$date_term;
        ?>
        if( !$("#bn_date_start").val() ) {
            document.getElementById("bn_date_start").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
        }
        if (today == "내일") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', G5_SERVER_TIME + 86400); ?>";
        } else if (today == "이번주") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('+'.$week_term.' days', G5_SERVER_TIME)); ?>";
        } else if (today == "이번달") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-t', G5_SERVER_TIME); ?>";
        } else if (today == "다음주") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('+'.($week_term + 7).' days', G5_SERVER_TIME)); ?>";
        } else if (today == "다음달") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-t', strtotime('+1 Month', G5_SERVER_TIME)); ?>";
        } else if (today == "1주일") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('+6 days', G5_SERVER_TIME)); ?>";
        } else if (today == "1개월") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('-1 days +1 Month', G5_SERVER_TIME)); ?>";
        } else if (today == "3개월") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('-1 days +3 Month', G5_SERVER_TIME)); ?>";
        } else if (today == "6개월") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('-1 days +6 Month', G5_SERVER_TIME)); ?>";
        } else if (today == "1년") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('-1 days +12 Month', G5_SERVER_TIME)); ?>";
        } else if (today == "10년") {
            document.getElementById("bn_date_end").value = "<?php echo date('Y-m-d', strtotime('-1 days +120 Month', G5_SERVER_TIME)); ?>";
        }
    }

    function fbanner_submit(f) {
        document.getElementById("btn_submit").disabled = "disabled";
        return true;
    }
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
