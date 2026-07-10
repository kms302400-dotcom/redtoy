<?php
$sub_menu = '102830';
include_once('./_common.php');

check_demo();

auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super')
    die('{"rescd":"95","restx":"최고관리자만 접근 가능합니다."}');

check_admin_token();

$phn = isset($_POST['phn']) ? trim($_POST['phn']) : '';
$tmplId = isset($_POST['tmplId']) ? trim($_POST['tmplId']) : '';
$msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
$smsKind = isset($_POST['smsKind']) ? trim($_POST['smsKind']) : '';
$reserveDtCheck = isset($_POST['reserveDtCheck']) ? trim($_POST['reserveDtCheck']) : '';
$reserveDt = isset($_POST['reserveDt']) ? trim($_POST['reserveDt']) : '';
$reserveTimeH = isset($_POST['reserveTimeH']) ? trim($_POST['reserveTimeH']) : '';
$reserveTimeM = isset($_POST['reserveTimeM']) ? trim($_POST['reserveTimeM']) : '';
$smsOnly = isset($_POST['smsOnly']) ? trim($_POST['smsOnly']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$emp_type = isset($_POST['emp_type']) ? trim($_POST['emp_type']) : '';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
$get_json = isset($_POST['get_json']) ? trim($_POST['get_json']) : '';
$img_url = isset($_POST['img_url']) ? trim($_POST['img_url']) : '';
$img_link = isset($_POST['img_link']) ? trim($_POST['img_link']) : '';
$ad_flag = isset($_POST['ad_flag']) ? trim($_POST['ad_flag']) : '';
$at_header = isset($_POST['at_header']) ? trim($_POST['at_header']) : '';
$at_itemHighlight_title = isset($_POST['at_itemHighlight_title']) ? trim($_POST['at_itemHighlight_title']) : '';
$at_itemHighlight_description = isset($_POST['at_itemHighlight_description']) ? trim($_POST['at_itemHighlight_description']) : '';
$select_member_type = isset($_POST['select_member_type']) ? preg_replace('/[^0-9]/', '', $_POST['select_member_type']) : 0; // 개별발송, 단체발송

$phn = preg_replace('/[^0-9]/', '', clean_xss_tags($phn));
$tmplId = clean_xss_tags($tmplId);
$smsKind = preg_replace('/[^0-9a-zA-Z]/', '', clean_xss_tags($smsKind));
$msg = preg_replace("#[\\\]+$#", "", substr(trim($msg),0,65536));
$reserveDtCheck = clean_xss_tags($reserveDtCheck);
$reserveDt = clean_xss_tags($reserveDt);
$reserveTimeH = clean_xss_tags($reserveTimeH);
$reserveTimeM = clean_xss_tags($reserveTimeM);
$smsOnly = clean_xss_tags($smsOnly);
$title = clean_xss_tags($title);
$emp_type = clean_xss_tags($emp_type);
$mode = clean_xss_tags($mode);
$get_json = clean_xss_tags($get_json);
$img_url = clean_xss_tags($img_url);
$img_link = clean_xss_tags($img_link);
$ad_flag = clean_xss_tags($ad_flag);
$at_header = clean_xss_tags($at_header);
$at_itemHighlight_title = clean_xss_tags($at_itemHighlight_title);
$at_itemHighlight_description = clean_xss_tags($at_itemHighlight_description);

if (strlen($msg) > 90) {
    $smsKind = 'L';
}

if ($reserveDtCheck)
    $reserveDt = preg_replace('/[^0-9]/', '', $reserveDt.$reserveTimeH.$reserveTimeM.'00');
else
    $reserveDt = '';

if (!$select_member_type && !$phn) {
    die('{"rescd":"60","restx":"수신받을 휴대폰번호를 입력해주세요."}');
}
if ($mode == '' && !$tmplId) {
    die('{"rescd":"50","restx":"템플릿 코드를 입력해주세요."}');
}
if (!$msg) {
    die('{"rescd":"40","restx":"발송 내용을 입력해주세요."}');
}

$message_type = '';
if ($mode == 'flt') { // 친구톡일경우
    $message_type = 'FT';
    if ($emp_type == 'IMAGE') {
        $message_type = 'FI'; // 이미지 친구톡
    }
    else if ($emp_type == 'IMAGEWIDE') {
        $message_type = 'FW'; // 와이드 이미지 친구톡
    }
    else {
        $img_url = '';
    }
}
else {
    if ($emp_type == 'IMAGE') {
        $message_type = 'AI'; // 이미지 알림톡
    }
}

$z = 0;
$buttons = $buttons2 = $buttons3 = array();
if (isset($_POST['at_button_type']) && count($_POST['at_button_type'])) {
    foreach ($_POST['at_button_type'] as $k => $v) {

        $button = array();
        $button['name'] = trim($_POST['at_button_name'][$z]);
        $button['type'] = $v;

        $atb_gubun = clean_xss_tags($_POST['atb_gubun'][$z]);

        switch ($v) {
            case 'WL':
                $button['url_mobile'] = trim($_POST['at_button_url_1'][$z]);
                if ($_POST['at_button_url_2'][$z]) {
                    $button['url_pc'] = trim($_POST['at_button_url_2'][$z]);
                }
                break;
            case 'AL':
                $button['scheme_android'] = trim($_POST['at_button_url_1'][$z]);
                $button['scheme_ios'] = trim($_POST['at_button_url_2'][$z]);
                $button['url_mobile'] = trim($_POST['at_button_url_3'][$z]);
                if ($_POST['at_button_url_4'][$z]) {
                    $button['url_pc'] = trim($_POST['at_button_url_4'][$z]);
                }
                break;
            case 'P1':
            case 'P2':
            case 'P3':
                $button['plugin_id'] = trim($_POST['at_button_url_1'][$z]);
                break;
            case 'BF':
                $button['biz_form_key'] = trim($_POST['at_button_url_1'][$z]);
                if ($atb_gubun == 'button' && $message_type != 'AT') { // 바로연결이 아니고 친구톡일경우
                    $button['biz_form_key'] = trim($_POST['atb_plugin_id'][$z]);
                }
                break;
        }

        if ($atb_gubun == 'button') {
            $buttons[] = $button; // 버튼타입
        }
        else if ($atb_gubun == 'link') {
            $buttons3[] = $button; // 대표링크
        }
        else {
            $buttons2[] = $button; // 바로연결
        }

        $z++;
    }
}

// 아이템정보 등록/수정
$arr_items = array();
if (isset($_POST['ati_title']) && $_POST['ati_title']) {
    foreach ($_POST['ati_title'] as $key => $value) {

        $ati_title = clean_xss_tags($_POST['ati_title'][$key]);
        $ati_summary = (int)($_POST['ati_summary'][$key]); // 아이템요약정보 여부
        $ati_description = clean_xss_tags($_POST['ati_description'][$key]);

        $row2 = array();
        $row2['title'] = $ati_title;
        $row2['description'] = $ati_description;

        if (!$row2['title'] || !$row2['description']) { // 공백일경우 엘리먼트를 적용하지 않음
            continue;
        }

        if ($ati_summary == '0') { // 아이템리스트
            $arr_items['list'][] = $row2;
        }
        else { // 요약정보
            $arr_items['summary'] = $row2;
        }
    }
}

$items = array();
$items['item'] = $arr_items;

if (count($arr_items) && $at_itemHighlight_title && $at_itemHighlight_description) {
    $items['itemHighlight'] = array('title'=>$at_itemHighlight_title, 'description'=>$at_itemHighlight_description);
}

$arr_list = array();
if (!$select_member_type) { // 개별발송
    $arr_list[0]['mb_hp'] = $phn;
}
else { // 단체발송
    if (!is_array($_POST['member_lev']) || !sizeof($_POST['member_lev'])) {
        die('{"rescd":"30","restx":"회원등급을 선택해주세요."}');
    }
    foreach ((array)$_POST['member_lev'] as $key => $lev) {
        $query = "select mb_id, mb_name, mb_nick, mb_hp from {$g5['member_table']} where mb_level = '".$lev."' and mb_leave_date = '' and mb_intercept_date = ''";
        $res = sql_query($query);
        while($row = sql_fetch_array($res)) {

            $row['mb_hp'] = preg_replace("/[^0-9]/", "", $row['mb_hp']);
		    if (!preg_match("/^01[0-9]{8,9}$/", $row['mb_hp']))
                continue;

            $arr_list[] = $row;
        }
    }
}

$bizmsg = new bizmsg();
$arrmsg = array();

$bizmsg->message_type = $message_type;
$bizmsg->tmplId = $tmplId;
$bizmsg->title = $title;
$bizmsg->smsKind = $smsKind;
$bizmsg->buttons = $buttons;
$bizmsg->buttons2 = $buttons2;
$bizmsg->buttons3 = $buttons3;
$bizmsg->reserveDt = $reserveDt;
$bizmsg->smsOnly = $smsOnly;
$bizmsg->img_url = $img_url;
$bizmsg->img_link = $img_link;
$bizmsg->ad_flag = $ad_flag;
$bizmsg->aheader = $at_header;
$bizmsg->items = (is_array($items) && count($items) ? $items : '');

$arr_json = array();
$count = 1;
set_time_limit(300);

foreach ((array)$arr_list as $k => $v) {

    $src = $dst = array();
    $src[] = "/#{회원명}/";
    $dst[] = $v['mb_name'];
    $src[] = "/#{회원닉네임}/";
    $dst[] = $v['mb_nick'];
    $src[] = "/#{회원아이디}/";
    $dst[] = $v['mb_id'];
    $msgtmp = preg_replace($src, $dst, $msg); // 템플릿 내용
    $bizmsg->msg = $msgtmp;
    $bizmsg->phn = $v['mb_hp'];
    $arrmsg[] = $bizmsg->create();

    if ($get_json == '1') {
        die(json_encode(array('rescd'=>'00', 'restx'=>$arrmsg[0])));
    }

    if ($count++%500 == 0) {
        if ($arrmsg && is_array($arrmsg) && count($arrmsg)) $arr_json[] = $bizmsg->toSend($arrmsg); // 알림톡발송
        unset($arrmsg);
        sleep(3); // 500건씩 전송 후 3초 쉼
    }
}

// 나머지 잔여 메시지 전송
if (count($arrmsg) && is_array($arrmsg)) {
    if ($arrmsg && is_array($arrmsg) && count($arrmsg)) $arr_json[] = $bizmsg->toSend($arrmsg); // 알림톡발송
}

$total_cnt = $success_cnt = $fail_cnt = 0;
if (is_array($arr_json) && count($arr_json) && !empty($arr_json)) {
    foreach ((array)$arr_json as $k => $v) {
        foreach ((array)$v as $k2 => $v2) {
            if ($v2['res_cd'] == 'success')
                $success_cnt++;
            else
                $fail_cnt++;

            $total_cnt++;
        }
    }
}

$restx = '총 '.number_format($total_cnt).'건 중 성공 : '.number_format($success_cnt).', 실패 : '.number_format($fail_cnt);

die('{"rescd":"00","restx":"'.$restx.'"}');