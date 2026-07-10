<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
// 전자결제를 사용할 때만 실행
if($default['de_iche_use'] || $default['de_vbank_use'] || $default['de_hp_use'] || $default['de_card_use'] || $default['de_easy_pay_use']) {
    ?>
    <script type='text/javascript'>
        function payment() {
            var frm = document.MAINPAY_FORM;
            if (frm.od_settle_case.value == "HPP") {
                frm.action = "/shop/danal_ready_teledit.php";
                <? if (!is_mobile()) { ?>
                var win = window.open('', 'danalpay', 'top=0,left=0,width=600,height=800')
                frm.target = 'danalpay';
                <? } ?>
            } else {
                frm.action = "/shop/danal_ready.php";
                <? if (!is_mobile()) { ?>
                var win = window.open('', 'danalpay', 'top=0,left=0,width=800,height=470')
                frm.target = 'danalpay';
                <? } ?>
            }
            frm.submit();
        }
        
        function danal_cancel() {
            alert("결제가 취소되었습니다");
        }
    </script>
<?php } ?>