<?php
$sub_menu = '600300';
include_once('./_common.php');


auth_check($auth[$sub_menu], "w");

check_admin_token();
$table = "g5_redcomm_seo";

$sql = " select * from {$table} ";
$redcomm_seo =  sql_fetch($sql);

$imgs_str ="";

$facebook_field = "";
$twitter_field = "";

// if ($_POST['facebook_img_del']) { 
//     @unlink(G5_DATA_PATH . "/common/" . $redcomm_seo['facebook_img']);
//     $facebook_field = ", facebook_img = ''";
// }
// if ($_POST['twitter_img_del'])  {
//     @unlink(G5_DATA_PATH . "/common/" . $redcomm_seo['twitter_img']);
//     $twitter_field = ", twitter_img = ''";

// }


if($_POST['facebook_img_del'] == '1') {
   $social_img = G5_DATA_PATH . "/common/" . $redcomm_seo['facebook_img'];
    @unlink($social_img);
     $facebook_field = "";
} else {
    $facebook_field = $redcomm_seo['facebook_img'];
}

if($_POST['twitter_img_del'] == '1') {
   $social_img = G5_DATA_PATH . "/common/" . $redcomm_seo['twitter_img'];
    @unlink($social_img);
     $twitter_field = "";
} else {
    $twitter_field = $redcomm_seo['twitter_img'];
}


if ($_FILES['facebook_img']['name']) {
    $social_img = G5_DATA_PATH . "/common/" . $redcomm_seo['facebook_img'];
    @unlink($social_img);
    $img_name = "facebook.".$_FILES['facebook_img']['name'];
    $facebook_img = upload_file($_FILES['facebook_img']['tmp_name'], $img_name , G5_DATA_PATH . "/common/");

    $facebook_field = $img_name;
}

if ($_FILES['twitter_img']['name']) {
    $social_img = G5_DATA_PATH . "/common/" . $redcomm_seo['twitter_img'];
    @unlink($social_img);
    $img_name = "twitter.".$_FILES['twitter_img']['name'];

    $twitter_img = upload_file($_FILES['twitter_img']['tmp_name'], $img_name, G5_DATA_PATH . "/common/");
    $twitter_field = $img_name;

}


$sql = " select count(*) as cnt from {$table} ";
$redcomm_seo =  sql_fetch($sql);
if($redcomm_seo['cnt'] > 0 ) {
// robots.txt는 다른 메뉴에서 생성
$sql = " UPDATE {$table}  
            SET seo_title = '{$_POST['seo_title']}',
                seo_description = '{$_POST['seo_description']}',
                seo_keyword = '{$_POST['seo_keyword']}',
                google = '{$_POST['google']}',
                naver = '{$_POST['naver']}',
                canonical = '{$_POST['canonical']}' ,
                facebook_img = '{$facebook_field}', 
                twitter_img = '{$twitter_field}'
";

} else {
$sql = " INSERT INTO  {$table}  
            (seo_title ,
             seo_description,
             seo_keyword,
             google ,
             naver,
             canonical,
             facebook_img,
             twitter_img
             )
             values 
             (
                '{$_POST['seo_title']}',
                '{$_POST['seo_description']}',
                '{$_POST['seo_keyword']}',
                '{$_POST['google']}',
                '{$_POST['naver']}',
                '{$_POST['canonical']}',
                '$facebook_field',
                '$twitter_field' 
             ) 
";

}

sql_query($sql) ;

goto_url("./redcomm_seo_form.php");
?>
