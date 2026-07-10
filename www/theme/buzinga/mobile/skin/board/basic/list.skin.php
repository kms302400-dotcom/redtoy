<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// 선택옵션으로 인해 셀합치기가 가변적으로 변함
$colspan = 2;

if ($is_checkbox) $colspan++;

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<h2 id="container_title"><?php echo ($board['bo_mobile_subject'] ? $board['bo_mobile_subject'] : $board['bo_subject']) ?></h2>

<!-- 게시판 목록 시작 -->
<div id="bo_list" class="<?php echo $bo_table ?>">

    <?php if ($is_category) { ?>
    <nav id="bo_cate">
        <h2><?php echo ($board['bo_mobile_subject'] ? $board['bo_mobile_subject'] : $board['bo_subject']) ?> 카테고리</h2>
        <ul id="bo_cate_ul">
            <?php echo $category_option ?>
        </ul>
    </nav>
    <?php } ?>
    
	<div id="bo_top_option">
	    <div id="bo_list_total">
	        <span>전체 <?php echo number_format($total_count) ?>건</span>
	        <?php echo $page ?> 페이지
	    </div>
	    
	    <?php if ($rss_href || $write_href) { ?>
		<ul class="<?php echo isset($view) ? 'view_is_list btn_top' : 'btn_top';?>">
		    <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
		    <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin">관리자</a></li><?php } ?>
		</ul>
		<?php } ?>
	</div>

    <form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">

    <?php if($bo_table == 'notice'){ ?>
    <ul class="list_tit">
        <?php if ($is_checkbox) { ?>
        <li class="tit_chk">
            <input type="checkbox" name="chk_all" value="1" id="chk_all" class="selec_chk">
            <label for="chk_all"><span></span></label>
        </li>
        <?php } ?>
        <li class="tit_date">일시</li>
        <li class="tit_subject">제목</li>
    </ul>
    <?php } ?>
    <div class="list_01">
        <ul>
            <?php
            for ($i=0; $i<count($list); $i++) {
            ?>
            <li class="<?php if ($list[$i]['is_notice']) echo "bo_notice"; ?>">
                <?php if ($is_checkbox) { ?>
                <div class="bo_chk">
                    <input type="checkbox" name="chk_wr_id[]" value="<?php echo $list[$i]['wr_id'] ?>" id="chk_wr_id_<?php echo $i ?>" class="selec_chk">
                    <label for="chk_wr_id_<?php echo $i ?>">
                        <span></span>
                        <b class="sound_only"><?php echo $list[$i]['subject'] ?></b>
                    </label>
                </div><?php } ?>

                <div class="bo_date">
                    <?php echo date("Y.m.d",strtotime($list[$i]['datetime'])) ?>
                </div>

                <div class="bo_subject">
                	<?php if ($list[$i]['is_notice']) { ?><strong class="notice_icon"><i class="fa fa-volume-up" aria-hidden="true"></i> 공지</strong><?php } ?> 
                    <?php if ($is_category && $list[$i]['ca_name']) { ?>
                    <a href="<?php echo $list[$i]['ca_name_href'] ?>" class="bo_cate_link"><?php echo $list[$i]['ca_name'] ?></a>
                    <?php } ?>

                    <a href="<?php echo $list[$i]['href'] ?>" class="bo_tit">
                        <?php echo $list[$i]['icon_reply']; ?>
                        <?php echo $list[$i]['subject'] ?>
                        <?php
                        // if ($list[$i]['link']['count']) { echo '['.$list[$i]['link']['count']}.']'; }
                        // if ($list[$i]['file']['count']) { echo '<'.$list[$i]['file']['count'].'>'; }

                        if (isset($list[$i]['icon_new'])) echo $list[$i]['icon_new'];
                        if (isset($list[$i]['icon_hot'])) echo $list[$i]['icon_hot'];
                        if (isset($list[$i]['icon_file'])) echo $list[$i]['icon_file'];
                        if (isset($list[$i]['icon_link'])) echo $list[$i]['icon_link'];
                        if (isset($list[$i]['icon_secret'])) echo $list[$i]['icon_secret'];
                        ?>
                    </a>
                    <!-- <div class="bo_info">
	                    <span class="sound_only">작성자</span><?php echo $list[$i]['name'] ?>
	                    <span class="bo_date"><?php if ($list[$i]['comment_cnt']) { ?><span class="sound_only">댓글</span><i class="fa fa-comment-dots" aria-hidden="true"></i><?php echo $list[$i]['comment_cnt']; ?><span class="sound_only">개</span><?php } ?> <i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo $list[$i]['datetime2'] ?></span>
	                </div> -->
                </div>
            </li><?php } ?>
            <?php if (count($list) == 0) { echo '<li class="empty_table">게시물이 없습니다.</li>'; } ?>
            
        </ul>
    </div>
    
	<!-- 페이지 -->
	<?php echo $write_pages; ?>

    <div class="bo_fx">
        <ul class="btn_bo_adm">
            <?php if ($list_href || $is_checkbox || $write_href) { ?>
                <?php if ($is_checkbox) { ?>
                <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value">삭제</button></li>
                <?php } 
            } ?>
            <li class="page_nav">
                <!-- pagenavigation -->
                <nav class="pg_wrap">
                    <span class="pg">
                        <span class="sound_only">열린</span>
                        <strong class="pg_current">1</strong>
                        <span class="sound_only">페이지</span>
                        <a href="" class="pg_page">2<span class="sound_only">페이지</span></a>
                        <a href="" class="pg_page">3<span class="sound_only">페이지</span></a>
                        <a href="" class="pg_page">4<span class="sound_only">페이지</span></a>
                        <a href="" class="pg_page pg_end">맨끝</a>
                    </span>
                </nav>
            </li>
            <?php if ($list_href || $is_checkbox || $write_href) { ?>
                <?php if ($is_checkbox) { ?>
                    <li><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value">이동</button></li>
                    <li><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value">복사</button></li>
                <?php } ?>
                <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b02">글쓰기</a></li><?php } ?>
            <?php } ?>
        </ul>
    </div>
    </form>
</div>

<?php if($is_checkbox) { ?>
<noscript>
<p>자바스크립트를 사용하지 않는 경우<br>별도의 확인 절차 없이 바로 선택삭제 처리하므로 주의하시기 바랍니다.</p>
</noscript>
<?php } ?>

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
    var f = document.fboardlist;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]")
            f.elements[i].checked = sw;
    }
}

function fboardlist_submit(f) {
    var chk_count = 0;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
            chk_count++;
    }

    if (!chk_count) {
        alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택복사") {
        select_copy("copy");
        return;
    }

    if(document.pressed == "선택이동") {
        select_copy("move");
        return;
    }

    if(document.pressed == "선택삭제") {
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
            return false;

        f.removeAttribute("target");
        f.action = "<?php echo G5_BBS_URL; ?>/board_list_update.php";
    }

    return true;
}

// 선택한 게시물 복사 및 이동
function select_copy(sw) {
    var f = document.fboardlist;

    if (sw == 'copy')
        str = "복사";
    else
        str = "이동";

    var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");

    f.sw.value = sw;
    f.target = "move";
    f.action = "<?php echo G5_BBS_URL; ?>/move.php";
    f.submit();
}
</script>
<?php } ?>
<!-- 게시판 목록 끝 -->
