<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ($w == 'c') {

    include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/config.php');
    include_once(G5_PLUGIN_PATH.'/wz.alimtalk.bizm/bizmsg.class.php');

    $data = array();
    $bizmsg = new bizmsg();

    if ($is_admin) { // 관리자가 등록한것일경우 원본글 등록자 전화번호로 발송.

        $tpl = $bizmsg->getMsg('게시글알림(작성자)');
        if ($tpl) {

            $tmplId = $tpl['tmplId'];
            $msg = $tpl['msg'];
            $buttons = $tpl['buttons'];
            $buttons2 = $tpl['buttons2'];
            $buttons3 = $tpl['buttons3'];
            unset($tpl);

            $src = $dst = array();
            $src[] = "/#{이벤트}/";
            $dst[] = '댓글이';
            $src[] = "/#{작성자명}/";
            $dst[] = $wr['wr_name'];
            $src[] = "/#{연결링크}/";
            $dst[] = get_pretty_url($bo_table, $wr_id);

            $bizmsg->phn = trim($wr['wr_1']);
            $bizmsg->tmplId = $tmplId;
            $bizmsg->msg = $msg;
            $bizmsg->buttons = $buttons;
            $bizmsg->buttons2 = $buttons2;
            $bizmsg->buttons3 = $buttons3;
            $bizmsg->replace($src, $dst);
            $data[] = $bizmsg->create();
        }
    }
    else { // 관리자가 등록한것이 아닐경우 관리자에게 알리기 위해 관리자에게 발송.

        $tpl = $bizmsg->getMsg('게시글알림(관리자)');
        if ($tpl) {

            $tmplId = $tpl['tmplId'];
            $msg = $tpl['msg'];
            $buttons = $tpl['buttons'];
            $buttons2 = $tpl['buttons2'];
            $buttons3 = $tpl['buttons3'];
            unset($tpl);

            $src = $dst = array();
            $src[] = "/#{이벤트}/";
            $dst[] = '댓글이';
            $src[] = "/#{제목}/";
            $dst[] = conv_subject($wr_subject, 22, '...');
            $src[] = "/#{작성자명}/";
            $dst[] = $wr['wr_name'];
            $src[] = "/#{연결링크}/";
            $dst[] = get_pretty_url($bo_table, $wr_id);

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
    }

    if ($data && is_array($data) && count($data)) $bizmsg->toSend($data); // 알림톡발송
}