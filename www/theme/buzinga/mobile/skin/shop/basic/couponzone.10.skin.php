<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_SHOP_CSS_URL.'/style.css">', 0);
?>

<style>
    /* #container_title {display: none;} */
</style>

<!-- 이벤트쿠폰 안내 -->
<div class="coupon_info">
    <div class="cp_regist">
        <p>레드토이 회원님께만 드리는 특별한 할인 혜택!</p>
        <form action="">
            <input type="text" placeholder="쿠폰 번호를 입력하세요.">
            <button>쿠폰등록</button>
        </form>
        <span>메일, 인쇄쿠폰 등에서 받으신 쿠폰번호를 입력해주세요.</span>
    </div>
    <!-- <div class="cp_hd"><?php echo $config['cf_title']; ?>가 처음인<br>당신을 위한 쿠폰 혜택</div>
    <div class="cp_sub">신규회원만 받을 수 있는 특별한 혜택!<br>아래에서 쿠폰을 다운로드받아 할인 혜택으로 첫 쇼핑을 가볍게 시작해 보세요</div> -->

    <section class="couponzone_list ">
        <?php
        $sql = " select * $sql_common and cz_type = '0' $sql_order ";
        $result = sql_query($sql);

        $coupon = '';

        for($i=0; $row=sql_fetch_array($result); $i++) {
            // echo "<pre>";
            // print_r($row);
            // echo "</pre>";
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
            $btn_text = '쿠폰 받기';
            if(is_coupon_downloaded($member['mb_id'], $row['cz_id'])){
                $disabled = ' disabled';
                $btn_text = '보유 쿠폰';
            }

            $coupon .= '<li><div class="coupon_wr '.$disabled.'">'.PHP_EOL;
            // $coupon .= '<div class="coupon_img"><img src="'.str_replace(G5_PATH, G5_URL, $img_file).'" alt="'.$subj.'"></div>'.PHP_EOL;
            $coupon .= '<div class="coupon_tit">'.$subj.'</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_price">'.number_format($row['cp_price']).'</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_date">'.date("Y년 m월 d일", strtotime($row['cz_end'])).' 까지</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_min">'.number_format($row['cp_minimum']).'원 이상 구매시</div>'.PHP_EOL;
            $coupon .= '<div class="coupon_btn"><button type="button" class="coupon_download btn02'.$disabled.'" data-cid="'.$row['cz_id'].'">'.$btn_text.'</button></div>'.PHP_EOL;
            $coupon .= '</div></li>'.PHP_EOL;
        }

        if($coupon)
            echo '<ul>'.PHP_EOL.$coupon.'</ul>'.PHP_EOL;
        else
            echo '<p class="no_coupon">사용할 수 있는 쿠폰이 없습니다.</p>';
        ?>
    </section>

    <!-- <div class="cp_content pc">
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
    </div> -->


    <div class="cpz_ft">
        <h4>참고하세요!</h4>
        <p>1. 쿠폰등록은 1인 1개만 등록 가능합니다.</p>
        <p>2. 쿠폰마다 사용기간이 상이하니 기간 내 사용해 주세요.</p>
    </div>

    <?php if ($is_member) {  ?>
    <?php } else {  ?>
        <div class="cp_reg"><a href="/bbs/register.php">가입하고 쿠폰 받기</a></div>
    <?php } ?>
</div>