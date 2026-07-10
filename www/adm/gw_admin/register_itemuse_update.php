<?php
$sub_menu = "700100";
include_once('./_common.php');
check_demo();

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

if (!count($_POST['chk']) && !count($_POST['item_chk'])) {
    alert("사용후기 일괄등록을 실행 할 분류 또는 개별상품을 하나 이상 체크하세요.");
}

if (!$_POST['itemuse_count']) { alert('등록개수를 반드시 입력하세요.'); }
if (!$_POST['fr_date'] || !$_POST['to_date']) { alert('등록기간을 반드시 입력하세요.'); }
if (!$_POST['fr_score'] || !$_POST['to_score']) { alert('등록 할 고객평점을 반드시 입력하세요.'); }

// 두 날짜 사이의 임의의 날짜를 생성
function randomDate($start_date, $end_date)
{
    $min = strtotime($start_date);
    $max = strtotime($end_date);

	// 두 날짜가 같으면 바로 반환
    if ($min == $max) {
        return date('Y-m-d H:i:s', $min);
    }

    $val = rand($min, $max);

    return date('Y-m-d H:i:s', $val);
}

// 후기제목 txt 배열로
$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_subject.txt";
$fp = @fopen($file_path,"r"); 
$fr = @fread($fp, filesize($file_path)); 
@fclose($fp);

$subject_arr = explode("\n",$fr);
shuffle($subject_arr);

// 후기내용 txt 배열로
$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_content.txt";
$fp = @fopen($file_path,"r"); 
$fr = @fread($fp, filesize($file_path)); 
@fclose($fp);

$content_arr = explode("\n",$fr);
shuffle($content_arr);

// 작성자 txt 배열로
$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_name.txt";
$fp = @fopen($file_path,"r"); 
$fr = @fread($fp, filesize($file_path)); 
@fclose($fp);

$name_arr = explode("\n",$fr);
shuffle($name_arr);

// 답변제목 txt 배열로
$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_admin_subject.txt";
$fp = @fopen($file_path,"r"); 
$fr = @fread($fp, filesize($file_path)); 
@fclose($fp);

$admin_subject_arr = explode("\n",$fr);
shuffle($admin_subject_arr);

// 답변내용 txt 배열로
$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_admin_content.txt";
$fp = @fopen($file_path,"r"); 
$fr = @fread($fp, filesize($file_path)); 
@fclose($fp);

$admin_content_arr = explode("\n",$fr);
shuffle($admin_content_arr);

$insert_arr = array(); // 배열선언

// 분류선택
if ($item_use_method == 1) {
	// SQL in 함수 사용을 위해 분류코드 앞뒤로 따옴표(')를 추가
	foreach($_POST['chk'] as $key => $val) {
		$_POST['chk'][$key] = "'".$val."'";
	}

	$chk_arr = implode(',', $_POST['chk']);

	// 체크박스를 선택한 분류의 상품을 조회
	$sql = " select it_id from {$g5['g5_shop_item_table']} where ( ca_id in ({$chk_arr}) or ca_id2 in ({$chk_arr}) or ca_id3 in ({$chk_arr}) ) order by it_id asc ";
	$result = sql_query($sql);

	// 상품의 개수만큼 반복
	while ($row = sql_fetch_array($result))
	{
		for ($i=0; $i<$_POST['itemuse_count']; $i++) { // 댓글 등록 개수만큼 반복

			$subject_num = array_rand($subject_arr); // 사용후기 내용 랜덤
			$content_num = array_rand($content_arr); // 사용후기 내용 랜덤
			$namet_num = array_rand($name_arr); // 사용후기 작성자 랜덤
			$admin_subject_num = array_rand($admin_subject_arr); // 답변제목 랜덤
			$admin_content_num = array_rand($admin_content_arr); // 답변내용 랜덤

			// 작성 일시의 중복을 막기위해 고유값이 나올때 까지 반복
			$j = 0;
			$create_is_time = false;

			do {
				$is_time = randomDate($fr_date, $to_date);

				if (!array_key_exists($is_time, $insert_arr)) {
					$create_is_time = true;
					break;
				} else {
					if($j > 20)
						break;
				}
			} while(1);

			if ($create_is_time) {
				$insert_arr[$is_time]['is_subject'] = $subject_arr[$subject_num]; // 사용후기 제목
				$insert_arr[$is_time]['is_content'] = $content_arr[$content_num]; // 사용후기 내용
				$insert_arr[$is_time]['is_name'] = $name_arr[$namet_num]; // 사용후기 작성자
				$insert_arr[$is_time]['is_reply_subject'] = $admin_subject_arr[$admin_subject_num]; // 관리자 답변 제목
				$insert_arr[$is_time]['is_reply_content'] = $admin_content_arr[$admin_content_num]; // 관리자 답변 내용
				$insert_arr[$is_time]['is_score'] = rand($fr_score, $to_score); // 고객평점 랜덤
				$insert_arr[$is_time]['is_time'] = $is_time;
				$insert_arr[$is_time]['it_id'] = $row['it_id'];
			}

		} // for
	} // while
}


// 개별상품선택
if ($item_use_method == 2) {
	// SQL in 함수 사용을 위해 분류코드 앞뒤로 따옴표(')를 추가
	foreach($_POST['item_chk'] as $key => $val) {
		$_POST['item_chk'][$key] = "'".$val."'";
	}

	$chk_arr = implode(',', $_POST['item_chk']);


	// 체크박스를 선택한 분류의 상품을 조회
	$sql = " select it_id from {$g5['g5_shop_item_table']} where it_id in ({$chk_arr}) order by it_id asc ";
	$result = sql_query($sql);

	// 상품의 개수만큼 반복
	while ($row = sql_fetch_array($result))
	{
		for ($i=0; $i<$_POST['itemuse_count']; $i++) { // 댓글 등록 개수만큼 반복

			$subject_num = array_rand($subject_arr); // 사용후기 내용 랜덤
			$content_num = array_rand($content_arr); // 사용후기 내용 랜덤
			$namet_num = array_rand($name_arr); // 사용후기 작성자 랜덤
			$admin_subject_num = array_rand($admin_subject_arr); // 답변제목 랜덤
			$admin_content_num = array_rand($admin_content_arr); // 답변내용 랜덤

			// 작성 일시의 중복을 막기위해 고유값이 나올때 까지 반복
			$j = 0;
			$create_is_time = false;

			do {
				$is_time = randomDate($fr_date, $to_date);

				if (!array_key_exists($is_time, $insert_arr)) {
					$create_is_time = true;
					break;
				} else {
					if($j > 20)
						break;
				}
			} while(1);

			if ($create_is_time) {
				$insert_arr[$is_time]['is_subject'] = $subject_arr[$subject_num]; // 사용후기 제목
				$insert_arr[$is_time]['is_content'] = $content_arr[$content_num]; // 사용후기 내용
				$insert_arr[$is_time]['is_name'] = $name_arr[$namet_num]; // 사용후기 작성자
				$insert_arr[$is_time]['is_reply_subject'] = $admin_subject_arr[$admin_subject_num]; // 관리자 답변 제목
				$insert_arr[$is_time]['is_reply_content'] = $admin_content_arr[$admin_content_num]; // 관리자 답변 내용
				$insert_arr[$is_time]['is_score'] = rand($fr_score, $to_score); // 고객평점 랜덤
				$insert_arr[$is_time]['is_time'] = $is_time;
				$insert_arr[$is_time]['it_id'] = $row['it_id'];
			}

		} // for
	} // while
}

ksort($insert_arr);

// 사용후기 등록시작
foreach($insert_arr as $key => $val) {
	$sql = "insert {$g5['g5_shop_item_use_table']}
	   set it_id = '{$insert_arr[$key]['it_id']}',
		   mb_id = 'admin',
		   is_score = '{$insert_arr[$key]['is_score']}',
		   is_name = '{$insert_arr[$key]['is_name']}',
		   is_password = '*DD26C2A1C032D814179245BCB5C5F5680CFC18EE',
		   is_subject = '{$insert_arr[$key]['is_subject']}',
		   is_content = '{$insert_arr[$key]['is_content']}',
		   is_time = '{$insert_arr[$key]['is_time']}',
		   is_ip = '{$_SERVER['REMOTE_ADDR']}',
		   is_confirm = '1',
		   is_reply_subject = '{$insert_arr[$key]['is_reply_subject']}',
		   is_reply_content = '{$insert_arr[$key]['is_reply_content']}',
		   is_reply_name = '{$config['cf_title']}'";
	sql_query($sql);
}

alert('사용후기 등록이 완료되었습니다.', './register_itemuse.php');