<?php
$sub_menu = '800300';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql = " select count(*) as cnt from {$g5['sh_banner_group_table']} ";
$row = sql_fetch($sql);
if(!isset($row['cnt'])) {
    $sql = "
        CREATE TABLE `{$g5['sh_banner_group_table']}` (
          `bn_gr_id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `bn_gr_name` varchar(50) NOT NULL,
          `bn_gr_memo` varchar(255) NOT NULL,
          `bn_gr_level_start` tinyint(1) unsigned NOT NULL,
          `bn_gr_level_end` tinyint(10) unsigned NOT NULL,
          `bn_gr_order` int(11) unsigned NOT NULL,
          `bn_order_opt` tinyint(1) unsigned NOT NULL,
          `bn_count_limit` tinyint(1) unsigned NOT NULL,
          `bn_gr_pc_use` tinyint(4) NOT NULL,
          `bn_gr_mobile_use` tinyint(4) NOT NULL,
          `bn_gr_skin` varchar(50) NOT NULL,
          `bn_gr_skin_set` text NOT NULL,
          INDEX bn_gr_order (bn_gr_order),
          INDEX bn_gr_name (bn_gr_name)
        );
    ";
    sql_query($sql);
}

$sql = " select count(*) as cnt, bn_gr_id from {$g5['sh_banner_table']} group by bn_gr_id";
$result = sql_query($sql);
while($row=sql_fetch_array($result)) {
    $group_banner_count[$row['bn_gr_id']] = $row['cnt'];
}

$sql_common = " from {$g5['sh_banner_group_table']} ";
$sql_search = " where (1) ";

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case "bn_gr_level_start" :
        case "bn_gr_level_end" :
        case "bn_gr_level_order" :
        case "bn_gr_id" :
            $sql_search .= " ($sfl = '$stx') ";
            break;
        default :
            $sql_search .= " ($sfl like '%$stx%') ";
            break;
    }
    $sql_search .= " ) ";
}

if (!$sst) {
    $sst  = "bn_gr_order";
    $sod = "desc";
}
$sql_order = " order by $sst $sod ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';
$g5['title'] = '배너그룹관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$colspan = 15;
$sh_banner_order_opt_text = array();
$sh_banner_order_opt_text[1] = '순서입력값';
$sh_banner_order_opt_text[2] = '이전등록순';
$sh_banner_order_opt_text[3] = '최근등록순';
$sh_banner_order_opt_text[4] = '랜덤순';
?>

<style>
.tbl_head01 tbody td.text-left {text-align:left}
.bn_gr_pc_use0, .bn_gr_pc_use1, .bn_gr_mobile_use0, .bn_gr_mobile_use1 {
    display: inline-block;
    padding: 0.2em 0.5em;
    margin: 0 0.2em;
    border: 1px solid #eed;
    border-radius: 0.5em;
    font-weight: bold;
}
.bn_gr_pc_use1, .bn_gr_mobile_use1 {
    background-color: #33e;
    color: #fff;
}
</style>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <span class="btn_ov01"><span class="ov_txt">배너리스트</span><span class="ov_num"> <?php echo number_format($total_count) ?>건</span></span>
</div>
<?php
//echo $sql;
?>
<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="bn_gr_name"<?php echo get_selected($_GET['sfl'], "bn_gr_name", true); ?>>그룹명</option>
    <option value="bn_gr_memo"<?php echo get_selected($_GET['sfl'], "bn_gr_memo"); ?>>메모</option>
    <option value="bn_gr_id"<?php echo get_selected($_GET['sfl'], "bn_gr_id"); ?>>그룹ID</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" required class="required frm_input">
<input type="submit" value="검색" class="btn_submit">

</form>

<form name="fboardlist" id="fboardlist" action="./banner_group_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <colgroup>
        <col>
        <col class="grid_1">
        <col class="grid_1">
        <col>
        <col>
        <col class="grid_2">
        <col class="grid_3">
        <col class="grid_2">
        <col class="grid_2">
        <col class="grid_2">
        <col class="grid_3">
        <col class="grid_3">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">배너그룹 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th>그룹ID</th>
        <th>순서</th>
        <th>그룹명</th>
        <th>그룹메모</th>
        <th>노출레벨</th>
        <th>노출기기</th>
        <th>등록배너</th>
        <th>노출순서</th>
        <th>노출갯수</th>
        <th>사용스킨</th>
        <th>작업</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        if(!$row['bn_count_limit']) $row['bn_count_limit'] = '전체';
        $bg = 'bg'.($i%2);
        $update_href = './banner_group_write.php?bn_gr_id='.$row['bn_gr_id'];
        $gr_delete_href = 'javascript:sh_gr_del('.$row['bn_gr_id'].')';
        $bn_write_href = './banner_write.php?bn_gr_id='.$row['bn_gr_id'];
    ?>

    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['bn_gr_name']) ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $row['bn_gr_id'] ?>" id="chk_<?php echo $i ?>">
        </td>
        <td><?php echo $row['bn_gr_id'] ?></td>
        <td><?php echo $row['bn_gr_order'] ?></td>
        <td class="text-left"><?php echo $row['bn_gr_name'] ?></td>
        <td class="text-left"><?php echo $row['bn_gr_memo'] ?></td>
        <td><?php echo $row['bn_gr_level_start'].' ~ '.$row['bn_gr_level_end'] ?></td>
        <td>
            <?php
                echo '<span class="bn_gr_pc_use'.$row['bn_gr_pc_use'].'">PC</span>';
                echo '<span class="bn_gr_mobile_use'.$row['bn_gr_mobile_use'].'">모바일</span>';
            ?>
        </td>
        <td>
            <a href="./banner_list.php?bn_gr_id=<?php echo $row['bn_gr_id'] ?>">
            <?php echo number_format($group_banner_count[$row['bn_gr_id']]) ?>
        </td>
        <td>
            <?php echo $sh_banner_order_opt_text[$row['bn_order_opt']] ?>
        </td>
        <td>
            <?php echo $row['bn_count_limit'] ?>
        </td>
        <td>
            <?php echo $row['bn_gr_skin'] ?>
        </td>
        <td class="td_mng td_mng_m">
            <a href="<?php echo $update_href ?>" class="btn btn_03">수정</a>
            <a href="<?php echo $gr_delete_href ?>" class="btn btn_02">삭제</a>
            <a href="<?php echo $bn_write_href ?>" class="btn btn_01">추가</a>
        </td>
    </tr>
    <?php
    }
    if ($i == 0)
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <?php if ($is_admin == 'super') { ?>
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn_02 btn">
    <a href="./banner_group_write.php" id="bo_add" class="btn_01 btn">그룹추가</a>
    <?php } ?>
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?bn_gr_id='.$bn_gr_id.'&'.$qstr.'&amp;page='); ?>

<script>
function fboardlist_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택삭제") {
        if(!confirm("선택한 그룹을 정말 삭제하시겠습니까?\r\n(해당 그룹의 배너도 전체 삭제됩니다)")) {
            return false;
        }
    }

    return true;
}

function sh_gr_del(bn_gr_id) {
   if(!confirm("선택한 그룹을 정말 삭제하시겠습니까?\r\n(해당 그룹의 배너도 전체 삭제됩니다)")) {
        return false;
    }
    location.replace(encodeURI('./banner_group_list_update.php?act_button=선택삭제&chk[]=' + bn_gr_id)) ;
}
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
