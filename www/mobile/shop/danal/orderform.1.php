<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
// 전자결제를 사용할 때만 실행
IF (is_mobile()) {
    if($default['de_iche_use'] || $default['de_vbank_use'] || $default['de_hp_use'] || $default['de_card_use'] || $default['de_easy_pay_use']) {
    ?>
    <script type='text/javascript'>
        function payment() {
            var frm = document.MAINPAY_FORM;
            if (frm.od_settle_case.value == "HPP") {
                frm.action = "/shop/danal_ready_teledit.php";
            } else {
                frm.action = "/shop/danal_ready.php";
            }
            frm.target = '_top';
            frm.submit();
        }
        
        function danal_cancel() {
            alert("결제가 취소되었습니다");
        }
    </script>
<?php }
} else {
if($default['de_iche_use'] || $default['de_vbank_use'] || $default['de_hp_use'] || $default['de_card_use'] || $default['de_easy_pay_use']) {
    ?>
    <script type='text/javascript'>
        function payment() {
            var win = window.open('', 'danalpay', '')
            var frm = document.MAINPAY_FORM;
            if (frm.od_settle_case.value == "HPP") {
                frm.action = "/shop/danal_ready_teledit.php";
            } else {
                frm.action = "/shop/danal_ready.php";
            }
            frm.target = 'danalpay';
            frm.submit();
        }
        
        function danal_cancel() {
            alert("결제가 취소되었습니다");
        }
    </script>
<?php } } ?>