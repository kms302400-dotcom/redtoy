<?php
include_once('./_common.php');

if (!$is_member) {
    alert_close("사용후기는 회원만 작성이 가능합니다.");
}

$w = isset($_REQUEST['w']) && is_string($_REQUEST['w']) ? $_REQUEST['w'] : '';
if (!in_array($w, array('', 'u', 'd'), true)) alert('잘못된 요청입니다.');
$it_id = isset($_REQUEST['it_id']) && is_string($_REQUEST['it_id']) ? safe_replace_regex($_REQUEST['it_id'], 'it_id') : '';
$is_id = isset($_REQUEST['is_id']) && is_scalar($_REQUEST['is_id']) ? (int)$_REQUEST['is_id'] : 0;
$ct_id = isset($_POST['ct_id']) && is_scalar($_POST['ct_id']) ? (int)$_POST['ct_id'] : 0;
$review_admin = redtoy_review_admin();
$review_existing = array();
if ($w === 'u' || $w === 'd') {
    $review_existing = sql_fetch("select * from {$g5['g5_shop_item_use_table']} where is_id='$is_id'");
    if (!$review_existing || (!$review_admin && ($review_existing['mb_id'] !== $member['mb_id'] || !empty($review_existing['is_provided'])))) {
        alert('리뷰를 변경할 권한이 없습니다.');
    }
    $it_id = $review_existing['it_id'];
}
// Validate authority and CSRF before uploading or changing files.
if ($w !== 'd') redtoy_review_check_token();
if ($w === 'd') {
    $hash = isset($_REQUEST['hash']) && is_string($_REQUEST['hash']) ? $_REQUEST['hash'] : '';
    if (!hash_equals(md5($review_existing['is_id'].$review_existing['is_time'].$review_existing['is_ip']), $hash)) alert('잘못된 삭제 요청입니다.');
}
$review_item = get_shop_item($it_id, true);
if (empty($review_item['it_id'])) alert('상품정보가 존재하지 않습니다.');
$review_provided = $w === '' ? ($review_admin && isset($_POST['review_provided']) && $_POST['review_provided'] === '1') : !empty($review_existing['is_provided']);
$review_values = null;
try {
    if ($w !== 'd') $review_values = redtoy_review_values($review_admin && $review_provided, $_POST, G5_TIME_YMDHIS);
} catch (InvalidArgumentException $e) {
    alert($e->getMessage());
}
if ($review_provided) $ct_id = 0; // Never invent a purchase or award review points to the administrator.
if ($ct_id) {
    $mb = sql_real_escape_string($member['mb_id']);
    $item = sql_real_escape_string($it_id);
    $cart = sql_fetch("select ct_id from {$g5['g5_shop_cart_table']} where ct_id='$ct_id' and mb_id='$mb' and it_id='$item' and ct_status='완료'");
    if (!$cart) $ct_id = 0;
}
$is_subject = isset($_POST['is_subject']) && is_string($_POST['is_subject']) ? trim(stripslashes($_POST['is_subject'])) : '';
$is_content = isset($_POST['is_content']) && is_string($_POST['is_content']) ? trim(stripslashes($_POST['is_content'])) : '';
$is_content = html_purifier($is_content);
$is_score = isset($_POST['is_score']) && is_scalar($_POST['is_score']) ? (int)$_POST['is_score'] : 0;
if ($w !== 'd' && ($is_score < 1 || $is_score > 5)) alert('별점은 1~5점으로 선택해 주세요.');
$get_editor_img_mode = $config['cf_editor'] ? false : true;
$upload_files = array();
if (!$review_admin) check_itemuse_write($it_id, $member['mb_id']);
if ($w !== 'd' && (!$is_subject || !$is_content)) alert('제목과 내용을 입력해 주세요.');
$image_dir   = $_SERVER["DOCUMENT_ROOT"] . "/data/upload/";
$upload_file = "";
if ($w !== 'd' && isset($_FILES["review_file_load"]["tmp_name"]) && is_array($_FILES["review_file_load"]["tmp_name"])) {
    $upload_files = array();
    $filecnt = count($_FILES["review_file_load"]["name"]) > 5 ? 5 : count($_FILES["review_file_load"]["name"]);
    for($i=0;$i<$filecnt;$i++) {
        if (!is_uploaded_file($_FILES["review_file_load"]["tmp_name"][$i])) continue;
        $image = @getimagesize($_FILES["review_file_load"]["tmp_name"][$i]);
        $ext = strtolower(pathinfo($_FILES["review_file_load"]["name"][$i], PATHINFO_EXTENSION));
        if (!$image || !in_array($image[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF), true) || !in_array($ext, array("jpg", "jpeg", "png", "gif"), true) || $_FILES["review_file_load"]["size"][$i] > 10 * 1024 * 1024) alert("10MB 이하 JPG, PNG, GIF 이미지만 첨부할 수 있습니다.");
        $upload_files[] = file_upload2($_FILES["review_file_load"]["tmp_name"][$i], $_FILES["review_file_load"]["name"][$i], $_FILES["review_file_load"]["size"][$i], "");
    }
}
$page = isset($_REQUEST["page"]) ? safe_replace_regex($_REQUEST["page"], "number") : 1;
$returnuri = isset($_REQUEST["returnuri"]) && is_string($_REQUEST["returnuri"]) ? trim($_REQUEST["returnuri"]) : "";
if ($returnuri && !preg_match("~^/[a-zA-Z0-9/_-]+\\.php$~D", $returnuri)) $returnuri = "";

if ($w == "" || $w == "u") {
    $is_name = $review_values ? $review_values['name'] : strip_tags($member['mb_name']);
    $is_password = $member['mb_password'];

    if (!$is_subject) alert("제목을 입력하여 주십시오.");
    if (!$is_content) alert("내용을 입력하여 주십시오.");
}

$is_name = sql_real_escape_string(isset($is_name) ? $is_name : '');
$is_subject = sql_real_escape_string($is_subject);
$is_content = sql_real_escape_string($is_content);
$is_password = sql_real_escape_string($member['mb_password']);
$review_time = $review_values ? $review_values['time'] : G5_TIME_YMDHIS;

if($is_mobile_shop)
    $url = './iteminfo.php?it_id='.$it_id.'&info=use';
else
    $url = "./item.php?it_id=$it_id&_=".get_token()."#sit_use";

if ($w == "")
{
    /*
    $sql = " select max(is_id) as max_is_id from {$g5['g5_shop_item_use_table']} ";
    $row = sql_fetch($sql);
    $max_is_id = $row['max_is_id'];

    $sql = " select max(is_id) as max_is_id from {$g5['g5_shop_item_use_table']} where it_id = '$it_id' and mb_id = '{$member['mb_id']}' ";
    $row = sql_fetch($sql);
    if ($row['max_is_id'] && $row['max_is_id'] == $max_is_id)
        alert("같은 상품에 대하여 계속해서 평가하실 수 없습니다.");
    */
    $sql = "insert {$g5['g5_shop_item_use_table']}
               set it_id = '$it_id',
                   mb_id = '{$member['mb_id']}',
                   ct_id = '$ct_id',
                   is_score = '$is_score',
                   is_name = '$is_name',
                   is_password = '$is_password',
                   is_subject = '$is_subject',
                   is_content = '$is_content',
                   is_time = '$review_time',
                   is_ip = '{$_SERVER['REMOTE_ADDR']}' ";
    if ($review_provided) {
        $actor = sql_real_escape_string($member['mb_id']);
        $sql .= ", is_provided=1, is_registered_by='$actor', is_registered_at='".G5_TIME_YMDHIS."'";
    }
    if (!$default['de_item_use_use'])
        $sql .= ", is_confirm = '1' ";
    sql_query($sql);

    $is_id = sql_insert_id();
    $filecount = 0;
    if (count($upload_files)) {
        foreach($upload_files as $upload_file) {
            if ($upload_file) {
                $sql = " INSERT INTO {$g5['g5_shop_item_use_image_table']} (is_id, bf_file) VALUES ('$is_id', '" . $upload_file . "') ";
                sql_query($sql);
                $filecount++;
            }
        }
    }

    if ($default['de_item_use_use']) {
        $alert_msg = "평가하신 글은 관리자가 확인한 후에 출력됩니다.";
    }  else {
        $alert_msg = "사용후기가 등록 되었습니다.";
    }
    
    if ($ct_id) {
        if ($filecount) {
            insert_point($member["mb_id"], 500, $is_subject . " 포토리뷰 작성", '@member', $is_id, '회원가입');
        } else {
            insert_point($member["mb_id"], 100, $is_subject . " 리뷰 작성", '@member', $is_id, '회원가입');
        }
    }
}
else if ($w == "u") {
    $where = '';
    if (!$review_admin) $where = " AND mb_id = '{$member['mb_id']}' ";
    $sql = " select * from {$g5['g5_shop_item_use_table']} where is_id = '$is_id' $where ";
    $row = sql_fetch($sql);
    
    if (!$row) exit;
    $is_id = $row["is_id"];

    $sql = " update {$g5['g5_shop_item_use_table']}
                set is_subject = '$is_subject',
                    is_content = '$is_content',
                    is_score = '$is_score'
                    ".($review_values ? ", is_name='$is_name', is_time='$review_time'" : '')."
              where is_id = '$is_id' ";
    sql_query($sql);
    
    if (count($upload_files) || !empty($_POST['delfile'])) {
        if (isset($_POST["delfile"]) && is_array($_POST["delfile"])) {
            $delfile = $_POST["delfile"];
            for($i=0;$i<count($delfile);$i++) {
                if (!is_string($delfile[$i]) || basename($delfile[$i]) !== $delfile[$i]) continue;
                $delfile[$i] = sql_real_escape_string($delfile[$i]);
                // 첨부된 이미지 삭제
                $sql = " select bf_file from {$g5['g5_shop_item_use_image_table']} where is_id = '$is_id' and bf_file = '" . $delfile[$i] . "' ";
                $res = sql_query($sql);
                $row = sql_fetch_array($res);
                if ($row["bf_file"]) {
                    @unlink($_SERVER["DOCUMENT_ROOT"] . "/data/upload/" . $row["bf_file"]);
                
                    $sql = " delete from {$g5['g5_shop_item_use_image_table']} where is_id = '$is_id' and bf_file = '" . $delfile[$i] . "'  ";
                    sql_query($sql);
                }
            }
        }
        
        foreach($upload_files as $upload_file) {
            if ($upload_file) {
                $sql = " INSERT INTO {$g5['g5_shop_item_use_image_table']} (is_id, bf_file) VALUES ('$is_id', '" . $upload_file . "') ";
                sql_query($sql);
            }
        }
    }

    run_event('shop_item_use_updated', $is_id, $it_id);

    $alert_msg = "사용후기가 수정 되었습니다.";
}
else if ($w == "d")
{
    $where = '';
    if (!$review_admin)
    {
        $sql = " select count(*) as cnt from {$g5['g5_shop_item_use_table']} where mb_id = '{$member['mb_id']}' and is_id = '$is_id' ";
        $row = sql_fetch($sql);
        if (!$row['cnt'])
            alert("자신의 사용후기만 삭제하실 수 있습니다.");
            
        $where = " and mb_id = '{$member['mb_id']}'";
    }

    // 에디터로 첨부된 이미지 삭제
    $sql = " select bf_file from {$g5['g5_shop_item_use_image_table']} where is_id = '$is_id' ";
    $res = sql_query($sql);
    $rows = sql_num_rows($res);

    if ($rows) {
        for($i=0;$i<$rows;$i++) {
            $row = sql_fetch_array($res);
            @unlink($_SERVER["DOCUMENT_ROOT"] . $row["bf_file"]);
        }
        
        $sql = " delete from {$g5['g5_shop_item_use_image_table']} where is_id = '$is_id' ";
        sql_query($sql);
    }

    $sql = " delete from {$g5['g5_shop_item_use_table']} where is_id = '$is_id' $where";
    sql_query($sql);

    run_event('shop_item_use_deleted', $is_id, $it_id);

    $alert_msg = "사용후기를 삭제 하였습니다.";
}

update_use_avg($it_id);

//쇼핑몰 설정에서 사용후기가 즉시 출력일 경우
if( ! $default['de_item_use_use'] ){
    update_use_cnt($it_id);
}

if($w == 'd')
    if ($returnuri) {
        alert($alert_msg, $returnuri . "?page=" . $page);
    } else {
        alert($alert_msg, $url);
    }
else
    alert($alert_msg);
    echo "<script>history.back();</script>";
?>