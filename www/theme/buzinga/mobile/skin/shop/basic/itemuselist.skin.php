<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>

<!-- 전체 상품 사용후기 목록 시작 { -->
<form method="get" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<div id="sps_sch">
    <div class="sch_wr">
        <label for="sfl" class="sound_only">검색항목</label>
        <select name="sfl" id="sfl" required>
            <option value="">선택</option>
            <option value="b.it_name"   <?php echo get_selected($sfl, "b.it_name"); ?>>상품명</option>
            <option value="a.it_id"     <?php echo get_selected($sfl, "a.it_id"); ?>>상품코드</option>
            <option value="a.is_subject"<?php echo get_selected($sfl, "a.is_subject"); ?>>후기제목</option>
            <option value="a.is_content"<?php echo get_selected($sfl, "a.is_content"); ?>>후기내용</option>
            <option value="a.is_name"   <?php echo get_selected($sfl, "a.is_name"); ?>>작성자명</option>
            <option value="a.mb_id"     <?php echo get_selected($sfl, "a.mb_id"); ?>>작성자아이디</option>
        </select>
        <span class="bar"></span>
        <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
        <input type="text" name="stx" placeholder="검색어를 입력해주세요." value="<?php echo $stx; ?>" id="stx" required class="sch_input" size="10">
        <button type="submit" value="검색" class="sch_btn"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">검색</span></button>
    </div>
    <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>">전체보기</a>
</div>
</form>

<div id="sps">

    <!-- <p><?php echo $config['cf_title']; ?> 전체 사용후기 목록입니다.</p> -->
    <?php
    $thumbnail_width = 500;

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        $num = $total_count - ($page - 1) * $rows - $i;
        $star = get_star($row['is_score']);

        $is_content = get_view_thumbnail(conv_content($row['is_content'], 1), $thumbnail_width);
        $member_nick = get_member($row['mb_id']);
        $row2 = get_shop_item($row['it_id'], true);
        $it_href = shop_item_url($row['it_id']);
        $nickname = get_member($row['mb_id'], 'mb_nick');
        if ($i == 0) echo '<ol>';

        $sql2 = " select * from redtoy.g5_shop_item_use_image where is_id = '" . $row["is_id"] . "' order by is_file_idx ";
        $result2 = sql_query($sql2);
    ?>
    <li>
        <div class="sps_img">
            <a href="<?php echo $it_href; ?>">
                <?php echo get_itemuselist_thumbnail($row['it_id'], $row['is_content'], 100, 100); ?>
                <span><?php echo $row2['it_name']; ?></span>
            </a>
        </div>

        <section class="sps_section">
            <?php
            $img_html = '';

            // 성인인증 여부 체크
            $is_adult_ok = false;
            if ($_SESSION['ss_mb_id']) {
                $mb = get_member($_SESSION['ss_mb_id']);
                if ($mb['mb_adult'] == 1) {
                    $is_adult_ok = true;
                }
            } else {
                if ($_SESSION['ss_cert_adult'] == 'OK') {
                    $is_adult_ok = true;
                }
            }

            // 성인 컨텐츠 여부
            $is_adult_content = empty($row['it_7_subj']);

            while ($row3 = sql_fetch_array($result2)) {
                if ($row3["bf_file"]) {
                    $is_id = (int)$row3['is_id'];
                    $bf_file = htmlspecialchars($row3['bf_file'], ENT_QUOTES);

                    if ($is_adult_content && !$is_adult_ok) {
                        $img_html .= '<img src="/img/19/19_img_thumb.png" width="70" />';
                    } else {
                        $img_html .= '<a onclick="reviewDetailOpen(' . $is_id . ')"><img src="/review_image.php?bf_file=' . $bf_file . '" onerror="this.src=\'/img/img_error.png\'" width="70" /></a>';
                    }
                    // $img_html .= '<a onclick="reviewDetailOpen(' . $is_id . ')"><img src="/review_image.php?bf_file=' . $bf_file . '" onerror="this.src=\'/img/img_error.png\'" width="70" /></a>';
                }
            }
            ?>
        	<h2><a href="<?php echo $it_href; ?>"><?php echo get_text($row['is_subject']); ?></a></h2> 
            <div class="rev_img">
                <?= $img_html ?>
            </div>

            <div id="sps_con_<?php echo $i; ?>" class="review_bt_cnt">
                <?php echo $is_content; // 사용후기 내용 ?>

                <?php
                if( !empty($row['is_reply_subject']) ){     //사용후기 답변이 있다면
                    $is_reply_content = get_view_thumbnail(conv_content($row['is_reply_content'], 1), $thumbnail_width);
                ?>
                <div class="sps_reply">
                    <div class="sps_img">
                        <a href="<?php echo $it_href; ?>">
                            <?php echo get_itemuselist_thumbnail($row['it_id'], $row['is_reply_content'], 50, 50); ?>
                            <span><?php echo $row2['it_name']; ?></span>
                        </a>
                    </div>

                    <section>
                        <h2 class="is_use_reply"><?php echo get_text($row['is_reply_subject']); ?></h2>
                        <div class="sps_dl">
                            <?php echo $row['is_reply_name']; ?>
                        </div>
                        <div id="sps_con_<?php echo $i; ?>_reply" style="display:none;">
                            <?php echo $is_reply_content; // 사용후기 답변 내용 ?>
                        </div>
                    </section>
                </div>
                <?php } //end if ?>

            </div>

            <div class="sps_con_btn">
                <button data-target="sps_con_<?php echo $i; ?>">
                    <span>리뷰 더보기</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11.001" height="6.208" viewBox="0 0 11.001 6.208">
                        <path id="chevron-down" d="M7.646,14.646a.5.5,0,0,0,.708.708ZM13,10l.354.354L13.707,10l-.353-.354ZM8.354,4.646a.5.5,0,0,0-.708.708Zm0,10.708,5-5-.708-.708-5,5Zm5-5.708-5-5-.708.708,5,5Z" transform="translate(15.501 -7.499) rotate(90)"></path>
                    </svg>
                </button>
            </div>
        </section>

        <div class="sps_dl">
            <p class="sps_star"><img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?php echo $star; ?>.png" alt="별<?php echo $star; ?>개" width="80"></p>
            <p><?php echo $nickname['mb_nick']; ?></p>
            <p><?php echo substr($row['is_time'],2,8); ?></p>
        </div>

    </li>
    <?php }
    if ($i > 0) echo '</ol>';
    if ($i == 0) echo '<p id="sps_empty">자료가 없습니다.</p>';
    ?>
</div>

<!-- 포토리뷰 상세보기 -->
<div id="popupContainer4" class="popup-container review_detail" >
    <div class="popup">
        <span id="closePopup4" class="close-btn">×</span>
        <div>
            <?php
            include_once(G5_MSHOP_SKIN_PATH.'/itemusedetail.skin.php');
            ?>
        </div>
    </div>
</div>

<?php echo get_paging($config['cf_mobile_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
$(function(){
    function checkReviewLineClamp() {
        // 리뷰 내용 2줄 넘어가면 더보기 버튼 노출
        $('.review_bt_cnt').each(function () {
            const $content = $(this);
            const $buttonWrap = $content.next('.sps_con_btn');

            const lineHeight = parseFloat($content.css('line-height')) || 22;
            const maxHeight = lineHeight * 2;

            // 버튼 초기화
            $buttonWrap.hide();

            // 줄 수 초과 시 버튼 보이기
            if ($content[0].scrollHeight > maxHeight) {
                $buttonWrap.show();
            }
        });
    }

    $(window).on('load resize', function () {
        checkReviewLineClamp();
    });

    // 사용후기 더보기
    $(".sps_con_btn button").click(function () {
        var $btn = $(this);
        var targetId = $btn.data("target");
        var $content = $("#" + targetId);

        $content.toggleClass("expanded");

        if ($content.hasClass("expanded")) {
            // 열기
            $content.css("-webkit-line-clamp", "unset");
            $btn.find("span").html('리뷰 접기');
            $btn.addClass("active");

            // 이미지 리사이즈 함수가 있다면 실행
            if (typeof $content.viewimageresize2 === "function") {
                $content.viewimageresize2();
            }
        } else {
            // 닫기
            $content.css("-webkit-line-clamp", "2");
            $btn.find("span").html('리뷰 더보기');
            $btn.removeClass("active");
        }
    });

    document.getElementById('closePopup4').addEventListener('click', function() {
        document.getElementById('popupContainer4').style.display = 'none';
    });
});

function reviewDetailOpen(is_id, bf_file){
        var photo_list = $("#sit_use_photo_all").find("ul").html()
        $("#detail_photo_list_all").html(photo_list);
        
        $("#detail_photo_list_all img").each((i, v) => {
            $(v).on("error", (v) => {
                $(v.srcElement).attr("src", "/img/img_error.png");
            })
        })
        
        $.ajax({
            url : "/shop/ajax.review.php"
            , type : "POST"
            , data : { "is_id" : is_id }
        }).done((res) => {
            try {
                var json = JSON.parse(res)
                
                $("#detail_score_image").attr("src", "/shop/img/s_star"+json.is_score+".png");
                $("#detail_score").html(json.is_score);
                $("#detail_username").html(json.is_name);
                $("#detail_date").html(json.is_time.split(' ')[0]);
                $("#detail_content").html(json.is_content);
                $("#detail_image_list").html("");
                
                var position = 0;
                json.image_list.forEach((v) => {
                    $("#detail_image_list").append("<img src='/review_image.php?bf_file=" + v.bf_file + "' onclick='viewDetailImage(this)' width='70' height='70' />");
                    if (position == 0 || v.bf_file == bf_file) {
                        $('#detail_image_list img').addClass("active");
                        $("#detail_image").attr("src", "/review_image.php?bf_file=" + v.bf_file);
                    }
                    position++;
                })
                
                if (bf_file) {
                    $("#detail_image_list img").removeClass("active");
                    $('#detail_image_list img[src$="/review_image.php?bf_file=' + bf_file + '"]').addClass("active");
                }
                
                $("#detail_image_list img").each((i, v) => {
                    $(v).on("error", (v) => {
                        $(v.srcElement).attr("src", "/img/img_error.png");
                    })
                })
                
                $('#popupContainer3').hide();
                $('#popupContainer4').show();
            } catch {
                //
            }
        })
    }

    function viewDetailImage(obj) {
        $("#detail_image").attr("src", $(obj).attr("src"));
        $("#detail_image_list img").removeClass('active');
        $(obj).addClass('active');
    }
</script>
<!-- } 전체 상품 사용후기 목록 끝 -->