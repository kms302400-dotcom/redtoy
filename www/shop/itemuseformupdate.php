<?php
include_once('./_common.php');

if (!$is_member) {
    alert_close("사용후기는 회원만 작성이 가능합니다.");
}

$it_id       = trim($_REQUEST['it_id']);
$is_subject  = trim($_POST['is_subject']);
$is_content  = trim($_POST['is_content']);
$is_content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $is_content);
$is_name     = trim($_POST['is_name']);
$is_password = trim($_POST['is_password']);
$is_score    = (int)$_POST['is_score'] > 5 ? 0 : (int)$_POST['is_score'];
$get_editor_img_mode = $config['cf_editor'] ? false : true;
$is_id       = (int) trim($_REQUEST['is_id']);
$ct_id       = (int) trim($_REQUEST['ct_id']);
$image_dir   = $_SERVER["DOCUMENT_ROOT"] . "/data/upload/";
$upload_file = "";
if (isset($_FILES["review_file_load"]) && $_FILES["review_file_load"]["tmp_name"]) {
    $upload_files = array();
    $filecnt = count($_FILES["review_file_load"]["name"]) > 5 ? 5 : count($_FILES["review_file_load"]["name"]);
    for($i=0;$i<$filecnt;$i++) {
        $upload_files[] = file_upload2($_FILES["review_file_load"]["tmp_name"][$i], $_FILES["review_file_load"]["name"][$i], $_FILES["review_file_load"]["size"][$i], "");
    }
}
$page = isset($_REQUEST["page"]) ? safe_replace_regex($_REQUEST["page"], "number") : 1;
$returnuri   = trim($_REQUEST["returnuri"]);

// 사용후기 작성 설정에 따른 체크
check_itemuse_write($it_id, $member['mb_id']);

if ($w == "" || $w == "u") {
    $is_name     = addslashes(strip_tags($member['mb_name']));
    $is_password = $member['mb_password'];

    if (!$is_subject) alert("제목을 입력하여 주십시오.");
    if (!$is_content) alert("내용을 입력하여 주십시오.");
}

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
                   is_time = '".G5_TIME_YMDHIS."',
                   is_ip = '{$_SERVER['REMOTE_ADDR']}' ";
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
    if (!$is_admin) $where = " AND mb_id = '{$member['mb_id']}' ";
    $sql = " select * from {$g5['g5_shop_item_use_table']} where is_id = '$is_id' $where ";
    $row = sql_fetch($sql);
    
    if (!$row) exit;
    $is_id = $row["is_id"];

    $sql = " update {$g5['g5_shop_item_use_table']}
                set is_subject = '$is_subject',
                    is_content = '$is_content',
                    is_score = '$is_score'
              where is_id = '$is_id' ";
    sql_query($sql);
    
    if (count($upload_files)) {
        if (isset($_REQUEST["delfile"])) {
            $delfile = $_REQUEST["delfile"];
            for($i=0;$i<count($delfile);$i++) {
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
    if (!$is_admin)
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