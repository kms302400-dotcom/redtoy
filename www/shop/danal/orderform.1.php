<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
// 전자결제를 사용할 때만 실행
if($default['de_iche_use'] || $default['de_vbank_use'] || $default['de_hp_use'] || $default['de_card_use'] || $default['de_easy_pay_use']) {
    ?>
    <script type='text/javascript'>
        function payment() {
            var frm = document.MAINPAY_FORM;
            var windowName = 'danalpay_' + frm.checkout_token.value;
            function money(name) {
                return frm[name] ? parseInt(frm[name].value || '0', 10) : 0;
            }
            var amount = money('od_price') + money('od_send_cost') + money('od_send_cost2')
                - money('item_coupon') - money('od_coupon') - money('od_send_coupon') - money('od_temp_point');
            if (!isFinite(amount) || amount <= 0) {
                alert('결제금액을 확인해 주세요.');
                return false;
            }
            frm.good_mny.value = String(amount);
            if (frm.od_settle_case.value == "HPP") {
                frm.action = "/shop/danal_ready_teledit.php";
                <? if (!is_mobile()) { ?>
                var win = window.open('', windowName, 'top=0,left=0,width=600,height=800')
                frm.target = windowName;
                <? } ?>
            } else {
                frm.action = "/shop/danal_ready.php";
                <? if (!is_mobile()) { ?>
                var win = window.open('', windowName, 'top=0,left=0,width=800,height=470')
                frm.target = windowName;
                <? } ?>
            }
            frm.submit();
        }
        
        function danal_cancel() {
            alert("결제가 취소되었습니다");
        }
    </script>
<?php } ?>