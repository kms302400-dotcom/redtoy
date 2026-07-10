<?php
$sub_menu = '600400';
include_once('./_common.php');
ini_set('display_errors',1);
error_reporting(E_ALL);

auth_check($auth[$sub_menu], "w");

check_admin_token();
$table = "g5_redcomm_seo";

$contents = $_POST['robots'];


$sql = " select count(*) as cnt from {$table} ";
$redcomm_seo =  sql_fetch($sql);
if($redcomm_seo['cnt'] > 0 ) {
	$sql = " UPDATE {$table} SET robots = '{$contents}'";	
} else {
	$sql = " INSERT INTO {$table} (robots) VALUES ('{$contents}')";
}

sql_query($sql);

$robots = fopen(G5_PATH. "/data/robots.txt", "w") or die('Could not open file'); 
$message = fwrite($robots, $contents);
fclose($robots);
// echo G5_PATH. "/robots.txt : " . $message;
goto_url("./redcomm_robots.php");
?>
