<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>

<section id="bo_v_ans" class="bo_v_wr">
    <h2><span class="bo_v_reply">답변</span> <?php echo get_text($answer['qa_subject']); ?></h2>
	<a href="<?php echo $rewrite_href; ?>" class="btn add_qa"><i class="fa fa-plus" aria-hidden="true"></i> 추가질문</a>
    <div id="ans_datetime" class="r_txt">
        <span>- 답변이 등록되었습니다. </span><?php echo $answer['qa_datetime']; ?>
    </div>
    <div id="ans_con">
        <?php echo get_view_thumbnail(conv_content($answer['qa_content'], $answer['qa_html']), $qaconfig['qa_image_width']); ?>
    </div>

</section>
<div id="ans_add">
    <?php if($answer_delete_href) { ?>
    <a href="<?php echo $answer_delete_href; ?>" class="btn_b03 btn del" onclick="del(this.href); return false;">삭제</a>
    <?php } ?>
    <?php if($answer_update_href) { ?>
    <a href="<?php echo $answer_update_href; ?>" class="btn_b03 btn">수정</a>
    <?php } ?>
    <a href="/bbs/qalist.php" class="btn_b03 btn">목록</a>
</div>