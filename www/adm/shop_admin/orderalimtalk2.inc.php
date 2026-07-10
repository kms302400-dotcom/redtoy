<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 제대로된 include 시에만 실행
if (!defined('_ORDERALIMTALK_')) exit;

// 주문자님께 발송 체크를 했다면
if ($od_send_alimtalk2) {

    $query = "select od.*, ct.it_name from {$g5['g5_shop_cart_table']} as ct inner join {$g5['g5_shop_order_table']} as od on ct.od_id = od.od_id where od.od_id = '".$od_id."'";
    $od = sql_fetch($query);
    $order_items = preg_replace("/\'|\"|\||\,|\&|\;/", "", $od['it_name']).($od['od_cart_count'] > 1 ? ' 외 '.($od['od_cart_count'] - 1).'건' : '');

    $order_hp = $od['od_hp'] ? $od['od_hp'] : $od['od_tel'];

    // 입금내역이 있다면 알림톡 발송
    if ($order_hp && $od['od_invoice']) {

        include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/config.php');
        include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/bizmsg.class.php');

        $arrmsg = array();
        $bizmsg = new bizmsg();

        // 직접코딩이 아닌 관리자화면에서 등록한 메시지 정보.
        $tpl = $bizmsg->getMsg('배송안내');
        if ($tpl) {

            $tmplId = $tpl['tmplId'];
            $msg = $tpl['msg'];
            $buttons = $tpl['buttons'];
            $buttons2 = $tpl['buttons2'];
            $buttons3 = $tpl['buttons3'];
            unset($tpl);

            $src = $dst = array();
            $src[] = "/#{주문자명}/";
            $dst[] = $od['od_name'];
            $src[] = "/#{주문상품}/";
            $dst[] = $order_items;
            $src[] = "/#{주문번호}/";
            $dst[] = $od_id;
            $src[] = "/#{택배회사}/";
            $dst[] = $od['od_delivery_company'];
            $src[] = "/#{송장번호}/";
            $dst[] = $od['od_invoice'];

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
        $od_shop_memo = G5_TIME_YMDHIS.' - 배송내역 알림톡발송\n' . $od['od_shop_memo'];

        sql_query(" update {$g5['g5_shop_order_table']} set od_shop_memo = '".$od_shop_memo."' where od_id = '".$od_id."' ");
    }
}