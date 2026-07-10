<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_MSHOP_SKIN_URL.'/style.css">', 0);
?>

<!-- 포토후기 전체 시작 { -->
<div id="sit_use_photo_all" class="">
    <h1>포토후기 전체 보기</h1>
    <ul>
		<?php
			for($i = 0; $i < count($imgArrayRows); $i++) {
		?>
        <li onclick="reviewDetailOpen(<?=$imgArrayRows[$i]["is_id"]?>)">
            <img src="/review_image.php?bf_file=<?=$imgArrayRows[$i]["bf_file"]?>" width="70" height="70" alt="test">
        </li>
		<?php
			}
			if (!$i) echo '등록된 이미지가 없습니다.';
		?>
    </ul>
    <span class="more_btn">더보기</span>
</div>

<script type="text/javascript">
function fitemuse_submit(f)
{
    <?php echo $editor_js; ?>

    return true;
}
</script>
<!-- } 포토후기 전체 끝 -->