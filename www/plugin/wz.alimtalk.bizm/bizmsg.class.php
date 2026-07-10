<?php
class Bizmsg {

    private $alimt          = array();
    private $act_uri        = 'https://alimtalk-api.bizmsg.kr';

	public $msg             = ''; // [필수] 사용자에게 전달될 메시지 (공백포함 1,000자)
    public $at_type         = ''; // 발송구분
    public $message_type    = 'AT'; // [필수] 메시지 타입 (AT:알림톡, FT:친구톡)
    public $phn             = ''; // [필수] 수신자 전화번호 (국가코드(대한민국:82)를 포함한 전화번호)
    public $reserveDt       = ''; // [선택] text(14) 메시지 예약발송을 위한 시간값(yyyyMMddHHmmss)
    public $title           = ''; // text(23) 템플릿 내용 중 강조 표기할 핵심 정보

    public $tmplId          = ''; // [필수] 메시지 유형을 확인할 템플릿 코드 (사전에 승인된 템플릿의 코드)
    public $smsKind         = 'L'; // [선택] 카카오 비즈메시지 발송이 실패했을 때 SMS 전환발송을 사용하는 경우 SMS/LMS 구분 (SMS: S, LMS: L)
    public $smsOnly         = 'N'; // [선택] text(1) 카카오 비즈메시지 발송과 관계 없이 문자메시지 전용 발송 요청 여부(Y: 사용, N: 미사용, 기본값: N)
    public $msgSms          = ''; // [선택] SMS 전환발송을 위한 메시지
    public $smsLmsTit       = ''; // [선택] LMS 발송을 위한 제목
    public $btn_name        = ''; // [선택] 메시지에 첨부할 버튼 이름 (템플릿 등록시 정의된 버튼 이름)
    public $btn_url         = ''; // [선택] 메시지에 첨부할 버튼의 URL(템플릿 등록시 정의된 버튼 URL)
    public $img_url         = ''; // [선택] 친구톡 발송시 메시지에 첨부할 이미지 URL
    public $img_link        = ''; // [선택] text(2083) 첨부된 이미지 클릭시 이동할 URL
    public $ad_flag         = 'N'; // [선택] 친구톡 메시지 발송시 광고성 메시지 필수 표기사항 노출여부

    public $phn_gcode       = '82'; // 국가번호 코드
    public $aheader         = ''; // [선택] 아이템리스트형 메시지 상단에 표기할 제목

    public $buttons         = array(); // 버튼타입
    public $buttons2        = array(); // 바로연결
    public $buttons3        = array(); // 대표링크
    public $items           = array(); // 아이템리스트

    public function __construct() {

        global $g5;

        $query = "select * from {$g5['wz_alimtalk_config_table']}";
        $this->alimt = sql_fetch($query);
    }

    public function create() { // 전문생성

        global $config;

        $this->phn = preg_replace("/[^0-9]*/s", "", $this->phn);
        $this->phn = $this->phn_gcode == '82' ? substr($this->phn, 1) : $this->phn;

        $data = array();
        $data['message_type']   = $this->message_type ? $this->message_type : 'AT';
        $data['phn']            = '+'.$this->phn_gcode . $this->phn; // 국가코드붙여서 처리.
        $data['profile']        = $this->alimt['cf_profile_key'];
        $data['tmplId']         = $this->tmplId;
        $data['msg']            = stripslashes($this->msg);
        $data['smsKind']        = $this->smsKind;
        $data['msgSms']         = $this->msgSms ? $this->msgSms : $this->msg;
        $data['smsSender']      = $this->alimt['cf_smssender'];
        $data['smsLmsTit']      = $this->smsLmsTit ? $this->smsLmsTit : $config['cf_title'];
        $data['smsOnly']        = $this->smsOnly ? $this->smsOnly : 'N';
        $data['title']          = $this->title ? $this->title : '';
        $data['img_url']        = $this->img_url ? $this->img_url : '';
        $data['img_link']       = $this->img_link ? $this->img_link : '';
        $data['ad_flag']        = $this->ad_flag ? $this->ad_flag : 'N';

        $data['header'] = $this->aheader ? $this->aheader : '';
        $data['items'] = $this->items ? $this->items : array();

        if ($this->reserveDt) { // 발송예약시간
            $this->reserveDt = preg_replace('/[^0-9]/', '', $this->reserveDt);
            $data['reserveDt'] = str_pad($this->reserveDt, 14, '0');
        }

        if (count($this->buttons) && $this->buttons) {
            $z = 1;
            foreach ((array)$this->buttons as $key => $val) {
                $data['button'.$z] = $val;
                $z++;
            }
        }
        if (count($this->buttons2) && $this->buttons2) { // 바로연결
            $z = 1;
            foreach ((array)$this->buttons2 as $key => $val) {
                $data['quickReply'.$z] = $val;
                $z++;
            }
        }
        if (count($this->buttons3) && $this->buttons3) { // 대표링크
            $z = 1;
            foreach ((array)$this->buttons3 as $key => $val) {
                $data['link'] = $val;
                $z++;
            }
        }

        return $data;
    }

    public function toSend($data, $json=false) { // 전문발송

        global $g5, $ALIMTALK_RESULT_CODE;

        $arr_phns = $send_data = array();
        $post = '';
        if ($json == false) {
            foreach ((array)$data as $k => $v) {

                if (in_array($v['tmplId'].$v['phn'], $arr_phns)) // 중복발송되지 않도록 수정
                    continue;

                if ($v['phn']) $arr_phns[] = $v['tmplId'].$v['phn'];
                $send_data[] = $v;
            }

            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                $post = json_encode($send_data, JSON_UNESCAPED_UNICODE);
            }
            else {
                $post = json_encode($send_data);
            }
        }
        else {
            $post = $data;
        }

        if (is_null($post) || empty($post)) {
            return false;
        }

        $headers = array('Content-type: Application/json', 'userId: '. $this->alimt['cf_userid']);
        $result = $this->get_curl($this->act_uri.'/v2/sender/send', $headers, $post);
        $json = json_decode($result);

        $arr_return = array();

        foreach ((array)$json as $k => $v) {

            $res_cd = @(string)$v->code;
            $message = @(string)$v->message;
            $ao_msgid = @(string)$v->data->msgid;
            $ao_phn = @(string)$v->data->phn;

            $ao_result = '';
            $result_code = substr($message, 0, 4);
            if ($result_code) {
                $ao_result = $ALIMTALK_RESULT_CODE[$result_code] ? $ALIMTALK_RESULT_CODE[$result_code] : $result_code;
            }

            $ao_is_success = 0; // 발송 성공(예약 성공)/실패 여부
            if ($res_cd == 'success' && ($result_code == 'K000' || $result_code == 'M000')) {
                $ao_is_success = 1;
            }

            $ao_reserveDt = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $send_data[$k]['reserveDt']);

            // 로그기록
            $query = "insert into {$g5['wz_alimtalk_log_table']} set ao_tmplId = '".$send_data[$k]['tmplId']."', ao_phn = '".$ao_phn."', ao_type = '".$send_data[$k]['message_type']."', ao_msgid = '".$ao_msgid."', ao_msg = '".$send_data[$k]['msg']."', ao_result = '".$ao_result."', ao_is_success = '".$ao_is_success."', ao_reserveDt = '".$ao_reserveDt."', ao_time = '".G5_TIME_YMDHIS."'";
            sql_query($query);

            $arr = array();
            $arr['phn'] = $ao_phn;
            $arr['res_cd'] = $res_cd;
            $arr['res_cd2'] = $result_code;
            $arr['res_msg'] = $message;
            $arr['res_msg2'] = $ao_result;
            $arr_return[] = $arr;
        }

        return $arr_return;
    }

    public function getMsg($atc_code) { // 관리자화면에서 등록한 메시지 정보.

        global $g5;

        if (!$atc_code) {
            return false;
        }
        $query = " select at.* from {$g5['wz_alimtalk_template_table']} as at inner join {$g5['wz_alimtalk_template_cate_table']} as atc on at.at_tmplId = atc.at_tmplId where atc.atc_code = '".$atc_code."' and at_use = 1 ";
        $at = sql_fetch($query);
        if (!$at['at_tmplId']) {
            return false;
        }
        // 버튼정보
        $buttons = $buttons2 = $buttons3 = array();
        $query = "select * from {$g5['wz_alimtalk_template_button_table']} where at_tmplId = '".$at['at_tmplId']."' order by atb_id asc ";
        $res = sql_query($query);
        while($row = sql_fetch_array($res)) {

            $button = array();
            $button['name'] = $row['atb_name'];
            $button['type'] = $row['atb_type'];

            switch ($row['atb_type']) {
                case 'WL':
                    $button['url_mobile'] = $row['atb_url_mobile'];
                    if ($row['atb_url_pc']) {
                        $button['url_pc'] = $row['atb_url_pc'];
                    }
                    break;
                case 'AL':
                    $button['scheme_android'] = $row['atb_scheme_android'];
                    $button['scheme_ios'] = $row['atb_scheme_ios'];
                    $button['url_mobile'] = $row['atb_url_mobile'];
                    if ($row['atb_url_pc']) {
                        $button['url_pc'] = $row['atb_url_pc'];
                    }
                    break;
                case 'P1':
                case 'P2':
                case 'P3':
                    if ($this->message_type == 'AT') { // 알림톡
                        $button['plugin_id'] = $row['atb_plugin_id'];
                    }
                    else { // 친구톡
                        $button['plugin_id'] = ''; // 친구톡인 경우 사용불가
                    }
                    break;
                case 'BF':
                    $button['biz_form_id'] = $row['atb_plugin_id'];
                    if ($row['atb_gubun'] == 'button' && $this->message_type != 'AT') { // 바로연결이 아니고 친구톡일경우
                        $button['biz_form_key'] = $row['atb_plugin_id'];
                    }
                    break;
            }

            if ($row['atb_gubun'] == 'button') {
                $buttons[] = $button; // 버튼타입
            }
            else if ($row['atb_gubun'] == 'link') {
                $buttons3[] = $button; // 대표링크
            }
            else {
                $buttons2[] = $button; // 바로연결
            }
        }

        $this->aheader = $this->title = '';
        $this->message_type = 'AT';
        $this->items = array();
        if ($at['at_emp_type'] == 'IMAGE') {
            $this->message_type = 'AI'; // 이미지 알림톡
        }
        else if ($at['at_emp_type'] == 'TEXT') {
            $this->title = get_text($at['at_accent_title']); // 강조표기형 (변수사용가능)
        }
        else if ($at['at_emp_type'] == 'ITEMLIST') { // 아이템리스트
            $this->aheader = $at['at_header'];

            // 아이템정보
            $arr_items = array();
            $query = "select * from {$g5['wz_alimtalk_template_items_table']} where at_id = '".$at['at_id']."' order by ati_id asc ";
            $res = sql_query($query);
            while($row = sql_fetch_array($res)) {
                $row2 = array();
                $row2['title'] = $row['ati_title'];
                $row2['description'] = $row['ati_description'];

                if (!$row2['title'] || !$row2['description']) { // 공백일경우 엘리먼트를 적용하지 않음
                    continue;
                }

                if ($row['ati_summary'] == '0') { // 아이템리스트
                    $arr_items['list'][] = $row2;
                }
                else { // 요약정보
                    $arr_items['summary'] = $row2;
                }
            }

            $this->items['item'] = $arr_items;

            if (count($arr_items) && $at['at_itemHighlight_title'] && $at['at_itemHighlight_description']) {
                $this->items['itemHighlight'] = array('title'=>$at['at_itemHighlight_title'], 'description'=>$at['at_itemHighlight_description']);
            }
        }

        $return = array();
        $return['tmplId'] = $at['at_tmplId'];
        $return['msg'] = $at['at_msg'];
        $return['buttons'] = $buttons;
        $return['buttons2'] = $buttons2;
        $return['buttons3'] = $buttons3;

        return $return;
    }

    public function balance() {

        $data = array();
        $post = $data;

        $headers = array('userId: '. $this->alimt['cf_userid'], 'userkey: '. $this->alimt['cf_userkey_key']);
        $result = $this->get_curl($this->act_uri.'/v1/user/balance', $headers, $post);
        $json = json_decode($result);

        if ((string)$json->code === 'success')
            return (string)$json->data->balance;
        else
            return false;
    }

    public function report($msgid='') { // 메시지 전송결과 확인

        if (!$msgid) {
            return false;
        }

        $headers = array('userId: '. $this->alimt['cf_userid']);

        $data = array();
        $data['profile'] = $this->alimt['cf_profile_key'];
        $data['msgid'] = $msgid;
        $post = http_build_query($data);

        $result = $this->get_curl($this->act_uri.'/v2/sender/report', $headers, $post);
        $json = json_decode($result);
        if ((string)$json->code === 'success')
            return array('msgid'=>(string)$json->data->msgid, 'message'=>substr((string)$json->message, 0, 4));
        else
            return false;


    }

    public function cancel_reserved($msgid='') { // 메시지 예약발송 취소

        if (!$msgid) {
            return false;
        }

        $headers = array('userId: '. $this->alimt['cf_userid']);

        $data = array();
        $data['profile'] = $this->alimt['cf_profile_key'];
        $data['msgid'] = $msgid;
        $post = http_build_query($data);

        $result = $this->get_curl($this->act_uri.'/v2/sender/cancel_reserved', $headers, $post);
        $json = json_decode($result);
        if ((string)$json->code === 'success')
            return array('msgid'=>(string)$json->data->msgid, 'message'=>substr((string)$json->message, 0, 4));
        else
            return false;
    }

    public function upload_image($val=array()) { // 친구톡 이미지 업로드

        if (empty($val) || !is_array($val) || count($val) < 1) {
            return false;
        }

        $headers = array('Content-Type: multipart/form-data', 'userId: '. $this->alimt['cf_userid']);

        $data = array();
        $data['image'] = '@'.$val['image'].';filename='.$val['image_name'].';type='.$val['image_type'];
        $data['wide'] = $val['wide'];
        $post = $data;

        $result = $this->get_curl($this->act_uri.'/v2/ft/'.$this->alimt['cf_profile_key'].'/upload_image', $headers, $post);
        $json = json_decode($result);
        if ((string)$json->code === 'success')
            return array('res_cd'=>'00', 'img_name'=>(string)$json->data->img_name, 'img_url'=>(string)$json->data->img_url);
        else
            return array('res_cd'=>'99', 'res_tx'=>(string)$json->message);
    }

    public function delete_image($image_url='') { // 친구톡 이미지 삭제

        if (empty($image_url) || !$image_url) {
            return false;
        }

        $headers = array('userId: '. $this->alimt['cf_userid']);

        $data = array();
        $data['image_name'] = $image_url;
        $post = $data;

        $result = $this->get_curl($this->act_uri.'/v2/ft/'.$this->alimt['cf_profile_key'].'/delete_image', $headers, $post);
        $json = json_decode($result);
        if ((string)$json->code === 'success')
            return array('res_cd'=>'00', 'img_name'=>(string)$json->data->img_name);
        else
            return array('res_cd'=>'99', 'res_tx'=>(string)$json->message);

    }

    private function get_curl($url, $headers='', $posts='') {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        if ($headers) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        if ($posts) {
        curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $posts);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, true); // SSL 버젼 (https 접속시에 필요)
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    public function replace($src=array(), $dst=array()) { // 문자열 치환

        $this->msg = preg_replace($src, $dst, $this->msg); // 템플릿 내용

        if (is_array($this->items) && count($this->items)) { // 아이템리스트형 : 아이템목록, 요약, 아이템하이라이트
            $arr_items = array();
            foreach ((array)$this->items['item']['list'] as $k => $v) {
                $v['description'] = preg_replace($src, $dst, $v['description']);
                $row2 = array();
                $row2['title'] = $v['title'];
                $row2['description'] = $v['description'];
                $arr_items['list'][] = $row2;
            }

            if (isset($this->items['item']['summary'])) {
                $row2 = array();
                $row2['title'] = $this->items['item']['summary']['title'];
                $row2['description'] = preg_replace($src, $dst, trim($this->items['item']['summary']['description']));
                $arr_items['summary'] = $row2;
            }

            $this->items['item'] = $arr_items;

            if ($this->items['itemHighlight']['title'] && $this->items['itemHighlight']['description']) {
                $row2 = array();
                $row2['title'] = preg_replace($src, $dst, $this->items['itemHighlight']['title']);
                $row2['description'] = preg_replace($src, $dst, $this->items['itemHighlight']['description']);
                $this->items['itemHighlight'] = $row2;
            }
        }

        if (isset($this->aheader) && $this->aheader) { // 아이템리스트형 : 템플릿 헤더
            $this->aheader = preg_replace($src, $dst, $this->aheader);
        }

        if (isset($this->title) && $this->title) { // 강조표기형 : 강조표기
            $this->title = preg_replace($src, $dst, $this->title);
        }

        if (isset($this->buttons) && $this->buttons) {
            $replace_buttons = array();
            foreach ((array)$this->buttons as $key => $val) {
                if ($key == 'url_mobile' || $key == 'url_pc' || $key == 'scheme_android' || $key == 'scheme_ios') {
                    $val = preg_replace($src, $dst, $val);
                }
                $replace_buttons[$key] = $val;
            }
            $this->buttons = $replace_buttons;
        }
        if (isset($this->buttons2) && $this->buttons2) { // 바로연결
            foreach ((array)$this->buttons2 as $key => $val) {

            }
        }
        if (isset($this->buttons3) && $this->buttons3) { // 대표링크
            foreach ((array)$this->buttons3 as $key => $val) {

            }
        }
    }
}