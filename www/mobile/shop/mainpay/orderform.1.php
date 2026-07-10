<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
// 전자결제를 사용할 때만 실행
    ?>
    <script src="https://api-std.mainpay.co.kr/js/mainpay.mobile-1.0.js"></script>
    <script type='text/javascript'>
        var READY_API_URL =  "/plugin/mainpay/mobile/_2_ready.php";
        function payment() {
            var request = mainpay_ready(READY_API_URL);
            request.done(function(response) {
                if (response.resultCode == '200') {
                    /* 결제창 호출 */
                    location.href = response.data.nextMobileUrl; // *주의* PC와 Mobile은 URL이 상이합니다.
                    return false;
                }
                alert("ERROR : "+JSON.stringify(response));
            });
        }
        window.onpopstate = function(){ history.go(-1)};
    </script>


