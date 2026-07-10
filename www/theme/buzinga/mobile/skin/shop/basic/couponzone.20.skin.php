<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$sql_common = " from {$g5['g5_shop_coupon_zone_table']}
                where cz_start <= '".G5_TIME_YMD."'
                  and cz_end >= '".G5_TIME_YMD."' ";

$sql_order  = " order by cz_id desc ";

add_javascript('<script src="'.G5_JS_URL.'/shop.couponzone.js"></script>', 100);

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_SHOP_CSS_URL.'/style.css">', 0);
if (!G5_IS_MOBILE && $is_admin)
    echo '<div class="sct_admin"><a href="'.G5_ADMIN_URL.'/shop_admin/couponzonelist.php" class="btn_admin btn"><span class="sound_only">쿠폰존 관리</span><i class="fa fa-cog fa-spin fa-fw"></i></a></div>';


?>

<style>
    #container_title {display: none;}

    .couponzone_list ul li {width: 100%;}
    .coupon_info .cp_content>ul li {margin: 15px 4px;}
</style>

<!-- 이벤트쿠폰 안내 -->
<div class="coupon_info">
    <div class="cp_hd">상품쿠폰</div>

    <div style="text-align: center;">무통장결제 시 사용가능 (이외 결제수단 사용불가)</div>

    <section class="couponzone_list ">
        <?php
        $sql = " select * $sql_common and cz_type = '0' $sql_order ";
        $result = sql_query($sql);

        $coupon = '';

        for($i=0; $row=sql_fetch_array($result); $i++) {
            if(!$row['cz_file'])
                continue;

            $img_file = G5_DATA_PATH.'/coupon/'.$row['cz_file'];
            if(!is_file($img_file))
                continue;

            $subj = get_text($row['cz_subject']);

            switch($row['cp_method']) {
                case '0':
                    $sql3 = " select it_id, it_name from {$g5['g5_shop_item_table']} where it_id = '{$row['cp_target']}' ";
                    $row3 = sql_fetch($sql3);
                    $cp_target = '<a href="./item.php?it_id='.$row3['it_id'].'">'.get_text($row3['it_name']).'</a>';
                    break;
                case '1':
                    $sql3 = " select ca_id, ca_name from {$g5['g5_shop_category_table']} where ca_id = '{$row['cp_target']}' ";
                    $row3 = sql_fetch($sql3);
                    $cp_target = '<a href="./list.php?ca_id='.$row3['ca_id'].'">'.get_text($row3['ca_name']).'</a>';
                    break;
                case '2':
                    $cp_target = '주문금액할인';
                    break;
                case '3':
                    $cp_target = '배송비할인';
                    break;
            }

            // 다운로드 쿠폰인지
            $disabled = '';
            if(is_coupon_downloaded($member['mb_id'], $row['cz_id']))
                $disabled = ' disabled';

            $coupon .= '<li><div class="coupon_wr">'.PHP_EOL;
            $coupon .= '<div class="coupon_img"><img src="'.str_replace(G5_PATH, G5_URL, $img_file).'" alt="'.$subj.'"></div>'.PHP_EOL;
            $coupon .= '<div class="coupon_tit">'.$subj.'</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_date">기한 : 다운로드 후 '.number_format($row['cz_period']).'일</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_target">적용 : '.$cp_target.'</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_btn"><button type="button" class="coupon_download btn02'.$disabled.'" data-cid="'.$row['cz_id'].'">쿠폰다운로드</button></div>'.PHP_EOL;
            $coupon .= '</div></li>'.PHP_EOL;
        }

        if($coupon)
            echo '<ul>'.PHP_EOL.$coupon.'</ul>'.PHP_EOL;
        else
            echo '<p class="no_coupon">사용할 수 있는 쿠폰이 없습니다.</p>';
        ?>
    </section>

    <div class="cp_content pc">
        <ul>
            <li style="border-right: 1px solid #eee;">
                <ul>
                    <li>사용 방법</li>
                    <li>최대 할인 금액</li>
                    <li>유효 기간</li>
                    <li>유의 사항</li>
                </ul>
            </li>

            <li>
                <ul>
                    <li>쿠폰은 쿠폰존에서 직접 다운로드 받아 ‘무통장’ 선택 시 적용 가능합니다</li>
                    <li>최대 100,000원 할인, 50,000원 이상 무통장 결제시 적용 가능합니다</li>
                    <li>쿠폰 다운로드일로부터 30일 이내 사용 가능</li>
                    <li>쿠폰 다운로드 후 유효기간이 지나면 사용 할 수 없습니다</li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="cp_content mobile">
        <ul>
            <li>
                <div class="cc_hd">사용 방법</div>
                <div class="cc_sub">쿠폰은 쿠폰존에서 직접 다운로드 받아 ‘무통장’ 선택 시 적용 가능합니다</div>
            </li>

            <li>
                <div class="cc_hd">최대 할인 금액</div>
                <div class="cc_sub">최대 100,000원 할인, 50,000원 이상 무통장 결제시 적용 가능합니다</div>
            </li>

            <li>
                <div class="cc_hd">유효 기간</div>
                <div class="cc_sub">쿠폰 다운로드일로부터 30일 이내 사용 가능</div>
            </li>

            <li>
                <div class="cc_hd">유의 사항</div>
                <div class="cc_sub">쿠폰 다운로드 후 유효기간이 지나면 사용 할 수 없습니다</div>
            </li>
        </ul>
    </div>


    <div class="cp_ft">보유하고 있는 쿠폰 확인은 마이페이지 > 쿠폰에서 확인하실 수 있으며, 신규가입 회원이 아니거나 이미 쿠폰을 사용한 이력이 있는 경우 쿠폰은 보이지 않습니다.</div>

    <?php if ($is_member) {  ?>
    <?php } else {  ?>
        <div class="cp_reg"><a href="/bbs/register.php">가입하고 쿠폰 받기</a></div>
    <?php } ?>
</div>