<?php
$sub_menu = '102840';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '친구톡 이미지 선택';
include_once(G5_PATH.'/head.sub.php');

$sql_common = " from {$g5['wz_alimtalk_fl_image_table']} ";

$sql_search = " where (1) ";

$sql_order = " order by fi_id desc ";

$sql = " select
                count(*) as cnt
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

        <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup>
            <col style="width:25%;"/>
            <col style="width:25%;"/>
            <col style="width:25%;"/>
            <col style="width:25%;"/>
        </colgroup>
        <tbody>
        <tr>
        <?php
        if ($cnt_data > 0) {
            $z = 0;
            foreach ((array)$arr_data as $k => $v) {
            ?>

                <td class="td_alignc" style="vertical-align:top;">
                    <div><img src="<?php echo $v['fi_img_url'];?>" style="max-width:100%;"></div>
                    <div><input type="radio" name="fi_src" value="<?php echo $v['fi_img_url'];?>"></div>
                    <div><?php echo $v['fi_img_name'];?></div>
                </td>

            <?php
            $z++;
            }
        }

        if ($z == 0)
            echo '<td colspan="4" class="empty_table">자료가 없습니다.</td>';
        ?>
        </tr>
        </tbody>
        </table>

    </div>

    <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

    <div class="btn_confirm01 btn_confirm" style="text-align:center;margin-top:20px;">
        <input type="button" value="이미지선택" class="btn_submit" id="btn-selected">
        <button type="button" onclick="parent.$.magnificPopup.close();">닫기</button>
    </div>

</div>

<script type="text/javascript">
<!--
    $(function() {
        $(document).on('click', '#btn-selected', function() { // 선택
            let fi_src = $(':input:radio[name=fi_src]:checked').val();
            parent.document.getElementById('img_url').value = fi_src;
            parent.$.magnificPopup.close();
        });
    });
//-->
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>