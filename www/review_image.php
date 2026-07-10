<?php
    include "./_common.php";
    
    $bf_file = isset($_REQUEST["bf_file"]) ? safe_replace_regex($_REQUEST["bf_file"]) : "";
    if (!$bf_file) exit;
    
    echo file_get_contents("./data/upload/" . $bf_file);
    
    //"https://www.redtoy.co.kr/data/item/1670480605/thumb-0191_70x70.jpg"