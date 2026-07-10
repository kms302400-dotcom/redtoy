<?php
$sub_menu = '102810';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '알림톡 발송 결과';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sch_frdate = isset($_REQUEST['sch_frdate']) ? clean_xss_tags($_REQUEST['sch_frdate']) : '';
$sch_todate = isset($_REQUEST['sch_todate']) ? clean_xss_tags($_REQUEST['sch_todate']) : '';
$sch_success = isset($_REQUEST['sch_success']) ? clean_xss_tags($_REQUEST['sch_success']) : '';
$sch_tmplId = isset($_REQUEST['sch_tmplId']) ? clean_xss_tags($_REQUEST['sch_tmplId']) : '';
$sch_phn = isset($_REQUEST['sch_phn']) ? clean_xss_tags($_REQUEST['sch_phn']) : '';
$sch_reserved = isset($_REQUEST['sch_reserved']) ? clean_xss_tags($_REQUEST['sch_reserved']) : '';

$sql_common = " from {$g5['wz_alimtalk_log_table']} ";

$sql_search = " where (1) ";

if ($sch_frdate && $sch_todate) {
    $sql_search .= " and DATE(ao_time) between '".$sch_frdate."' and '".$sch_todate."' ";
    $qstr .= "&sch_frdate=".$sch_frdate."&sch_todate=".$sch_todate;
}
if ($sch_success <> '') {
    $sql_search .= " and ao_is_success = '".$sch_success."' ";
    $qstr .= "&sch_success=".$sch_success;
}
if ($sch_tmplId) {
    $sql_search .= " and ao_tmplId = '".$sch_tmplId."' ";
    $qstr .= "&sch_tmplId=".$sch_tmplId;
}
if ($sch_phn) {
    $sql_search .= " and ao_phn like '%".$sch_phn."%' ";
    $qstr .= "&sch_phn=".$sch_phn;
}
if ($sch_reserved <> '') {
    if ($sch_reserved == '1') {
        $sql_search .= " and ( ao_is_success = '0' and ao_reserveDt <> '0000-00-00 00:00:00' and ao_reserveDt > NOW() ) ";
    }
    else {
        $sql_search .= " and ( ao_is_success = '1' and (ao_reserveDt = '0000-00-00 00:00:00' or ao_reserveDt <= NOW() ) ) ";
    }
    $qstr .= "&sch_reserved=".$sch_reserved;
}

if (!$sst) {
    $sst  = "ao_id";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select
                count(*) as cnt,
                sum(case when ao_is_success = 1 then 1 else 0 end) as success_cnt,
                sum(case when ao_is_success = 0 then 1 else 0 end) as fail_cnt
            {$sql_common}
            {$sql_search}
            {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$success_count = $row['success_cnt'];
$fail_count = $row['fail_cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$bizmsg = new bizmsg();

unset($arr_data);
$arr_data = array();
$sql = " select *, TIMESTAMPDIFF(hour, ao_time, now()) as houre_diff
            {$sql_common}
            {$sql_search}
            {$sql_order}
            limit {$from_record}, {$rows} ";
$result = sql_query($sql);
while($row = sql_fetch_array($result)) {

    if (!$row['ao_is_success'] && $row['houre_diff'] < 48) { // 발송성공이 아닐경우, 48시간이 지난건은 조회되지 않음.
        $data = $bizmsg->report($row['ao_msgid']);
        if ($data !== false) {
            $row['ao_is_success'] = ($data['message'] == 'K000' || $data['message'] == 'M000' || $data['message'] == 'R000' ? 1 : 0); // 카카오 비즈메시지 발송, SMS/LMS, 발송 예약 성공일경우
            $row['ao_msgid'] = $data['msgid'];
            $row['ao_result'] = $ALIMTALK_RESULT_CODE[$data['message']] ? $ALIMTALK_RESULT_CODE[$data['message']] : $data['message'];
            sql_query("update {$g5['wz_alimtalk_log_table']} set ao_msgid = '".$row['ao_msgid']."', ao_result = '".$row['ao_result']."', ao_is_success = '".$row['ao_is_success']."' where ao_id = '".$row['ao_id']."'");
        }
    }

    $row['is_reserved'] = false;
    if ($row['ao_is_success'] == '0' && $row['ao_reserveDt'] <> '0000-00-00 00:00:00' && $row['ao_reserveDt'] > G5_TIME_YMDHIS) {
        $row['is_reserved'] = true;
    }

    if ($row['ao_reserveDt'] == '0000-00-00 00:00:00') {
        $row['ao_reserveDt'] = '-';
    }


    $arr_data[] = $row;
}
$cnt_data = count($arr_data);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$colspan = 8;
?>

<style>
.txt-at-type {color:blue}
.local_sch03 strong {width:140px}
tr.resultfail {color:red}
</style>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <span class="btn_ov01"><span class="ov_txt">전체</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span>
    <span class="btn_ov01"><span class="ov_txt">성공</span><span class="ov_num"> <?php echo number_format($success_count); ?>건</span></span>
    <span class="btn_ov01"><span class="ov_txt">실패</span><span class="ov_num"> <?php echo number_format($fail_count); ?>건</span></span>
</div>

<form name="fsearch" id="fsearch" class="local_sch03" method="get" onsubmit="return getSearch(this);">

<div class="wz_tbl_1">
    <table cellpadding="0" cellspacing="0" border="0">
    <colgroup>
        <col width="10%">
        <col width="40%">
        <col width="10%">
        <col width="40%">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">요청일자</th>
        <td colspan="3">
            <input type="text" id="sch_frdate" name="sch_frdate" value="<?php echo $sch_frdate;?>" class="frm_input" size="10" maxlength="10"> ~
            <input type="text" id="sch_todate" name="sch_todate" value="<?php echo $sch_todate;?>" class="frm_input" size="10" maxlength="10">
            <button type="button" onclick="javascript:set_date('오늘');">오늘</button>
            <button type="button" onclick="javascript:set_date('어제');">어제</button>
            <button type="button" onclick="javascript:set_date('이번주');">이번주</button>
            <button type="button" onclick="javascript:set_date('이번달');">이번달</button>
            <button type="button" onclick="javascript:set_date('지난주');">지난주</button>
            <button type="button" onclick="javascript:set_date('지난달');">지난달</button>
            <button type="button" onclick="javascript:set_date('전체');">전체</button>
        </td>
    </tr>
    <tr>
        <th scope="col">템플릿코드</th>
        <td>
            <input type="text" name="sch_tmplId" id="sch_tmplId" value="<?php echo $sch_tmplId;?>" class="frm_input" style="width:170px;" maxlength="50" />
        </td>
        <th scope="col">수신자번호</th>
        <td>
            <input type="text" name="sch_phn" id="sch_phn" value="<?php echo $sch_phn;?>" class="frm_input" style="width:170px;" maxlength="50" />
        </td>
    </tr>
    <tr>
        <th scope="col">발송성공여부</th>
        <td>
            <input type="radio" name="sch_success" value="" id="sch_success1" <?php echo ($sch_success == "" ? "checked=checked" : "");?>>
            <label for="sch_success1">전체</label>
            <input type="radio" name="sch_success" value="1" id="sch_success2" <?php echo ($sch_success == "1" ? "checked=checked" : "");?>>
            <label for="sch_success2">성공</label>
            <input type="radio" name="sch_success" value="0" id="sch_success3" <?php echo ($sch_success == "0" ? "checked=checked" : "");?>>
            <label for="sch_success3">실패</label>
        </td>
        <th scope="col">발송상태</th>
        <td>
            <input type="radio" name="sch_reserved" value="" id="sch_reserved1" <?php echo ($sch_reserved == "" ? "checked=checked" : "");?>>
            <label for="sch_reserved1">전체</label>
            <input type="radio" name="sch_reserved" value="1" id="sch_reserved2" <?php echo ($sch_reserved == "1" ? "checked=checked" : "");?>>
            <label for="sch_reserved2">발송대기건</label>
            <input type="radio" name="sch_reserved" value="0" id="sch_reserved3" <?php echo ($sch_reserved == "0" ? "checked=checked" : "");?>>
            <label for="sch_reserved3">발송완료건</label>
        </td>
    </tr>
    <tr>
        <th></th>
        <td colspan="3">
            <input type="submit" value="검색하기" class="btn_01 btn">
        </td>
    </tr>
    </table>
</div>

</form>

<div class="local_desc01 local_desc">
    <ol>
        <li><strong>48 시간이 지난 발송정보는 발송동기화 처리가 되지 않으므로</strong> 비즈엠 사이트에서 확인바랍니다.</li>
        <li><strong>예약발송건을 선택삭제 할 경우 예약발송 취소 와 함께 삭제처리됩니다.</strong></li>
        <li>선택삭제를 하여도 비즈엠 사이트의 발신목록에는 정보가 남아 있으니 참고바랍니다.</li>
    </ol>
</div>

<form name="frm" id="frm" method="post" action="./send_list_update.php" onsubmit="return frm_submit(this);">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="sch_frdate" value="<?php echo $sch_frdate; ?>">
<input type="hidden" name="sch_todate" value="<?php echo $sch_todate; ?>">
<input type="hidden" name="sch_success" value="<?php echo $sch_success; ?>">
<input type="hidden" name="sch_tmplId" value="<?php echo $sch_tmplId; ?>">
<input type="hidden" name="sch_phn" value="<?php echo $sch_phn; ?>">
<input type="hidden" name="sch_reserved" value="<?php echo $sch_reserved; ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">템플릿 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col">No.</th>
        <th scope="col">템플릿코드</th>
        <th scope="col">수신자번호</th>
        <th scope="col">메시지내용</th>
        <th scope="col">결과</th>
        <th scope="col">예약일시</th>
        <th scope="col">요청일시</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $z = 0;
    if ($cnt_data > 0) {
        foreach ((array)$arr_data as $k => $v) {

        $num = number_format($total_count - ($page - 1) * $rows - $z);
        $bg = 'bg'.($z%2);

        $tdClass = '';
        if (!$v['ao_is_success']) {
            $tdClass = ' resultfail';
        }
        ?>

        <tr class="<?php echo $bg.$tdClass; ?>">
            <td class="td_chk">
                <input type="hidden" name="ao_id[<?php echo $z; ?>]" value="<?php echo $v['ao_id']; ?>">
                <input type="checkbox" id="chk_<?php echo $z; ?>" name="chk[]" value="<?php echo $z; ?>" title="내역선택">
            </td>
            <td class="td_alignc"><?php echo $num; ?></td>
            <td class="td_alignc"><?php echo $v['ao_tmplId'];?></td>
            <td class="td_alignc"><?php echo $v['ao_phn'];?></td>
            <td class="td_alignc"><?php echo $v['ao_msg'];?></td>
            <td class="td_alignc"><?php echo $v['ao_result'];?></td>
            <td class="td_alignc"><?php echo $v['ao_reserveDt'];?></td>
            <td class="td_alignc"><?php echo $v['ao_time'];?></td>
        </tr>

        <?php
        $z++;
        }
    }

    if ($z == 0)
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn_01 btn">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
$(function() {

	// iframe 윈도우
	$('.wz-iframe-win').magnificPopup({
		type: 'iframe',
		overflowY: 'scroll',
	});

});
function set_date(today)
{
    <?php
    $date_term = date('w', G5_SERVER_TIME);
    $week_term = $date_term + 7;
    $last_term = strtotime(date('Y-m-01', G5_SERVER_TIME));
    ?>
    if (today == "오늘") {
        document.getElementById("sch_frdate").value = "<?php echo G5_TIME_YMD; ?>";
        document.getElementById("sch_todate").value = "<?php echo G5_TIME_YMD; ?>";
    } else if (today == "어제") {
        document.getElementById("sch_frdate").value = "<?php echo date('Y-m-d', G5_SERVER_TIME - 86400); ?>";
        document.getElementById("sch_todate").value = "<?php echo date('Y-m-d', G5_SERVER_TIME - 86400); ?>";
    } else if (today == "이번주") {
        document.getElementById("sch_frdate").value = "<?php echo date('Y-m-d', strtotime('-'.$date_term.' days', G5_SERVER_TIME)); ?>";
        document.getElementById("sch_todate").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
    } else if (today == "이번달") {
        document.getElementById("sch_frdate").value = "<?php echo date('Y-m-01', G5_SERVER_TIME); ?>";
        document.getElementById("sch_todate").value = "<?php echo date('Y-m-d', G5_SERVER_TIME); ?>";
    } else if (today == "지난주") {
        document.getElementById("sch_frdate").value = "<?php echo date('Y-m-d', strtotime('-'.$week_term.' days', G5_SERVER_TIME)); ?>";
        document.getElementById("sch_todate").value = "<?php echo date('Y-m-d', strtotime('-'.($week_term - 6).' days', G5_SERVER_TIME)); ?>";
    } else if (today == "지난달") {
        document.getElementById("sch_frdate").value = "<?php echo date('Y-m-01', strtotime('-1 Month', $last_term)); ?>";
        document.getElementById("sch_todate").value = "<?php echo date('Y-m-t', strtotime('-1 Month', $last_term)); ?>";
    } else if (today == "전체") {
        document.getElementById("sch_frdate").value = "";
        document.getElementById("sch_todate").value = "";
    }
}
function frm_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택삭제") {
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }

    return true;
}
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>