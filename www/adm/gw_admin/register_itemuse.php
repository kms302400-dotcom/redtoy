<?php
$sub_menu = "700100";
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

if ($is_admin != 'super')
    alert('최고관리자만 접근 가능합니다.');

$g5['title'] = '사용후기 일괄등록';
include_once (G5_ADMIN_PATH.'/admin.head.php');
include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');

// 사용후기 의 답변 필드 추가
if (!isset($is['is_reply_subject'])) {
    sql_query(" ALTER TABLE `{$g5['g5_shop_item_use_table']}`
                ADD COLUMN `is_reply_subject` VARCHAR(255) NOT NULL DEFAULT '' AFTER `is_confirm`,
                ADD COLUMN `is_reply_content` TEXT NOT NULL AFTER `is_reply_subject`,
                ADD COLUMN `is_reply_name` VARCHAR(25) NOT NULL DEFAULT '' AFTER `is_reply_content`
                ", false);
}

$pg_anchor = '<ul class="anchor">
<li><a href="#anc_cf_basic">사용후기 등록설정</a></li>
<li><a href="#anc_cf_info">사용후기 내용설정</a></li>
<li><a href="#anc_cf_admin">사용후기 답변설정</a></li>
</ul>';

function get_mshop_category($ca_id, $len)
{
    global $g5;

    $sql = " select ca_id, ca_name from {$g5['g5_shop_category_table']}
                where ca_use = '1' ";
    if($ca_id)
        $sql .= " and ca_id like '$ca_id%' ";
    $sql .= " and length(ca_id) = '$len' order by ca_order, ca_id ";

    return $sql;
}
?>

<style>
.tbl_head01 thead td {
    border: 1px solid #d6dce7;
    padding: 5px;
    text-align: center;
}

/* 상품후기 일괄등록 */
#review_category .review_category_1depth > li + li{margin-top:10px;}
#review_category .review_category_2depth, 
#review_category .review_category_3depth{margin-left:1.5rem;}
#review_category .form_bg{margin-bottom:4px; padding:6px 10px; background:#f1f1f1;}
#review_category .form_padding{margin:2px;}
</style>

<link rel="stylesheet" type="text/css" href="<?php echo G5_ADMIN_URL?>/gw_admin/multi-select.css">
<script src="<?php echo G5_ADMIN_URL?>/gw_admin/jquery.multi-select.js"></script>

<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);" autocomplete="off">
<input type="hidden" name="token" value="" id="token">

<section id="anc_cf_basic">
	<h2 class="h2_frm">사용후기 등록설정</h2>
	<?php echo $pg_anchor; ?>
	<div class="local_desc02 local_desc">
        <p>
            사용후기 일괄등록을 실행 할 분류와 평점, 등록일시를 설정합니다.<br>
        </p>
    </div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>사용후기 등록설정</caption>
			<colgroup>
				<col class="grid_4">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th scope="row"><label for="item_use_method">등록종류</label></th>
				<td>
				   <?php echo help("쿠폰 종류를 변경하시면 입력 서식도 일부 변경됩니다."); ?>
				   <select name="item_use_method" id="item_use_method">
						<option value="1">분류 전체</option>
						<option value="2">개별 상품 선택</option>
				   </select>
				</td>
			</tr>

			<tr id="tr_target_type_1">
				<th scope="row">
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"><label for="chkall" class="">분류 전체</label>
				</th>
				<td>
					<div id="review_category">
						<ul class="review_category_1depth">
							<?php
							$mshop_ca_res1 = sql_query(get_mshop_category('', 2));
							for($i=0; $mshop_ca_row1=sql_fetch_array($mshop_ca_res1); $i++) {
							?>
							<li>
								<div class="form_bg">
									<input type="checkbox" name="chk[]" value="<?php echo $mshop_ca_row1['ca_id'] ?>" id="chkall_<?php echo $mshop_ca_row1['ca_id'] ?>" onclick="chk_all(this.form, '<?php echo $mshop_ca_row1['ca_id'] ?>', '<?php echo $mshop_ca_row1['ca_id'] ?>')">
									<label for="chkall_<?php echo $mshop_ca_row1['ca_id'] ?>"><?php echo get_text($mshop_ca_row1['ca_name']); ?></label>
								</div>
								<ul class="review_category_2depth">
									<?php
									$mshop_ca_res2 = sql_query(get_mshop_category($mshop_ca_row1['ca_id'], 4));
									for($j=0; $mshop_ca_row2=sql_fetch_array($mshop_ca_res2); $j++) {
									?>
									<li>
										<div class="form_padding">
											<input type="checkbox" name="chk[]" value="<?php echo $mshop_ca_row2['ca_id'] ?>" id="chkall_<?php echo $mshop_ca_row2['ca_id'] ?>" data-value="<?php echo $mshop_ca_row1['ca_id'] ?>" onclick="chk_all(this.form, '<?php echo $mshop_ca_row2['ca_id'] ?>', '<?php echo $mshop_ca_row2['ca_id'] ?>')">
											<label for="chkall_<?php echo $mshop_ca_row2['ca_id'] ?>"><?php echo get_text($mshop_ca_row2['ca_name']); ?></label>
										</div>	

										<ul class="review_category_3depth">
										<?php
										$mshop_ca_res3 = sql_query(get_mshop_category($mshop_ca_row2['ca_id'], 6));
										for($k=0; $mshop_ca_row3=sql_fetch_array($mshop_ca_res3); $k++) {
										?>
											<li>
												<div class="form_padding">
													<input type="checkbox" name="chk[]" value="<?php echo $mshop_ca_row3['ca_id'] ?>" id="chkall_<?php echo $mshop_ca_row3['ca_id'] ?>" data-value="<?php echo $mshop_ca_row2['ca_id'] ?>" data-parents="<?php echo $mshop_ca_row1['ca_id'] ?>">
													<label for="chkall_<?php echo $mshop_ca_row3['ca_id'] ?>"><?php echo get_text($mshop_ca_row3['ca_name']); ?></label>
												</div>
											</li>
										<?php
										}
										?>
										</ul>
									</li>
									<?php
									}
									?>
								</ul>
							</li>
							<?php
							}
							?>
						</ul>
					</div>
				</td>
			</tr>

			<tr id="tr_target_type_2">
				<th scope="row">개별 상품 선택</th>
				<td>
				<select name='item_chk[]' id='optgroup' multiple='multiple'>
					<?php
					//$sql = " select *  from {$g5['g5_shop_category_table']} where length(ca_id) = '2' order by ca_id asc ";
					$sql = " select * from {$g5['g5_shop_category_table']} order by ca_id asc ";
					$mshop_ca_res1 = sql_query($sql);
					for($i=0; $mshop_ca_row1=sql_fetch_array($mshop_ca_res1); $i++) {
						echo "<optgroup label='{$mshop_ca_row1['ca_name']}'>";
						$sql2 = " select * from {$g5['g5_shop_item_table']} where (ca_id = '{$mshop_ca_row1['ca_id']}' or ca_id2 = '{$mshop_ca_row1['ca_id']}' or ca_id3 = '{$mshop_ca_row1['ca_id']}') order by it_name asc ";
						$mshop_ca_res2 = sql_query($sql2);
						for($j=0; $mshop_ca_row2=sql_fetch_array($mshop_ca_res2); $j++) {
							//$selected = in_array($mshop_ca_row2['it_id'], $gi_item_use) ? " selected" : "";
							echo "<option value='{$mshop_ca_row2['it_id']}' {$selected}>{$mshop_ca_row2['it_name']}</option>";
						}
						echo "</optgroup>";
					}
					?>
				</select>
				</td>
			</tr>

			</tbody>
		</table>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>사용후기 등록설정</caption>
			<colgroup>
				<col class="grid_4">
				<col>
				<col class="grid_4">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th scope="row">등록개수</th>
				<td colspan="3">
					<?php echo help('입력한 개수만큼 사용후기가 등록됩니다.') ?>
					<input type="text" name="itemuse_count" value="" required class="required frm_input" size="5"> 개
				</td>
			</tr>
			<tr>
				<th scope="row">고객평점</th>
				<td>
					<?php echo help('지정한 평점으로 사용후기 평점이 등록됩니다.') ?>
					<select name="fr_score" id="fr_score">
						<option value="">최소평점</option>
						<option value="5">매우만족</option>
						<option value="4">만족</option>
						<option value="3">보통</option>
						<option value="2">불만</option>
						<option value="1">매우불만</option>
					</select>
					<label for="fr_score" class="sound_only">최소평점</label>
					~
					<select name="to_score" id="to_score">
						<option value="">최대평점</option>
						<option value="5">매우만족</option>
						<option value="4">만족</option>
						<option value="3">보통</option>
						<option value="2">불만</option>
						<option value="1">매우불만</option>
					</select>
					<label for="to_score" class="sound_only">최대평점</label>
				</td>
				<th scope="row">등록일시</th>
				<td>
					<?php echo help('지정한 기간으로 사용후기 날짜가 등록됩니다.') ?>
					<input type="text" name="fr_date" value="" required id="fr_date" class="required frm_input" size="11" maxlength="10">
					<label for="fr_date" class="sound_only">시작일</label>
					~
					<input type="text" name="to_date" value="" required id="to_date" class="required frm_input" size="11" maxlength="10">
					<label for="to_date" class="sound_only">종료일</label>
				</td>
			</tr>

			</tbody>
		</table>
	</div>
</section>

<div class="btn_fixed_top">
	<a href="<?php echo G5_URL?>" class="btn btn_02">메인으로</a>
	<input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>

</form>

<section id="anc_cf_info">
	<h2 class="h2_frm">사용후기 내용설정</h2>
	<?php echo $pg_anchor; ?>
	<div class="local_desc02 local_desc">
        <p>
            사용후기를 작성 할 내용을 설정 할 수 있습니다. <strong>(엔터로 구분)</strong><br>
            <strong>내용을 입력 후 반드시 상단의 저장 버튼을 클릭하세요.</strong>
        </p>
    </div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>사용후기 내용설정</caption>
			<colgroup>
				<col class="grid_4">
				<col>
				<col class="grid_4">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th scope="row">후기 제목</th>
				<td>
					<input type="button" class="btn btn_03" onclick="add('subject')" value="후기제목 저장">
					<?php
					$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_subject.txt";
					$fp = @fopen($file_path,"r"); 
					$fr = @fread($fp, filesize($file_path)); 
					@fclose($fp); 
					?>
                    <textarea name="itemuse_subject" id="itemuse_subject"><?php echo($fr); ?></textarea>
				</td>
				<th scope="row">후기 작성자</th>
				<td>
					<input type="button" class="btn btn_03" onclick="add('name')" value="작성자 저장">
					<?php
					$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_name.txt";
					$fp = @fopen($file_path,"r"); 
					$fr = @fread($fp, filesize($file_path)); 
					@fclose($fp); 
					?>
                    <textarea name="itemuse_name" id="itemuse_name"><?php echo($fr); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">후기 내용</th>
				<td colspan="3">
					<input type="button" class="btn btn_03" onclick="add('content')" value="후기내용 저장">
					<?php
					$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_content.txt";
					$fp = @fopen($file_path,"r"); 
					$fr = @fread($fp, filesize($file_path)); 
					@fclose($fp); 
					?>
					<textarea name="itemuse_content" id="itemuse_content"><?php echo($fr); ?></textarea>
				</td>
			</tr>

			</tbody>
		</table>
	</div>
</section>

<section id="anc_cf_admin">
	<h2 class="h2_frm">사용후기 답변설정</h2>
	<?php echo $pg_anchor; ?>
	<div class="local_desc02 local_desc">
        <p>
            사용후기에 답변 할 내용을 설정 할 수 있습니다. <strong>(엔터로 구분)</strong><br>
            <strong>내용을 입력 후 반드시 상단의 저장 버튼을 클릭하세요.</strong>
        </p>
    </div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>사용후기 답변설정</caption>
			<colgroup>
				<col class="grid_4">
				<col>
				<col class="grid_4">
				<col>
			</colgroup>
			<tbody>

			<tr>
				<th scope="row">관리자 답변 제목</th>
				<td>
					<input type="button" class="btn btn_03" onclick="add('admin_subject')" value="답변제목 저장">
					<?php
					$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_admin_subject.txt";
					$fp = @fopen($file_path,"r"); 
					$fr = @fread($fp, filesize($file_path)); 
					@fclose($fp); 
					?>
                    <textarea name="itemuse_admin_subject" id="itemuse_admin_subject"><?php echo($fr); ?></textarea>
				</td>
				<th scope="row">관리자 답변 제목</th>
				<td>
					<input type="button" class="btn btn_03" onclick="add('admin_content')" value="답변내용 저장">
					<?php
					$file_path = G5_ADMIN_PATH."/gw_admin/itemuse_admin_content.txt";
					$fp = @fopen($file_path,"r"); 
					$fr = @fread($fp, filesize($file_path)); 
					@fclose($fp); 
					?>
                    <textarea name="itemuse_admin_content" id="itemuse_admin_content"><?php echo($fr); ?></textarea>
				</td>
			</tr>

			</tbody>
		</table>
	</div>
</section>

<script>
$(function() {
	// 초기 설정
	$("#tr_target_type_2").hide();
	$("#tr_target_type_1").show();

	$("#item_use_method").change(function() {
		var item_use_method = $(this).val();
		item_use_change_method(item_use_method);
	});

	$('#optgroup').multiSelect({ selectableOptgroup: true });
});

function item_use_change_method(item_use_method)
{
	if(item_use_method == "1") {
		$("#tr_target_type_2").hide();
		$("#tr_target_type_1").show();
	} else {
		$("#tr_target_type_1").hide();
		$("#tr_target_type_2").show();
	}
}

$(function(){
    $("#fr_date, #to_date").datepicker({ changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99", maxDate: "+0d" });
});


function chk_all(f, ca_id, data)
{
	$("#chkall_"+ca_id).change(function () {
		$("input[data-value="+data+"]").prop("checked", this.checked);
		$("input[data-parents="+data+"]").prop("checked", this.checked);
	});
}

function add(type) {
	$.ajax({
		url: "./itemuse_config_update.php",
		type: "post",
		data: {
			content : $("#itemuse_"+type).val(),
			type : type,
		},
		success : function(data) {
				alert(data);
		}
	});
}

function fconfigform_submit(f)
{
	if ($("select[name=item_use_method]").val() == '1') {
		if($("input[name='chk[]']:checkbox:checked").size() == 0){
			window.alert("사용후기 일괄등록을 실행 할 분류를 하나 이상 체크하세요."); 
			$("input[name='chk[]']").focus();
			return false; 
		}
	}

	// 고객평점 검사
	var fr_score = $("#fr_score option:selected").val();
	var to_score = $("#to_score option:selected").val();

	if (fr_score > to_score) {
		alert("최소평점을 최대평점보다 작은 값으로 입력하십시오.");
		f.fr_score.focus();
		return false;
	}

	// 등록일시 검사
	var fr_date = $("#fr_date").val();
	var to_date = $("#to_date").val();

	if (fr_date > to_date) {
		alert("등록일시의 앞의 기간이 뒤의 기간보다 큽니다.\n등록일시를 다시 입력하십시오.");
		f.fr_date.focus();
		return false;
	}

	f.action = "./register_itemuse_update.php";
	
	if(!confirm("사용후기 일괄등록을 실행하시겠습니까?")) {
		return false;
	}

	return true;
}
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');