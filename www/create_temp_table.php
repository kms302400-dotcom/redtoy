<?php
include_once("./common.php");


$sql = "CREATE TABLE g5_temp_cart (idx int(11) NOT NULL auto_increment,temp_id int(11) NOT NULL,temp_value text NOT NULL,temp_regdate varchar(200) NOT NULL, PRIMARY KEY  (idx), UNIQUE KEY g5_temp_cart_idx_uindex (idx)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=euckr";
sql_query($sql);

echo "테이블 생성 완료";