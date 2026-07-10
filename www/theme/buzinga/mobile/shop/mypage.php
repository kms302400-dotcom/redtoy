<?php
include_once('./_common.php');

$g5['title'] = 'MY PAGE';

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');

$client_id = "4KFtr2owsnAgfnF3IaM9"; // 네이버 개발자센터에서 발급받은 CLIENT ID
$client_secret = "hSXUlJoTDH";// 네이버 개발자센터에서 발급받은 CLIENT SECRET
$encText = urlencode("https://redtoy.co.kr/bbs/recommid.php?rid=".$member['mb_id']);
$postvars = "url=" . $encText;
//$url = "https://openapi.naver.com/v1/util/shorturl";
//$is_post = true;
$url = "https://openapi.naver.com/v1/util/shorturl?url=" . $encText;
$is_post = false;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, $is_post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch,CURLOPT_POSTFIELDS, $postvars);
$headers = array();
$headers[] = "X-Naver-Client-Id: " . $client_id;
$headers[] = "X-Naver-Client-Secret: " . $client_secret;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($status_code == 200) {
    $re = json_decode($response);
} else {
    // echo "Error 내용:" . $response;
}


foreach($re as $k1 => $v1){
    if(!empty($v1)) {
        foreach ($v1 as $kk => $vv) {
            if ($kk == 'url') {
                $result = $vv;
            }
        }
    }
}


$encText = urlencode("https://redtoy.co.kr/bbs/check.php?rid=".$member['mb_id']);
$postvars = "url=" . $encText;
//$url = "https://openapi.naver.com/v1/util/shorturl";
//$is_post = true;
$url = "https://openapi.naver.com/v1/util/shorturl?url=" . $encText;
$is_post = false;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, $is_post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch,CURLOPT_POSTFIELDS, $postvars);
$headers = array();
$headers[] = "X-Naver-Client-Id: " . $client_id;
$headers[] = "X-Naver-Client-Secret: " . $client_secret;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($status_code == 200) {
    $res = json_decode($response);
} else {
    // echo "Error 내용:" . $response;
}


foreach($res as $k1 => $v1){
    if(!empty($v1)) {
        foreach ($v1 as $kk => $vv) {
            if ($kk == 'url') {
                $result1 = $vv;
            }
        }
    }
}


// 쿠폰
$cp_count = 0;
$sql = " select cp_id
            from {$g5['g5_shop_coupon_table']}
            where mb_id IN ( '{$member['mb_id']}', '전체회원' )
              and cp_start <= '".G5_TIME_YMD."'
              and cp_end >= '".G5_TIME_YMD."' ";
$res = sql_query($sql);

for($k=0; $cp=sql_fetch_array($res); $k++) {
    if(!is_used_coupon($member['mb_id'], $cp['cp_id']))
        $cp_count++;
}

?>

<style>
    #container {max-width:none;padding:0;}
    #container_title {display:none}
</style>


<!-- 마이페이지 상단 공통 -->
<div id="mp_top">
    <h1>마이 페이지</h1>
    <ul class="mp_info">
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/couponzone.php">            
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_coupon_icon.png" alt="">
                <p>쿠폰 <span><?=number_format(get_coupon_count($member["mb_id"]))?></span> 장</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage3.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_point_icon.png" alt="">
                <p>포인트 <span><?=number_format(get_point_sum($member["mb_id"]))?></span> P</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/wishlist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_wish_icon.png" alt="">
                <p>찜한 상품 <span><?=number_format(get_wishlist_datas_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
        <li>
            <a href="/bbs/qalist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_qa_icon.png" alt="">
                <p>1:1 문의 <span><?=number_format(get_qna_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
    </ul>
</div>

<div id="mypage_wrap">
    <div id="tab_menu">
        <ul>
            <li class="active"><a href="/shop/mypage.php">주문 배송 조회</a></li>
            <!-- 회원정보 수정 페이지 임시로 만들어놓음 -->
            <li><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage2.php">회원 정보 수정</a></li>
            <li><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage4.php">리뷰 관리</a></li>
            <li><a href="/bbs/qalist.php">1:1 문의</a></li>
        </ul>
    </div>
    <?
        
        $sql = " select od_status, count(*) as cnt
                    from {$g5['g5_shop_order_table']}
                    where mb_id = '{$member['mb_id']}'
                    group by od_status
                    order by od_status desc";
        $result = sql_query($sql);
        $response = array(
            "주문" => 0
            , "결제" => 0
            , "준비" => 0
            , "배송" => 0
            , "완료" => 0
        );
        for ($i=0; $row=sql_fetch_array($result); $i++)
        {
            $response[$row['od_status']] = $row['cnt'];
        }
    ?>

    <div id="smb_my">
        <ul class="order_stat">
            <li>
                <p>입금 대기</p>
                <span><?=$response["주문"]?></span>
            </li>
            <li>
                <p>결제 완료</p>
                <span><?=$response["결제"]?></span>
            </li>
            <li>
                <p>배송 준비</p>
                <span><?=$response["준비"]?></span>
            </li>
            <li>
                <p>배송중</p>
                <span><?=$response["배송"]?></span>
            </li>
            <li>
                <p>배송 완료</p>
                <span><?=$response["완료"]?></span>
            </li>
        </ul>

        <!-- 주문내역 -->
        <section id="smb_my_od">
            <div class="filter_wrap">
                <select id="months" onchange="month_change(this.value);">
                    <option value="3">3개월 전</option>
                    <option value="6">6개월 전</option>
                    <option value="12">12개월 전</option>
                    <option value="">전체</option>
                </select>
                <select id="orders" onchange="order_change(this.value);">
                    <option value="0">최신순</option>
                    <option value="1">오래된순</option>
                </select>
            </div>
            
            <script>
                <?
                    $months = isset($_REQUEST["months"]) ? safe_replace_regex($_REQUEST["months"], "number") : "";
                    switch($months) {
                        case 3 :
                            $months = "3";
                            break;
                        case 6 :
                            $months = "6";
                            break;
                        case 12 :
                            $months = "12";
                            break;
                        default :
                            $months = "";
                            break;
                    }
                    $orders = isset($_REQUEST["orders"]) ? safe_replace_regex($_REQUEST["orders"], "number") : "";
                    switch($orders) {
                        case 1 :
                            $orders = "1";
                            break;
                        default :
                            $orders = "0";
                            break;
                    }
                ?>
                $("#months").val("<?=$months?>")
                $("#orders").val("<?=$orders?>")
                
                function order_change(ords) {
                    location.href = "?months=<?=$months?>&orders=" + ords
                }
                
                function month_change(mons) {
                    location.href = "?orders=<?=$orders?>&months=" + mons
                }
            </script>

            <?php
            // 최근 주문내역
            define("_ORDERINQUIRY_", true);

            $limit = " limit 0, 4 ";
            $where = "";
            $order = " od_id desc ";
            if ($months) $where .= " AND od_time >= now() - interval $months month ";
            if ($orders) $order = " od_id asc ";
            include G5_MSHOP_PATH.'/orderinquiry.sub.php';
            ?>
        </section>
        <div class="more_btn"><a href="<?php echo G5_SHOP_URL; ?>/orderinquiry.php">주문내역 더보기</a></div>

        <!-- logout -->
        <div class="out_wrap">
            <!-- 로그아웃 -->
            <ul>
                <?php if ($is_admin) {  ?>
                    <li class="adm"><a href="<?php echo G5_ADMIN_URL ?>/shop_admin/">관리자</a></li>
                    <li class="theme"><a href="<?php echo G5_THEME_ADM_URL ?>" target="_blank">테마관리</a></li>
                <?php } ?>
                <li style="color:#777; cursor: pointer;" onclick="member_leave()">회원탈퇴</li>
                <li><a href="<?php echo G5_BBS_URL ?>/logout.php">로그아웃</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
    $(function() {
        $(".btn_my_if").click(function() {
            $(".my_info").toggle();
        });
    });

    $(function (){
        $(".tab_con>li").hide();
        $(".tab_con>li:first").show();
        $(".tab_tit li button").click(function(){
            $(".tab_tit li button").removeClass("selected");
            $(this).addClass("selected");
            $(".tab_con>li").hide();
            $($(this).attr("rel")).show();
        });
    });

$(function() {
    $(".win_coupon").click(function() {
        var new_win = window.open($(this).attr("href"), "win_coupon", "left=100,top=100,width=700, height=600, scrollbars=1");
        new_win.focus();
        return false;
    });
});

function member_leave()
{
    if (confirm("정말 회원에서 탈퇴 하시겠습니까?"))
        location.href = "<?php echo G5_BBS_URL ?>/member_confirm.php?url=member_leave.php";
}

$("#container_wr").addClass("container_wr");

</script>

    <script>
        function copyToClipboard(element) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val($(element).text()).select();
            document.execCommand("copy");
            alert('복사되었습니다.');
            $temp.remove();
        }

    </script>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>