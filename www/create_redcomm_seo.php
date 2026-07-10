<?php

include "./_common.php";

$table = "g5_redcomm_seo";
$sql= " SELECT 1 FROM information_schema.tables WHERE TABLE_NAME like '$table'" ;
$row = sql_fetch($sql);

if(empty($row[1])){
    $sql = " CREATE TABLE  {$table} (
        seo_title varchar(256) NOT NULL DEFAULT '',
        seo_description text NOT NULL ,
        seo_keyword text NOT NULL ,
        google varchar(256) NOT NULL DEFAULT '',
        naver varchar(256) NOT NULL DEFAULT '',
        facebook_img varchar(255) NOT NULL DEFAULT '',
        twitter_img varchar(255) NOT NULL DEFAULT '',
        canonical varchar(255) NOT NULL DEFAULT '',
        robots text  NOT NULL ,
        rs_1 varchar(255) NOT NULL DEFAULT '',
        rs_2 varchar(255) NOT NULL DEFAULT '',
        rs_3 varchar(255) NOT NULL DEFAULT '',
        rs_4 varchar(255) NOT NULL DEFAULT '',
        rs_5 varchar(255) NOT NULL DEFAULT '',
        rs_6 varchar(255) NOT NULL DEFAULT '',
        rs_7 varchar(255) NOT NULL DEFAULT '',
        rs_8 varchar(255) NOT NULL DEFAULT '',
        rs_9 varchar(255) NOT NULL DEFAULT '',
        rs_10 varchar(255) NOT NULL DEFAULT ''
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
    ";
    // echo $sql;
    $rs = sql_query($sql) ;
    // or die("테이블 생성에 실패하였습니다. : " . mysql_error() ." : " . mysql_errno() ); 
    
    echo"$table 테이블에 성공하였습니다.". PHP_EOL;
} else {
    echo "{$table} 테이블 설치 이력이 있습니다. " . PHP_EOL;
    echo "확인 후 적용 부탁드립니다.". PHP_EOL;    
}
