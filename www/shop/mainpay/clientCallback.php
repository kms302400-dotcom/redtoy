<?
$rstCode = $_POST['rstCode'];
$rstMsg = $_POST['rstMsg'];
$od_id = $_GET['od_id'];
if(empty($rstCode)){
    $rstCode=$_GET['rstCode'];
    $rstMsg = $_POST['rstMsg'];
}
    if($rstCode=='00'){
        ?>
        <script>
            alert('결제처리가 정상적으로 완료되었습니다.');
            opener.location.href='/shop/orderinquiryview.php?od_id=<?=$od_id?>&order_finish=ok';
            self.close();
        </script>
        <?
    }else{
        ?>
        <script>
            alert('오류가 발상하였습니다. \n 오류내용은 <?=$rstMsg?> 입니다.');
            opener.location.href='/';
            self.close();
        </script>
        <?
    }
?>