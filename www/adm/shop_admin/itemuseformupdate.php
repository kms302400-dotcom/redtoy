<?php
$sub_menu = '400650';
include_once('./_common.php');

check_demo();

if ($w == 'd')
    auth_check($auth[$sub_menu], "d");
else
    auth_check($auth[$sub_menu], "w");

check_admin_token();
$is_id = isset($_POST['is_id']) && is_scalar($_POST['is_id']) ? (int)$_POST['is_id'] : 0;
$proxy = sql_fetch("select * from {$g5['g5_shop_item_use_table']} where is_id='$is_id'");
if (!$proxy) alert('리뷰가 존재하지 않습니다.');
$proxy_sql = '';
if (!empty($proxy['is_provided'])) {
    if (!redtoy_review_admin()) alert('대리 리뷰 수정 권한이 없습니다.');
    redtoy_review_check_token();
    try { $values = redtoy_review_values(true, $_POST, G5_TIME_YMDHIS); }
    catch (InvalidArgumentException $e) { alert($e->getMessage()); }
    $proxy_sql = ", is_name='".sql_real_escape_string($values['name'])."', is_time='".sql_real_escape_string($values['time'])."'";
}
$is_content = sql_real_escape_string(html_purifier(isset($_POST['is_content']) && is_string($_POST['is_content']) ? stripslashes($_POST['is_content']) : ''));
$is_subject = sql_real_escape_string(isset($_POST['is_subject']) && is_string($_POST['is_subject']) ? stripslashes($_POST['is_subject']) : '');
if (!$is_subject || !$is_content) alert('제목과 내용을 입력해 주세요.');
$is_confirm = isset($_POST['is_confirm']) && $_POST['is_confirm'] === '1' ? 1 : 0;
$is_reply_subject = sql_real_escape_string(isset($_POST['is_reply_subject']) && is_string($_POST['is_reply_subject']) ? stripslashes($_POST['is_reply_subject']) : '');
$is_reply_content = sql_real_escape_string(html_purifier(isset($_POST['is_reply_content']) && is_string($_POST['is_reply_content']) ? stripslashes($_POST['is_reply_content']) : ''));
$review_reply_name = sql_real_escape_string($member['mb_nick']);

if ($w == "u")
{
		
		// 노출확인 아니오 에서 예로 변경시에 포인트 지급하기 시작
		$sql = " select * from {$g5['g5_shop_item_use_table']} where is_id = '{$is_id}'  ";
		$use1 = sql_fetch($sql);
			
		if(empty($proxy['is_provided']) && $use1['is_confirm'] == 0 && isset($_POST['is_confirm']) && $_POST['is_confirm'] == '1'){			//노출확인 아니오 에서 예로 변경시
			$itit = sql_fetch(" select it_name from g5_shop_item where it_id = '{$use1['it_id']}' ");	// 상품조회
			$point = $config['cf_6'];				// 일반리뷰 포인트
			$point_img = $config['cf_5'];		// 포토리뷰 포인트

			if(@preg_match("/img src/", $is_content)){		// 포토리뷰 작성시
				insert_point($use1['mb_id'], $point_img, $itit['it_name'].' 상품 포토리뷰 작성', '@review', $use1['mb_id']."/".$use1['it_id'], '리뷰작성');
			}else{																					// 일반리뷰 작성시
				insert_point($use1['mb_id'], $point, $itit['it_name'].' 상품 일반리뷰 작성', '@review', $use1['mb_id']."/".$use1['it_id'], '리뷰작성');
			}
		}
		// 노출확인 아니오 에서 예로 변경시에 포인트 지급하기 끝

    $sql = "update {$g5['g5_shop_item_use_table']}
               set is_subject = '$is_subject',
                   is_content = '$is_content',
                   is_confirm = '$is_confirm',
                   is_reply_subject = '$is_reply_subject',
                   is_reply_content = '$is_reply_content',
                   is_reply_name = '$review_reply_name'
                   $proxy_sql
             where is_id = '$is_id' ";
    sql_query($sql);

    update_use_cnt($proxy['it_id']);
    update_use_avg($proxy['it_id']);

		


    goto_url("./itemuseform.php?w=$w&amp;is_id=$is_id&amp;sca=$sca&amp;$qstr");
}
else
{
    alert();
}
?>
