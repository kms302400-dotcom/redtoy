<?php
include_once('./_common.php');
require_once G5_LIB_PATH.'/redtoy_checkout.lib.php';
try {
    $redtoy_checkout = redtoy_checkout_cancel(redtoy_checkout_request_token());
    $sw_direct = $redtoy_checkout['direct'] ? 1 : 0;
    if (function_exists('redtoy_order_log')) redtoy_order_log($redtoy_checkout['order_id'], 'cancelled');
} catch (Throwable $e) {
    redtoy_checkout_reject(true);
}
?>
<script>
    if (window.opener) {
        try {
            window.opener.danal_cancel();
            window.close();
        } catch (e) {
            location.href = '/shop/orderform.php?sw_direct=<?php echo $sw_direct; ?>';
        }
    } else {
        location.href = '/shop/orderform.php?sw_direct=<?php echo $sw_direct; ?>';
    }
</script>
