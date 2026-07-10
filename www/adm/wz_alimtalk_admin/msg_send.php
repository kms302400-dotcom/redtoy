<?php
$sub_menu = '102830';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '알림톡 발송';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$mode = isset($_GET['mode']) ? clean_xss_tags($_GET['mode']) : '';

$at = $arr_buttons = $arr_buttons2 = $arr_buttons3 = $arr_items = array();
$at_tmplId = isset($_GET['at_tmplId']) ? trim(clean_xss_tags($_GET['at_tmplId'])) : '';
if ($at_tmplId) {
    $sql = " select * from {$g5['wz_alimtalk_template_table']} where at_tmplId = '".$at_tmplId."' ";
    $at = sql_fetch($sql);
    if (!$at['at_id']) alert('등록된 자료가 없습니다.');

    // 버튼정보
    $query2 = "select * from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$at['at_tmplId']."' and atb_gubun = 'button' order by atb_id asc ";
    $res2 = sql_query($query2);
    while($row2 = sql_fetch_array($res2)) {
        $arr_buttons[] = $row2;
    }

    // 버튼정보 (바로연결)
    $query2 = "select * from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$at['at_tmplId']."' and atb_gubun = 'quick' order by atb_id asc ";
    $res2 = sql_query($query2);
    while($row2 = sql_fetch_array($res2)) {
        $arr_buttons2[] = $row2;
    }

    // 버튼정보 (대표링크)
    $query2 = "select * from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$at['at_tmplId']."' and atb_gubun = 'link' order by atb_id asc ";
    $res2 = sql_query($query2);
    while($row2 = sql_fetch_array($res2)) {
        $arr_buttons3[] = $row2;
    }

    // 아이템정보
    $query2 = "select * from {$g5['wz_alimtalk_template_items_table']} where at_id = '".$at['at_id']."' order by ati_id asc ";
    $res2 = sql_query($query2);
    while($row2 = sql_fetch_array($res2)) {
        $arr_items[$row2['ati_summary']][] = $row2;
    }
}
else {
    $at['at_tmplId'] = '';
    $at['at_emp_type'] = '';
}

if ($mode == 'flt') { // 친구톡일경우
    $ALIMTALK_BUTN_TYPE = array_diff($ALIMTALK_BUTN_TYPE, array('DS', 'P1', 'P2', 'P3', 'BC', 'BT'));
}

$query = "select * from {$g5['wz_alimtalk_config_table']}";
$cf = sql_fetch($query);

$bf_btn_select_options = array('톡에서 예약하기', '톡에서 설문하기', '톡에서 응모하기');
$bf_btn_select = '<select name="at_button_name_select[]" class="bf-btn-select"><option>'.implode('</option><option>', $bf_btn_select_options).'</option></select>';

include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');
?>

<style>
.tbl_wrap {position:relative;min-height:400px;}
.tbl_wrap .west {margin-right:400px;}
.tbl_wrap .east {position:absolute;top:46px;right:0;width:380px;}
.tbl_frm01 textarea {height:300px;}

.json_code {background-color:#fbfbfb;}
.json_code .code {padding:10px;border:1px solid #dcdcdc}

.tab{float:left;}
.tabnav{font-size:0;border-top: 1px solid #ddd;border-left: 1px solid #ddd;border-right: 1px solid #ddd;}
.tabnav li{display: inline-block; width:250px; height:46px; text-align:center; border-right:1px solid #ddd;}
.tabnav li:last-child {border-right:0;}
.tabnav li a:before{content:""; position:absolute; left:0; top:0px; width:100%; height:2px; }
.tabnav li a.active:before{background:#6b6b6b;}
.tabnav li a.active{border-bottom:1px solid #fff;}
.tabnav li a{ position:relative; display:block; background: #f6f6f6; color: #000; padding:0 30px; line-height:46px; text-decoration:none; font-size:16px;}
.tabnav li a:hover,
.tabnav li a.active{background:#fff; color:#000; }
.tabcontent{padding: 20px; height:244px; border:1px solid #ddd; border-top:none;}

.bx-massage {border:1px solid #dfdfdf;padding:15px;}
.bx-massage .bx-hd {margin: 0 0 10px;padding: 10px;background-color: #3f51b5;color: #fff;text-align: center;}
.bx-massage .sms-con {padding:0px;width:100%;}

.at-button-link ul {}
.at-button-link ul li {text-align:left;margin-left:3px;}
.at-button-link ul.list-number li {margin-left:19px;list-style: disc;}
.at-button-link dl.list-box {margin: 0;}
.at-button-link dl.list-box > dt {float:left;width:20%;text-align:right;height:36px;line-height:36px;padding-right:7px}
.at-button-link dl.list-box > dd {float:left;width:80%;text-align:left;height:36px;line-height:36px;padding-left:0}
.at-button-link dl.list-box > dd:after {display:block;visibility:hidden;clear:both;content:""}
.bf-btn-select {display:none}

.select-group {padding:0 0 7px;}
</style>

<form name="frm" id="frm" action="./msg_send_update.php" method="post" enctype="multipart/form-data" onsubmit="return getAction(document.forms.frm);">
<input type="hidden" name="mode" id="mode" value="<?php echo $mode;?>" />
<input type="hidden" name="get_json" id="get_json" value="0" />

<section>
    <div class="local_desc02 local_desc">
        <p>
            <strong>알림톡</strong> 발송시 템플릿 코드, 템플릿 내용은 <strong>승인받은 템플릿만 발송이 가능</strong>합니다.<br>
            <strong>친구톡</strong>은 발송내용을 <strong>승인받지 않아도 발송이 가능</strong>합니다. 단 카카오톡 채널을 맺은 이용자 대상으로만 메시지 발송이 가능합니다.<br>
            Json코드는 전문개발자를 위해서 생성되는 샘플용 코드입니다. 비 개발자는 메시지발송 버튼만 사용하시면 됩니다.<br>
        </p>
    </div>

    <div class="tbl_frm01 tbl_wrap">

        <div class="tab">
        <ul class="tabnav">
            <li><a href="./msg_send.php?mode=&at_tmplId=<?php echo $at_tmplId;?>" class="<?php echo $mode == '' ? 'active' : '';?>">알림톡</a></li>
            <li><a href="./msg_send.php?mode=flt&at_tmplId=<?php echo $at_tmplId;?>" class="<?php echo $mode == 'flt' ? 'active' : '';?>">친구톡</a></li>

            <?php if ($cf['cf_sms_use']) {?>
            <li><a href="./msg_send.php?mode=sms&at_tmplId=<?php echo $at_tmplId;?>" class="<?php echo $mode == 'sms' ? 'active' : '';?>">문자메시지</a></li>
            <?php } ?>
        </ul>
        </div><!--tab-->

        <div class="west">

            <table>
            <caption>환경설정</caption>
            <tbody>
            <tr>
                <th scope="row">수신받을 휴대폰번호</th>
                <td>
                    <?php
                    if ($mode == 'flt') {
                        echo help("카카오톡 채널을 추가한 사용자 대상으로만 메시지 발송이 가능한 대신, 광고성 메시지도 발송 가능합니다.\n광고성 메시지로 야간 발송 제한이 있어 20:50 ~ 익일 08:00에 발송 불가.");
                    }
                    ?>
                    <div class="select-group">
                        <label><input type="radio" name="select_member_type" value="0" checked="checked"> 개별발송</label>&nbsp;
                        <label><input type="radio" name="select_member_type" value="1"> 단체발송</label>
                    </div>
                    <div class="input-group" id="select-member0">
                        <input type="text" name="phn" value="" id="phn" required class="frm_input required" size="20">
                        <a href="./msg_send_search_member.php" class="btn_frmline" id="search-member">회원검색</a>
                    </div>
                    <div class="input-group" id="select-member1">
                        <?php
                        echo help("단체발송시 발송내용에 #{회원명}, #{회원닉네임}, #{회원아이디} 를 입력시 해당회원의 회원명, 회원닉네임, 회원아이디로 자동 치환됩니다. (※ 아이템 리스트 제외)");
                        $mb = get_member($config['cf_admin'], 'mb_level');
                        $max_level = (int)$mb['mb_level'];
                        for ($z=2; $z<=$max_level; $z++) {

                            $query = " select count(*) as cnt from {$g5['member_table']} where mb_level = '".$z."' and mb_leave_date = '' and mb_intercept_date = '' ";
                            $row2 = sql_fetch($query);
                            $member_cnt = (int)$row2['cnt'];

                            echo '<label><input type="checkbox" name="member_lev[]" value="'.$z.'"> '.$z.'등급 ('.number_format($member_cnt).'명)</label>&nbsp;'.PHP_EOL;
                        }
                        ?>
                    </div>
                </td>
            </tr>

            <?php if ($mode != 'sms') {?>
                <tr>
                    <th scope="row">발송실패시</th>
                    <td>
                        <?php echo help('발송에 실패할경우 문자메시지 발송여부를 설정합니다.<br /><strong><a href="https://www.bizmsg.kr/myinfo/product" target="_blank">비즈엠사이트 &gt; 내정보 &gt; 이용상품</a></strong> 에서 "발송실패시 보내지 않음"으로 설정되어있는경우 SMS, LMS 를 설정하여도 발송되지 않습니다.'); ?>
                        <input type="radio" name="smsKind" value="S" id="smsKind1" <?php echo ($config['cf_sms_type'] == '' ? 'checked' : '');?>>
                        <label for="smsKind1"> SMS</label>&nbsp;
                        <input type="radio" name="smsKind" value="L" id="smsKind2" <?php echo ($config['cf_sms_type'] == 'LMS' ? 'checked' : '');?>>
                        <label for="smsKind2"> LMS</label>&nbsp;
                        <input type="radio" name="smsKind" value="N" id="smsKind3">
                        <label for="smsKind3"> 발송하지 않음</label>
                    </td>
                </tr>
            <?php } else { ?>
                <input type="hidden" name="smsKind" id="smsKind" value="S" />
            <?php } ?>

            <?php if ($mode == 'sms') {?>
                <input type="hidden" name="smsOnly" id="smsOnly" value="Y" />
            <?php } ?>

            <?php if ($mode == '') {?>
                <tr>
                    <th scope="row">템플릿 코드</th>
                    <td>
                        <?php echo help('템플릿 코드가 정확하지 않으면 문자로 발송됩니다.');?>
                        <input type="text" name="tmplId" value="<?php echo $at['at_tmplId'];?>" id="tmplId" required class="frm_input required" size="20">
                    </td>
                </tr>
            <?php } ?>

            <?php if ($mode == '' || $mode == 'flt') {?>
                <tr>
                    <th scope="row">발송 강조 유형</th>
                    <td>
                        <select name="emp_type" id="emp_type">
                            <option value="" <?php echo $at['at_emp_type'] == '' ? 'selected="selected"' : '';?>>선택안함</option>
                            <?php if ($mode == 'flt') { // 친구톡일경우 ?>
                                <option value="IMAGE" <?php echo $at['at_emp_type'] == 'IMAGE' ? 'selected="selected"' : '';?>>이미지형</option>
                                <option value="IMAGEWIDE" <?php echo $at['at_emp_type'] == 'IMAGEWIDE' ? 'selected="selected"' : '';?>>와이드 이미지형</option>
                            <?php } else { ?>
                                <option value="TEXT" <?php echo $at['at_emp_type'] == 'TEXT' ? 'selected="selected"' : '';?>>강조표기형</option>
                                <option value="IMAGE" <?php echo $at['at_emp_type'] == 'IMAGE' ? 'selected="selected"' : '';?>>이미지형</option>
                                <option value="ITEMLIST" <?php echo $at['at_emp_type'] == 'ITEMLIST' ? 'selected="selected"' : '';?>>아이템리스트형</option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr class="tr-emp-text">
                    <th scope="row">강조표기</th>
                    <td>
                        <?php echo help("템플릿 내용 중 강조 표기할 핵심 정보 (CBT, 템플릿 검수 가이드 참고)"); ?>
                        <input type="text" name="title" value="<?php echo stripslashes($at['at_accent_title']); ?>" id="title" class="frm_input" size="30">
                    </td>
                </tr>

                <tr class="tr-itemlist">
                    <th scope="row"><label for="at_header">템플릿 헤더</label></th>
                    <td>
                        <?php echo help("아이템리스트형 메시지 상단에 표기할 제목"); ?>
                        <input type="text" name="at_header" value="<?php echo stripslashes($at['at_header']); ?>" id="at_header" class="frm_input" size="30">
                    </td>
                </tr>
                <tr class="tr-itemlist">
                    <th scope="row">아이템 하이라이트</th>
                    <td>
                        <table cellspacing="0" border="1" class="wz_tbl_1">
                            <caption></caption>
                            <colgroup>
                                <col width="120">
                                <col width="auto">
                            </colgroup>
                            <tr>
                                <th>타이틀 *</th>
                                <td>
                                    <input type="text" name="at_itemHighlight_title" value="<?php echo get_text($at['at_itemHighlight_title']);?>" class="frm_input" maxlength="150" style="width:90%;" />
                                </td>
                            </tr>
                            <tr>
                                <th>디스크립션 *</th>
                                <td>
                                    <input type="text" name="at_itemHighlight_description" value="<?php echo get_text($at['at_itemHighlight_description']);?>" class="frm_input" maxlength="150" style="width:90%;" />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="tr-itemlist">
                    <th scope="row">아이템 리스트 *</th>
                    <td>
                        <table cellspacing="0" border="1" class="wz_tbl_1" id="tbl-item-list" style="width:100%;">
                            <caption></caption>
                            <colgroup>
                                <col width="10%"/>
                                <col width="20%"/>
                                <col width="60%"/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th scope="row">no</th>
                                <th scope="row">아이템명</th>
                                <th scope="row">아이템 내용	</th>
                                <th scope="row"><a href="#none" class="btn_frmline add-item-tr">추가</a></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (isset($arr_items[0]) && count($arr_items[0]) > 0) {
                                foreach ((array)$arr_items[0] as $k => $v) {
                                    ?>
                                    <tr>
                                        <td class="center">
                                            <span class="no3"></span>
                                            <input type="hidden" name="ati_id[]" value="<?php echo $v['ati_id'];?>">
                                        </td>
                                        <td class="center">
                                            <input type="hidden" name="ati_summary[]" value="0" />
                                            <input type="text" name="ati_title[]" value="<?php echo $v['ati_title']?>" maxlength="150" class="frm_input frm_input_full" />
                                        </td>
                                        <td class="center">
                                            <input type="text" name="ati_description[]" value="<?php echo $v['ati_description']?>" maxlength="150" class="frm_input frm_input_full" />
                                        </td>
                                        <td class="center"><a href="#none" class="btn_frmline del-item-tr" data-ati_id="<?php echo $v['ati_id'];?>">삭제</a></td>
                                    </tr>
                                    <?php
                                }
                            }
                            else {
                                ?>
                                <tr class="item-empty">
                                    <td colspan="4">추가버튼을 클릭해서 아이템을 추가해주세요.</td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr class="tr-itemlist">
                    <th scope="row"><label>아이템 요약정보</label></th>
                    <td>
                        <table cellspacing="0" border="1" class="wz_tbl_1" style="width:100%;">
                            <caption></caption>
                            <colgroup>
                                <col width="10%"/>
                                <col width="20%"/>
                                <col width="60%"/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th scope="row"></th>
                                <th scope="row">아이템명</th>
                                <th scope="row">아이템 내용	</th>
                                <th scope="row"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="center">
                                    -
                                    <input type="hidden" name="ati_id[]" value="<?php echo (isset($arr_items[1][0]['ati_id']) ? $arr_items[1][0]['ati_id'] : '')?>">
                                </td>
                                <td class="center">
                                    <input type="hidden" name="ati_summary[]" value="1" />
                                    <input type="text" name="ati_title[]" value="<?php echo (isset($arr_items[1][0]['ati_title']) ? $arr_items[1][0]['ati_title'] : '')?>" maxlength="150" class="frm_input frm_input_full" />
                                </td>
                                <td class="center">
                                    <input type="text" name="ati_description[]" value="<?php echo (isset($arr_items[1][0]['ati_description']) ? $arr_items[1][0]['ati_description'] : '')?>" maxlength="150" class="frm_input frm_input_full" />
                                </td>
                                <td class="center">-</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php } ?>

            <?php if ($mode == 'flt') {?>
                <tr class="tr-flt-image">
                    <th scope="row">이미지 URL 입력</th>
                    <td>
                        <?php echo help('이미지는 반드시 사전에 등록이 된 이미지만 발송이 가능합니다. <a href="./attch_image_list.php" target="_blank">[친구톡 이미지 관리]</a> 메뉴에서 등록 가능합니다.');?>
                        <input type="text" name="img_url" value="" id="img_url" class="frm_input" size="70">
                        <a href="./attch_image_select.php" class="btn_frmline wz-iframe-win" id="add-attch-image">등록된이미지선택</a>
                    </td>
                </tr>
                <tr class="tr-flt-image">
                    <th scope="row">첨부된 이미지 클릭시 이동할 URL</th>
                    <td>
                        <?php echo help('http:// 또는 https:// 포함 해서 입력해주세요.');?>
                        <input type="text" name="img_link" id="img_link" class="frm_input" value="" style="width:100%;" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">광고성 메시지 필수 표시사항</th>
                    <td>
                        <?php echo help('노출을 선택할경우 (광고) 표시 <img src="./img/banner_ad_info.gif" /> 가 되어 발송됩니다.');?>
                        <input type="radio" name="ad_flag" value="Y" id="ad_flag1" checked>
                        <label for="ad_flag1"> 노출</label>&nbsp;
                        <input type="radio" name="ad_flag" value="N" id="ad_flag2">
                        <label for="ad_flag2"> 노출하지 않음</label>
                    </td>
                </tr>
            <?php } ?>

            <tr>
                <th scope="row">예약발송</th>
                <td>
                    <input type="text" name="reserveDt" id="reserveDt" class="frm_input" disabled value="<?php echo G5_TIME_YMD;?>" />
                    <select name="reserveTimeH" id="reserveTimeH" disabled>
                        <?php
                        $reserveTimeH = date('H', G5_SERVER_TIME);
                        for ($z=1; $z<=24; $z++) {
                            $hh = sprintf('%02d', $z);
                            echo '<option value="'.$hh.'" '.($hh == $reserveTimeH ? 'selected' : '').'>'.$z.'시</option>'.PHP_EOL;
                        }
                        ?>
                    </select>
                    <select name="reserveTimeM" id="reserveTimeM" disabled>
                        <?php
                        $reserveTimeM = date('i', G5_SERVER_TIME);
                        for ($z=0; $z<=59; $z++) {
                            $mm = sprintf('%02d', $z);
                            echo '<option value="'.$mm.'" '.($mm == $reserveTimeM ? 'selected' : '').'>'.$z.'분</option>'.PHP_EOL;
                        }
                        ?>
                    </select>
                    <label><input type="checkbox" name="reserveDtCheck" id="reserveDtCheck" value="1" /> 예약발송</label>
                </td>
            </tr>

            <?php if ($mode != 'sms') {?>
                <tr>
                    <th scope="row"><label for="">버튼타입</label></th>
                    <td>
                        <table cellspacing="0" border="1" class="wz_tbl_1" id="tbl-clr-list" style="width:100%;">
                            <caption></caption>
                            <colgroup>
                                <col width="10%"/>
                                <col width="20%"/>
                                <col width="20%"/>
                                <col width="40%"/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th scope="row">no</th>
                                <th scope="row">버튼타입</th>
                                <th scope="row">버튼명</th>
                                <th scope="row">버튼링크</th>
                                <th scope="row"><a href="#none" class="btn_frmline add-clr-tr">추가</a></th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (count($arr_buttons) > 0) {
                                    foreach ((array)$arr_buttons as $k => $v) {
                                        ?>
                                        <tr>
                                            <td class="center">
                                                <span class="no"></span>
                                            </td>
                                            <td class="center">
                                                <input type="hidden" name="atb_gubun[]" value="button" />
                                                <select name="at_button_type[]" class="at-button-type">
                                                    <?php
                                                    foreach ((array)$ALIMTALK_BUTN_TYPE as $k2 => $v2) {
                                                        $selected = '';
                                                        if ($v['atb_type'] == $v2) {
                                                            $selected = 'selected=selected';
                                                        }
                                                        echo '<option value="'.$v2.'" '.$selected.'>'.$k2.'</option>'.PHP_EOL;
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td class="center">
                                                <input type="text" name="at_button_name[]" value="<?php echo ($v['atb_type'] == 'AC' ? '채널 추가' : $v['atb_name']);?>" <?php echo ($v['atb_type'] == 'BF' ? '' : 'required' );?> class="frm_input" <?php echo ($v['atb_type'] == 'AC' ? 'readonly' : '');?> maxlength="150" style="width:90%;<?php echo ($v['atb_type'] == 'BF' ? 'display:none;' : '' );?>" />
                                                <?php
                                                if ($v['atb_type'] == 'BF') {
                                                    echo '<select name="at_button_name_select[]" class="bf-btn-select" style="display:inline-block;">';
                                                    foreach ((array)$bf_btn_select_options as $key => $options) {
                                                        $selected = '';
                                                        if ($v['atb_name'] == $options) {
                                                            $selected = 'selected=selected';
                                                        }
                                                        echo '<option '.$selected.'>'.$options.'</option>'.PHP_EOL;
                                                    }
                                                    echo '</select>';
                                                }
                                                else {
                                                    echo $bf_btn_select;
                                                }
                                                ?>
                                            </td>
                                            <td class="center">
                                                <span class="at-button-link">
                                                <?php
                                                switch ($v['atb_type']) {
                                                    case 'DS':
                                        echo '<ul class="list-number"><li>알림톡 메시지 파싱을 통해 배송조회 버튼 클릭시 각 택배사 배송조회 페이지 링크가 자동 생성 됩니다.</li><li>파싱 지원 택배사 : KGB택배 우체국택배 로젠택배 일양로지스 GTX로지스 FedEx 한진택배 경동택배 합동택배 롯데택배 농협택배 호남택배 CU 편의점택배 CVSnet편의점택배 TNT Express USPS EMS 천일택배 DHL 대신택배 건영택배 한덱스 굿투럭</li></ul><input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                    break;
                                                    case 'WL':
                                        echo '<dl class="list-box"><dt>Mobile : </dt><dd><input type="text" name="at_button_url_1[]" value="'.$v['atb_url_mobile'].'" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>PC(선택) : </dt><dd><input type="text" name="at_button_url_2[]" value="'.$v['atb_url_pc'].'" class="frm_input frm_input_full" maxlength="150" /><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/></dd></dl>';
                                                    break;
                                                    case 'AL':
                                                        echo '<dl class="list-box"><dt>Android : </dt><dd><input type="text" name="at_button_url_1[]" value="'.$v['atb_scheme_android'].'" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>iOS : </dt><dd><input type="text" name="at_button_url_2[]" value="'.$v['atb_scheme_ios'].'" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>Mobile : </dt><dd><input type="text" name="at_button_url_3[]" value="'.$v['atb_url_mobile'].'" class="frm_input frm_input_full" maxlength="150" /></dd><dt>PC(선택) : </dt><dd><input type="text" name="at_button_url_4[]" value="'.$v['atb_url_pc'].'" class="frm_input frm_input_full" maxlength="150" /></dd></dl>';
                                                    break;
                                                    case 'P1':
                                                    case 'P2':
                                                    case 'P3':
                                        echo '플러그인 ID : <input type="text" name="at_button_url_1[]" value="'.$v['atb_plugin_id'].'" required class="required frm_input" style="width:200px;" maxlength="150" /><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                    break;
                                                    case 'BF':
                                        echo '비즈폼 key : <input type="text" name="at_button_url_1[]" value="'.$v['atb_plugin_id'].'" required class="required frm_input" style="width:200px;" maxlength="150" /><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                    break;
                                                    default:
                                        echo '<input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                    break;
                                                }
                                                ?>
                                                </span>
                                            </td>
                                            <td class="center"><a href="#none" class="btn_frmline del-clr-tr">삭제</a></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                else {
                                    ?>
                                    <tr class="clr-empty">
                                        <td colspan="5">추가버튼을 클릭해서 추가해주세요.</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="">바로연결</label></th>
                    <td>
                        <table cellspacing="0" border="1" class="wz_tbl_1" id="tbl-clr-list2" style="width:100%;">
                            <caption></caption>
                            <colgroup>
                                <col width="10%"/>
                                <col width="20%"/>
                                <col width="20%"/>
                                <col width="40%"/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th scope="row">no</th>
                                <th scope="row">바로연결타입</th>
                                <th scope="row">바로연결명</th>
                                <th scope="row">바로연결링크</th>
                                <th scope="row"><a href="#none" class="btn_frmline add-clr-tr2">추가</a></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (count($arr_buttons2) > 0) {
                                foreach ((array)$arr_buttons2 as $k => $v) {
                                    ?>
                                    <tr>
                                        <td class="center">
                                            <span class="no2"></span>
                                        </td>
                                        <td class="center">
                                            <input type="hidden" name="atb_gubun[]" value="quick" />
                                            <select name="at_button_type[]" class="at-button-type">
                                                <?php
                                                foreach ((array)$ALIMTALK_BUTN_TYPE as $k2 => $v2) {
                                                    if (!($v2 == 'WL' || $v2 == 'AL' || $v2 == 'BK' || $v2 == 'MD' || $v2 == 'BC' || $v2 == 'BT' || $v2 == 'BF')) continue;
                                                    $selected = '';
                                                    if ($v['atb_type'] == $v2) {
                                                        $selected = 'selected=selected';
                                                    }
                                                    echo '<option value="'.$v2.'" '.$selected.'>'.$k2.'</option>'.PHP_EOL;
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td class="center">
                                            <input type="text" name="at_button_name[]" value="<?php echo ($v['atb_type'] == 'AC' ? '채널 추가' : $v['atb_name']);?>" <?php echo ($v['atb_type'] == 'BF' ? '' : 'required' );?> class="frm_input" <?php echo ($v['atb_type'] == 'AC' ? 'readonly' : '');?> maxlength="150" style="width:90%;<?php echo ($v['atb_type'] == 'BF' ? 'display:none;' : '' );?>" />
                                            <?php
                                            if ($v['atb_type'] == 'BF') {
                                                echo '<select name="at_button_name_select[]" class="bf-btn-select" style="display:inline-block;">';
                                                foreach ((array)$bf_btn_select_options as $key => $options) {
                                                    $selected = '';
                                                    if ($v['atb_name'] == $options) {
                                                        $selected = 'selected=selected';
                                                    }
                                                    echo '<option '.$selected.'>'.$options.'</option>'.PHP_EOL;
                                                }
                                                echo '</select>';
                                            }
                                            else {
                                                echo $bf_btn_select;
                                            }
                                            ?>
                                        </td>
                                        <td class="center">
                                            <span class="at-button-link">
                                            <?php
                                            switch ($v['atb_type']) {
                                                case 'DS':
                                        echo '<ul class="list-number"><li>알림톡 메시지 파싱을 통해 배송조회 버튼 클릭시 각 택배사 배송조회 페이지 링크가 자동 생성 됩니다.</li><li>파싱 지원 택배사 : KGB택배 우체국택배 로젠택배 일양로지스 GTX로지스 FedEx 한진택배 경동택배 합동택배 롯데택배 농협택배 호남택배 CU 편의점택배 CVSnet편의점택배 TNT Express USPS EMS 천일택배 DHL 대신택배 건영택배 한덱스 굿투럭</li></ul><input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                break;
                                                case 'WL':
                                        echo '<dl class="list-box"><dt>Mobile : </dt><dd><input type="text" name="at_button_url_1[]" value="'.$v['atb_url_mobile'].'" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>PC(선택) : </dt><dd><input type="text" name="at_button_url_2[]" value="'.$v['atb_url_pc'].'" class="frm_input frm_input_full" maxlength="150" /><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/></dd></dl>';
                                                break;
                                                case 'AL':
                                                    echo '<dl class="list-box"><dt>Android : </dt><dd><input type="text" name="at_button_url_1[]" value="'.$v['atb_scheme_android'].'" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>iOS : </dt><dd><input type="text" name="at_button_url_2[]" value="'.$v['atb_scheme_ios'].'" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>Mobile : </dt><dd><input type="text" name="at_button_url_3[]" value="'.$v['atb_url_mobile'].'" class="frm_input frm_input_full" maxlength="150" /></dd><dt>PC(선택) : </dt><dd><input type="text" name="at_button_url_4[]" value="'.$v['atb_url_pc'].'" class="frm_input frm_input_full" maxlength="150" /></dd></dl>';
                                                break;
                                                case 'P1':
                                                case 'P2':
                                                case 'P3':
                                        echo '플러그인 ID : <input type="text" name="at_button_url_1[]" value="'.$v['atb_plugin_id'].'" required class="required frm_input" style="width:200px;" maxlength="150" /><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                break;
                                                case 'BF':
                                        echo '비즈폼 key : <input type="text" name="at_button_url_1[]" value="'.$v['atb_plugin_id'].'" required class="required frm_input" style="width:200px;" maxlength="150" /><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                break;
                                                default:
                                        echo '<input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/><input type="hidden" name="at_button_url_3[]"/><input type="hidden" name="at_button_url_4[]"/>';
                                                break;
                                            }
                                            ?>
                                            </span>
                                        </td>
                                        <td class="center"><a href="#none" class="btn_frmline del-clr-tr2">삭제</a></td>
                                    </tr>
                                    <?php
                                }
                            }
                            else {
                                ?>
                                <tr class="clr-empty2">
                                    <td colspan="5">추가버튼을 클릭해서 추가해주세요.</td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="">대표링크</label></th>
                    <td>
                        <table cellspacing="0" border="1" class="wz_tbl_1" id="tbl-clr-list3" style="width:100%;">
                            <caption></caption>
                            <colgroup>
                                <col width="22%"/>
                                <col width="22%"/>
                                <col width="23%"/>
                                <col width="23%"/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th scope="row">Mobile</th>
                                <th scope="row">PC</th>
                                <th scope="row">Android</th>
                                <th scope="row">iOS</th>
                                <th scope="row"><a href="#none" class="btn_frmline add-clr-tr3">추가</a></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (count($arr_buttons3) > 0) {
                                foreach ((array)$arr_buttons3 as $k => $v) {
                                    ?>
                                    <tr>
                                        <td class="center">
                                            <input type="hidden" name="atb_gubun[]" value="link" />
                                            <input type="hidden" name="at_button_name[]" value="대표링크" />
                                            <input type="hidden" name="at_button_name_select[]" value="" />
                                            <input type="hidden" name="at_button_type[]" value="AL" />
                                            <input type="text" name="at_button_url_3[]" value="<?php echo $v['atb_url_mobile'];?>" class="frm_input frm_input_full" maxlength="150" />
                                        </td>
                                        <td class="center">
                                            <input type="text" name="at_button_url_4[]" value="<?php echo $v['atb_url_pc'];?>" class="frm_input frm_input_full" maxlength="150" />
                                        </td>
                                        <td class="center">
                                            <input type="text" name="at_button_url_1[]" value="<?php echo $v['atb_scheme_android'];?>" class="frm_input frm_input_full" maxlength="150" />
                                        </td>
                                        <td class="center">
                                            <input type="text" name="at_button_url_2[]" value="<?php echo $v['atb_scheme_ios'];?>" class="frm_input frm_input_full" maxlength="150" />
                                        </td>
                                        <td class="center"><a href="#none" class="btn_frmline del-clr-tr">삭제</a></td>
                                    </tr>
                                    <?php
                                }
                            }
                            else {
                                ?>
                                <tr class="clr-empty3">
                                    <td colspan="5">대표링크를 추가할 수 있습니다.</td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </td>
                </tr>

            <?php } ?>

            </tbody>
            </table>
        </div>
        <div class="east">
            <div class="bx-massage">
                <div class="bx-hd">
                    <p>발송내용</p>
                </div>
                <div class="bx-ft">
                    <p class="important">
                        <textarea name="msg" id="msg" cols="50" rows="6" class="sms-con required" required onkeyup="byte_check('msg', 'byte1', 'byte1_max');"><?php echo isset($at['at_msg']) ? $at['at_msg'] : '';?></textarea>
                    </p>
                    <p class="important">
                        <span id="byte1">2</span> / <span id="byte1_max">90</span> byte
                    </p>
                </div>
            </div>
        </div>
    </div>

    <input type="submit" value="메시지발송" class="btn_submit btn btn_01" accesskey="s">
    <input type="button" value="Json 코드 생성 하기" class="btn_submit btn btn_01" onclick="getJsonCode();">

    <div id="make_json_code" class="local_desc02 json_code"></div>

</section>

</form>

<script type="text/javascript">
<!--
    function getAction(f) {

        f.get_json.value = "0";

        var dataString = $('#frm').serialize();
        $.post('./msg_send_update.php', dataString, function( data ) {
            alert(data.restx);
        }, 'json');

        $('#make_json_code').empty();

        return false;
    }
    function getJsonCode() {

        var f = document.forms.frm;
            f.get_json.value = "1";

        var $f = $(f);
        if(typeof f.token === "undefined")
            $f.prepend('<input type="hidden" name="token" value="">');

        var token = get_ajax_token();
        f.token.value = token;

        var dataString = $('#frm').serialize();
        $.post('./msg_send_update.php', dataString, function( data ) {
            if (data.rescd == '00') {
                var json_code = JSON.stringify(data.restx);
                $('#make_json_code').html('<div class="code">['+json_code+']</div>');
            }
            else {
                alert(data.restx);
            }
        }, 'json');
    }
    $(function() {
        $(document).on('click', '.add-clr-tr', function() {
            $('.clr-empty').remove();
            tbl_clr_tr_add();
        });
        $(document).on('click', '.del-clr-tr', function() {
            $(this).closest('tr').remove();
            var tr_cnt = $('#tbl-clr-list tbody tr').length;
            if (tr_cnt == 0) {
                $('#tbl-clr-list').append('<tr class="clr-empty"><td colspan="5">추가버튼을 클릭해서 추가해주세요.</td></tr>');
            }
            tbl_numbering();
        });
        $(document).on('click', '.add-clr-tr2', function() {
            $('.clr-empty2').remove();
            tbl_clr_tr_add2();
        });
        $(document).on('click', '.del-clr-tr2', function() {
            $(this).closest('tr').remove();
            var tr_cnt = $('#tbl-clr-list2 tbody tr').length;
            if (tr_cnt == 0) {
                $('#tbl-clr-list2').append('<tr class="clr-empty2"><td colspan="5">추가버튼을 클릭해서 추가해주세요.</td></tr>');
            }
            tbl_numbering();
        });
        $(document).on('click', '.add-clr-tr3', function() {
            $('.clr-empty3').remove();
            tbl_clr_tr_add3();
        });
        $(document).on('click', '.del-clr-tr3', function() {
            $(this).closest('tr').remove();
            var tr_cnt = $('#tbl-clr-list3 tbody tr').length;
            if (tr_cnt == 0) {
                $('#tbl-clr-list3').append('<tr class="clr-empty3"><td colspan="5">대표링크를 추가할 수 있습니다.</td></tr>');
            }
            tbl_numbering();
        });
        $(document).on('change', '.at-button-type', function() { // 버튼타입선택
            var button_type = $(this).val();
            var $el = $(this).closest('tr');
                $el.find("input[name='at_button_name[]']").attr('required', true).show().attr('readonly', false).val('');
                $el.find(".bf-btn-select").hide();
            switch (button_type) {
                case 'DS':
                    $el.find('.at-button-link').html('<ul class="list-number"><li>알림톡 메시지 파싱을 통해 배송조회 버튼 클릭시 각 택배사 배송조회 페이지 링크가 자동 생성 됩니다.</li><li>파싱 지원 택배사 : KGB택배 우체국택배 로젠택배 일양로지스 GTX로지스 FedEx 한진택배 경동택배 합동택배 롯데택배 농협택배 호남택배 CU 편의점택배 CVSnet편의점택배 TNT Express USPS EMS 천일택배 DHL 대신택배 건영택배 한덱스 굿투럭</li></ul><input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/>');
                break;
                case 'WL':
                    $el.find('.at-button-link').html('<dl class="list-box"><dt>Mobile : </dt><dd><input type="text" name="at_button_url_1[]" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>PC(선택) : </dt><dd><input type="text" name="at_button_url_2[]" class="frm_input frm_input_full" maxlength="150" /></dd></dl>');
                break;
                case 'AL':
                    $el.find('.at-button-link').html('<dl class="list-box"><dt>Android : </dt><dd><input type="text" name="at_button_url_1[]" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>iOS : </dt><dd><input type="text" name="at_button_url_2[]" required class="required frm_input frm_input_full" maxlength="150" /></dd><dt>Mobile : </dt><dd><input type="text" name="at_button_url_3[]" class="frm_input frm_input_full" maxlength="150" /></dd><dt>PC(선택) : </dt><dd><input type="text" name="at_button_url_4[]" class="frm_input frm_input_full" maxlength="150" /></dd></dl>');
                break;
                case 'AC':
                    $el.find("input[name='at_button_name[]']").attr('readonly', true).val('채널 추가');
                    $el.find('.at-button-link').html('<input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/>');
                break;
                case 'P1':
                case 'P2':
                case 'P3':
                    $el.find('.at-button-link').html('플러그인 ID : <input type="text" name="at_button_url_1[]" required class="required frm_input" style="width:200px;" maxlength="150" />');
                break;
                case 'BF':
                    $el.find("input[name='at_button_name[]']").attr('required', false).hide();
                    $el.find('.bf-btn-select').show();
                    $el.find('.at-button-link').html('비즈폼 key : <input type="text" name="at_button_url_1[]" required class="required frm_input" style="width:200px;" maxlength="150" />');
                break;
                default:
                    $el.find('.at-button-link').html('<input type="hidden" name="at_button_url_1[]"/><input type="hidden" name="at_button_url_2[]"/>');
                break;
            }
        });
        $(document).on('click', '#reserveDtCheck', function() {
            if ($(this).is(':checked')) {
                $('#reserveDt').attr('disabled', false).focus();
                $('#reserveTimeH').attr('disabled', false);
                $('#reserveTimeM').attr('disabled', false);
            }
            else {
                $('#reserveDt').attr('disabled', true);
                $('#reserveTimeH').attr('disabled', true);
                $('#reserveTimeM').attr('disabled', true);
            }
        });

        $(document).on('click', '#tbl-item-list .add-item-tr', function() {
            $('#tbl-item-list .item-empty').remove();
            tbl_tr_add_item();
        });
        $(document).on('click', '#tbl-item-list .del-item-tr', function() {
            var ati_id = $(this).data('ati_id');
            if (ati_id) {
                $('#frm').prepend('<input type="hidden" name="del_ati_id[]" value="'+ati_id+'">');
            }

            $(this).closest('tr').remove();
            var tr_cnt = $('#tbl-item-list tbody tr').length;
            if (tr_cnt == 0) {
                $('#tbl-item-list').append('<tr class="item-empty"><td colspan="4">추가버튼을 클릭해서 아이템을 추가해주세요.</td></tr>');
            }
            tbl_numbering3();
        });

        $(document).on('change', '#emp_type', function() {
            display_accent_type();
        });
        $('.wz-iframe-win').magnificPopup({
            type: 'iframe',
            overflowY: 'scroll',
        });
        $(document).on('change', 'input[name="select_member_type"]', function() {
            display_member_type();
        });
        $('#search-member').magnificPopup({ // 회원검색
            type: 'iframe',
            overflowY: 'scroll',
        });

        tbl_numbering();
        tbl_numbering2();
        tbl_numbering3();
        display_accent_type();
        display_member_type();
        $('#reserveDt').datepicker({ changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', showButtonPanel: true, yearRange: "c-0:c+99", minDate: "+0d" });

    });
    function tbl_clr_tr_add() {

        var tr_cnt = $('#tbl-clr-list tbody tr').length;
        if (tr_cnt >= 5) {
            alert("5개까지만 등록이 가능합니다.");
            return;
        }

        var tbl_tr_html = '';
            tbl_tr_html += '<tr>';
            tbl_tr_html += '    <td class="center"><span class="no"></span></td>';
            tbl_tr_html += '    <td class="center">';
            tbl_tr_html += '    <input type="hidden" name="atb_gubun[]" value="button" />';
            tbl_tr_html += '    <select name="at_button_type[]" class="at-button-type">';
            tbl_tr_html += '        <option value="">선택</option>';

            <?php
            foreach ((array)$ALIMTALK_BUTN_TYPE as $k2 => $v2) {
                ?>
                tbl_tr_html += '        <option value="<?php echo $v2?>"><?php echo $k2?></option>';
                <?php
            }
            ?>

            tbl_tr_html += '    </select>';
            tbl_tr_html += '    </td>';
            tbl_tr_html += '    <td class="center"><input type="text" name="at_button_name[]" required class="frm_input" style="width:90%;" maxlength="150" /><?php echo $bf_btn_select;?></td>';
            tbl_tr_html += '    <td class="center"><span class="at-button-link"></span></td>';
            tbl_tr_html += '    <td class="center"><a href="#none" class="btn_frmline del-clr-tr">삭제</a></td>';
            tbl_tr_html += '</tr>';

        $('#tbl-clr-list').append(tbl_tr_html);
        tbl_numbering();
    }
    function tbl_clr_tr_add2() {

        var tr_cnt = $('#tbl-clr-list2 tbody tr').length;
        if (tr_cnt >= 10) {
            alert("10개까지만 등록이 가능합니다.");
            return;
        }

        var tbl_tr_html = '';
            tbl_tr_html += '<tr>';
            tbl_tr_html += '    <td class="center"><span class="no2"></span></td>';
            tbl_tr_html += '    <td class="center">';
            tbl_tr_html += '    <input type="hidden" name="atb_gubun[]" value="quick" />';
            tbl_tr_html += '    <select name="at_button_type[]" class="at-button-type">';
            tbl_tr_html += '        <option value="">선택</option>';

            <?php
            foreach ((array)$ALIMTALK_BUTN_TYPE as $k2 => $v2) {
                if (!($v2 == 'WL' || $v2 == 'AL' || $v2 == 'BK' || $v2 == 'MD' || $v2 == 'BC' || $v2 == 'BT')) continue;
                ?>
                tbl_tr_html += '        <option value="<?php echo $v2?>"><?php echo $k2?></option>';
                <?php
            }
            ?>

            tbl_tr_html += '    </select>';
            tbl_tr_html += '    </td>';
            tbl_tr_html += '    <td class="center"><input type="text" name="at_button_name[]" required class="frm_input" style="width:90%;" maxlength="150" /><?php echo $bf_btn_select;?></td>';
            tbl_tr_html += '    <td class="center"><span class="at-button-link"></span></td>';
            tbl_tr_html += '    <td class="center"><a href="#none" class="btn_frmline del-clr-tr2">삭제</a></td>';
            tbl_tr_html += '</tr>';

        $('#tbl-clr-list2').append(tbl_tr_html);
        tbl_numbering2();
    }

    function tbl_clr_tr_add3() {

        var tr_cnt = $('#tbl-clr-list3 tbody tr').length;
        if (tr_cnt >= 1) {
            alert("1개까지만 등록이 가능합니다.");
            return;
        }

        var tbl_tr_html = '';
            tbl_tr_html += '<tr>';
            tbl_tr_html += '    <td class="center">';
            tbl_tr_html += '    <input type="hidden" name="atb_gubun[]" value="link" />';
            tbl_tr_html += '    <input type="hidden" name="at_button_name[]" value="대표링크" />';
            tbl_tr_html += '    <input type="hidden" name="at_button_name_select[]" value="" />';
            tbl_tr_html += '    <input type="hidden" name="at_button_type[]" value="AL" />';
            tbl_tr_html += '    <input type="text" name="at_button_url_3[]" value="" class="frm_input frm_input_full" maxlength="150" />';
            tbl_tr_html += '</td>';
            tbl_tr_html += '<td class="center">';
            tbl_tr_html += '    <input type="text" name="at_button_url_4[]" value="" class="frm_input frm_input_full" maxlength="150" />';
            tbl_tr_html += '</td>';
            tbl_tr_html += '<td class="center">';
            tbl_tr_html += '    <input type="text" name="at_button_url_1[]" value="" class="frm_input frm_input_full" maxlength="150" />';
            tbl_tr_html += '</td>';
            tbl_tr_html += '<td class="center">';
            tbl_tr_html += '    <input type="text" name="at_button_url_2[]" value="" class="frm_input frm_input_full" maxlength="150" />';
            tbl_tr_html += '</td>';
            tbl_tr_html += '    <td class="center"><a href="#none" class="btn_frmline del-clr-tr3">삭제</a></td>';
            tbl_tr_html += '</tr>';

        $('#tbl-clr-list3').append(tbl_tr_html);
    }

    function tbl_tr_add_item() {

        var tbl_tr_html = '';
            tbl_tr_html += '<tr>';
            tbl_tr_html += '    <td class="center"><span class="no3"></span><input type="hidden" name="ati_id[]" value=""></td>';
            tbl_tr_html += '    <td class="center">';
            tbl_tr_html += '        <input type="hidden" name="ati_summary[]" value="0" />';
            tbl_tr_html += '        <input type="text" name="ati_title[]" value="" maxlength="150" class="frm_input frm_input_full" />';
            tbl_tr_html += '    </td>';
            tbl_tr_html += '    <td class="center">';
            tbl_tr_html += '        <input type="text" name="ati_description[]" value="" maxlength="150" class="frm_input frm_input_full" />';
            tbl_tr_html += '    </td>';
            tbl_tr_html += '    <td style="text-align:center;"><a href="#none" class="btn_frmline del-item-tr">삭제</a></td>';
            tbl_tr_html += '</tr>';

        $('#tbl-item-list').append(tbl_tr_html);
        tbl_numbering3();
    }
    function tbl_numbering() {
        var i = 1;
        $('.no').each(
            function(){
                $(this).text(i);
                i++;
            }
        )
    }
    function tbl_numbering2() {
        var i = 1;
        $('.no2').each(
            function(){
                $(this).text(i);
                i++;
            }
        )
    }
    function tbl_numbering3() {
        var i = 1;
        $('.no3').each(
            function(){
                $(this).text(i);
                i++;
            }
        )
    }
    function display_accent_type() {

        var emp_type = $('#emp_type > option:selected').val();

        $('.tr-emp-text').hide();
        $('.tr-flt-image').hide();
        $('.tr-itemlist').hide();

        if (emp_type == 'TEXT') {
            $('.tr-emp-text').show();
        }
        else if (emp_type == 'IMAGE' || emp_type == 'IMAGEWIDE') {
            $('.tr-flt-image').show();
        }
        else if (emp_type == 'ITEMLIST') {
            $('.tr-itemlist').show();
        }
    }
    function display_member_type() { // 개별, 단체 발송 선택

        var member_type = $(':input:radio[name=select_member_type]:checked').val();

        $('#select-member0').hide();
        $('#select-member1').hide();
        $('#phn').attr('required', false).removeClass('required');

        if (member_type == '0') {
            $('#select-member0').show();
            $('#phn').attr('required', true).addClass('required');
        }
        else {
            $('#select-member1').show();
        }

    }
    function byte_check(wr_message, sms_bytes, sms_max_bytes) {
        var conts = document.getElementById(wr_message);
        var bytes = document.getElementById(sms_bytes);
        var max_bytes = document.getElementById(sms_max_bytes);

        var i = 0;
        var cnt = 0;
        var exceed = 0;
        var ch = '';

        for (i=0; i<conts.value.length; i++)
        {
            ch = conts.value.charAt(i);
            if (escape(ch).length > 4) {
                cnt += 2;
            } else {
                cnt += 1;
            }
        }

        bytes.innerHTML = cnt;

        if(cnt > 90)
            max_bytes.innerHTML = 1500;
        else
            max_bytes.innerHTML = 90;

        if (cnt > 1500) {
            exceed = cnt - 1500;
            alert('메시지 내용은 1500바이트를 넘을수 없습니다.\n\n작성하신 메세지 내용은 '+ exceed +'byte가 초과되었습니다.\n\n초과된 부분은 자동으로 삭제됩니다.');
            var tcnt = 0;
            var xcnt = 0;
            var tmp = conts.value;
            for (i=0; i<tmp.length; i++)
            {
                ch = tmp.charAt(i);
                if (escape(ch).length > 4) {
                    tcnt += 2;
                } else {
                    tcnt += 1;
                }

                if (tcnt > 1500) {
                    tmp = tmp.substring(0,i);
                    break;
                } else {
                    xcnt = tcnt;
                }
            }
            conts.value = tmp;
            bytes.innerHTML = xcnt;
            return;
        }
    }

    byte_check('msg', 'byte1', 'byte1_max');
//-->
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');