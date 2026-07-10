<?php
include_once("./_common.php");
header('Content-Type: text/html; charset=UTF-8');
//set_session('ss_cert_adult',   "OK");

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