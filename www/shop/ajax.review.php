<?php
    include "./_common.php";
    
    $is_id = isset($_REQUEST["is_id"]) ? safe_replace_regex($_REQUEST["is_id"], "number") : "";
    if (!$is_id) exit;
    
    $sql = " SELECT is_id, ct_id, it_id, concat(substring(is_name, 1, 1), '*', substring(is_name, 3, 1)) as is_name, is_score, is_subject, is_content, is_time, is_reply_subject, is_reply_content, concat(substring(is_reply_name, 1, 1), '*', substring(is_reply_name, 3, 1)) as is_reply_name FROM {$g5['g5_shop_item_use_table']} WHERE is_id = '" . $is_id . "' ";
    $row = sql_fetch($sql);
    
    $sql = " SELECT * FROM {$g5['g5_shop_item_use_image_table']} WHERE is_id = '" . $is_id . "' ";
    $res = sql_query($sql);
    $rows = sql_num_rows($res);
    
    for($i=0;$i<$rows;$i++) {
        $file = sql_fetch_array($res);
        $row["image_list"][] = $file;
    }
    
    echo json_encode($row);