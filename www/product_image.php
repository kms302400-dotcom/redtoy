<?php
    include "./_common.php";
    
    $it_id = isset($_REQUEST["it_id"]) ? safe_replace_regex($_REQUEST["it_id"], "number") : "";
    if (!$it_id) exit;
    
    $item = get_shop_item($it_id);
    if (!$item["it_img1"]) exit;
    //echo get_it_image($item['it_id'], 70, 70, '', '', $item['it_name']);
    
    echo file_get_contents("./data/item/" . $item["it_img1"]);
    
    //"https://www.redtoy.co.kr/data/item/1670480605/thumb-0191_70x70.jpg"