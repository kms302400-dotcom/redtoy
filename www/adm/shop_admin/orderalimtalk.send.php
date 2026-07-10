<?php
$sub_menu = '400400';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

check_admin_token();

$od_id = clean_xss_tags($_POST['od_id']);

$query = "select od.*, ct.it_name from {$g5['g5_shop_cart_table']} as ct inner join {$g5['g5_shop_order_table']} as od on ct.od_id = od.od_id where od.od_id = '".$od_id."'";
$od = sql_fetch($query);

if (!$od['od_id']) {
    die('{"rescd":"99","restx":"잘못된 접근입니다."}');
}

$order_items = preg_replace("/\'|\"|\||\,|\&|\;/", "", $od['it_name']).($od['od_cart_count'] > 1 ? ' 외 '.($od['od_cart_count'] - 1).'건' : '');

include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/config.php');
include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/bizmsg.class.php');

$arrmsg = array();
$bizmsg = new bizmsg();

if ($od['od_misu'] > 0) { // 미수금이 존재할경우에만 알림톡을 발송.

    $tpl = $bizmsg->getMsg('입금요청');
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
        $src[] = "/#{입금금액}/";
        $dst[] = number_format($od['od_misu']).'원';
        $src[] = "/#{입금계좌}/";
        $dst[] = $od['od_bank_account'];

        $bizmsg->phn = $od['od_hp'];
        $bizmsg->tmplId = $tmplId;
        $bizmsg->msg = $msg;
        $bizmsg->buttons = $buttons;
        $bizmsg->buttons2 = $buttons2;
        $bizmsg->buttons3 = $buttons3;
        $bizmsg->replace($src, $dst);
        $arrmsg[] = $bizmsg->create();
    }

    if ($arrmsg && is_array($arrmsg) && count($arrmsg)) $bizmsg->toSend($arrmsg); // 알림톡발송

    // 메일 보낸 내역 상점메모에 update
    $od_shop_memo = G5_TIME_YMDHIS.' - 입금요청 알림톡발송\n' . $od['od_shop_memo'];
    sql_query(" update {$g5['g5_shop_order_table']} set od_shop_memo = '$od_shop_memo' where od_id = '".$od_id."' ");

    die('{"rescd":"00","restx":"전송되었습니다."}');
}

die('{"rescd":"80","restx":"전송실패"}');