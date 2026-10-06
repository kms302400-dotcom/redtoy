<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>

<!-- 상세 후기 시작 { -->
<div id="sit_use_detail" class="">
    <div class="review">
        <div class="img_area" style="overflow:hidden">
            <img src="" id="detail_image" style="width:398px">
        </div>
        <div class="detail">
            <div class="txt">
                <div class="review_wr">
                    <div class="sit_thum">
                        <img src="<?php echo G5_URL; ?>/shop/img/user_thumnail.png" width="50" height="50" alt="">
                    </div>
                    <div class="review_txt">
                        <div class="use_score">
                            <img src="" id="detail_score_image">
                            <span><b id="detail_score">0</b>/5</span>
                        </div>
                        <dl class="sit_use_dl">
                            <dt>작성자</dt>
                            <dd id="detail_review_notice"></dd>
                            <dd class="nick" id="detail_username">작성자</dd>
                            <dt>작성일</dt>
                            <dd id="detail_date">25-03-10</dd>
                        </dl>
                    </div>
                </div>
                <div class="review_txt" id="detail_content"></div>
            </div>
            <div class="img" id="detail_image_list"></div>
        </div>
    </div>
    <div class="photo">
        <div>
            <p>이 상품의 다른 포토후기</p>
            <span onclick="photo_all_popup_show()" style="cursor:pointer">전체보기 ></span>
        </div>
        <ul id="detail_photo_list_all"></ul>
    </div>
</div>

<script type="text/javascript">
function fitemuse_submit(f)
{
    <?php echo $editor_js; ?>

    return true;
}
</script>
<!-- } 상세 후기 끝 -->