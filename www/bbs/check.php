<?php
include_once("../common.php");
$rid = $_GET['rid'];
set_session('ss_recommid', $rid);
set_session('ss_mb_recommend',$rid);
set_session('ss_cert_adult',   "OK");
if(get_session('ss_cert_url')){
    ?>
    <script>
        location.href='<?=get_session('ss_cert_url')?>';
    </script>
<?}else{?>

    <script>
        location.href='/';
    </script>
<?}?>
