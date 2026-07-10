<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
$is_id = isset($_REQUEST["is_id"]) ? safe_replace_regex($_REQUEST["is_id"], "number") : "";
if (!$is_id || !$it_id) exit;

if (!$is_admin) $where = " AND mb_id = '" . $member["mb_id"] . "' ";
$sql = " SELECT * FROM {$g5['g5_shop_item_use_table']} WHERE is_id = '" . $is_id . "' $where";
$row = sql_fetch($sql);

$sql = " SELECT * FROM {$g5['g5_shop_item_use_image_table']} WHERE is_id = '" . $is_id . "' order by is_file_idx ";
$res = sql_query($sql);
$rows = sql_num_rows($res);

$it = get_shop_item($row["it_id"]);
?>

<!-- 사용후기 쓰기 시작 { -->
<div id="sit_use_write" class="">
    <h1 id="win_title">리뷰 수정</h1>

    <form name="fitemuseedit" method="post" enctype="multipart/form-data" action="<?php echo G5_SHOP_URL;?>/itemuseformupdate.php" onsubmit="return fitemuse_submit2(this);" autocomplete="off">
    <input type="hidden" name="w" value="u">
    <input type="hidden" name="is_id" value="<?php echo $is_id; ?>">
    <input type="hidden" name="is_subject" value="<?=$it["it_name"]?>" />
    <input type="hidden" name="is_score" id="edit_is_score" value="<?=$row["is_score"]?>" />

    <div class="form_01 chk_box">
        <ul>
            <li class="product_info">
                <img src="/product_image.php?it_id=<?=$it["it_id"]?>" width="70" height="70" id="product_image">
                <div class="product_name">
                    <p class="p_brand"><?=$it["it_brand"]?></p>
                    <p class="p_name"><?=$it["it_name"]?></p>
                </div>  
            </li>
            <li class="review_score">
                <span class="tit">상품 별점을 선택해 주세요.</span>
                <div class="use_score" style="gap:0 !important;flex-direction: initial !important">
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star5.png" id="edit_stars1" class="stars" onclick="editPoint(1)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?=$row["is_score"] > 1 ? "5" : "0"?>.png" id="edit_stars2" class="stars" onclick="editPoint(2)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?=$row["is_score"] > 2 ? "5" : "0"?>.png" id="edit_stars3" class="stars" onclick="editPoint(3)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?=$row["is_score"] > 3 ? "5" : "0"?>.png" id="edit_stars4" class="stars" onclick="editPoint(4)"></div>
                    <div style="display:inline;width:26px;overflow:hidden;padding:0;margin:0;"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?=$row["is_score"] > 4 ? "5" : "0"?>.png" id="edit_stars5" class="stars" onclick="editPoint(5)"></div>
                    <div style="display:inline;">&nbsp;<b id="edit_txt_point"><?=$row["is_score"]?></b>/5</div>
                </div>
            </li>
            <li class="review_txt">
                <span class="tit">리뷰(상품 후기)를 작성해 주세요.</span>
                <textarea name="is_content" id="review_text" placeholder="※ 다른 고객님에게 도움이 되도록 상품에 대한 솔직한 평가를 남겨주세요.&#13;&#10;※ 좋았던 점과 아쉬웠던점에 대한 평가를 남겨주세요."><?=$row['is_content']?></textarea>
                <div class="review_file">
                    <input type="file" name="review_file_load[]" multiple id="edit_review_file_load">
                    <label for="edit_review_file_load">사진 첨부</label>
                    <p>최대 10MB 이하의 JPG, PNG, GIF 파일 첨부 가능</p>
                    <span><span class="edit_file_count"><?=$rows?></span>/5</span>
                </div>
            </li>
            <li class="review_img edit_review_img">
                <?
                if ($rows) {
                    for($i=0;$i<$rows;$i++) {
                        $file = sql_fetch_array($res);
                        $uuid = uuidv4();
                ?>
                <span id="previewImageIndent<?=$uuid?>"><span class="close" onclick="deleteImage2('<?=$uuid?>', '<?=$file["bf_file"]?>')">&#10005;</span><img id="previewImage<?=$uuid?>" src="/review_image.php?bf_file=<?=$file["bf_file"]?>" style="width:70px;height:70px"></span>
                <?
                    }
                }
                ?>
            </li>
        </ul>
    </div>

    <div class="win_btn">
        <button type="submit" class="btn_submit">리뷰 등록하기</button>
    </div>

    </form>
</div>

<script>
    fileIndex = <?=$rows?>;
    dataTransfer = new DataTransfer();
    $("#edit_review_file_load").on("change", fileChangeUpload2);
</script>