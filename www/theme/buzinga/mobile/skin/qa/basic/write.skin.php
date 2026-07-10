<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$qa_skin_url.'/style.css">', 0);
?>
<style>
    #container {max-width:none;padding:0;}
    #container_title {display:none}
</style>
<!-- 1:1문의 등록하기 -->
<div id="mp_top">
    <h1>고객 센터</h1>
    <!-- 마이페이지 상단 공통 -->
    <ul class="mp_info">
        <li>
            <a href="">            
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_coupon_icon.png" alt="">
                <p>쿠폰 <span><?=number_format(get_coupon_count($member["mb_id"]))?></span> 장</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage3.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_point_icon.png" alt="">
                <p>포인트 <span><?=number_format(get_point_sum($member["mb_id"]))?></span> P</p>
            </a>
        </li>
        <li>
            <a href="/bbs/qalist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_qa_icon.png" alt="">
                <p>1:1 문의 <span><?=number_format(get_qna_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
    </ul>
</div>
<section id="bo_w">
    <div id="tab_menu">
        <ul>
            <li><a href="/bbs/faq.php">자주 묻는 질문 (FAQ)</a></li>
            <li class="active"><a href="/bbs/qalist.php">1:1 문의하기</a></li>
        </ul>
    </div>
    <!-- 게시물 작성/수정 시작 { -->
     <div id="qa_wrap">
         <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
         <input type="hidden" name="w" value="<?php echo $w ?>">
         <input type="hidden" name="qa_id" value="<?php echo $qa_id ?>">
         <input type="hidden" name="sca" value="<?php echo $sca ?>">
         <input type="hidden" name="stx" value="<?php echo $stx ?>">
         <input type="hidden" name="page" value="<?php echo $page ?>">
         <input type="hidden" name="token" value="<?php echo $token ?>">
         <?php
         $option = '';
         $option_hidden = '';
         $option = '';
     
         if ($is_dhtml_editor) {
             $option_hidden .= '<input type="hidden" name="qa_html" value="1">';
         } else {
             $option .= "\n".'<input type="checkbox" id="qa_html" name="qa_html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="qa_html">html</label>';
         }
     
         echo $option_hidden;
         ?>
     
         <div class="form_01 qa_write">
            <h3>1:1 문의 등록하기</h3>
             <ul>
                <li>
                    <label for="qa_writer" class="tit">작성자</label>
                    <span class="qa_writer">작성자</span>
                </li>
                 <!-- <?php if ($category_option) { ?>
                 <li>
                     <label for="qa_category" class="sound_only">분류</label>
                     <select name="qa_category" id="qa_category" required>
                         <option value="">문의 분류 선택</option>
                         <?php echo $category_option ?>
                     </select>
                 </li>
                 <?php } ?> -->
     
                 <?php if ($option) { ?>
                 <li>
                     <span class="sound_only">옵션</span>
                     <?php echo $option; ?>
                 </li>
                 <?php } ?>
     
                 <!-- <?php if ($is_email) { ?>
                 <li>
                     <label for="qa_email" class="tit">이메일</label>
                     <input type="email" name="qa_email" value="<?php echo get_text($write['qa_email']); ?>" id="qa_email" <?php echo $req_email; ?> class="<?php echo $req_email.' '; ?>frm_input full_input email" maxlength="100" placeholder="이메일">                     
                 </li>
                 <?php } ?> -->
     
                 <?php if ($is_hp) { ?>
                 <li>
                     <label for="qa_hp" class="tit">전화번호</label>
                     <input type="text" name="qa_hp" value="<?php echo get_text($write['qa_hp']); ?>" id="qa_hp" <?php echo $req_hp; ?> class="<?php echo $req_hp.' '; ?>frm_input" size="30" placeholder="휴대폰">
                     <?php if($qaconfig['qa_use_sms']) { ?>
                     <input type="checkbox" name="qa_sms_recv" value="1" <?php if($write['qa_sms_recv']) echo 'checked="checked"'; ?>> 답변등록 SMS알림 수신
                     <?php } ?>
                 </li>
                 <?php } ?>
     
                 <li>
                     <label for="qa_subject" class="tit">제목</label>
                     <!-- 문의 분류 -->
                     <?php if ($category_option) { ?>
                        <select name="qa_category" id="qa_category" required>
                            <option value="">문의 분류 선택</option>
                            <?php echo $category_option ?>
                        </select>
                     <?php } ?>
                     <input type="text" name="qa_subject" value="<?php echo get_text($write['qa_subject']); ?>" id="qa_subject" required class="frm_input full_input" maxlength="255" placeholder="제목">
                 </li>
     
                 <li>
                    <label for="qa_content" class="tit">내용</label>
                     <div class="wr_content">
                         <?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
                     </div>
                 </li>
     
                 <li class="bo_w_flie">
                    <label for="bf_file" class="tit">파일 첨부</label>
                     <div class="file_wr">
                        <span class="file_name"></span>
                         <input type="file" id="bf_file1" name="bf_file1" class="frm_file">
                         <?php if($w == 'u' && $write['qa_file1']) { ?>
                         <input type="checkbox" id="bf_file_del1" name="bf_file_del[1]" value="1"> <label for="bf_file_del1"><?php echo $write['qa_source1']; ?> 파일 삭제</label>
                         <?php } ?>
                         <label for="bf_file1">선택</label>
                         <span class="file_info">2MB 미만의 jpg,gif,png 파일만 첨부하실 수 있습니다.</span>
                     </div>
                 </li>
     
                 <!-- <li class="bo_w_flie">
                     <div class="file_wr">
                         <span class="lb_icon"><i class="fa fa-download" aria-hidden="true"></i><span class="sound_only">파일 #2</span></span>
                         <input type="file" name="bf_file[2]" title="파일첨부 2 :  용량 <?php echo $upload_max_filesize; ?> 이하만 업로드 가능" class="frm_file">
                         <?php if($w == 'u' && $write['qa_file2']) { ?>
                         <input type="checkbox" id="bf_file_del2" name="bf_file_del[2]" value="1"> <label for="bf_file_del2"><?php echo $write['qa_source2']; ?> 파일 삭제</label>
                         <?php } ?>
                     </div>
                 </li> -->
     
             </ul>
         </div>
     
         <div class="btn_write">
             <a href="<?php echo $list_href; ?>" class="btn_cancel">목록으로</a>
             <button type="submit" value="작성완료" id="btn_submit" accesskey="s" class="btn_submit">등록하기</button>
         </div>
         </form>
     </div>

    <script>
$(document).ready(function() {
    $('#bf_file1').on('change', function() {
        console.log("파일체크");
        
        var fileName = this.files.length > 0 ? this.files[0].name : ''; // 파일명이 없으면 빈 문자열
        $('.file_name').text(fileName); // 파일명을 .file_name 클래스에 표시
    });
});


// $("#bf_file[1]").on('change',function(){
//   var fileName = $("#bf_file[1]").val();
//   console.log(fileName);
  
//   $(".file_name").text(fileName);
// });

    function html_auto_br(obj)
    {
        if (obj.checked) {
            result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
            if (result)
                obj.value = "2";
            else
                obj.value = "1";
        }
        else
            obj.value = "";
    }

    function fwrite_submit(f)
    {
        <?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>

        var subject = "";
        var content = "";
        $.ajax({
            url: g5_bbs_url+"/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.qa_subject.value,
                "content": f.qa_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (subject) {
            alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
            f.qa_subject.focus();
            return false;
        }

        if (content) {
            alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
            if (typeof(ed_qa_content) != "undefined")
                ed_qa_content.returnFalse();
            else
                f.qa_content.focus();
            return false;
        }

        <?php if ($is_hp) { ?>
        var hp = f.qa_hp.value.replace(/[0-9\-]/g, "");
        if(hp.length > 0) {
            alert("휴대폰번호는 숫자, - 으로만 입력해 주십시오.");
            return false;
        }
        <?php } ?>

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
</section>
<!-- } 게시물 작성/수정 끝 -->