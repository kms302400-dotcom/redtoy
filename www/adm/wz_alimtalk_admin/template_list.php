<?php
$sub_menu = '102840';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '알림톡 템플릿 관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sql_common = " from {$g5['wz_alimtalk_template_table']} ";

$sql_search = " where (1) ";

$sch_cate = isset($_REQUEST['sch_cate']) ? clean_xss_tags($_REQUEST['sch_cate']) : '';
$sch_tmplId = isset($_REQUEST['sch_tmplId']) ? clean_xss_tags($_REQUEST['sch_tmplId']) : '';
$sch_title = isset($_REQUEST['sch_title']) ? clean_xss_tags($_REQUEST['sch_title']) : '';
$sch_msg = isset($_REQUEST['sch_msg']) ? clean_xss_tags($_REQUEST['sch_msg']) : '';

if ($sch_cate) {
    $sql_search .= " and at_tmplId in (select atc.at_tmplId from {$g5['wz_alimtalk_template_cate_table']} as atc where atc_code = '".$sch_cate."') ";
    $qstr .= "&sch_cate=".$sch_cate;
}
if ($sch_tmplId) {
    $sql_search .= " and at_tmplId = '".$sch_tmplId."' ";
    $qstr .= "&sch_tmplId=".$sch_tmplId;
}
if ($sch_title) {
    $sql_search .= " and at_title like '%".$sch_title."%' ";
    $qstr .= "&sch_title=".$sch_title;
}
if ($sch_msg) {
    $sql_search .= " and at_msg like '%".$sch_msg."%' ";
    $qstr .= "&sch_msg=".$sch_msg;
}

if (!$sst) {
    $sst  = "at_id";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
            {$sql_common}
            {$sql_search}
            {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

unset($arr_data);
$arr_data = array();
$sql = " select *
            {$sql_common}
            {$sql_search}
            {$sql_order}
            limit {$from_record}, {$rows} ";
$result = sql_query($sql);
while($row = sql_fetch_array($result)) {

    // 템플릿선택정보
    $sql2 = " select group_concat(atc_code) as code from {$g5['wz_alimtalk_template_cate_table']} where at_tmplId = '".$row['at_tmplId']."' ";
    $row2 = sql_fetch($sql2);
    $row['atc_code'] = $row2['code'];

    // 버튼정보
    $query2 = "select * from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$row['at_tmplId']."' order by atb_id asc ";
    $res2 = sql_query($query2);
    while($row2 = sql_fetch_array($res2)) {
        $row2['atb_type_str'] = array_search($row2['atb_type'], $ALIMTALK_BUTN_TYPE);
        $row['btn'][] = $row2;
    }

    $arr_data[] = $row;
}
$cnt_data = count($arr_data);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$colspan = 10;
?>

<style>
.txt-at-type {color:blue}
.local_sch03 strong {width:140px}
</style>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <span class="btn_ov01"><span class="ov_txt">전체</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span>
</div>

<form name="fsearch" id="fsearch" class="local_sch03" method="get" onsubmit="return getSearch(this);">

<div class="wz_tbl_1">
    <table cellpadding="0" cellspacing="0" border="0">
    <colgroup>
        <col width="120px">
        <col width="auto">
        <col width="120px">
        <col width="auto">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">발송방법</th>
        <td colspan="3">
            <select name="sch_cate" id="sch_cate">
                <option value="" selected="selected">전체</option>
                <?php
                foreach ((array)$ALIMTALK_SEND_CATE as $k => $v) {
                    $selected = '';
                    if ($sch_cate == $v) {
                        $selected = 'selected=selected';
                    }
                    echo '<option value="'.$v.'" '.$selected.'>'.$v.'</option>'.PHP_EOL;
                }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="col">템플릿코드</th>
        <td>
            <input type="text" name="sch_tmplId" id="sch_tmplId" value="<?php echo $sch_tmplId;?>" class="frm_input" style="width:170px;" maxlength="50" />
        </td>
        <th scope="col">템플릿명</th>
        <td>
            <input type="text" name="sch_title" id="sch_title" value="<?php echo $sch_title;?>" class="frm_input" style="width:170px;" maxlength="50" />
        </td>
    </tr>
    <tr>
        <th scope="col">템플릿내용</th>
        <td colspan="3">
            <input type="text" name="sch_msg" id="sch_msg" value="<?php echo $sch_msg;?>" class="frm_input" style="width:370px;" maxlength="50" />
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

<div class="local_desc02 local_desc">
    <p>
        등록된 템플릿을 사용하기 위해서는 반드시 <strong>우측상단의 [선택적용] 버튼을 클릭해서 템플릿을 사용가능상태로 설정</strong>해주셔야 합니다.
    </p>
</div>

<form name="frm" id="frm" method="post" action="./template_list_update.php" onsubmit="return frm_submit(this);">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">

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
        <th scope="col">템플릿명</th>
        <th scope="col">강조유형<br />(강조표기)</th>

        <th scope="col">템플릿내용</th>
        <th scope="col">버튼정보</th>
        <th scope="col">사용여부</th>
        <th scope="col">발송</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $z = 0;
    if ($cnt_data > 0) {
        foreach ((array)$arr_data as $k => $v) {

        $num = number_format($total_count - ($page - 1) * $rows - $z);
        $bg = 'bg'.($z%2);
        ?>

        <tr class="<?php echo $bg; ?>">
            <td class="td_chk">
                <input type="hidden" name="at_tmplId[<?php echo $z; ?>]" value="<?php echo $v['at_tmplId']; ?>">
                <input type="checkbox" id="chk_<?php echo $z; ?>" name="chk[]" value="<?php echo $z; ?>" title="내역선택">
            </td>
            <td class="td_alignc"><?php echo $num; ?></td>
            <td class="td_alignc"><?php echo $v['at_tmplId'] . ($v['atc_code'] ? '<br /><span class="txt-at-type">('.$v['atc_code'].')</span>' : ''); ?></td>
            <td class="td_alignc"><?php echo $v['at_title']; ?></td>
            <td class="td_alignc">
                <?php
                echo $v['at_emp_type'];
                if ($v['at_emp_type'] == 'TEXT' && $v['at_accent_title']) {
                    echo '<br />('.$v['at_accent_title'].')';
                }
                ?>
            </td>
            <td class="td_addr"><?php echo conv_content($v['at_msg'], ''); ?></td>
            <td class="td_alignc">
                <?php
                if (isset($v['btn']) && is_array($v['btn'])) {
                    foreach ((array)$v['btn'] as $k2 => $v2) {
                        echo '<div style="padding:5px 0;">';
                        echo '['.$v2['atb_name'].'] '.$v2['atb_type_str'].'<br />';
                        echo ($v2['atb_url_mobile'] ? '모바일링크 : '.$v2['atb_url_mobile'].'<br />' : '');
                        echo ($v2['atb_url_pc'] ? 'PC링크 : '.$v2['atb_url_pc'].'<br />' : '');
                        echo ($v2['atb_scheme_android'] ? 'android스킴 : '.$v2['atb_scheme_android'].'<br />' : '');
                        echo ($v2['atb_scheme_ios'] ? 'ios스킴 : '.$v2['atb_scheme_ios'].'<br />' : '');

                        if ($v2['atb_type'] == 'BF') {
                            echo ($v2['atb_plugin_id'] ? '비즈폼 key : '.$v2['atb_plugin_id'].'<br />' : '');
                        }
                        else {
                            echo ($v2['atb_plugin_id'] ? '플러그인 ID : '.$v2['atb_plugin_id'].'<br />' : '');
                        }

                        echo '</div>';
                    }
                }
                ?>
            </td>
            <td class="td_alignc"><?php echo $v['at_use'] ? '사용' : '사용안함'; ?></td>
            <td class="">
                <a href="./msg_send.php?at_tmplId=<?php echo $v['at_tmplId']; ?>" class="btn btn_03"><span class="sound_only"><?php echo $v['at_id']; ?> </span>발송</a>
            </td>
            <td class="">
                <a href="./template_form.php?w=u&amp;at_id=<?php echo $v['at_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03"><span class="sound_only"><?php echo $v['at_id']; ?> </span>수정</a>
            </td>
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
    선택한 템플릿을
    <select name="atc_code" id="atc_code">
        <option value="" selected="selected">전체</option>
        <?php
        foreach ((array)$ALIMTALK_SEND_CATE as $k => $v) {
            $selected = '';
            if ($sch_cate == $v) {
                $selected = 'selected=selected';
            }
            echo '<option value="'.$v.'" '.$selected.'>'.$v.'</option>'.PHP_EOL;
        }
        ?>
    </select> 으로
    <input type="submit" name="act_button" value="선택적용" onclick="document.pressed=this.value" class="btn_01 btn">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

    <a href="./template_form.php" class="btn btn_01">템플릿 추가</a>
    <a href="./template_excel.php" class="wz-iframe-win btn_01 btn">템플릿 엑셀 일괄등록</a>
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn_02 btn">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
function frm_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if(document.pressed == "선택적용") {
        var cnt = $("input[name='chk[]']:checked").length;
        if (cnt > 1) {
            alert("선택적용은 한개만 선택이 가능합니다.");
            return false;
        }
        if (f.atc_code.selectedIndex == 0) {
            alert("선택적용할 발송방법을 선택해주세요.");
            f.atc_code.focus();
            return false;
        }
    }

    if(document.pressed == "선택삭제") {
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }

    return true;
}
$(function() {

	// iframe 윈도우
	$('.wz-iframe-win').magnificPopup({
		type: 'iframe',
		overflowY: 'scroll',
	});

});
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>