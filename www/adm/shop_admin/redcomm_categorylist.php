<?php
$sub_menu = '600100';
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

$g5['title'] = '카테고리 SEO';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$where = " where ";
$sql_search = "";
if ($stx != "") {
    if ($sfl != "") {
        $sql_search .= " $where $sfl like '%$stx%' ";
        $where = " and ";
    }
    if ($save_stx && ($save_stx != $stx))
        $page = 1;
}

$sql_common = " from {$g5['g5_shop_category_table']} ";
if ($is_admin != 'super')
    $sql_search .= " $where ca_mb_id = '{$member['mb_id']}' ";
$sql_common .= $sql_search;


// 테이블의 전체 레코드수만 얻음
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

if (!$sst)
{
    $sst  = "ca_id";
    $sod = "asc";
}
$sql_order = "order by $sst $sod";

// 출력할 레코드를 얻음
$sql  = " select *
             $sql_common
             $sql_order
             limit $from_record, $rows ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';
?>
<style type="text/css">
    input {border: 1px solid #cccccc;}
</style>
<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <span class="btn_ov01"><span class="ov_txt">생성된  분류 수</span><span class="ov_num">  <?php echo number_format($total_count); ?>개</span></span>
</div>

<form name="flist" class="local_sch01 local_sch">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="save_stx" value="<?php echo $stx; ?>">

    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="ca_name"<?php echo get_selected($_GET['sfl'], "ca_name", true); ?>>분류명</option>
        <option value="ca_id"<?php echo get_selected($_GET['sfl'], "ca_id", true); ?>>분류코드</option>
        <option value="ca_mb_id"<?php echo get_selected($_GET['sfl'], "ca_mb_id", true); ?>>회원아이디</option>
    </select>

    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" required class="required frm_input">
    <input type="submit" value="검색" class="btn_submit">

</form>

<form name="fcategorylist" method="post" action="./redcomm_categorylistupdate.php" autocomplete="off">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod" value="<?php echo $sod; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">

    <div id="sct" class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
            <tr>
                <th scope="col" ><?php echo subject_sort_link("ca_id"); ?>분류코드</a></th>
                <th scope="col" id="sct_cate"><?php echo subject_sort_link("ca_name"); ?>분류명</a></th>
                <th scope="col" id="sct_amount">상품수</th>

                <th scope="col" id="sct_title">타이틀(Title)</th>
                <th scope="col" id="sct_description">설명(Description)</th>
                <!--<th scope="col" id="sct_keyword">키워드(Keyword)</th>-->
                <th scope="col" >관리</th>
            </tr>

            </thead>
            <tbody>
            <?php
            for ($i=0; $row=sql_fetch_array($result); $i++)
            {
                $level = strlen($row['ca_id']) / 2 - 1;
                $p_ca_name = '';

                if ($level > 0) {
                    $class = 'class="name_lbl"'; // 2단 이상 분류의 label 에 스타일 부여 - 지운아빠 2013-04-02
                    // 상위단계의 분류명
                    $p_ca_id = substr($row['ca_id'], 0, $level*2);
                    $sql = " select ca_name from {$g5['g5_shop_category_table']} where ca_id = '$p_ca_id' ";
                    $temp = sql_fetch($sql);
                    $p_ca_name = $temp['ca_name'].'의하위';
                } else {
                    $class = '';
                }

                $s_level = '<div><label for="ca_name_'.$i.'" '.$class.'><span class="sound_only">'.$p_ca_name.''.($level+1).'단 분류</span></label></div>';
                $s_level_input_size = 25 - $level *2; // 하위 분류일 수록 입력칸 넓이 작아짐 - 지운아빠 2013-04-02

                $s_upd = '<a href="./categoryform.php?w=u&amp;ca_id='.$row['ca_id'].'&amp;'.$qstr.'" class="btn btn_02"><span class="sound_only">'.get_text($row['ca_name']).' </span>수정</a> ';

                if ($is_admin == 'super')
                    $s_del = '<a href="./categoryformupdate.php?w=d&amp;ca_id='.$row['ca_id'].'&amp;'.$qstr.'" onclick="return delete_confirm(this);" class="btn btn_02"><span class="sound_only">'.get_text($row['ca_name']).' </span>삭제</a> ';

                // 해당 분류에 속한 상품의 수
                $sql1 = " select COUNT(*) as cnt from {$g5['g5_shop_item_table']}
                      where ca_id = '{$row['ca_id']}'
                      or ca_id2 = '{$row['ca_id']}'
                      or ca_id3 = '{$row['ca_id']}' ";
                $row1 = sql_fetch($sql1);

                // 스킨 Path
                if(!$row['ca_skin_dir'])
                    $g5_shop_skin_path = G5_SHOP_SKIN_PATH;
                else {
                    if(preg_match('#^theme/(.+)$#', $row['ca_skin_dir'], $match))
                        $g5_shop_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/shop/'.$match[1];
                    else
                        $g5_shop_skin_path  = G5_PATH.'/'.G5_SKIN_DIR.'/shop/'.$row['ca_skin_dir'];
                }

                if(!$row['ca_mobile_skin_dir'])
                    $g5_mshop_skin_path = G5_MSHOP_SKIN_PATH;
                else {
                    if(preg_match('#^theme/(.+)$#', $row['ca_mobile_skin_dir'], $match))
                        $g5_mshop_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/shop/'.$match[1];
                    else
                        $g5_mshop_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/shop/'.$row['ca_mobile_skin_dir'];
                }

                $bg = 'bg'.($i%2);
                ?>
                <tr class="<?php echo $bg; ?> id="<?php echo $row['ca_id'];?>" >
                <td class="td_code">
                    <input type="hidden" name="ca_id[<?php echo $i; ?>]" value="<?php echo $row['ca_id']; ?>">
                    <?php echo $row['ca_id']; ?>
                </td>
                <td headers="sct_cate" class="sct_name<?php echo $level; ?>"><?php echo $s_level; ?> <input type="text" name="ca_name[<?php echo $i; ?>]" value="<?php echo get_text($row['ca_name']); ?>" id="ca_name_<?php echo $i; ?>" required class="tbl_input full_input required"></td>
                <td headers="sct_amount" class="td_amount"><a href="./itemlist.php?sca=<?php echo $row['ca_id']; ?>"><?php echo $row1['cnt']; ?></a></td>
                <td headers="sct_ca_8" class="sct_name<?php echo $level; ?>"><?php echo $s_level; ?> <input type="text" name="ca_8[<?php echo $i; ?>]" value="<?php echo get_text($row['ca_8']); ?>" id="ca_8"  class="tbl_input full_input "></td>
                <td headers="sct_ca_9" class="sct_name<?php echo $level; ?>"><?php echo $s_level; ?> <input type="text" name="ca_9[<?php echo $i; ?>]" value="<?php echo get_text($row['ca_9']); ?>" id="ca_9"  class="tbl_input full_input "></td>
                <!--<td headers="sct_ca_10" class="sct_name<?php echo $level; ?>"><?php echo $s_level; ?> <input type="text" name="ca_10[<?php echo $i; ?>]" value="<?php echo get_text($row['ca_10']); ?>" id="ca_10"  class="tbl_input full_input "></td>-->


                <td class="td_mng td_mng_s" id="<?php echo $row['ca_id'];?>" style="padding: 9px 5px;">
                    <?php //echo $s_upd; ?><input type="button" value="수정"  style="padding: 5px 10px;">
                </td>
                </tr>
            <?php }
            if ($i == 0) echo "<tr><td colspan=\"7\" class=\"empty_table\">자료가 한 건도 없습니다.</td></tr>\n";
            ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top" style="margin: 0 0 10px;padding: 0 20px;">
        <input type="submit" value="일괄수정" class="btn_02 btn" style="margin: 0 0 10px;padding: 5px 10px;">
    </div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>


<script>
    $(".td_mng").click(function(){
        ca_id =(this.id);

        ca_name = $(this).closest("tr").find("[id^=ca_name]").val();

        title = $(this).closest("tr").find("#ca_8").val();
        description = $(this).closest("tr").find("#ca_9").val();
        keywords = $(this).closest("tr").find("#ca_10").val();

        var form = $('<form></form>');
        form.attr('action', './redcomm_categoryupdate.php');
        form.attr('method', 'post');
        form.append($('<input/>', {type: 'hidden', name:'ca_id', value: ca_id }));
        form.append($('<input/>', {type: 'hidden', name:'ca_name', value: ca_name }));
        form.append($('<input/>', {type: 'hidden', name:'ca_8', value: title }));
        form.append($('<input/>', {type:'hidden', name: 'ca_9', value: description }));
        form.append($('<input/>', {type:'hidden', name: 'ca_10', value: keywords }));

        form.appendTo('body');
        form.submit();

    });
</script>



<script>
    $(function() {
        $("select.skin_dir").on("change", function() {
            var type = "";
            var dir = $(this).val();
            if(!dir)
                return false;

            var id = $(this).attr("id");
            var $sel = $(this).siblings("select");
            var sval = $sel.find("option:selected").val();

            if(id.search("mobile") > -1)
                type = "mobile";

            $sel.load(
                "./ajax.skinfile.php",
                { dir : dir, type : type, sval: sval }
            );
        });
    });
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
