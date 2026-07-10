<?
    session_start();
    
    $sw_direct = 0;
    $pay_info = $_SESSION["payinfo"];
    if ($pay_info) {
        $sw_direct = $pay_info["order_info"]["sw_direct"];
    }
    $_SESSION["payinfo"] = "";
?>
<script>
    if (opener) {
        try {
            opener.danal_cancel();
            window.close();
        } catch {
            location.href = '/shop/orderform.php?sw_direct=<?=$sw_direct?>'
        }
    } else {
        location.href = '/shop/orderform.php?sw_direct=<?=$sw_direct?>'
    }
</script>