<?php
$sub_menu = '102834';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

$fi_id = isset($_REQUEST['fi_id']) ? clean_xss_tags(preg_replace('/[^0-9]/', '', $_REQUEST['fi_id'])) : '';
$upload_type = isset($_POST['upload_type']) ? clean_xss_tags($_POST['upload_type']) : '';
$fi_img_name = isset($_POST['fi_img_name']) ? clean_xss_tags($_POST['fi_img_name']) : '';
$fi_img_url = isset($_POST['fi_img_url']) ? clean_xss_tags($_POST['fi_img_url']) : '';
$fi_wide = isset($_POST['fi_wide']) ? clean_xss_tags($_POST['fi_wide']) : 'N';

$file_save_dir  = '/alimtalk_image/';
$file_save_path = G5_DATA_PATH.$file_save_dir;
@mkdir($file_save_path, G5_DIR_PERMISSION);
@chmod($file_save_path, G5_DIR_PERMISSION);

$fi_upload_type = '비즈엠등록';

if ($w == '' || $w == 'u') {

    if ($upload_type == '1') { // API 업로드

        if (!isset($_FILES['fi_img']['name'])) {
            alert("이미지 파일을 첨부해주세요");
        }

        $tmp_file  = $_FILES['fi_img']['tmp_name'];
		$filesize  = $_FILES['fi_img']['size'];
		$filename  = $_FILES['fi_img']['name'];
		$filetype  = $_FILES['fi_img']['type'];
		$filename  = preg_replace('/(\s|\<|\>|\=|\(|\))/', '_', $filename);

        if ($_FILES['fi_img'][error] != 0) {
            alert("파일이 정상적으로 업로드되지 않았습니다.");
        }
        if (!is_uploaded_file($tmp_file)) {
            alert("파일이 정상적으로 업로드되지 않았습니다.");
        }

        $upload_max_size = 1048576 * 1; // 1M
        if ($fi_wide == 'Y') { // 와이드형 이미지는 2M 까지 업로드 가능
            $upload_max_size = 1048576 * 2; // 2M
        }

        if ($filesize > $upload_max_size) {
            alert("파일의 용량이 ".get_filesize($filesize)." 입니다. 업로드 가능한 파일 사이즈는 ".get_filesize($upload_max_size)." 까지 입니다.");
        }

        $upload_permit_ext = 'jpg|png';
        if (!preg_match("/\.(". $upload_permit_ext .")$/i", $filename) ) {
            alert("허용된 파일이 아닙니다.");
        }

        $data = array();
        $data['image'] = $tmp_file;
        $data['image_type'] = $filetype;
        $data['image_name'] = $filename;
        $data['wide'] = $fi_wide;

        $bizmsg = new bizmsg();
        $result = $bizmsg->upload_image($data);

        if ($result['res_cd'] == '00') {
            $fi_img_name = $result['img_name'];
            $fi_img_url = $result['img_url'];
        }
        else {
            alert($result['res_tx']);
        }

        $fi_upload_type = 'API업로드';
    }
}

if ($w == '') {

    $sql = " insert into {$g5['wz_alimtalk_fl_image_table']} set
                    fi_insert_type = '".$fi_upload_type."',
                    fi_wide = '".$fi_wide."',
                    fi_img_name = '".$fi_img_name."',
                    fi_img_url = '".$fi_img_url."',
                    fi_time = '".G5_TIME_YMDHIS."'
            ";
    sql_query($sql);

    goto_url('./attch_image_list.php');

}
else if ($w == 'u') {

    $sql = " update {$g5['wz_alimtalk_fl_image_table']}
                set fi_insert_type = '".$fi_upload_type."',
                    fi_wide = '".$fi_wide."',
                    fi_img_name = '".$fi_img_name."',
                    fi_img_url = '".$fi_img_url."'
                where fi_id = '".$fi_id."' ";
    sql_query($sql);

    goto_url('./attch_image_form.php?w=u&fi_id='.$fi_id);
}