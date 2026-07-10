<?php
$sub_menu = '600200';
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

$g5['title'] = '상품 SEO';
include_once (G5_ADMIN_PATH.'/admin.head.php');

if (!function_exists('shop_item_url')) {
    function shop_item_url($it_id, $add_param=''){
        $add_params = $add_param ? '&'.$add_param : '';
        return G5_SHOP_URL.'/item.php?it_id='.urlencode($it_id).$add_params;
    }
}

// 분류
$ca_list  = '<option value="">선택</option>'.PHP_EOL;
$sql = " select * from {$g5['g5_shop_category_table']} ";
if ($is_admin != 'super')
    $sql .= " where ca_mb_id = '{$member['mb_id']}' ";
$sql .= " order by ca_order, ca_id ";
$result = sql_query($sql);
for ($i=0; $row=sql_fetch_array($result); $i++)
{
    $len = strlen($row['ca_id']) / 2 - 1;
    $nbsp = '';
    for ($i=0; $i<$len; $i++) {
        $nbsp .= '&nbsp;&nbsp;&nbsp;';
    }
    $ca_list .= '<option value="'.$row['ca_id'].'">'.$nbsp.$row['ca_name'].'</option>'.PHP_EOL;
}

$where = " and ";
$sql_search = "";
if ($stx != "") {
    if ($sfl != "") {
        $sql_search .= " $where $sfl like '%$stx%' ";
        $where = " and ";
    }
    if ($save_stx != $stx)
        $page = 1;
}

if ($sca != "") {
    $sql_search .= " $where (a.ca_id like '$sca%' or a.ca_id2 like '$sca%' or a.ca_id3 like '$sca%') ";
}

if ($sfl == "")  $sfl = "it_name";

$sql_common = " from {$g5['g5_shop_item_table']} a ,
                     {$g5['g5_shop_category_table']} b
               where (a.ca_id = b.ca_id";
if ($is_admin != 'super')
    $sql_common .= " and b.ca_mb_id = '{$member['mb_id']}'";
$sql_common .= ") ";
$sql_common .= $sql_search;

// 테이블의 전체 레코드수만 얻음
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

if (!$sst) {
    $sst  = "it_id";
    $sod = "desc";
}
$sql_order = "order by $sst $sod";


$sql  = " select *
           $sql_common
           $sql_order
           limit $from_record, $rows ";
$result = sql_query($sql);

//$qstr  = $qstr.'&amp;sca='.$sca.'&amp;page='.$page;
$qstr  = $qstr.'&amp;sca='.$sca.'&amp;page='.$page.'&amp;save_stx='.$stx;

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';
?>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <span class="btn_ov01"><span class="ov_txt">등록된 상품</span><span class="ov_num"> <?php echo $total_count; ?>건</span></span>
</div>

<style type="text/css">
    input {border: 1px solid #cccccc;}
</style>
<form name="flist" class="local_sch01 local_sch">
    <input type="hidden" name="save_stx" value="<?php echo $stx; ?>">

    <label for="sca" class="sound_only">분류선택</label>
    <select name="sca" id="sca">
        <option value="">전체분류</option>
        <?php
        $sql1 = " select ca_id, ca_name from {$g5['g5_shop_category_table']} order by ca_order, ca_id ";
        $result1 = sql_query($sql1);
        for ($i=0; $row1=sql_fetch_array($result1); $i++) {
            $len = strlen($row1['ca_id']) / 2 - 1;
            $nbsp = '';
            for ($i=0; $i<$len; $i++) $nbsp .= '&nbsp;&nbsp;&nbsp;';
            echo '<option value="'.$row1['ca_id'].'" '.get_selected($sca, $row1['ca_id']).'>'.$nbsp.$row1['ca_name'].'</option>'.PHP_EOL;
        }
        ?>
    </select>

    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="it_name" <?php echo get_selected($sfl, 'it_name'); ?>>상품명</option>
        <option value="it_id" <?php echo get_selected($sfl, 'it_id'); ?>>상품코드</option>
        <option value="it_maker" <?php echo get_selected($sfl, 'it_maker'); ?>>제조사</option>
        <option value="it_origin" <?php echo get_selected($sfl, 'it_origin'); ?>>원산지</option>
        <option value="it_sell_email" <?php echo get_selected($sfl, 'it_sell_email'); ?>>판매자 e-mail</option>
    </select>

    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input">
    <input type="submit" value="검색" class="btn_submit">

</form>

<form name="fitemlistupdate" method="post" action="./redcomm_itemlistupdate.php" onsubmit="return fitemlist_submit(this);" autocomplete="off" id="fitemlistupdate">
    <input type="hidden" name="sca" value="<?php echo $sca; ?>">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod" value="<?php echo $sod; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
            <tr>
                <th scope="col" rowspan="3">
                    <label for="chkall" class="sound_only">상품 전체</label>
                    <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                </th>
                <th scope="col"><?php echo subject_sort_link('it_id', 'sca='.$sca); ?>상품코드</a></th>
                <th scope="col" id="th_img">이미지</th>
                <th scope="col" id="th_pc_title"><?php echo subject_sort_link('it_name', 'sca='.$sca); ?>상품명</a></th>
                <th scope="col" id="th_title">타이틀(Title)</th>
                <th scope="col" id="th_description">설명(Description)</th>
                <!--<th scope="col" id="th_keyword">키워드(Keyword)</th>-->
                <th scope="col" >관리</th>
            </tr>
            </thead>
            <tbody>
            <?php
            for ($i=0; $row=sql_fetch_array($result); $i++)
            {
                $href = shop_item_url($row['it_id']);
                $bg = 'bg'.($i%2);

                $it_point = $row['it_point'];
                if($row['it_point_type'])
                    $it_point .= '%';
                ?>
                <tr class="<?php echo $bg; ?>">
                    <td class="td_chk">
                        <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['it_name']); ?></label>
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i; ?>">
                    </td>
                    <td class="td_num">
                        <input type="hidden" name="it_id[<?php echo $i; ?>]" value="<?php echo $row['it_id']; ?>">
                        <?php echo $row['it_id']; ?>
                    </td>

                    <td class="td_img"><a href="<?php echo $href; ?>"><?php echo get_it_image($row['it_id'], 50, 50); ?></a></td>
                    <td headers="th_pc_title" class="td_input">
                        <label for="name_<?php echo $i; ?>" class="sound_only">상품명</label>
                        <input type="text" name="it_name[<?php echo $i; ?>]" value="<?php echo htmlspecialchars2(cut_str($row['it_name'],250, "")); ?>" id="name_<?php echo $i; ?>" required class="tbl_input required" size="30">
                    </td>

                    <td headers="th_title" class="td_input">
                        <label for="title_<?php echo $i; ?>" class="sound_only">타이틀</label>
                        <input type="text" name="it_8[<?php echo $i; ?>]" value="<?php echo htmlspecialchars2(cut_str($row['it_8'],250, "")); ?>" id="it_8"  class="tbl_input" size="20">
                    </td>

                    <td headers="th_description" class="td_input">
                        <label for="description_<?php echo $i; ?>" class="sound_only">설명</label>
                        <input type="text" name="it_9[<?php echo $i; ?>]" value="<?php echo htmlspecialchars2(cut_str($row['it_9'],250, "")); ?>" id="it_9"  class="tbl_input" size="20">
                    </td>

                    <!--<td headers="th_keyword" class="td_input">
            <label for="keyword_<?php echo $i; ?>" class="sound_only">설명</label>
            <input type="text" name="it_10[<?php echo $i; ?>]" value="<?php echo htmlspecialchars2(cut_str($row['it_10'],250, "")); ?>" id="it_10"  class="tbl_input" size="20">
        </td>-->

                    <td class="td_mng td_mng_s" id="<?php echo $row['it_id'];?>">

                        <input type="button" class="btn btn_03" value="수정" style="margin: 0 0 10px;padding: 5px 10px;">
                    </td>
                </tr>
                <?php
            }
            if ($i == 0)
                echo '<tr><td colspan="12" class="empty_table">자료가 한건도 없습니다.</td></tr>';
            ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top" class="btn_fixed_top" style="margin: 0 0 10px;padding: 0 20px;">
        <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value" class="btn btn_02" style="margin: 0 0 10px;padding: 5px 10px;">
    </div>
    <!-- <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="일괄수정" class="btn_submit" accesskey="s">
    </div> -->
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>


<script>
    $(".td_mng").click(function(){
        it_id =(this.id);

        it_name = $(this).closest("tr").find("[id^=name_]").val();

        title = $(this).closest("tr").find("#it_8").val();
        description = $(this).closest("tr").find("#it_9").val();
        keywords = $(this).closest("tr").find("#it_10").val();

        var form = $('<form></form>');
        form.attr('action', './redcomm_itemupdate.php');
        form.attr('method', 'post');
        form.append($('<input/>', {type: 'hidden', name:'it_id', value: it_id }));
        form.append($('<input/>', {type: 'hidden', name:'it_name', value: it_name }));
        form.append($('<input/>', {type: 'hidden', name:'it_8', value: title }));
        form.append($('<input/>', {type:'hidden', name: 'it_9', value: description }));
        form.append($('<input/>', {type:'hidden', name: 'it_10', value: keywords }));

        form.appendTo('body');
        form.submit();

    });
</script>
<script>
    function fitemlist_submit(f)
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

    $(function() {
        $(".itemcopy").click(function() {
            var href = $(this).attr("href");
            window.open(href, "copywin", "left=100, top=100, width=300, height=200, scrollbars=0");
            return false;
        });
    });

    function excelform(url)
    {
        var opt = "width=600,height=450,left=10,top=10";
        window.open(url, "win_excel", opt);
        return false;
    }
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
