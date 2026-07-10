<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>

<!-- 사용후기 쓰기 시작 { -->
<div id="sit_use_write" class="">
    <h1 id="win_title">리뷰 작성</h1>

    <form name="fitemuse" method="post" enctype="multipart/form-data" action="<?php echo G5_SHOP_URL;?>/itemuseformupdate.php" onsubmit="return fitemuse_submit(this);" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo $w; ?>">
    <input type="hidden" name="it_id" id="use_it_id" value="">
    <input type="hidden" name="is_subject" id="is_subject" value="">
    <input type="hidden" name="is_id" value="<?php echo $is_id; ?>">
    <input type="hidden" name="is_score" id="is_score" value="5" />
    <input type="hidden" name="ct_id" id="use_ct_id" value="<?php echo $ct_id; ?>" />

    <div class="form_01 chk_box">
        <ul>
            <li class="product_info">
                <img src="" width="70" height="70" id="product_image">
                <div class="product_name">
                    <p class="p_brand" id="it_brand"></p>
                    <p class="p_name" id="it_name"></p>
                </div>  
            </li>
            <li class="review_score">
                <span class="tit">상품 별점을 선택해 주세요.</span>
                <div class="use_score" style="gap:0 !important;flex-direction: initial !important">
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star5.png" id="stars1" class="stars" onclick="setPoint(1)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star0.png" id="stars2" class="stars" onclick="setPoint(2)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star0.png" id="stars3" class="stars" onclick="setPoint(3)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star0.png" id="stars4" class="stars" onclick="setPoint(4)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star0.png" id="stars5" class="stars" onclick="setPoint(5)"></div>
                    <div style="display:inline;">&nbsp;<b id="txt_point">1</b>&#8201;/&#8201;5</div>
                </div>
            </li>
            <li class="review_txt">
                <span class="tit">리뷰(상품 후기)를 작성해 주세요.</span>
                <textarea name="is_content" id="review_text" placeholder="※ 다른 고객님에게 도움이 되도록 상품에 대한 솔직한 평가를 남겨주세요.&#13;&#10;※ 좋았던 점과 아쉬웠던점에 대한 평가를 남겨주세요."><?=$use['is_content']?></textarea>
                <div class="review_file">
                    <input type="file" name="review_file_load[]" multiple id="review_file_load">
                    <label for="review_file_load">사진 첨부</label>
                    <p>최대 10MB 이하의 JPG, JPEG, PNG, GIF 파일 첨부 가능</p>
                    <span id="file_count">0/5</span>
                </div>
            </li>
            <li class="review_img"></li>
        </ul>
    </div>

    <div class="win_btn">
        <button type="submit" class="btn_submit">리뷰 등록하기</button>
        <!-- <button type="button" onclick="self.close();" class="btn_close">닫기</button> -->
    </div>

    </form>
</div>

<script type="text/javascript">
function fitemuse_submit(f)
{
    <?php echo $editor_js; ?>
    
    if ($("#review_text").val().length < 5) {
        alert("후기는 5글자 이상 작성해야 합니다.")
        return false;
    }

    return true;
}

function setPoint(point) {
    $(".stars").each((i, v) => {
        $(v).attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star0.png")
    })
    
    $("#is_score").val(point);
    
    switch(point) {
        case 5 :
            $("#stars5").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        case 4 :
            $("#stars4").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        case 3 :
            $("#stars3").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        case 2 :
            $("#stars2").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        default :
            $("#stars1").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
            break;
    }
    
    switch(point) {
        case 5 :
            $("#txt_point").html(5);
            break;
        case 4 :
            $("#txt_point").html(4);
            break;
        case 3 :
            $("#txt_point").html(3);
            break;
        case 2 :
            $("#txt_point").html(2);
            break;
        default :
            $("#txt_point").html(1);
            break;
    }
}

function editPoint(point) {
    $(".stars").each((i, v) => {
        $(v).attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star0.png")
    })
    
    $("#edit_is_score").val(point);
    
    switch(point) {
        case 5 :
            $("#edit_stars5").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        case 4 :
            $("#edit_stars4").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        case 3 :
            $("#edit_stars3").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        case 2 :
            $("#edit_stars2").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
        default :
            $("#edit_stars1").attr("src", "<?php echo G5_SHOP_URL; ?>/img/s_star5.png");
            break;
    }
    
    switch(point) {
        case 5 :
            $("#edit_txt_point").html(5);
            break;
        case 4 :
            $("#edit_txt_point").html(4);
            break;
        case 3 :
            $("#edit_txt_point").html(3);
            break;
        case 2 :
            $("#edit_txt_point").html(2);
            break;
        default :
            $("#edit_txt_point").html(1);
            break;
    }
}

$(document).ready(() => {
    setPoint(5);
})
</script>
<!-- } 사용후기 쓰기 끝 -->