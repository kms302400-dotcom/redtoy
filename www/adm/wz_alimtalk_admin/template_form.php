<?php
$sub_menu = '102840';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '알림톡 템플릿 등록/수정';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$at_id = isset($_REQUEST['at_id']) ? clean_xss_tags($_REQUEST['at_id']) : '';

$arr_buttons = $arr_buttons2 = $arr_buttons3 = $arr_items = array();

if ($w == 'u') {
    $html_title = '알림톡 템플릿 수정';

    $sql = " select * from {$g5['wz_alimtalk_template_table']} where at_id = '".$at_id."' ";
    $at = sql_fetch($sql);
    if (!$at['at_id']) alert('등록된 자료가 없습니다.');

    // 버튼정보 (버튼타입)
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
    $html_title = '알림톡 템플릿 입력';
}

$qstr .= "&sch_cate=".$sch_cate."&sch_tmplId=".$sch_tmplId."&sch_title=".$sch_title."&sch_msg=".$sch_msg;

$bf_btn_select_options = array('톡에서 예약하기', '톡에서 설문하기', '톡에서 응모하기');
$bf_btn_select = '<select name="at_button_name_select[]" class="bf-btn-select"><option>'.implode('</option><option>', $bf_btn_select_options).'</option></select>';

// 템플릿 아이템리스트 생성 : 2022-08-01
if(!sql_query(" DESCRIBE {$g5['wz_alimtalk_template_items_table']} ", false)) {
    sql_query(" CREATE TABLE {$g5['wz_alimtalk_template_items_table']} (
                    `ati_id` int(11) NOT NULL AUTO_INCREMENT,
                    `at_id` int(11) NOT NULL COMMENT '템플릿키',
                    `ati_summary` tinyint(4) NOT NULL default '0' COMMENT '요약정보여부',
                    `ati_title` varchar(60) NOT NULL COMMENT '타이틀',
                    `ati_description` varchar(100) NOT NULL COMMENT '부가정보',
                    PRIMARY KEY (`ati_id`),
                	INDEX `at_id` (`at_id`)
                )
                COMMENT='알림톡템플릿아이템리스트'
                ;", true);

    sql_query(" ALTER TABLE `{$g5['wz_alimtalk_template_table']}`
                    ADD `at_header` varchar(100) NOT NULL COMMENT '템플릿 헤더',
                    ADD `at_itemHighlight_title` varchar(100) NOT NULL COMMENT '아이템하이라이트타이틀',
                    ADD `at_itemHighlight_description` varchar(100) NOT NULL COMMENT '아이템하이라이트부가정보'
                    ; ", true);
}
?>

<style>
.at-button-link ul {}
.at-button-link ul li {text-align:left;margin-left:3px;}
.at-button-link ul.list-number li {margin-left:19px;list-style: disc;}
.at-button-link dl.list-box {margin: 0;}
.at-button-link dl.list-box > dt {float:left;width:20%;text-align:right;height:36px;line-height:36px;padding-right:7px}
.at-button-link dl.list-box > dd {float:left;width:80%;text-align:left;height:36px;line-height:36px;padding-left:0}
.at-button-link dl.list-box > dd:after {display:block;visibility:hidden;clear:both;content:""}
.bf-btn-select {display:none}
</style>

<div class="local_desc02 local_desc">
    <p>
        비즈엠 사이트에서 템플릿을 먼저 등록 및 검수까지 완료하시고 등록 하시기 바랍니다.
    </p>
</div>

<form name="frm" id="frm" action="./template_form_update.php" method="post" onsubmit="return getAction(this);">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="at_id" value="<?php echo $at_id; ?>">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="page" value="<?php echo $page;?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row"><label for="at_tmplId">템플릿코드 *</label></th>
        <td>
            <?php echo help("템플릿 코드는 반드시 비즈엠에 등록이 되어야 합니다."); ?>
            <input type="text" name="at_tmplId" value="<?php echo stripslashes($at['at_tmplId']); ?>" id="at_tmplId" required class="required frm_input" size="30">
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="at_title">템플릿명 *</label></th>
        <td>
           <input type="text" name="at_title" value="<?php echo stripslashes($at['at_title']); ?>" id="at_title" required class="required frm_input" size="50">
        </td>
    </tr>
    <tr>
        <th scope="row">템플릿 강조 유형 *</th>
        <td>
            <select name="at_emp_type" id="emp_type">
                <option value="" <?php echo $at['at_emp_type'] == '' ? 'selected="selected"' : '';?>>선택안함</option>
                <option value="TEXT" <?php echo $at['at_emp_type'] == 'TEXT' ? 'selected="selected"' : '';?>>강조표기형</option>
                <option value="IMAGE" <?php echo $at['at_emp_type'] == 'IMAGE' ? 'selected="selected"' : '';?>>이미지형</option>
                <option value="ITEMLIST" <?php echo $at['at_emp_type'] == 'ITEMLIST' ? 'selected="selected"' : '';?>>아이템리스트형</option>
            </select>
        </td>
    </tr>
    <tr class="tr-image">
        <th scope="row"><label for="at_accent_title">강조표기</label></th>
        <td>
            <?php echo help("템플릿 내용 중 강조 표기할 핵심 정보 (CBT, 템플릿 검수 가이드 참고)"); ?>
            <input type="text" name="at_accent_title" value="<?php echo stripslashes($at['at_accent_title']); ?>" id="at_accent_title" class="frm_input" size="30">
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
        <th scope="row"><label for="at_msg">아이템 요약정보</label></th>
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

    <tr>
        <th scope="row"><label for="at_msg">템플릿 내용 *</label></th>
        <td>
            <?php echo help("템플릿 내용은 반드시 비즈엠에 등록이 되어 있어야 하며 검수완료된 내용만 발송이 가능합니다."); ?>
            <textarea name="at_msg" id="at_msg" required class="required"><?php echo $at['at_msg']; ?></textarea>
        </td>
    </tr>
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
                                        if (!($v2 == 'WL' || $v2 == 'AL' || $v2 == 'BK' || $v2 == 'BC' || $v2 == 'BT' || $v2 == 'BF')) continue;
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

    <tr>
        <th scope="row"><label for="at_use">템플릿 사용여부</label></th>
        <td>
            <input type="radio" name="at_use" value="1" id="at_use1" <?php echo ($at['at_use'] == '1' || $at['at_use'] == '' ? 'checked' : '');?>>
            <label for="at_use1"> 사용</label>&nbsp;
            <input type="radio" name="at_use" value="0" id="at_use2" <?php echo ($at['at_use'] == '0' ? 'checked' : '');?>>
            <label for="at_use2"> 사용안함</label>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" value="확인" class="btn_submi btn btn_01" accesskey="s" >
    <a href="./template_list.php?<?php echo $qstr; ?>" class="btn_02 btn">목록</a>
</div>

</form>

<script type="text/javascript">
<!--
    function getAction(f) {
        return true;
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
                $el.find("input[name='at_button_name[]']").show().attr('required', true).attr('readonly', false).val('');
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
        tbl_numbering();
        tbl_numbering2();
        tbl_numbering3();
        display_accent_type();

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
                if (!($v2 == 'WL' || $v2 == 'AL' || $v2 == 'BK' || $v2 == 'BC' || $v2 == 'BT' || $v2 == 'BF')) continue;
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

        $('.tr-image').hide();
        $('.tr-itemlist').hide();

        if (emp_type == 'TEXT') {
            $('.tr-image').show();
        }
        else if (emp_type == 'ITEMLIST') {
            $('.tr-itemlist').show();
        }
    }
//-->
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>