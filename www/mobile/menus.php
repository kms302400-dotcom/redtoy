<?
    function path_check($path) {
        if ($path == $_SERVER["REQUEST_URI"]) {
            return ' style="padding: 5px 10px; font-weight:600; color: #fff; background: #fd5c63; border: none;"';
        }
    }
?>
<div class="type_ct">
    <ul>
        <li><a href="/shop/type-<?=$type?>" <?=path_check("/shop/type-$type")?>>전체</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=40" <?=path_check("/shop/type-$type?cate=40")?>>남성토이</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=30" <?=path_check("/shop/type-$type?cate=30")?>>여성토이</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=60" <?=path_check("/shop/type-$type?cate=60")?>>애널토이</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=90" <?=path_check("/shop/type-$type?cate=90")?>>커플토이</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=50" <?=path_check("/shop/type-$type?cate=50")?>>BDSM</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=70" <?=path_check("/shop/type-$type?cate=70")?>>속옷</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=10" <?=path_check("/shop/type-$type?cate=10")?>>콘돔</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=20" <?=path_check("/shop/type-$type?cate=20")?>>윤활제</a></li>
        <li><a href="/shop/type-<?=$type?>?cate=80" <?=path_check("/shop/type-$type?cate=80")?>>세척/관리도구</a></li>
    </ul>
</div>