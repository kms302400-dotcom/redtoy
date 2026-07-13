<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 제대로된 include 시에만 실행
if (!defined('_ORDERALIMTALK_')) exit;

// 주문자님께 발송 체크를 했다면
if ($od_send_alimtalk) {

    $od = sql_fetch(" select * from {$g5['g5_shop_order_table']} where od_id = '".$od_id."' ");

    $is_receipt = false;

    // 신용카드 입금
    if ($od['od_receipt_price'] > 0) {
        $is_receipt = true;
    }

    // 포인트 입금
    if ($od['od_receipt_point'] > 0) {
        $is_receipt = true;
    }

    $order_hp = $od['od_hp'] ? $od['od_hp'] : $od['od_tel'];

    // 입금내역이 있다면 알림톡 발송
    if ($is_receipt && $order_hp) {

        include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/config.php');
        include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/bizmsg.class.php');

        $arrmsg = array();
        $bizmsg = new bizmsg();

        // 직접코딩이 아닌 관리자화면에서 등록한 메시지 정보.
        $tpl = $bizmsg->getMsg('입금확인');
        if ($tpl) {

            $alimtalk_receipt_price = (int) $od['od_receipt_price'];
            if ($od['od_settle_case'] === '무통장' && (int) $od['od_misu'] <= 0) {
                $alimtalk_receipt_price = (int) $od['od_cart_price'] + (int) $od['od_send_cost'] + (int) $od['od_send_cost2']
                    - (int) $od['od_cart_coupon'] - (int) $od['od_coupon'] - (int) $od['od_send_coupon']
                    - (int) $od['od_cancel_price'] - (int) $od['od_receipt_point'];
            }

            $tmplId = $tpl['tmplId'];
            $msg = $tpl['msg'];
            $buttons = $tpl['buttons'];
            $buttons2 = $tpl['buttons2'];
            $buttons3 = $tpl['buttons3'];
            unset($tpl);

            $src = $dst = array();
            $src[] = "/#{주문자명}/";
            $dst[] = $od['od_name'];
            $src[] = "/#{입금액}/";
            $dst[] = number_format(max(0, $alimtalk_receipt_price)).'원';
            $src[] = "/#{주문번호}/";
            $dst[] = $od_id;

            $bizmsg->phn = $order_hp;
            $bizmsg->tmplId = $tmplId;
            $bizmsg->msg = $msg;
            $bizmsg->buttons = $buttons;
            $bizmsg->buttons2 = $buttons2;
            $bizmsg->buttons3 = $buttons3;
            $bizmsg->replace($src, $dst);
            $arrmsg[] = $bizmsg->create();
        }

        if ($arrmsg && is_array($arrmsg) && count($arrmsg)) $bizmsg->toSend($arrmsg); // 알림톡발송

        // 알림톡 보낸 내역 상점메모에 update
        $od_shop_memo = G5_TIME_YMDHIS.' - 결제내역 알림톡발송\n' . $od['od_shop_memo'];

        sql_query(" update {$g5['g5_shop_order_table']} set od_shop_memo = '".$od_shop_memo."' where od_id = '".$od_id."' ");
    }
}
