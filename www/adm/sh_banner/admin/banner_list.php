<?php
$sub_menu = "800100";
include_once('./_common.php');
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$thumb_width = 100;
$sh_banner_group_list = sh_banner_get_group_list();
auth_check($auth[$sub_menu], 'r');

$sql = " select count(*) as cnt from {$g5['sh_banner_table']} ";
$row = sql_fetch($sql);
if(!isset($row['cnt'])) {
    $sql = "
        CREATE TABLE `{$g5['sh_banner_table']}` (
          `bn_id` int(11) NOT NULL AUTO_INCREMENT,
          `bn_gr_id` int(11) NOT NULL,
          `bn_status` tinyint(4) NOT NULL DEFAULT '1',
          `bn_title` varchar(255) NOT NULL,
          `bn_memo` varchar(255) NOT NULL,
          `bn_href` varchar(255) NOT NULL,
          `bn_target` varchar(255) NOT NULL,
          `bn_order` int(11) unsigned NOT NULL,
          `bn_date_start` date NOT NULL,
          `bn_date_end` date NOT NULL,
          `bn_filename` varchar(255) NOT NULL,
          `bn_hit` int(10) unsigned NOT NULL,
          `bn_datetime` datetime NOT NULL,
          PRIMARY KEY (`bn_id`),
          KEY `bn_gr_order` (`bn_order`),
          KEY `bn_gr_id` (`bn_gr_id`),
          KEY `bn_date_start` (`bn_date_start`),
          KEY `bn_date_end` (`bn_date_end`),
          KEY `bn_date_start_bn_date_end` (`bn_date_start`,`bn_date_end`)
        );
    ";
    sql_query($sql);
}

$sql = " select bn_gr_id, bn_gr_name from {$g5['sh_banner_group_table']} ";
$result = sql_query($sql);
while($row=sql_fetch_array($result)) {
    $group_list[$row['bn_gr_id']] = $row['bn_gr_name'];
}



$sql_common = " from {$g5['sh_banner_table']} ";
$sql_search = " where (1) ";

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case "bn_title" :
            $sql_search .= " ($sfl like '$stx%') ";
            break;
        default :
            $sql_search .= " ($sfl like '%$stx%') ";
            break;
    }
    $sql_search .= " ) ";
}

if($bn_gr_id) {
	$sql_search .= " and bn_gr_id = '{$bn_gr_id}' ";
}

if (!$sst) {
    $sst  = "bn_order {$sod}, bn_id {$sod} ";
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
$g5['title'] = '배너리스트';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$colspan = 15;
?>
<style>
.text-blue {color:#33e}
.text-red {color:#e33}
.text-gray {color:#aaa}
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
    <option value="bn_title"<?php echo get_selected($_GET['sfl'], "bn_title", true); ?>>배너명</option>
    <option value="bn_memo"<?php echo get_selected($_GET['sfl'], "bn_memo"); ?>>메모</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" required class="required frm_input">
<input type="submit" value="검색" class="btn_submit">

</form>

<ul class="anchor">
    <li class="<?php echo (!$bn_gr_id) ? 'selected' : ''; ?>"><a href="./banner_list.php">전체</a></li>
    <?php foreach((array)$group_list as $_gr_id => $_gr_name) { ?>
    <li class="<?php echo ($_gr_id == $bn_gr_id) ? 'selected' : ''; ?>"><a href="./banner_list.php?bn_gr_id=<?php echo $_gr_id ?>"><?php echo $_gr_name ?></a></li>
    <?php } ?>
</ul>

<form name="fboardlist" id="fboardlist" action="./banner_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
<input type="hidden" name="bn_group" value="<?php echo $bn_group ?>">
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
        <col class="grid_2">
        <col>
        <col>
        <col class="grid_3">
        <col class="grid_3">
        <col class="grid_4">
        <col class="grid_2">
        <col class="grid_2">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">배너 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th>배너ID</th>
        <th>순서</th>
        <th>그룹명</th>
        <th>배너명</th>
        <th>링크</th>
        <th>등록일</th>
        <th>이미지</th>
        <th>노출일정</th>
        <th>상태</th>
        <th>작업</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        $bg = 'bg'.($i%2);
        $update_href = './banner_write.php?w=u&bn_id='.$row['bn_id'];
        $bn_delete_href = 'javascript:sh_bn_del('.$row['bn_id'].')';
        $tname = thumbnail($row['bn_filename'], $g5['sh_banner_file_path'], $g5['sh_banner_file_path'].'/thumb', $thumb_width, 0, 0);
        if($tname) {
            $thumb_img = $g5['sh_banner_file_url'].'/thumb/'.$tname;
        } else {
            $thumb_img = $g5['sh_banner_file_url'].'/'.$row['bn_filename'];
        }
    ?>

    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['bn_title']) ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $row['bn_id'] ?>" id="chk_<?php echo $i ?>">
        </td>
        <td><?php echo $row['bn_id'] ?></td>
        <td><?php echo $row['bn_order'] ?></td>
        <td><?php echo $sh_banner_group_list[$row['bn_gr_id']]['bn_gr_name'] ?></td>
        <td class="td_left"><?php echo $row['bn_title'] ?></td>
        <td class="td_left"><?php echo $row['bn_href'] ?></td>
        <td><?php echo $row['bn_datetime'] ?></td>
        <td><img src="<?php echo $thumb_img ?>" style="max-width:100px"></td>
        <td>
            <?php echo $row['bn_date_start'].' ~ '.$row['bn_date_end'] ?>
        </td>
        <td>
            <?php
                if($row['bn_date_end'] < G5_TIME_YMD) {
                    echo '<span class="text-gray">기간종료</span>';
                } else {
                    echo ($row['bn_status'] == 1) ? '<span class="text-blue">노출</span>' : '<span class="text-red">일시정지</span>';
                }
            ?>
        </td>
        <td class="td_mng td_mng_m">
            <a href="<?php echo $update_href ?>" class="btn btn_03">수정</a>
            <a href="<?php echo $bn_delete_href ?>" class="btn btn_02">삭제</a>
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
    <a href="./banner_write.php?bn_gr_id=<?php echo $bn_gr_id ?>" id="bo_add" class="btn_01 btn">배너추가</a>
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
        if(!confirm("선택한 배너를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }

    return true;
}

function sh_bn_del(bn_id) {
   if(!confirm("선택한 배너를 정말 삭제하시겠습니까?")) {
        return false;
    }
    location.replace(encodeURI('./banner_list_update.php?act_button=선택삭제&chk[]=' + bn_id)) ;
}
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
