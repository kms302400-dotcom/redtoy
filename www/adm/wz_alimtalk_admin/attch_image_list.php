<?php
$sub_menu = '102834';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '친구톡 이미지 관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sch_filename = isset($_REQUEST['sch_filename']) ? clean_xss_tags($_REQUEST['sch_filename']) : '';

$sql_common = " from {$g5['wz_alimtalk_fl_image_table']} ";

$sql_search = " where (1) ";

if ($sch_filename) {
    $sql_search .= " and fi_img_name like '%".$sch_filename."%' ";
    $qstr .= "&sch_filename=".$sch_filename;
}

if (!$sst) {
    $sst  = "fi_id";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select
                count(*) as cnt
            {$sql_common}
            {$sql_search}
            {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$bizmsg = new bizmsg();

unset($arr_data);
$arr_data = array();
$sql = " select *
            {$sql_common}
            {$sql_search}
            {$sql_order}
            limit {$from_record}, {$rows} ";
$result = sql_query($sql);
while($row = sql_fetch_array($result)) {
    $arr_data[] = $row;
}
$cnt_data = count($arr_data);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$colspan = 6;
?>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <span class="btn_ov01"><span class="ov_txt">전체</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span>
</div>

<form name="fsearch" id="fsearch" class="local_sch03" method="get" onsubmit="return getSearch(this);">

<div class="wz_tbl_1">
    <table cellpadding="0" cellspacing="0" border="0">
    <colgroup>
        <col width="10%">
        <col width="90%">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">이미지파일명</th>
        <td>
            <input type="text" name="sch_filename" id="sch_filename" value="<?php echo $sch_filename;?>" class="frm_input" style="width:370px;" maxlength="50" />
        </td>
    </tr>
    <tr>
        <th></th>
        <td>
            <input type="submit" value="검색하기" class="btn_01 btn">
        </td>
    </tr>
    </table>
</div>

</form>

<div class="local_desc01 local_desc">
    <ol>
        <li>친구톡 발송시 이미지를 포함해서 발송을 원하실 경우 반드시 사전에 이미지파일이 등록되어있어야 합니다.</li>
        <li>비즈엠 사이트에서 친구톡이미지파일을 등록할 필요없이 바로 등록이 가능합니다.</li>
        <li>삭제할경우 비즈엠사이트의 친구톡이미지도 원격으로 삭제됩니다.</li>
    </ol>
</div>

<form name="frm" id="frm" method="post" action="./attch_image_list_update.php" onsubmit="return frm_submit(this);">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="sch_filename" value="<?php echo $sch_filename; ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col style="width:30px;"/>
        <col style="width:50px;"/>
        <col style="width:100px;"/>
        <col style="width:auto;"/>
        <col style="width:400px;"/>
        <col style="width:170px;"/>
    </colgroup>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">템플릿 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col">No.</th>
        <th scope="col">구분</th>
        <th scope="col">이미지</th>
        <th scope="col">이미지파일명 (이미지 KEY)</th>
        <th scope="col">등록일</th>
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
                <input type="hidden" name="fi_id[<?php echo $z; ?>]" value="<?php echo $v['fi_id']; ?>">
                <input type="checkbox" id="chk_<?php echo $z; ?>" name="chk[]" value="<?php echo $z; ?>" title="내역선택">
            </td>
            <td class="td_alignc"><?php echo $num; ?></td>
            <td class="td_alignc"><?php echo $v['fi_insert_type'];?></td>
            <td class="td_alignc"><a href="<?php echo $v['fi_img_url'];?>" class="wz-label image-popup" title="<?php echo get_text($v['fi_img_name']);?>"><?php echo $v['fi_img_url'];?></a></td>
            <td class="td_alignc"><?php echo $v['fi_img_name'];?></td>
            <td class="td_alignc"><?php echo $v['fi_time'];?></td>
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
    <a href="./attch_image_form.php" class="btn btn_01">친구톡 이미지 추가</a>
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

    if(document.pressed == "선택삭제") {
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }

    return true;
}
function getSearch() {
    return true;
}
$(document).ready(function() {

	$('.image-popup').magnificPopup({
		type: 'image',
		closeOnContentClick: true,
        fixedContentPos: true,
		mainClass: 'mfp-no-margins mfp-with-zoom', // class to remove default margin from left and right side
		image: {
			verticalFit: false
		}

	});
});
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>