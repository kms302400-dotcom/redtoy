<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);

?>

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>

<!-- 사용후기 모아보기 -->
<div id="sit_use_sum">
    <div class="use_sum_top">
        <p>포토후기 / <?php echo $result_img_rows; ?>건</p>
        <span id="photo_all_popup">전체보기 ></span>
    </div>
    <div class="use_photo_all">
		<?php
			for($i = 0; $i < (count($imgArrayRows) > 9 ? 9 : count($imgArrayRows)); $i++) {
		?>
        <a href="javascript:reviewDetailOpen(<?=$imgArrayRows[$i]["is_id"]?>, '<?=$imgArrayRows[$i]["bf_file"]?>')">
            <img src="/review_image.php?bf_file=<?php echo $imgArrayRows[$i]["bf_file"];?>" onError="this.src='/img/img_error.png'" title="" width="70"/>
        </a>
		<?php
			}
			if (!$i) echo '등록된 이미지가 없습니다.';
		?>
    </div>
    <div class="rating_box">
        <div class="rating_score">
            <p>고객 리뷰 총 별점</p>
            <div class="rating_num"><?php echo $item_use_score; ?></div>
            <div class="rating_star">
                <img src="/shop/img/s_star<?php echo get_star($item_use_score < 1 ? 1 : $item_use_score); ?>.png" alt="고객평점 " class="sit_star" width="100">
            </div>
            <p>총 <?php echo $item_use_count; ?> 후기가 등록되었습니다.</p>
        </div>
        <span></span>
        <?
            $sql = " select COUNT(is_score) as cnt, is_score as score from `{$g5['g5_shop_item_use_table']}` where it_id = '{$it_id}' and is_confirm = '1' group by is_score ";
            $result_score = sql_query($sql);
            $score_array = array();
            $score_array[5] = 0;
            $score_array[4] = 0;
            $score_array[3] = 0;
            $score_array[2] = 0;
            $score_array[1] = 0;
            
            $score_per_array = array();
            for($i=0; $row_score=sql_fetch_array($result_score); $i++) {
                $score_array[$row_score["score"]] = $row_score["cnt"];
            }
            $score_per_array[5] = ($score_array[5] / ($item_use_count ? $item_use_count : 1)) * 100;
            $score_per_array[4] = ($score_array[4] / ($item_use_count ? $item_use_count : 1)) * 100;
            $score_per_array[3] = ($score_array[3] / ($item_use_count ? $item_use_count : 1)) * 100;
            $score_per_array[2] = ($score_array[2] / ($item_use_count ? $item_use_count : 1)) * 100;
            $score_per_array[1] = ($score_array[1] / ($item_use_count ? $item_use_count : 1)) * 100;
        ?>
        <div class="rating_bar">
            <p>별점 비율</p>
            <div class="bars">
                <!-- 별점 비율 영역 -->
                 <div class="bar_wrap">
                    <span class="bar_cnt" data-count="<?=$score_array[5]?>"><?=$score_array[5]?></span>
                    <div class="bar"><span style="height:<?=$score_per_array[5]?>%"></span></div>
                    <span class="bar_label">5점</span>
                 </div>
                 <div class="bar_wrap">
                    <span class="bar_cnt" data-count="<?=$score_array[4]?>"><?=$score_array[4]?></span>
                    <div class="bar"><span style="height:<?=$score_per_array[4]?>%"></span></div>
                    <span class="bar_label">4점</span>
                 </div>
                 <div class="bar_wrap">
                    <span class="bar_cnt" data-count="<?=$score_array[3]?>"><?=$score_array[3]?></span>
                    <div class="bar"><span style="height:<?=$score_per_array[3]?>%"></span></div>
                    <span class="bar_label">3점</span>
                 </div>
                 <div class="bar_wrap">
                    <span class="bar_cnt" data-count="<?=$score_array[2]?>"><?=$score_array[2]?></span>
                    <div class="bar"><span style="height:<?=$score_per_array[2]?>%"></span></div>
                    <span class="bar_label">2점</span>
                 </div>
                 <div class="bar_wrap">
                    <span class="bar_cnt" data-count="<?=$score_array[1]?>"><?=$score_array[1]?></span>
                    <div class="bar"><span style="height:<?=$score_per_array[1]?>%"></span></div>
                    <span class="bar_label">1점</span>
                 </div>
            </div>
        </div>        
    </div>
</div>

<div id="sit_use_wbtn">
    <select name="" id="">
        <option value="">최신순</option>
        <option value="">평점 높은순</option>
        <option value="">평점 낮은순</option>
    </select>
    <a href="#" class="qa_wr review_write_btn" onclick="return false;">상품 후기 작성<span class="sound_only"> 새 창</span></a>
    
    <!--a href="<?php echo $itemuse_list; ?>" id="itemuse_list" class="btn01">더보기</a-->
</div>

<!-- 상품 사용후기 시작 { -->
<div id="sit_use_list">

    <?php
    $thumbnail_width = 500;

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        $is_num     = $total_count - ($page - 1) * $rows - $i;
        $is_star    = get_star($row['is_score']);
        $is_name    = get_text($row['is_name']);
        $is_nick    = get_text($row['mb_nick']);
        $is_subject = conv_subject($row['is_subject'],80,"…");
        //$is_content = ($row['wr_content']);
        $is_content = get_view_thumbnail(conv_content($row['is_content'], 1), $thumbnail_width);
        $is_reply_name = !empty($row['is_reply_name']) ? get_text($row['is_reply_name']) : '';
        $is_reply_subject = !empty($row['is_reply_subject']) ? conv_subject($row['is_reply_subject'],50,"…") : '';
        $is_reply_content = !empty($row['is_reply_content']) ? get_view_thumbnail(conv_content($row['is_reply_content'], 1), $thumbnail_width) : '';
        $is_time    = substr($row['is_time'], 2, 8);
        $is_href    = './itemuselist.php?bo_table=itemuse&amp;wr_id='.$row['is_id'];
        $nickname = get_member($row['mb_id'], 'mb_name');
        $hash = md5($row['is_id'].$row['is_time'].$row['is_ip']);
		
		$sql2 = " select * from redtoy.g5_shop_item_use_image where is_id = '" . $row["is_id"] . "' order by is_file_idx ";
        $result2 = sql_query($sql2);
		
        if ($i == 0) echo '<ol id="sit_use_ol">';
    ?>

        <li class="sit_use_li">
            <div class="review_wr">
                <div class="sit_thum">
                    <img src="<?php echo G5_URL; ?>/shop/img/user_thumnail.png" width="50" height="50" alt="">
                    <!-- <?php echo get_itemuselist_thumbnail($row['it_id'], $row['is_content'], 50, 50); ?> -->
                </div>
                <div class="review_txt">
                    <div class="use_score">
                        <img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?php echo $is_star; ?>.png" alt="별<?php echo $is_star; ?>개">
                        <span><b><?php echo $is_star; ?></b>&#8201;/&#8201;5</span>
                    </div>
                    <?php echo redtoy_review_notice($row); ?>
                    <dl class="sit_use_dl">
                        <dt>작성자</dt>
                        <dd class="nick"><?php echo !empty($row['is_provided']) ? $is_name : $is_nick; ?></dd>
                        <dt>작성일</dt>
                        <dd><?php echo $is_time; ?></dd>
                    </dl>
                </div>
                <?php if (redtoy_review_admin() || $row['mb_id'] == $member['mb_id']) { ?>
                    <div class="sit_use_cmd">
                        <a href="<?php echo $itemuse_form."&amp;is_id={$row['is_id']}&amp;w=u"; ?>" class="itemuse_form btn01" onclick="return false;">수정</a>
                        <a href="<?php echo $itemuse_formupdate."&amp;is_id={$row['is_id']}&amp;w=d&amp;hash={$hash}"; ?>" class="itemuse_delete btn01">삭제</a>
                    </div>
                <?php } ?>

                <?php if( $is_reply_subject ){  //  사용후기 답변 내용이 있다면 ?>
                    <div class="sit_use_reply">
                        <div class="use_reply_icon">답변</div>
                        <div class="use_reply_tit">
                            <?php echo $is_reply_subject; // 답변 제목 ?>
                        </div>
                        <div class="use_reply_name">
                            <?php echo $is_reply_name; // 답변자 이름 ?>
                        </div>
                        <div class="use_reply_p">
                            <?php echo $is_reply_content; // 답변 내용 ?>
                        </div>
                    </div>
                <?php } //end if ?>
            </div>
            <div class="review_txt">
                <!-- 리뷰내용 텍스트만 -->
                <?php echo get_text(strip_tags($row['is_content']), 1); ?>
            </div>
            <?
                $img_rows = sql_num_rows($result2);
                if ($img_rows) {
            ?>

            <div class="review_photo">
                <!-- 리뷰 사진만 이미지 사이즈 70 누르면 리뷰 상세창 -->
				<?php
					for ($j=0; $row2=sql_fetch_array($result2); $j++)
					{
					    if ($row2["bf_file"]) {
				?>
				<a onclick="reviewDetailOpen(<?=$row2['is_id']?>, '<?php echo $row2['bf_file'];?>')">
					<img src="/review_image.php?bf_file=<?php echo $row2["bf_file"];?>" onError="this.src='/img/img_error.png'" title="" width="70"/>
				</a>
				<?php
					    }
					}
				?>
            </div>
            <?
                }
            ?>

            <!-- <div id="sit_use_con_<?php echo $i; ?>" class="sit_use_con">
                <div class="sit_use_p">
                    <?php echo $is_content; // 사용후기 내용 ?>
                </div>                
            </div> -->
        </li>

    <?php }

    if ($i > 0) echo '</ol>';

    if (!$i) echo '<p class="sit_empty">사용후기가 없습니다.</p>';
    ?>
</div>



<?php
echo itemuse_page($config['cf_mobile_pages'], $page, $total_page, G5_SHOP_URL."/itemuse.php?it_id=$it_id&amp;page=", "");
?>

<script>
function photo_all_popup_show(){
    $("#popupContainer4").hide();
    $("#popupContainer3").show();
}

$(document).ready(function(){
    // 리뷰작성 팝업창
    $(".review_write_btn").click(function(){
        <? if ($member["mb_id"]) { ?>
        $("#product_image").attr("src", "/product_image.php?it_id=<?=$it_id?>");
        $("#use_it_id").val("<?=$it_id?>");
        $("#is_subject").val("<?=$it["it_name"]?>");
        $("#it_brand").html("<?=$it["it_brand"]?>");
        $("#it_name").html("<?=$it["it_name"]?>");
        fileIndex = 0;
        dataTransfer = new DataTransfer();
        $(".review_write_popup").show();
        <? } else { ?>
        alert("사용후기는 회원만 작성이 가능합니다")
        <? } ?>
    });
    $("#photo_all_popup").on("click", () => { 
        photo_all_popup_show()
    });

    $(".itemuse_form").click(function(){
        $.ajax({
            url : this.href.replace("itemuseform", "itemedit"), 
            type : "POST"
        }).done((res) => {
            $("#sit_use_modify").html(res)
            dataTransfer = new DataTransfer();
            $(".review_edit_popup").show();
        })
        // window.open(this.href, "itemuse_form", "width=810,height=680,scrollbars=1");
        // return false;
    });

    $(".itemuse_delete").click(function(){
        if (confirm("정말 삭제 하시겠습니까?\n\n삭제후에는 되돌릴수 없습니다.")) {
            return true;
        } else {
            return false;
        }
    });

    // $(".sit_use_li_title").click(function(){
    //     var $con = $(this).siblings(".sit_use_con");
    //     if($con.is(":visible")) {
    //         $con.slideUp();
    //     } else {
    //         $(".sit_use_con:visible").hide();
    //         $con.slideDown(
    //             function() {
    //                 // 이미지 리사이즈
    //                 $con.viewimageresize2();
    //             }
    //         );
    //     }
    // });

    $(".pg_page").click(function(){
        $("#itemuse").load($(this).attr("href"));
        return false;
    });

    $("a#itemuse_list").on("click", function() {
        window.opener.location.href = this.href;
        self.close();
        return false;
    });
});
</script>
<!-- } 상품 사용후기 끝 -->