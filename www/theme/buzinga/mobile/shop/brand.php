<?php
include_once('./_common.php');

if (!defined('_INDEX_')) define('_INDEX_', true);

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');
?>

<style>
    .brand_wr div {margin: 20px 0; font-size: 17px; font-weight: 600; }
    .brand_wr ul {display: flex; flex-wrap: wrap; justify-content: space-between;}
    .brand_wr ul li {margin: 0 0 10px; width: 24.5%; border: 1px solid #fff; background: #fff; border-radius: 10px;}
    .brand_wr ul li a {color: #fd5c63;}
    .brand_wr ul li img {width: 100%; border-radius: 10px;}

    .brand_all div {margin: 20px 0; font-size: 17px; font-weight: 600; }
    .brand_all ul {display: flex; flex-wrap: wrap; justify-content: space-between;}
    .brand_all ul li {margin: 0 0 10px; padding: 10px 0; width: 12%; text-align: center; color: #fd5c63; border: 1px solid #fff; background: #fff; border-radius: 5px;}
    .brand_all ul li:hover {text-align: center; color: #fff; background: #fd5c63; border: 1px solid #fd5c63; transition: 0.3s;}
    .brand_all ul li:hover a {color: #fff; transition: 0.3s;}

    @media (max-width: 970px){
        .brand_wr ul li {margin: 0 0 10px; width: 48.5%; border: 1px solid #fff; background: #fff; border-radius: 10px;}

        .brand_wr div {margin: 20px 0; font-size: 17px; font-weight: 600; }
        .brand_wr ul {display: flex; flex-wrap: wrap; justify-content: space-between;}
        .brand_wr ul li img {width: 100%;}

        .brand_all div {margin: 20px 0; font-size: 17px; font-weight: 600; }
        .brand_all ul {display: flex; flex-wrap: wrap; justify-content: space-between;}
        .brand_all ul li {width: 48%;}
    }
</style>

<section class="idx_only">
    <h1 id="container_title">브랜드</h1>

    <div class="brand_wr">
        <div>인기 브랜드</div>

        <ul>
            <li><a href="/shop/search.php?q=zalo"><img src="/main_img/brand/01.png" alt="잘로"></a></li>
            <li><a href="/shop/search.php?q=새티스파이어"><img src="/main_img/brand/02.png" alt="새티스파이어"></a></li>
            <li><a href="/shop/search.php?q=redholics"><img src="/main_img/brand/03.png" alt="레드홀릭스"></a></li>
            <li><a href="/shop/search.php?q=사가미"><img src="/main_img/brand/04.png" alt="사가미"></a></li>
            <li><a href="/shop/search.php?q=레텐"><img src="/main_img/brand/05.png" alt="레텐"></a></li>
            <li><a href="/shop/search.php?q=유니더스"><img src="/main_img/brand/06.png" alt="유니더스"></a></li>
            <li><a href="/shop/search.php?q=펀팩토리"><img src="/main_img/brand/07.png" alt="펀팩토리"></a></li>
            <li><a href="/shop/search.php?q=러벤스"><img src="/main_img/brand/08.png" alt="러벤스"></a></li>
            <li><a href="/shop/search.php?q=Le+Desir"><img src="/main_img/brand/09.png" alt="바치"></a></li>
            <li><a href="/shop/search.php?q=업코"><img src="/main_img/brand/10.png" alt="업코"></a></li>
            <li><a href="/shop/search.php?q=엑상스"><img src="/main_img/brand/11.png" alt="엑상스"></a></li>
            <li><a href="/shop/search.php?q=b-vibe"><img src="/main_img/brand/12.png" alt="비바이브"></a></li>
            <li><a href="/shop/search.php?q=우머나이저"><img src="/main_img/brand/13.png" alt="우머나이저"></a></li>
            <li><a href="/shop/search.php?q=르완드"><img src="/main_img/brand/14.png" alt="르완드"></a></li>
            <li><a href="/shop/search.php?q=롬프"><img src="/main_img/brand/15.png" alt="롬프"></a></li>
            <li><a href="/shop/search.php?q=로마"><img src="/main_img/brand/16.png" alt="로마"></a></li>
        </ul>
    </div>

    <div class="brand_all">
        <div>입점 브랜드 전체보기</div>

        <ul>
            <li><a href="/shop/search.php?q=갈락쿠">갈락쿠</a></li>
            <li><a href="/shop/search.php?q=러벤스">러벤스</a></li>
            <li><a href="/shop/search.php?q=레로">레로</a></li>
            <li><a href="/shop/search.php?q=레텐">레텐</a></li>
            <li><a href="/shop/search.php?q=로마">로마</a></li>
            <li><a href="/shop/search.php?q=롬프">롬프</a></li>
            <li><a href="/shop/search.php?q=사가미">사가미</a></li>
            <li><a href="/shop/search.php?q=샷츠">샷츠</a></li>
            <li><a href="/shop/search.php?q=안셀">안셀</a></li>
            <li><a href="/shop/search.php?q=아스트로글라이드">아스트로글라이드</a></li>
            <li><a href="/shop/search.php?q=업코">업코</a></li>
            <li><a href="/shop/search.php?q=우머나이저">우머나이저</a></li>
            <li><a href="/shop/search.php?q=새티스파이어">새티스파이어</a></li>
            <li><a href="/shop/search.php?q=잘로">잘로</a></li>
            <li><a href="/shop/search.php?q=케어허">케어허</a></li>
            <li><a href="/shop/search.php?q=플레이보이">플레이보이</a></li>
            <li><a href="/shop/search.php?q=펀팩토리">펀팩토리</a></li>
            <li><a href="/shop/search.php?q=스바콤">스바콤</a></li>
            <li><a href="/shop/search.php?q=KMP">KMP</a></li>
            <li><a href="/shop/search.php?q=문헤일로">문헤일로</a></li>
            <li><a href="/shop/search.php?q=Rocks-Off">Rocks-Off</a></li>
            <li><a href="/shop/search.php?q=프리티러브">프리티러브</a></li>
            <li><a href="/shop/search.php?q=밤부">밤부</a></li>
            <li><a href="/shop/search.php?q=총각시리즈">총각시리즈</a></li>
            <li><a href="/shop/search.php?q=ToysHeart">ToysHeart</a></li>
            <li><a href="/shop/search.php?q=에이스제약">에이스제약</a></li>
            <li><a href="/shop/search.php?q=LUST">LUST</a></li>
            <li><a href="/shop/search.php?q=매직아이즈">매직아이즈</a></li>
            <li><a href="/shop/search.php?q=Care">Care</a></li>
            <li><a href="/shop/search.php?q=한국라텍스">한국라텍스</a></li>
            <li><a href="/shop/search.php?q=유니더스">유니더스</a></li>
            <li><a href="/shop/search.php?q=findom">findom</a></li>
        </ul>
    </div>
</section>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>
