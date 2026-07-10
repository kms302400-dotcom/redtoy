<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$order_hp = $od_hp ? $od_hp : $od_tel;

if ($order_hp) {

    $query = "select ct.it_name, od.od_cart_count from {$g5['g5_shop_cart_table']} as ct inner join {$g5['g5_shop_order_table']} as od on ct.od_id = od.od_id where od.od_id = '".$od_id."' and ct_select = '1'";
    $ct = sql_fetch($query);
    $order_items = preg_replace("/\'|\"|\||\,|\&|\;/", "", $ct['it_name']).($ct['od_cart_count'] > 1 ? ' 외 '.($ct['od_cart_count'] - 1).'건' : '');

    include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/config.php');
    include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/bizmsg.class.php');

    $data = array();
    $bizmsg = new bizmsg();

    // 직접코딩이 아닌 관리자화면에서 등록한 메시지 정보.
    $tpl = $bizmsg->getMsg('제품주문');
    if ($tpl) {

        $tmplId = $tpl['tmplId'];
        $msg = $tpl['msg'];
        $buttons = $tpl['buttons'];
        $buttons2 = $tpl['buttons2'];
        $buttons3 = $tpl['buttons3'];
        unset($tpl);

        $src = $dst = array();
        $src[] = "/#{주문자명}/";
        $dst[] = $od_name;
        $src[] = "/#{주문상품}/";
        $dst[] = $order_items;
        $src[] = "/#{주문번호}/";
        $dst[] = $od_id;
        $src[] = "/#{배송지}/";
        $dst[] = $od_b_addr1.' '.$od_b_addr2.' '.$od_b_addr3;
        $src[] = "/#{주문금액}/";
        $dst[] = number_format($tot_ct_price + $od_send_cost + $od_send_cost2).'원';

        $bizmsg->phn = $order_hp;
        $bizmsg->tmplId = $tmplId;
        $bizmsg->msg = $msg;
        $bizmsg->buttons = $buttons;
        $bizmsg->buttons2 = $buttons2;
        $bizmsg->buttons3 = $buttons3;
        $bizmsg->replace($src, $dst);
        $data[] = $bizmsg->create();
    }

    if ($od_settle_case == '무통장') { // 무통장입금일때 입금요청 알림톡을 발송.

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
            $dst[] = $od_name;
            $src[] = "/#{주문상품}/";
            $dst[] = $order_items;
            $src[] = "/#{주문번호}/";
            $dst[] = $od_id;
            $src[] = "/#{입금금액}/";
            $dst[] = number_format($od_misu).'원';
            $src[] = "/#{입금계좌}/";
            $dst[] = $od_bank_account;

            $bizmsg->phn = $order_hp;
            $bizmsg->tmplId = $tmplId;
            $bizmsg->msg = $msg;
            $bizmsg->buttons = $buttons;
            $bizmsg->buttons2 = $buttons2;
            $bizmsg->buttons3 = $buttons3;
            $bizmsg->replace($src, $dst);
            $data[] = $bizmsg->create();
        }
    }
}

// 관리자에게 발송
// 알림톡설정 > 환경설정 설정된 번호로 발송.
$tpl = $bizmsg->getMsg('주문알림');
if ($tpl) {

    $tmplId = $tpl['tmplId'];
    $msg = $tpl['msg'];
    $buttons = $tpl['buttons'];
    $buttons2 = $tpl['buttons2'];
    $buttons3 = $tpl['buttons3'];
    unset($tpl);

    $src = $dst = array();
    $src[] = "/#{주문자명}/";
    $dst[] = $od_name;
    $src[] = "/#{주문상품}/";
    $dst[] = $order_items;
    $src[] = "/#{주문번호}/";
    $dst[] = $od_id;
    $src[] = "/#{배송지}/";
    $dst[] = $od_b_addr1.' '.$od_b_addr2.' '.$od_b_addr3;
    $src[] = "/#{주문금액}/";
    $dst[] = number_format($tot_ct_price + $od_send_cost + $od_send_cost2).'원';

    $cf = sql_fetch("select cf_receiver from {$g5['wz_alimtalk_config_table']}");
    $cf_receiver = $cf['cf_receiver'];
    if ($cf_receiver) {
        $arr_receiver = explode(',', $cf_receiver);
        foreach ($arr_receiver as $key => $phone) {
            $bizmsg->phn = trim($phone);
            $bizmsg->tmplId = $tmplId;
            $bizmsg->msg = $msg;
            $bizmsg->buttons = $buttons;
            $bizmsg->buttons2 = $buttons2;
            $bizmsg->buttons3 = $buttons3;
            $bizmsg->replace($src, $dst);
            $data[] = $bizmsg->create();
        }
    }
}

if ($data && is_array($data) && count($data)) $bizmsg->toSend($data); // 알림톡발송