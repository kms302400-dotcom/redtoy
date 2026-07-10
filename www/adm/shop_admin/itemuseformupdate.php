<?php
$sub_menu = '400650';
include_once('./_common.php');

check_demo();

if ($w == 'd')
    auth_check($auth[$sub_menu], "d");
else
    auth_check($auth[$sub_menu], "w");

check_admin_token();

if ($w == "u")
{
		
		// 노출확인 아니오 에서 예로 변경시에 포인트 지급하기 시작
		$sql = " select * from {$g5['g5_shop_item_use_table']} where is_id = '{$is_id}'  ";
		$use1 = sql_fetch($sql);
			
		if($use1[is_confirm] == 0 && $_POST[is_confirm][$k] == 1){			//노출확인 아니오 에서 예로 변경시
			$itit = sql_fetch(" select it_name from g5_shop_item where it_id = '$use1[it_id]' ");	// 상품조회
			$point = $config['cf_6'];				// 일반리뷰 포인트
			$point_img = $config['cf_5'];		// 포토리뷰 포인트

			if(@preg_match("/img src/", $is_content)){		// 포토리뷰 작성시
				insert_point($use1[mb_id], $point_img, $itit[it_name].' 상품 포토리뷰 작성', '@review', $use1[mb_id]."/".$use1[it_id], '리뷰작성');
			}else{																					// 일반리뷰 작성시
				insert_point($use1[mb_id], $point, $itit[it_name].' 상품 일반리뷰 작성', '@review', $use1[mb_id]."/".$use1[it_id], '리뷰작성');
			}
		}
		// 노출확인 아니오 에서 예로 변경시에 포인트 지급하기 끝

    $sql = "update {$g5['g5_shop_item_use_table']}
               set is_subject = '$is_subject',
                   is_content = '$is_content',
                   is_confirm = '$is_confirm',
                   is_reply_subject = '$is_reply_subject',
                   is_reply_content = '$is_reply_content',
                   is_reply_name = '".$member['mb_nick']."'
             where is_id = '$is_id' ";
    sql_query($sql);

    update_use_cnt($_POST['it_id']);
    update_use_avg($_POST['it_id']);

		


    goto_url("./itemuseform.php?w=$w&amp;is_id=$is_id&amp;sca=$sca&amp;$qstr");
}
else
{
    alert();
}
?>
