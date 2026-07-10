<?php
    include_once('./_common.php');
    
    if (!is_numeric($reviewidx)) exit;
    if (!is_numeric($index)) exit;
    
    $sql = " SELECT b.bf_file from g5_shop_item_use a inner join g5_shop_item_use_image b on a.is_id = b.is_id WHERE a.is_id = '" . $reviewidx . "' AND a.is_confirm = 1 AND b.bf_no = '" . $index . "' ";
    $result = db_query($sql);
    $row = db_fetch_array($result);
    
    if ($row["bf_file"]) {
        $default_image = file_get_contents(G5_DATA_PATH.'/file/review/'.$row["bf_file"]);
    } else {
        $default_image = file_get_contents("./shop/img/no_image.gif");
    }
    
    header("Content-Type: image/png");
    
    echo $default_image;