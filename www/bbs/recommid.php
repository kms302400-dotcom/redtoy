<?php
include_once("../common.php");
$rid = $_GET['rid'];
set_session('ss_recommid', $rid);
set_session('ss_mb_recommend',$rid);
if(!empty($rid)){
    echo "<title>파트너 시스템 접속</title>";
}
?>
<script>
    location.href='/shop';
</script>