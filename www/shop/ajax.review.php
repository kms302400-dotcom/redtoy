<?php
    include "./_common.php";
    
    $is_id = isset($_REQUEST["is_id"]) ? safe_replace_regex($_REQUEST["is_id"], "number") : "";
    if (!$is_id) exit;
    
    $sql = " SELECT is_id, ct_id, it_id, is_provided, case when is_provided=1 then is_name else concat(substring(is_name, 1, 1), '*', substring(is_name, 3, 1)) end as is_name, is_score, is_subject, is_content, is_time, is_reply_subject, is_reply_content, concat(substring(is_reply_name, 1, 1), '*', substring(is_reply_name, 3, 1)) as is_reply_name FROM {$g5['g5_shop_item_use_table']} WHERE is_id = '" . $is_id . "' ";
    $row = sql_fetch($sql);
    $visible = sql_fetch("select is_confirm, mb_id from {$g5['g5_shop_item_use_table']} where is_id='$is_id'");
    if (!$row || (!$visible['is_confirm'] && !$is_admin && (empty($member['mb_id']) || $visible['mb_id'] !== $member['mb_id']))) exit;
    $row['is_content'] = html_purifier($row['is_content']);
    $row['review_notice'] = !empty($row['is_provided']) ? '상품 제공 리뷰 · 고객이 전달한 후기를 관리자가 대신 등록했습니다' : '';
    $row['image_list'] = array();
    header('Content-Type: application/json; charset=utf-8');
    
    $sql = " SELECT * FROM {$g5['g5_shop_item_use_image_table']} WHERE is_id = '" . $is_id . "' ";
    $res = sql_query($sql);
    $rows = sql_num_rows($res);
    
    for($i=0;$i<$rows;$i++) {
        $file = sql_fetch_array($res);
        $row["image_list"][] = $file;
    }
    
    echo json_encode($row);