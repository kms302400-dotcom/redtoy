<?php
$sub_menu = '800400';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if($bn_gr_id) {
    $bn = sql_fetch(" select * from {$g5['sh_banner_group_table']} where bn_gr_id = '{$bn_gr_id}'  ");
    if(!$bn['bn_gr_id']) {
        alert('삭제된 배너입니다.');
        exit;
    }
    $w = 'u';
} else {
    $bn['bn_gr_level_start'] = 1;
    $bn['bn_gr_level_end'] = 10;
    $bn['bn_gr_pc_use'] = 1;
    $bn['bn_gr_mobile_use'] = 1;
    $bn['bn_order_opt'] = 1;
    $bn['bn_count_limit'] = 5;
}

$g5['title'] .= '배너그룹';
if($bn['bn_gr_id']) $g5['title'] .= ' 수정';
else  $g5['title'] .= ' 등록';
if(!$bn['bn_gr_skin']) $bn['bn_gr_skin'] = 'swiper_main';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<form name="fbanner" id="fbanner" action="./banner_group_write_update.php" onsubmit="return fbanner_submit(this);" method="post">
<input type="hidden" name="bn_gr_id" value="<?php echo $bn_gr_id ?>">
<input type="hidden" name="w" value="<?php echo $w ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_2">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">그룹명</th>
        <td>
            <input type="text" name="bn_gr_name" value="<?php echo $bn['bn_gr_name'] ?>" id="bn_gr_name" required class="frm_input required" maxlength="50">
        </td>
    </tr>
    <tr>
        <th scope="row">메모</th>
        <td>
            <input type="text" name="bn_gr_memo" value="<?php echo $bn['bn_gr_memo'] ?>" id="bn_gr_memo" class="frm_input" maxlength="50" style="width:90%">
        </td>
    </tr>
    <tr>
        <th scope="row">배너 노출 레벨</th>
        <td>
            <input type="text" name="bn_gr_level_start" value="<?php echo $bn['bn_gr_level_start'] ?>" id="bn_gr_level_start" class="frm_input" maxlength="2" style="width:30px">
            ~
            <input type="text" name="bn_gr_level_end" value="<?php echo $bn['bn_gr_level_end'] ?>" id="bn_gr_level_end" class="frm_input" maxlength="2" style="width:30px">
            ※ Level 1 : 비회원, Level 2 ~ 10 : 회원
        </td>
    </tr>
    <tr>
        <th scope="row">노출기기</th>
        <td>
            <label for="bn_gr_pc_use">
            <input type="checkbox" name="bn_gr_pc_use" value="1" id="bn_gr_pc_use" <?php echo ($bn['bn_gr_pc_use']) ? ' checked' : '' ?>>
            PC
            </label>
            &nbsp;&nbsp;&nbsp;
            <label for="bn_gr_mobile_use">
            <input type="checkbox" name="bn_gr_mobile_use" value="1" id="bn_gr_mobile_use" <?php echo ($bn['bn_gr_mobile_use']) ? ' checked' : '' ?>>
            모바일
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">순서</th>
        <td>
            <input type="text" name="bn_gr_order" value="<?php echo $bn['bn_gr_order'] ?>" id="bn_gr_order" class="frm_input" maxlength="10" style="20px"> ※ 숫자가 높을수록 먼저 출력 됩니다.
        </td>
    </tr>
    <tr>
        <th scope="row">배너 노출 순서</th>
        <td>
            <label for="bn_order_opt_1">
            <input type="radio" name="bn_order_opt" value="1" id="bn_order_opt_1" <?php echo ($bn['bn_order_opt'] == 1) ? ' checked' : '' ?>>
            순서입력값
            </label>
            &nbsp;&nbsp;&nbsp;
            <label for="bn_order_opt_2">
            <input type="radio" name="bn_order_opt" value="2" id="bn_order_opt_2" <?php echo ($bn['bn_order_opt'] == 2) ? ' checked' : '' ?>>
            이전등록순
            </label>
            &nbsp;&nbsp;&nbsp;
            <label for="bn_order_opt_3">
            <input type="radio" name="bn_order_opt" value="3" id="bn_order_opt_3" <?php echo ($bn['bn_order_opt'] == 3) ? ' checked' : '' ?>>
            최근등록순
            </label>
            &nbsp;&nbsp;&nbsp;
            <label for="bn_order_opt_4">
            <input type="radio" name="bn_order_opt" value="4" id="bn_order_opt_4" <?php echo ($bn['bn_order_opt'] == 4) ? ' checked' : '' ?>>
            랜덤순
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">배너 노출 갯수</th>
        <td>
            <input type="text" name="bn_count_limit" value="<?php echo $bn['bn_count_limit'] ?>" id="bn_count_limit" class="frm_input" maxlength="10" style="20px"> ※ 0 입력시 등록된 전체 아이템
        </td>
    </tr>
    <tr>
        <th scope="row">배너스킨</th>
        <td>
            <?php echo sh_banner_get_skin_select('bn_gr_skin', 'bn_gr_skin', $bn['bn_gr_skin'], 'required'); ?>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div id="bn_skin_set_wrap" class="tbl_frm01 tbl_wrap">
<?php
$bn_gr_skin_set = json_decode($bn['bn_gr_skin_set'], true);
@include_once($g5['sh_banner_skin_path'].'/'.$bn['bn_gr_skin'].'/sh_banner.admin.php');
?>
</div>


<div class="btn_fixed_top">
    <a href="./banner_group_list.php?<?php echo $qstr ?>" class="btn btn_02">목록</a>
    <input type="submit" value="확인" class="btn_submit btn" accesskey='s'>
</div>
</form>

<script>
function fbanner_submit(f)
{
    return true;
}
var skin_set = {}
var f_skin_name = "<?php echo $bn['bn_gr_skin'] ?>";
var o_skin_name = '';
$("#bn_gr_skin").on("change", function() {
    var skin_name = $(this).val();
    if(!skin_name) return;
    if(!skin_set[f_skin_name]) skin_set[f_skin_name] = $("#bn_skin_set_wrap").html();
    if(o_skin_name) skin_set[o_skin_name] = $("#bn_skin_set_wrap").html();
    if(skin_set[skin_name]) {
        $("#bn_skin_set_wrap").html(skin_set[skin_name]);
        o_skin_name = skin_name;
        return;
    }
    $.ajax({
        url: "<?php echo $g5['sh_banner_url'] ?>/admin/banner_group_write.ajax.php?skin_name=" + skin_name
    })
    .done(function(data) {
        $("#bn_skin_set_wrap").html(data);
        skin_set[skin_name] = data;
        o_skin_name = skin_name;
    })
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
