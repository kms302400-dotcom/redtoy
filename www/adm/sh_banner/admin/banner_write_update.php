<?php
$sub_menu = "800200";
include_once("./_common.php");
auth_check($auth[$sub_menu], 'w');
//check_admin_token();
$g5['title'] = '배너 저장';
$upload_max_filesize = ini_get('upload_max_filesize');

if (empty($_POST)) {
    alert("파일 또는 글내용의 크기가 서버에서 설정한 값을 넘어 오류가 발생하였습니다.\\npost_max_size=".ini_get('post_max_size')." , upload_max_filesize=".$upload_max_filesize."\\n게시판관리자 또는 서버관리자에게 문의 바랍니다.");
    exit;
}
$sql = " select count(*) as cnt from {$g5['sh_banner_table']} ";
$row = sql_fetch($sql);
if(!isset($row['cnt'])) {
    $sql = "
        CREATE TABLE `{$g5['sh_banner_table']}` (
          `bn_id` int(11) NOT NULL AUTO_INCREMENT,
          `bn_gr_id` int(11) NOT NULL,
          `bn_status` tinyint(4) NOT NULL DEFAULT '1',
          `bn_title` varchar(255) NOT NULL,
          `bn_memo` varchar(255) NOT NULL,
          `bn_href` varchar(255) NOT NULL,
          `bn_target` varchar(255) NOT NULL,
          `bn_order` int(11) unsigned NOT NULL,
          `bn_date_start` date NOT NULL,
          `bn_date_end` date NOT NULL,
          `bn_filename` varchar(255) NOT NULL,
          `bn_hit` int(10) unsigned NOT NULL,
          `bn_datetime` datetime NOT NULL,
          PRIMARY KEY (`bn_id`),
          KEY `bn_gr_order` (`bn_order`),
          KEY `bn_gr_id` (`bn_gr_id`),
          KEY `bn_date_start` (`bn_date_start`),
          KEY `bn_date_end` (`bn_date_end`),
          KEY `bn_date_start_bn_date_end` (`bn_date_start`,`bn_date_end`)
        );
    ";
    sql_query($sql);
}

$bn_title = substr(trim($_POST['bn_title']),0,255);
$bn_title = addslashes(preg_replace("#[\\\]+$#", "", $bn_title));
$bn_memo = substr(trim($_POST['bn_memo']),0,255);
$bn_memo = addslashes(preg_replace("#[\\\]+$#", "", $bn_memo));
$bn_order = $bn_order + 0;

$sql_set = " set bn_gr_id = '{$bn_gr_id}',
                    bn_status = '{$bn_status}',
					bn_title = '{$bn_title}',
                    bn_memo = '{$bn_memo}',
                    bn_href = '{$bn_href}',
                    bn_target = '{$bn_target}',
                    bn_order = '{$bn_order}',
                    bn_date_start = '{$bn_date_start}',
                    bn_date_end = '{$bn_date_end}',
                    bn_datetime = '".G5_TIME_YMDHIS."'
                    ";
if($bn_id && $w == 'u') {
    $sql = " update {$g5['sh_banner_table']} {$sql_set} where bn_id = '{$bn_id}' ";
    sql_query($sql);
    $chk_id = $bn_id;
} else {
    $sql = " insert into {$g5['sh_banner_table']} {$sql_set}  ";
    sql_query($sql);
    $chk_id = sql_insert_id();
}

@mkdir(G5_DATA_PATH.'/file/_sh_banner_', G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH.'/file/_sh_banner_', G5_DIR_PERMISSION);

// 파일개수 체크
$file_count   = 0;
$upload_count = count($_FILES['bn_file']['name']);
$chars_array = array_merge(range(0,9), range('a','z'), range('A','Z'));

// 가변 파일 업로드
$file_upload_msg = '';
$upload = array();
for ($i=0; $i<count($_FILES['bn_file']['name']); $i++) {
    $upload[$i]['file']     = '';
    $upload[$i]['source']   = '';
    $upload[$i]['filesize'] = 0;
    $upload[$i]['image']    = array();
    $upload[$i]['image'][0] = '';
    $upload[$i]['image'][1] = '';
    $upload[$i]['image'][2] = '';

    $tmp_file  = $_FILES['bn_file']['tmp_name'][$i];
    $filesize  = $_FILES['bn_file']['size'][$i];
    $filename  = $_FILES['bn_file']['name'][$i];
    $filename  = get_safe_filename($filename);

    // 서버에 설정된 값보다 큰파일을 업로드 한다면
    if ($filename) {
        if ($_FILES['bn_file']['error'][$i] == 1) {
            $file_upload_msg .= '\"'.$filename.'\" 파일의 용량이 서버에 설정('.$upload_max_filesize.')된 값보다 크므로 업로드 할 수 없습니다.\\n';
            continue;
        }
        else if ($_FILES['bn_file']['error'][$i] != 0) {
            $file_upload_msg .= '\"'.$filename.'\" 파일이 정상적으로 업로드 되지 않았습니다.\\n';
            continue;
        }
    }

    if (is_uploaded_file($tmp_file)) {

        //=================================================================\
        // 090714
        // 이미지나 플래시 파일에 악성코드를 심어 업로드 하는 경우를 방지
        // 에러메세지는 출력하지 않는다.
        //-----------------------------------------------------------------
        $timg = @getimagesize($tmp_file);
        // image type
        if ( preg_match("/\.({$config['cf_image_extension']})$/i", $filename) ||
             preg_match("/\.({$config['cf_flash_extension']})$/i", $filename) ) {
            if ($timg['2'] < 1 || $timg['2'] > 16)
                continue;
        }
        //=================================================================

        $upload[$i]['image'] = $timg;

        // 프로그램 원래 파일명
        $upload[$i]['source'] = $filename;
        $upload[$i]['filesize'] = $filesize;

        // 아래의 문자열이 들어간 파일은 -x 를 붙여서 웹경로를 알더라도 실행을 하지 못하도록 함
        $filename = preg_replace("/\.(php|pht|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", "$0-x", $filename);

        shuffle($chars_array);
        $shuffle = implode('', $chars_array);

        // 첨부파일 첨부시 첨부파일명에 공백이 포함되어 있으면 일부 PC에서 보이지 않거나 다운로드 되지 않는 현상이 있습니다. (길상여의 님 090925)
        $upload[$i]['file'] = abs(ip2long($_SERVER['REMOTE_ADDR'])).'_'.substr($shuffle,0,8).'_'.replace_filename($filename);
        $dest_file = G5_DATA_PATH.'/file/_sh_banner_/'.$upload[$i]['file'];
        // 업로드가 안된다면 에러메세지 출력하고 죽어버립니다.
        $error_code = move_uploaded_file($tmp_file, $dest_file) or die($_FILES['bf_file']['error'][$i]);
        var_dump($error_code);
        // 올라간 파일의 퍼미션을 변경합니다.
        chmod($dest_file, G5_FILE_PERMISSION);
    }
}
for ($i=0; $i<count($upload); $i++) {
    if(!$upload[$i]['file']) continue;
    $row = sql_fetch(" select bn_filename from {$g5['sh_banner_table']} where bn_id = '{$chk_id}' ");
    if ($row['bn_filename']) {
        @unlink(G5_DATA_PATH.'/file/_sh_banner_/'.$row['bn_filename']);
    }
    $sql = "update {$g5['sh_banner_table']} set bn_filename = '{$upload[$i]['file']}' where bn_id = '{$chk_id}' ";
    sql_query($sql);
}
if($selected_bn_gr_id) $qstr = 'bn_gr_id='.$selected_bn_gr_id.'&';
goto_url('./banner_list.php?'.$qstr);
?>
