<?php
include_once("./_common.php");
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

$g5['title'] = "비밀번호 찾기";
include_once("./_head.php");
?>
<style>
*{margin:0; padding:0;}
input[type="text"] {}
img, fieldset, button{border:none;}
.input_text{ padding: 5px 0 0 3px; display:inline; position:relative; width:174px; height:20px;border:1px solid #d4d4d4; color:#444; font-size:14px; vertical-align:middle; }
</style>

<?php if ($config['cf_use_tel']) {  ?>
<form method="post" name="femaillost" style="margin:0; padding:0;" onsubmit="return femaillost_submit(this);">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td style="width:6px;"><img src="../help_img/box1_left.gif" alt="" /></td>
			<td style="background-image:url(../help_img/box1_bg.gif); background-repeat:repeat-x; padding-left:40px; font-weight:bold;">이메일 주소 찾기</td>
			<td style="width:6px;"><img src="../help_img/box1_right.gif" alt="" /></td>
		</tr>
		<tr>
			<td style="background-image:url(../help_img/box2_left.gif); background-repeat:repeat-y; background-position:left;"></td>
			<td style="padding:25px 0 25px 39px; font-weight:normal; color:#656565; line-height:140%; border-bottom:1px solid #eaeaea;">회원정보에 등록된 정보를 입력하면 이메일을 알려드립니다.</td>
			</td>
			<td style="background-image:url(../help_img/box2_right.gif); background-repeat:repeat-y; background-position:right;"></td>
		</tr>
		<tr>
			<td style="background-image:url(../help_img/box2_left.gif); background-repeat:repeat-y; background-position:left;"></td>
			<td align="center" style="padding:20px 0px 20px 0px;">
				<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="60" height="37"><img src="../help_img/bullet.gif" alt=""> 이름</td>
						<td width="145"><input name="name" id="rname" type="text" style="width:145px;" accesskey="L" maxlength="30"></td>
						<td width="50" rowspan="2" style="padding-left:5px;">
							<img src="../help_img/btn_ok.gif" alt="확인" onClick="find_email();">
						</td>
						<td rowspan="2" style="padding-left:15px;"><div id="msg_mail"></div></td>
					</tr>
					<tr>
						<td height="37"><img src="../help_img/bullet.gif" alt="" width="8" height="8"> 전화번호</td>
						<td>
							<input name="tel" type="text" id="rphone1" maxlength="4" style="width:40px;"> -
							<input name="tel" type="text" id="rphone2" maxlength="4" style="width:40px;"> -
							<input name="tel" type="text" id="rphone3" maxlength="4" style="width:40px;">
						</td>
					</tr>
					<tr>
						<td colspan="3"><?php //echo captcha_html(); ?></td>
					</tr>
				</table>
			</td>
			<td style="background-image:url(../help_img/box2_right.gif); background-repeat:repeat-y; background-position:right;"></td>
		</tr>
		<tr>
			<td style="width:6px; height:6px;"><img src="../help_img/box3_left.gif" alt=""></td>
			<td style="background-image:url(../help_img/box3_bg.gif); background-repeat:repeat-x; background-position:bottom;"></td>
			<td style="width:6px; height:6px;"><img src="../help_img/box3_right.gif" alt=""</td>
		</tr>
	</table>
</form>
<br />
<br />
<?php } ?>
<form method="post" name="fidlost" style="margin:0; padding:0;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box1_left.gif" alt=""></td>
			<td style="height:6px;  line-height:6px;background-image:url(../help_img/box1_bg.gif); background-repeat:repeat-x; padding-left:40px; font-weight:bold;">아이디 찾기</td>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box1_right.gif" alt=""></td>
		</tr>
		<tr>
			<td style="width:6px; background-image:url(../help_img/box2_left.gif); background-repeat:repeat-y; background-position:left;"></td>
			<td style="padding:25px 0 25px 39px; font-weight:normal; color:#656565; line-height:140%; border-bottom:1px solid #eaeaea;">회원정보에 등록된 정보를 입력하면 아이디를 알려드립니다.</td>
			<td style="width:6px; background-image:url(../help_img/box2_right.gif); background-repeat:repeat-y; background-position:right;"></td>
		</tr>
		<tr>
			<td style="width:6px; background-image:url(../help_img/box2_left.gif); background-repeat:repeat-y; background-position:left;"></td>
			<td align="center" style="padding:20px 0px 20px 0px;">
				<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="60" height="37"><img src="../help_img/bullet.gif" alt=""> 이름</td>
						<td width="145"><input name="r2name" id="r2name" type="text" style="width:145px;" accesskey="L" maxlength="30"></td>
						<td width="50" rowspan="2" style="padding-left:5px;">
							<img src="../help_img/btn_ok.gif" alt="확인" onClick="find_id();">
						</td>
						<td rowspan="2" style="padding-left:15px;"><div id="msg_id"></div></td>
					</tr>
					<tr>
						<td height="37"><img src="../help_img/bullet.gif" alt="" width="8" height="8"> 이메일</td>
						<td><input name="r2email" id="r2email" type="text" style="width:145px;"></td>
					</tr>
					<tr>
						<td colspan="3"><?php //echo captcha_html(); ?></td>
					</tr>
				</table>
			</td>
			<td style="width:6px; background-image:url(../help_img/box2_right.gif); background-repeat:repeat-y; background-position:right;"></td>
		</tr>
		<tr>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box3_left.gif" alt=""></td>
			<td style="height:6px; line-height:6px; background-image:url(../help_img/box3_bg.gif); background-repeat:repeat-x; background-position:bottom;"></td>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box3_right.gif" alt=""></td>
		</tr>
	</table>
</form>
<br />
<br />
<form action="" method="post" name="fpasswordlost" style="margin:0; padding:0;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box1_left.gif" alt=""></td>
			<td style="height:6px; line-height:6px; background-image:url(../help_img/box1_bg.gif); background-repeat:repeat-x; padding-left:40px; font-weight:bold;">비밀번호 변경</td>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box1_right.gif" alt=""></td>
		</tr>
		<tr>
			<td style="width:6px; background-image:url(../help_img/box2_left.gif); background-repeat:repeat-y; background-position:left;"></td>
			<td style="padding:25px 0 25px 39px; font-weight:normal; color:#656565; line-height:140%; border-bottom:1px solid #eaeaea;">회원정보에 등록된 정보를 입력하면 변경될 비밀번호를 알려드립니다.</td>
			<td style="width:6px; background-image:url(../help_img/box2_right.gif); background-repeat:repeat-y; background-position:right;"></td>
		</tr>
		<tr>
			<td style="width:6px; background-image:url(../help_img/box2_left.gif); background-repeat:repeat-y; background-position:left;"></td>
			<td align="center" style="padding:20px 0px 20px 0px;">
				<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="60" height="25"><img src="../help_img/bullet.gif" alt="" width="8" height="8"> 이름</td>
						<td width="145"><input name="name" id="r3name" type="text" style="width:145px;" accesskey="L" maxlength="30"></td>
						<td width="50" rowspan="3" style="padding-left:15px;">
							<img src="../help_img/btn_ok.gif" alt="확인" onclick="find_pass();">
						</td>
						<td rowspan="3" style="padding-left:15px;"><div id="msg_pass"></div></td>
					</tr>
					<tr>
						<td height="25" style="padding:5px 0;"><img src="../help_img/bullet.gif" alt=""> 아이디</td>
						<td height="25" style="padding:5px 0;"><input name="id" id="r3id" type="text" style="width:145px;"></td>
					</tr>
					<tr>
						<td height="25"><img src="../help_img/bullet.gif" alt="" width="8" height="8"> 이메일</td>
						<td><input name="email" id="r3email" type="text" style="width:145px;"></td>
					</tr>
					<tr>
						<td colspan="3" style="padding-top:15px;"><?php //echo captcha_html(); ?></td>
					</tr>
				</table>
			</td>
			<td style="width:6px; background-image:url(../help_img/box2_right.gif); background-repeat:repeat-y; background-position:right;"></td>
		</tr>
		<tr>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box3_left.gif" alt=""></td>
			<td style="height:6px; line-height:6px; background-image:url(../help_img/box3_bg.gif); background-repeat:repeat-x; background-position:bottom;"></td>
			<td style="width:6px; height:6px; line-height:6px;"><img src="../help_img/box3_right.gif" alt=""></td>
		</tr>
	</table>
</form>

<script>
function find_email() {
	$('#msg_mail').slideUp();
	
	$.ajax({
		type: 'POST',
		url: './member_helper_email.php',
		data: {
			'name': $('#rname').val(),
			'phone': $('#rphone1').val()+'-'+$('#rphone2').val()+'-'+$('#rphone3').val()
		},
		cache: false,
		async: false,
		success: function(result) {
			var msg = $('#msg_mail');

			if(result=="000"){ 
				msg.html("정보가 없습니다.");
				$('#msg_mail').slideDown();
			}
			else{
				msg.html("회원님의 이메일은 <span style='color:#ff3300;font-weight:bold;'>"+result+"</span> 입니다.");
				$('#msg_mail').slideDown();
			}
		}
	});
}

function find_id() {
	$('#msg_id').slideUp();
	
	$.ajax({
		type: 'POST',
		url: './member_helper_id.php',
		data: {
			'name': $('#r2name').val(),
			'email': $('#r2email').val()
		},
		cache: false,
		async: false,
		success: function(result) {
			var msg = $('#msg_id');

			if(result=="000"){ 
				msg.html("정보가 없습니다.");
				$('#msg_id').slideDown();
			}
			else{
				msg.html("회원님의 아이디는 <span style='color:#ff3300;font-weight:bold;'>"+result+"</span> 입니다.");
				$('#msg_id').slideDown();
			}
		}
	});
}
	
 function find_pass(){
	$('#msg_pass').slideUp();

	$.ajax({
		type: 'POST',
		url: './member_helper_pass.php',
		data: {
			'name': $('#r3name').val(),
			'id': $('#r3id').val(),
			'email': $('#r3email').val()
			//'email': $('#pemail1').val()+'@'+$('#pemail2').val()
		},
		cache: false,
		async: false,
		success: function(result) {
			var msg = $('#msg_pass');
			var len=result.length;
			//alert(result);
			msg.html(result);
			$('#msg_pass').slideDown();
		}
	});
}
</script>

<?php
include_once("./_tail.php");