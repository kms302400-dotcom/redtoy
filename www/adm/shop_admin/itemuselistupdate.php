<?php
$sub_menu = '400650';
include_once('./_common.php');

check_demo();

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {
    auth_check($auth[$sub_menu], 'w');
} else if ($_POST['act_button'] == "선택삭제") {
    auth_check($auth[$sub_menu], 'd');
} else {
    alert("선택수정이나 선택삭제 작업이 아닙니다.");
}

for ($i=0; $i<count($_POST['chk']); $i++)
{
    $k = $_POST['chk'][$i]; // 실제 번호를 넘김

    if ($_POST['act_button'] == "선택수정")
    {

				// 노출확인 아니오 에서 예로 변경시에 포인트 지급하기 시작
				$sql = " select * from {$g5['g5_shop_item_use_table']} where is_id = '{$_POST['is_id'][$k]}'  ";
				$use1 = sql_fetch($sql);
					
				if(empty($use1['is_provided']) && $use1['is_confirm'] == 0 && $_POST['is_confirm'][$k] == 1){			//노출확인 아니오 에서 예로 변경시
					$itit = sql_fetch(" select it_name from g5_shop_item where it_id = '$use1[it_id]' ");	// 상품조회
					$point = $config['cf_6'];				// 일반리뷰 포인트
					$point_img = $config['cf_5'];		// 포토리뷰 포인트

					if(@preg_match("/img src/", $use1[is_content])){		// 포토리뷰 작성시
						insert_point($use1[mb_id], $point_img, $itit[it_name].' 상품 포토리뷰 작성', '@review', $use1[mb_id]."/".$use1[it_id], '리뷰작성');
					}else{																					// 일반리뷰 작성시
						insert_point($use1[mb_id], $point, $itit[it_name].' 상품 일반리뷰 작성', '@review', $use1[mb_id]."/".$use1[it_id], '리뷰작성');
					}
				}
				// 노출확인 아니오 에서 예로 변경시에 포인트 지급하기 끝

        $sql = "update {$g5['g5_shop_item_use_table']}
                   set is_score   = '{$_POST['is_score'][$k]}',
                       is_confirm = '{$_POST['is_confirm'][$k]}',
                       is_recommend = '{$_POST['is_recommend'][$k]}'
                 where is_id      = '{$_POST['is_id'][$k]}' ";
        sql_query($sql);

			
    }
    else if ($_POST['act_button'] == "선택삭제")
    {
        $sql = "delete from {$g5['g5_shop_item_use_table']} where is_id = '{$_POST['is_id'][$k]}' ";
        sql_query($sql);
    }

    update_use_cnt($_POST['it_id'][$k]);
    update_use_avg($_POST['it_id'][$k]);
}

goto_url("./itemuselist.php?sca=$sca&amp;sst=$sst&amp;sod=$sod&amp;sfl=$sfl&amp;stx=$stx&amp;page=$page");
?>
