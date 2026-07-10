<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
require_once(G5_SHOP_PATH . '/settle_' . $default['de_pg_service'] . '.inc.php');

require_once(G5_SHOP_PATH . '/settle_kakaopay.inc.php');
$READY_API_URL = "_2_ready.php";
if ($default['de_inicis_lpay_use'] || $default['de_inicis_kakaopay_use']) {   //이니시스 Lpay 또는 이니시스 카카오페이 사용시
    require_once(G5_SHOP_PATH . '/inicis/lpay_common.php');
}

if (function_exists('is_use_easypay') && is_use_easypay('global_nhnkcp')) {  // 타 PG 사용시 NHN KCP 네이버페이 사용이 설정되어 있다면
    require_once(G5_SHOP_PATH . '/kcp/global_nhn_kcp.php');
}

// 결제대행사별 코드 include (스크립트 등)
require_once(G5_SHOP_PATH . '/danal/orderform.1.php');
//require_once(G5_SHOP_PATH . '/mainpay/orderform.1.php');

if ($default['de_inicis_lpay_use'] || $default['de_inicis_kakaopay_use']) {   //이니시스 L.pay 사용시
    require_once(G5_SHOP_PATH . '/inicis/lpay_form.1.php');
}

if (function_exists('is_use_easypay') && is_use_easypay('global_nhnkcp')) {  // 타 PG 사용시 NHN KCP 네이버페이 사용이 설정되어 있다면
    require_once(G5_SHOP_PATH . '/kcp/global_nhn_kcp_form.1.php');
}

if ($is_kakaopay_use) {
    require_once(G5_SHOP_PATH . '/kakaopay/orderform.1.php');
}

$_SESSION["payinfo"] = null;
?>

<form name="MAINPAY_FORM" id="MAINPAY_FORM" method="post" action="<?php echo $order_action_url; ?>" autocomplete="off">
    <input type="hidden" name="adult_check" value="<?php echo $_SESSION["ss_cert_adult"]; ?>" />
    
    <!-- 주문상품 확인 시작 { -->
    <div class="tbl_head01 tbl_wrap">
        <h2>주문 상품 <span><span class="r_txt" id="row_count"></span>건</span></h2>
        <table id="sod_list">
            <thead>
            <tr>
                <th scope="col">상품</th>
                <th scope="col">가격</th>
                <th scope="col">수량</th>
                <th scope="col">총계</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $tot_point = 0;
            $tot_sell_price = 0;
            $goods = $goods_it_id = "";
            $goods_count = -1;

            // $s_cart_id 로 현재 장바구니 자료 쿼리
            $sql = " select a.ct_id,
                    a.it_id,
                    a.it_name,
                    if (a.io_type = 0, (a.ct_price + a.io_price) * a.ct_qty, (a.io_price * a.ct_qty)) as price,
                    if (a.io_type = 0, (a.ct_price + a.io_price), (a.io_price)) as sell_price,
                    a.ct_point * a.ct_qty as point,
                    a.ct_qty,
                    a.ct_status,
                    a.ct_send_cost,
                    a.it_sc_type,
                    b.ca_id,
                    b.ca_id2,
                    b.ca_id3,
                    b.it_notax,
                    a.io_id
                from {$g5['g5_shop_cart_table']} a left join {$g5['g5_shop_item_table']} b on ( a.it_id = b.it_id )
                where a.od_id = '$s_cart_id'
                and a.ct_select = '1' ";
            //$sql .= " group by a.it_id ";
            $sql .= " order by a.ct_id ";
            $result = sql_query($sql);

            $good_info = '';
            $it_send_cost = 0;
            $it_cp_count = 0;

            $comm_tax_mny = 0; // 과세금액
            $comm_vat_mny = 0; // 부가세
            $comm_free_mny = 0; // 면세금액
            $tot_tax_mny = 0;

            for ($i = 0; $row = sql_fetch_array($result); $i++) {
                if (!$goods) {
                    //$goods = addslashes($row[it_name]);
                    //$goods = get_text($row[it_name]);
                    $goods = preg_replace("/\'|\"|\||\,|\&|\;/", "", $row['it_name']);
                    $goods_it_id = $row['it_id'];
                }
                $goods_count++;

                // 에스크로 상품정보
                if ($default['de_escrow_use']) {
                    if ($i > 0)
                        $good_info .= chr(30);
                    $good_info .= "seq=" . ($i + 1) . chr(31);
                    $good_info .= "ordr_numb={$od_id}_" . sprintf("%04d", $i) . chr(31);
                    $good_info .= "good_name=" . addslashes($row['it_name']) . chr(31);
                    $good_info .= "good_cntx=" . $row['ct_qty'] . chr(31);
                    $good_info .= "good_amtx=" . $row['sell_price'] . chr(31);
                }

                $image = get_it_image($row['it_id'], 80, 80);

                $it_name = stripslashes($row['it_name']);
                // $it_options = print_item_options($row['it_id'], $s_cart_id);
                // if ($it_options) {
                //     $it_name .= '<div class="sod_opt">' . $it_options . '</div>';
                // }

                // 복합과세금액
                if ($default['de_tax_flag_use']) {
                    if ($row['it_notax']) {
                        $comm_free_mny += $row['price'];
                    } else {
                        $tot_tax_mny += $row['price'];
                    }
                }

                $point = $row['point'];
                $sell_price = $row['price'];

                // 쿠폰
                $cp_button = '';
                if ($is_member) {
                    $cp_count = 0;

                    $sql = " select cp_id
                        from {$g5['g5_shop_coupon_table']}
                        where mb_id IN ( '{$member['mb_id']}', '전체회원' )
                            and cp_start <= '" . G5_TIME_YMD . "'
                            and cp_end >= '" . G5_TIME_YMD . "'
                            and cp_minimum <= '$sell_price'
                            and (
                                ( cp_method = '0' and cp_target = '{$row['it_id']}' )
                                OR
                                ( cp_method = '1' and ( cp_target IN ( '{$row['ca_id']}', '{$row['ca_id2']}', '{$row['ca_id3']}' ) ) )
                                ) ";
                    $res = sql_query($sql);

                    for ($k = 0; $cp = sql_fetch_array($res); $k++) {
                        if (is_used_coupon($member['mb_id'], $cp['cp_id']))
                            continue;

                        $cp_count++;
                    }

                    if ($cp_count) {
                        $cp_button = '<button type="button" class="cp_btn">쿠폰적용</button>';
                        $it_cp_count++;
                    }
                }

                // 배송비
                switch ($row['ct_send_cost']) {
                    case 1:
                        $ct_send_cost = '착불';
                        break;
                    case 2:
                        $ct_send_cost = '무료';
                        break;
                    default:
                        $ct_send_cost = '선불';
                        break;
                }

                // 조건부무료
                if ($row['it_sc_type'] == 2) {
                    $sendcost = get_item_sendcost($row['it_id'], $row['price'], $row['qty'], $s_cart_id);

                    if ($sendcost == 0)
                        $ct_send_cost = '무료';
                }
                
                $io_id  = $row["io_id"];
                ?>

                <tr>
                    <!-- <td><div class="sod_img"></div></td> -->
                    <td class="td_prd">
                        <div class="sod_name">
                            <input type="hidden" name="it_id[<?php echo $i; ?>]"
                                    value="<?php echo $row['it_id']; ?>">
                            <input type="hidden" name="it_name[<?php echo $i; ?>]"
                                    value="<?php echo get_text($row['it_name']); ?>">
                            <input type="hidden" name="it_price[<?php echo $i; ?>]"
                                    value="<?php echo $sell_price; ?>">
                            <input type="hidden" name="cp_id[<?php echo $i; ?>]" value="">
                            <input type="hidden" name="cp_price[<?php echo $i; ?>]" value="0">
                            <?php if ($default['de_tax_flag_use']) { ?>
                                <input type="hidden" name="it_notax[<?php echo $i; ?>]"
                                        value="<?php echo $row['it_notax']; ?>">
                            <?php } ?>
                            <?php echo $image; ?>
                            <div class="li_name">
                                <?php echo $it_name; ?>
                                <? if ($io_id) { ?>
                                <p><span>옵션</span><?=$io_id?></p>
                                <? } ?>
                                <p><span>배송비</span><?php echo $sendcost == 0 ? "무료" : number_format($sendcost) . "원"; ?></p>
                            </div>    
                        </div>
                    </td>
                    <td class="td_numbig  text_right"><?php echo number_format($row['sell_price']); ?></td>
                    <td class="td_num">
                        <div id="sit_opt_added" class="li_mod sit_opt_added">
                            <button type="button" class="sit_qty_minus" data-ctid="<?php echo $row['ct_id']; ?>"><i class="fa fa-minus" aria-hidden="true"></i><span class="sound_only">감소</span></button>
                            <label for="ct_qty_0" class="sound_only">수량</label>
                            <input type="text" onchange="checkqty('<?php echo $row['ct_id']; ?>', '<?php echo $row["sell_price"]; ?>', this.value)" onkeypress="this.onchange();" onpaste="this.onchange();" oninput="this.onchange();" value="<?php echo $row['ct_qty']; ?>" id="qty_<?php echo $row['ct_id']; ?>" class="num_input" size="5">
                            <button type="button" class="sit_qty_plus" data-ctid="<?php echo $row['ct_id']; ?>"><i class="fa fa-plus" aria-hidden="true"></i><span class="sound_only">증가</span></button>
                        </div>
                    </td>
                    <td class="td_numbig  text_right" id="sell_price_<?php echo $row['ct_id']; ?>"><?php echo number_format($sell_price); ?>원</td>
                </tr>

                <?php
                $tot_point += $point;
                $tot_sell_price += $sell_price;
            } // for 끝

            if ($i == 0) {
                //echo '<tr><td colspan="7" class="empty_table">장바구니에 담긴 상품이 없습니다.</td></tr>';
                alert('장바구니가 비어 있습니다.', G5_SHOP_URL . '/cart.php');
            } else {
                // 배송비 계산
                $send_cost = get_sendcost($s_cart_id);
            }

            // 복합과세처리
            if ($default['de_tax_flag_use']) {
                $comm_tax_mny = round(($tot_tax_mny + $send_cost) / 1.1);
                $comm_vat_mny = ($tot_tax_mny + $send_cost) - $comm_tax_mny;
            }
            ?>
            </tbody>
        </table>
    </div> 

    <div id="sod_frm" class="sod_frm_pc orderform">
        <div class="sod_left">
            <!-- 주문하시는 분 입력 시작 { -->
            <section id="sod_frm_orderer">
                <h2>주문자</h2>

                <div class="tbl_frm01 tbl_wrap">
                    <table>
                        <tbody>
                        <tr>
                            <th scope="row"><label for="od_name">이름<strong class="sound_only"> 필수</strong></label></th>
                            <td>
                                <input type="text" name="od_name"
                                       value="<?php echo isset($member['mb_name']) ? get_text($member['mb_name']) : ''; ?>"
                                       id="od_name" required class="frm_input required" size="35" maxlength="20">
                            </td>
                        </tr>

                        <?php if (!$is_member && 1==0) { // 비회원이면 ?>
                            <tr>
                                <th scope="row"><label for="od_pwd">비밀번호</label></th>
                                <td>
                                    <span class="frm_info">영,숫자 3~20자 (주문서 조회시 필요)</span>
                                    <input type="password" name="od_pwd" id="od_pwd" required class="frm_input required"
                                           maxlength="20">
                                </td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <th scope="row"><label for="od_email">이메일<strong class="sound_only"> 필수</strong></label>
                            </th>
                            <td><input type="text" name="od_email" value="<?php echo $member['mb_email']; ?>"
                                       id="od_email" required class="frm_input required" size="35" maxlength="100"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="od_hp">전화번호<strong class="sound_only"> 필수</strong></label></th>
                            <td><input type="text" name="od_hp" value="<?php echo get_text($member['mb_hp']); ?>"
                                       id="od_hp" required class="frm_input required" size="35" maxlength="20"></td>
                        </tr>
                        <?php if ($default['de_hope_date_use']) { // 배송희망일 사용 ?>
                            <tr>
                                <th scope="row"><label for="od_hope_date">희망배송일</label></th>
                                <td>
                                    <!-- <select name="od_hope_date" id="od_hope_date">
                        <option value="">선택하십시오.</option>
                        <?php
                                    for ($i = 0; $i < 7; $i++) {
                                        $sdate = date("Y-m-d", time() + 86400 * ($default['de_hope_date_after'] + $i));
                                        echo '<option value="' . $sdate . '">' . $sdate . ' (' . get_yoil($sdate) . ')</option>' . PHP_EOL;
                                    }
                                    ?>
                        </select> -->
                                    <input type="text" name="od_hope_date" value="" id="od_hope_date" required
                                           class="frm_input required" size="11" maxlength="10" readonly="readonly"> 이후로
                                    배송 바랍니다.
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- } 주문하시는 분 입력 끝 -->

            <!-- 받으시는 분 입력 시작 { -->
            <section id="sod_frm_taker">
                <div class="tbl_frm01 tbl_wrap">
                    <table>
                        <tbody>
                        <?php
                        $addr_list = '';
                        if ($is_member) {
                            // 배송지 이력
                            $sep = chr(30);

                            // 주문자와 동일
                            $addr_list .= '<input type="checkbox" name="ad_sel_addr" value="same" id="ad_sel_addr_same">' . PHP_EOL;
                            $addr_list .= '<label for="ad_sel_addr_same">주문자와 동일</label>' . PHP_EOL;

                            // 기본배송지
                            $sql = " select *
                                from {$g5['g5_shop_order_address_table']}
                                where mb_id = '{$member['mb_id']}'
                                  and ad_default = '1' ";
                            $row = sql_fetch($sql);
                            if (isset($row['ad_id']) && $row['ad_id']) {
                                $val1 = $row['ad_name'] . $sep . $row['ad_tel'] . $sep . $row['ad_hp'] . $sep . $row['ad_zip1'] . $sep . $row['ad_zip2'] . $sep . $row['ad_addr1'] . $sep . $row['ad_addr2'] . $sep . $row['ad_addr3'] . $sep . $row['ad_jibeon'] . $sep . $row['ad_subject'];
                                $addr_list .= '<input type="radio" name="ad_sel_addr" value="' . get_text($val1) . '" id="ad_sel_addr_def">' . PHP_EOL;
                                $addr_list .= '<label for="ad_sel_addr_def">기본배송지</label>' . PHP_EOL;
                            }
                            
                            $ad_name   = $row["ad_name"];
                            $ad_tel    = $row["ad_tel"];
                            $ad_hp     = $row["ad_hp"];
                            $ad_zip1   = $row["ad_zip1"];
                            $ad_zip2   = $row["ad_zip2"];
                            $ad_addr1  = $row["ad_addr1"];
                            $ad_addr2  = $row["ad_addr2"];
                            $ad_addr3  = $row["ad_addr3"];
                            $ad_jibeon = $row["ad_jibeon"];

                            // 최근배송지
                            $sql = " select *
                                from {$g5['g5_shop_order_address_table']}
                                where mb_id = '{$member['mb_id']}'
                                  and ad_default = '0'
                                order by ad_id desc
                                limit 1 ";
                            $result = sql_query($sql);
                            for ($i = 0; $row = sql_fetch_array($result); $i++) {
                                $val1 = $row['ad_name'] . $sep . $row['ad_tel'] . $sep . $row['ad_hp'] . $sep . $row['ad_zip1'] . $sep . $row['ad_zip2'] . $sep . $row['ad_addr1'] . $sep . $row['ad_addr2'] . $sep . $row['ad_addr3'] . $sep . $row['ad_jibeon'] . $sep . $row['ad_subject'];
                                $val2 = '<label for="ad_sel_addr_' . ($i + 1) . '">최근배송지(' . ($row['ad_subject'] ? get_text($row['ad_subject']) : get_text($row['ad_name'])) . ')</label>';
                                $addr_list .= '<input type="radio" name="ad_sel_addr" value="' . get_text($val1) . '" id="ad_sel_addr_' . ($i + 1) . '"> ' . PHP_EOL . $val2 . PHP_EOL;
                            }

                            $addr_list .= '<input type="radio" name="ad_sel_addr" value="new" id="od_sel_addr_new">' . PHP_EOL;
                            $addr_list .= '<label for="od_sel_addr_new">신규배송지</label>' . PHP_EOL;

                            $addr_list .= '<a href="' . G5_SHOP_URL . '/orderaddress.php" id="order_address" class="btn_frmline">배송지목록</a>';
                        } else {
                            // 주문자와 동일
                            $addr_list .= '<input type="checkbox" name="ad_sel_addr" value="same" id="ad_sel_addr_same">' . PHP_EOL;
                            $addr_list .= '<label for="ad_sel_addr_same">주문자와 동일</label>' . PHP_EOL;
                        }
                        ?>
                        <tr>
                            <th scope="row">배송지</th>
                            <td>
                                <div class="order_choice_place">
                                    <input type="checkbox" name="ad_sel_addr" value="same" id="ad_sel_addr_same" style="display:none">
                                    <label for="ad_sel_addr_same"><span></span>주문자와 동일</label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="od_b_name">받는사람<strong class="sound_only"> 필수</strong></label>
                            </th>
                            <td><input type="text" name="od_b_name" id="od_b_name" value="<?=$ad_name?>" required class="frm_input required"  size="35" maxlength="20"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="od_b_hp">전화번호<strong class="sound_only"> 필수</strong></label></th>
                            <td><input type="text" name="od_b_hp" id="od_b_hp" value="<?=$ad_hp?>" required class="frm_input required" size="35" maxlength="20"></td>
                        </tr>
                        <tr>
                            <th scope="row">주소</th>
                            <td id="sod_frm_addr">
                                <button type="button" class="btn_address"
                                        onclick="win_zip('MAINPAY_FORM', 'od_b_zip', 'od_b_addr1', 'od_b_addr2', 'od_b_addr3', 'od_b_addr_jibeon');">
                                    주소 찾기
                                </button>
                                <label for="od_b_zip" class="sound_only">우편번호<strong class="sound_only">
                                        필수</strong></label>
                                <input type="text" name="od_b_zip" id="od_b_zip" value="<?=$ad_zip1 . $ad_zip2?>" required class="frm_input required"
                                       size="21" maxlength="6" placeholder="우편번호" readonly>
                                <br>
                                <input type="text" name="od_b_addr1" id="od_b_addr1" value="<?=$ad_addr1?>" required
                                       class="frm_input frm_address required" size="60" placeholder="기본주소" readonly>
                                <label for="od_b_addr1" class="sound_only">기본주소<strong> 필수</strong></label><br>
                                <input type="text" name="od_b_addr2" id="od_b_addr2" value="<?=$ad_addr2?>" class="frm_input frm_address"
                                       size="60" placeholder="상세주소 입력">
                                <label for="od_b_addr2" class="sound_only">상세주소 입력</label>
                                <br>
                                <input type="hidden" name="od_b_addr3" id="od_b_addr3" value="<?=$ad_addr3?>" readonly="readonly"
                                       class="frm_input frm_address" size="60" placeholder="참고항목">
                                <label for="od_b_addr3" class="sound_only">참고항목</label><br>
                                <input type="hidden" name="od_b_addr_jibeon" id="od_b_addr_jibeon" value="<?=$ad_jibeon?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="od_memo">배송 메모</label></th>
                            <td><textarea name="od_memo" id="od_memo"></textarea></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- } 받으시는 분 입력 끝 -->

            <!-- 결제정보 입력 시작 { -->
            <section id="sod_frm_pay" <? if (!$is_member) echo "style='display:none'"; ?>>
                <div class="sod_frm_cp">
                    <h3>포인트/쿠폰 사용</h3>
                    <?php
                    $temp_point = 0;
                        // 포인트 결제 사용 포인트보다 회원의 포인트가 크다면
                    $temp_point = (int)$default['de_settle_max_point'];

                    if ($temp_point > (int)$tot_sell_price)
                        $temp_point = (int)$tot_sell_price;

                    if ($temp_point > (int)$member['mb_point'])
                        $temp_point = (int)$member['mb_point'];

                    $point_unit = (int)$default['de_settle_point_unit'];
                    $temp_point = (int)((int)($temp_point / $point_unit) * $point_unit);
                    ?>
                    <div class="pay_cp">
                        <div>
                            <input type="hidden" name="max_temp_point" value="<?php echo $temp_point; ?>">
                            <label for="od_temp_point">포인트 사용</label>
                            <input type="number" name="od_temp_point" value="0" min="0" id="od_temp_point" <?php echo $temp_point < 3000 ? "disabled style='background-color:#e8e8e8'" : ""; ?> size="18">
                            <button id="point_apply">사용</button>
                            <p>사용 가능한 포인트 <span class="r_txt"><?php echo number_format($temp_point); ?></span>P</p>
                        </div>
                    <?php
                    $oc_cnt = $sc_cnt = 0;
                        // 주문쿠폰
                        $sql = " select cp_id
                                from {$g5['g5_shop_coupon_table']}
                                where mb_id IN ( '{$member['mb_id']}', '전체회원' )
                                and cp_method = '2'
                                and cp_start <= '" . G5_TIME_YMD . "'
                                and cp_end >= '" . G5_TIME_YMD . "'
                                and cp_minimum <= '$tot_sell_price' ";
                        $res = sql_query($sql);

                        for ($k = 0; $cp = sql_fetch_array($res); $k++) {
                            if (is_used_coupon($member['mb_id'], $cp['cp_id']))
                                continue;

                            $oc_cnt++;
                        }

                        if ($send_cost > 0) {
                            // 배송비쿠폰
                            $sql = " select cp_id
                                    from {$g5['g5_shop_coupon_table']}
                                    where mb_id IN ( '{$member['mb_id']}', '전체회원' )
                                    and cp_method = '3'
                                    and cp_start <= '" . G5_TIME_YMD . "'
                                    and cp_end >= '" . G5_TIME_YMD . "'
                                    and cp_minimum <= '$tot_sell_price' ";
                            $res = sql_query($sql);

                            for ($k = 0; $cp = sql_fetch_array($res); $k++) {
                                if (is_used_coupon($member['mb_id'], $cp['cp_id']))
                                    continue;

                                $sc_cnt++;
                            }
                        }
                    ?>
                        <div>
                            <input type="hidden" name="max_temp_coupon" value="">
                            <input type="hidden" name="sc_cp_id" value="">
                            <input type="hidden" name="od_cp_id" value="">
                            <label for="od_temp_coupon">쿠폰 적용</label>
                            <input type="text" name="od_temp_coupon" placeholder="사용 가능한 쿠폰 <?=$oc_cnt + $sc_cnt?>장" <?php echo $oc_cnt > 0 || $sc_cnt > 0 ? "" : "disabled"; ?> size="18" readonly>
                            <button id="od_coupon_btn">선택</button>
                        </div>
                    </div>
                </div>
                <?php
                    $multi_settle++;
                ?>
            </section>
            <!-- } 결제 정보 입력 끝 -->

            <? /*
            <div class="pay_tbl tbl_frm01 tbl_wrap">
                <table>
                    <tbody>
                    <?php if($oc_cnt > 0) { ?>
                        <tr>
                            <th scope="row">쿠폰 적용</th>
                            <td>
                                <input type="hidden" name="od_cp_id" value="">
                                <button type="button" id="od_coupon_btn" class="btn_frmline">쿠폰선택</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">쿠폰 할인금액</th>
                            <td><span id="od_cp_price">0</span>원</td>
                        </tr>
                    <?php } ?>
                    <?php if($sc_cnt > 0) { ?>
                        <tr>
                            <th scope="row">배송비할인쿠폰</th>
                            <td>
                                <input type="hidden" name="sc_cp_id" value="">
                                <button type="button" id="sc_coupon_btn" class="btn_frmline">쿠폰적용</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">배송비할인금액</th>
                            <td><span id="sc_cp_price">0</span>원</td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th>총 주문금액</th>
                        <td><span id="od_tot_price"><?php echo number_format($tot_price); ?></span>원</td>
                    </tr>
                    <tr>
                        <th>추가배송비</th>
                        <td><span id="od_send_cost2">0</span>원 (제주도 및 도서 산간지역 추가비용)</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            */
            ?>

            <?php if ($goods_count) $goods .= ' 외 ' . $goods_count . '건'; ?>
            <!-- } 주문상품 확인 끝 -->
              
            <input type="hidden" name="od_price" value="<?php echo $tot_sell_price; ?>">
            <input type="hidden" name="org_od_price" value="<?php echo $tot_sell_price; ?>">
            <input type="hidden" name="od_send_cost" value="<?php echo $send_cost; ?>">
            <input type="hidden" name="od_partner" value="<?php echo get_session('set_partner_id')?>">
            <input type="hidden" name="od_send_cost2" value="0">
            <input type="hidden" name="item_coupon" value="0">
            <input type="hidden" name="good_mny" value="<?=$tot_sell_price + $send_cost?>">
            <input type="hidden" name="od_coupon" value="0">
            <input type="hidden" name="od_send_coupon" value="0">
            <input type="hidden" name="od_goods_name" value="<?php echo $goods; ?>">

            <?php
            // 결제대행사별 코드 include (결제대행사 정보 필드)
            require_once(G5_SHOP_PATH . '/mainpay/orderform.2.php');

            if ($is_kakaopay_use) {
                require_once(G5_SHOP_PATH . '/kakaopay/orderform.2.php');
            }
            ?>

            
        </div>

        <div class="sod_right">

            <!-- 결제수단 -->
            <div id="od_pay_sl">
                <div class="od_pay_buttons_el">
                    <h2>결제 수단</h2>
                    <?php
                    if (!$default['de_card_point'])
                        echo '<p id="sod_frm_pt_alert"><strong>무통장입금</strong> 이외의 결제 수단으로 결제하시는 경우 포인트를 적립해드리지 않습니다.</p>';

                    $multi_settle = 0;
                    $checked = '';

                    $escrow_title = "";
                    if ($default['de_escrow_use']) {
                        $escrow_title = "에스크로<br>";
                    }

                    if ($is_kakaopay_use || $default['de_bank_use'] || $default['de_vbank_use'] || $default['de_iche_use'] || $default['de_card_use'] || $default['de_hp_use'] || $default['de_easy_pay_use'] || $default['de_inicis_lpay_use'] || $default['de_inicis_kakaopay_use']) {
                        echo '<fieldset id="sod_frm_paysel">';
                        echo '<legend>결제방법 선택</legend>';
                    }

                    // 카카오페이
                    if ($is_kakaopay_use) {
                        $multi_settle++;
                        echo '<input type="radio" id="od_settle_kakaopay" name="od_settle_case" value="KAKAOPAY" ' . $checked . '> <label for="od_settle_kakaopay" class="kakao_icon lb_icon">KAKAOPAY</label>' . PHP_EOL;
                        $checked = '';
                    }

                    // 신용카드 사용 (디폴트 체크)
                    if ($default['de_card_use']) {
                        $multi_settle++;
                        echo '<input type="radio" id="od_settle_card" name="od_settle_case" value="CARD" checked> <label for="od_settle_card" class="lb_icon card_icon">카드<span class="icon"></span><span class="txt"></span></label>' . PHP_EOL;
                        $checked = '';
                    }

                    // 무통장입금 사용
                    if ($default['de_bank_use']) {
                        $multi_settle++;
                        echo '<input type="radio" id="od_settle_bank" name="od_settle_case" value="무통장" ' . $checked . '> <label for="od_settle_bank" class="lb_icon bank_icon">무통장입금<span class="icon"></span><span class="txt"></span></label>' . PHP_EOL;
                        $checked = '';
                    }

                    // 가상계좌 사용
                    if ($default['de_vbank_use']) {
                        $multi_settle++;
                        echo '<input type="radio" id="od_settle_vbank" name="od_settle_case" value="VACCT" ' . $checked . '> <label for="od_settle_vbank" class="lb_icon vbank_icon">' . $escrow_title . '가상계좌</label>' . PHP_EOL;
                        $checked = '';
                    }

                    // 계좌이체 사용
                    if ($default['de_iche_use']) {
                        $multi_settle++;
                        echo '<input type="radio" id="od_settle_iche" name="od_settle_case" value="ACCT" ' . $checked . '> <label for="od_settle_iche" class="lb_icon iche_icon">' . $escrow_title . '계좌이체</label>' . PHP_EOL;
                        $checked = '';
                    }

                    // 휴대폰 사용
                    if ($default['de_hp_use']) {
                        $multi_settle++;
                        echo '<input type="radio" id="od_settle_hp" name="od_settle_case" value="HPP"' . $checked . '> <label for="od_settle_hp" class="lb_icon hp_icon">휴대폰<span class="icon"></span><span class="txt"></span></label>' . PHP_EOL;
                        $checked = '';
                    }

                    $easypay_prints = array();

                    // PG 간편결제
                    if ($default['de_easy_pay_use']) {
                        switch ($default['de_pg_service']) {
                            case 'lg':
                                $pg_easy_pay_name = 'PAYNOW';
                                break;
                            case 'inicis':
                                $pg_easy_pay_name = 'KPAY';
                                break;
                            default:
                                $pg_easy_pay_name = 'PAYCO';
                                break;
                        }

                        $multi_settle++;

                        if ($default['de_pg_service'] === 'kcp' && isset($default['de_easy_pay_services']) && $default['de_easy_pay_services']) {
                            $de_easy_pay_service_array = explode(',', $default['de_easy_pay_services']);
                            if (in_array('nhnkcp_payco', $de_easy_pay_service_array)) {
                                $easypay_prints['nhnkcp_payco'] = '<input type="radio" id="od_settle_nhnkcp_payco" name="od_settle_case" data-pay="payco" value="간편결제"> <label for="od_settle_nhnkcp_payco" class="PAYCO nhnkcp_payco payco_icon lb_icon" title="NHN_KCP - PAYCO">PAYCO</label>';
                            }
                            if (in_array('nhnkcp_naverpay', $de_easy_pay_service_array)) {
                                $easypay_prints['nhnkcp_naverpay'] = '<input type="radio" id="od_settle_nhnkcp_naverpay" name="od_settle_case" data-pay="naverpay" value="간편결제" > <label for="od_settle_nhnkcp_naverpay" class="naver_icon nhnkcp_naverpay lb_icon" title="NHN_KCP - 네이버페이">네이버페이</label>';
                            }
                            if (in_array('nhnkcp_kakaopay', $de_easy_pay_service_array)) {
                                $easypay_prints['nhnkcp_kakaopay'] = '<input type="radio" id="od_settle_nhnkcp_kakaopay" name="od_settle_case" data-pay="kakaopay" value="간편결제" > <label for="od_settle_nhnkcp_kakaopay" class="kakao_icon nhnkcp_kakaopay lb_icon" title="NHN_KCP - 카카오페이">카카오페이</label>';
                            }
                        } else {
                            $easypay_prints[strtolower($pg_easy_pay_name)] = '<input type="radio" id="od_settle_easy_pay" name="od_settle_case" value="간편결제"> <label for="od_settle_easy_pay" class="' . $pg_easy_pay_name . ' lb_icon">' . $pg_easy_pay_name . '</label>';
                        }

                    }

                    if (!isset($easypay_prints['nhnkcp_naverpay']) && function_exists('is_use_easypay') && is_use_easypay('global_nhnkcp')) {
                        $easypay_prints['nhnkcp_naverpay'] = '<input type="radio" id="od_settle_nhnkcp_naverpay" name="od_settle_case" data-pay="naverpay" value="간편결제" > <label for="od_settle_nhnkcp_naverpay" class="naver_icon nhnkcp_naverpay lb_icon" title="NHN_KCP - 네이버페이">네이버페이</label>';
                    }

                    if ($easypay_prints) {
                        $multi_settle++;
                        echo run_replace('shop_orderform_easypay_buttons', implode(PHP_EOL, $easypay_prints), $easypay_prints, $multi_settle);
                    }

                    //이니시스 Lpay
                    if ($default['de_inicis_lpay_use']) {
                        echo '<input type="radio" id="od_settle_inicislpay" data-case="lpay" name="od_settle_case" value="lpay" ' . $checked . '> <label for="od_settle_inicislpay" class="inicis_lpay lb_icon">L.pay</label>' . PHP_EOL;
                        $checked = '';
                    }

                    //이니시스 카카오페이
                    if (isset($default['de_inicis_kakaopay_use']) && $default['de_inicis_kakaopay_use']) {
                        echo '<input type="radio" id="od_settle_inicis_kakaopay" data-case="inicis_kakaopay" name="od_settle_case" value="inicis_kakaopay" ' . $checked . ' title="KG 이니시스 카카오페이"> <label for="od_settle_inicis_kakaopay" class="inicis_kakaopay lb_icon">KG 이니시스 카카오페이<em></em></label>' . PHP_EOL;
                        $checked = '';
                    }
                    ?>
                    
                </div>
                    
                <?php
                if ($default['de_bank_use']) {
                    // 은행계좌를 배열로 만든후
                    $str = explode("\n", trim($default['de_bank_account']));
                    if (count($str) <= 1) {
                        $bank_account = '<input type="hidden" name="od_bank_account" value="' . $str[0] . '">' . $str[0] . PHP_EOL;
                    } else {
                        $bank_account = '<select name="od_bank_account" id="od_bank_account">' . PHP_EOL;
                        $bank_account .= '<option value="">선택하십시오.</option>';
                        for ($i = 0; $i < count($str); $i++) {
                            //$str[$i] = str_replace("\r", "", $str[$i]);
                            $str[$i] = trim($str[$i]);
                            $bank_account .= '<option value="' . $str[$i] . '">' . $str[$i] . '</option>' . PHP_EOL;
                        }
                        $bank_account .= '</select>' . PHP_EOL;
                    }
                    echo '<div id="settle_bank" style="display:none"><span style="display: block; margin: 10px 0; font-size: 14px; font-weight: 500; color: #5c5c5c">입금계좌</span>';
                    echo '<div style="padding: 15px 10px 10px; border: 1px solid #e9e9e9"><label for="od_bank_account" class="sound_only">입금할 계좌</label>';
                    echo '<p style="margin-bottom:10px">'.$bank_account.'</p>';
                    echo '<label for="od_deposit_name">입금자명</label> ';
                    echo '<input type="text" name="od_deposit_name" id="od_deposit_name" size="10" maxlength="20">';
                    echo '</div></div>';
                }

                if ($is_kakaopay_use || $default['de_bank_use'] || $default['de_vbank_use'] || $default['de_iche_use'] || $default['de_card_use'] || $default['de_hp_use'] || $default['de_easy_pay_use'] || $default['de_inicis_lpay_use'] || $default['de_inicis_kakaopay_use']) {
                    echo '</fieldset>';
                }

                if ($multi_settle == 0)
                    echo '<p>결제할 방법이 없습니다.<br>운영자에게 알려주시면 감사하겠습니다.</p>';
                ?>
            </div>

            <div class="od_totbox">
                <h2>결제 금액</h2>
                <ul>
                    <li>
                        <span>총 상품 금액</span>
                        <span id="od_tot_price"><?php echo number_format($tot_sell_price); ?>원</span>
                    </li>
                    <li>
                        <span>총 배송비</span>
                        <span id="tot_send_cost"><?php echo number_format($send_cost); ?>원</span>
                    </li>
                    <li>
                        <span>쿠폰 사용</span>
                        <span id="tot_coupon">0원</span>
                    </li>
                    <li>
                        <span>포인트 사용</span>
                        <span id="tot_use_point">0P</span>
                    </li>
                </ul>
                <div class="total">
                    <span>최종 결제 금액</span>
                    <?php $tot_price = $tot_sell_price + $send_cost; // 총계 = 주문상품금액합계 + 배송비 ?>
                    <span class="total_cnt" id="tot_sell_price"><?php echo number_format($tot_price); ?>원</span>
                    <p><span class="point" id="tot_point"><?php echo number_format($tot_point); ?>P</span>적립</p>
                </div>
                <div class="pay_info">
                    <input type="checkbox" name="chk_all" id="chk_all">
                    <label for="chk_all"><span></span>전체 동의</label>
                    <input type="checkbox" name="od_agree" id="od_agree">
                    <label for="od_agree"><span></span>(필수) 주문내용 확인 및 결제 동의</label>
                    <input type="checkbox" name="non_member_info" id="non_member_info">
                    <label for="non_member_info"><span></span>(필수) 개인정보 제3자 제공 동의</label>
                    <div class="order_info" style="display:none">
                        <p>※ 개인정보의 수집·이용 목적</p>
                        <p>서비스 제공 및 계약의 이행, 구매 및 대금결제, 물품배송 또는 청구지 발송, 회원관리 등을 위한 목적</p>
                        <p>※ 수집하려는 개인정보의 항목</p>
                        <p>이름, 주소, 연락처, 핸드폰, 이메일 등</p>
                        <p>※ 개인정보의 보유 및 이용 기간</p>
                        <p>회사는 개인정보 수집 및 이용목적이 달성된 후에는 예외없이 해당정보를 파기합니다.</p>
                        <p>이용자의 개인정보는 원칙적으로 위 사항 외에 외부에 제공하지 않습니다. 다만, 아래의 경우에는 예외로 합니다.</p>
                        <p>이용자들이 사전에 동의한 경우</p>
                        <p>법령의 규정에 의거하거나, 수사 목적으로 법령에 정해진 절차와 방법에 따라 수사기관의 요구가 있는 경우</p>
                    </div>
                    <span class="down_btn" onclick="drop_down_info()"></span>
                </div>
            </div>

            <div id="display_pay_button" class="btn_confirm">
                <input type="button" value="결제하기" class="btn_submit" onclick="forderform_check(this.form);" class="btn_submit"/>
            </div>
        </div>

    </div>
</form>

<script>
    function drop_down_info(){
        $('.order_info').slideToggle();
    }
    
    $(document).ready(() => {
        $("#row_count").html(<?=$goods_count + 1?>);
    })

    var zipcode = "";
    var form_action_url = "<?php echo $order_action_url; ?>";

    $(function () {
        var $cp_btn_el;
        var $cp_row_el;
        
        $("#point_apply").on("click", () => {
            <?php if($temp_point > 0 && $is_member) { ?>
            calculate_temp_point();
            <?php } ?>
            
            var od_temp_point = $("input[name=od_temp_point]").val();
            var max_temp_point = $("input[name=max_temp_point]").val();
            
            if (od_temp_point > max_temp_point) {
                alert("최대 사용 포인트는 " + max_temp_point + "P 입니다");
                
                $("input[name=od_temp_point]").val(max_temp_point);
            }
            
            calculate_order_price();
            return false
        })
        
        $("#chk_all").on("click", () => {
            if ($("#chk_all").prop("checked")) {
                $("#od_agree").prop("checked", true);
                $("#non_member_info").prop("checked", true);
            } else {
                $("#od_agree").prop("checked", false);
                $("#non_member_info").prop("checked", false);
            }
        });
        
        $("#od_agree").on("click", () => {
            if ($("#od_agree").prop("checked") && $("#non_member_info").prop("checked")) {
                $("#chk_all").prop("checked", true);
            } else {
                $("#chk_all").prop("checked", false);
            }
        })
        
        $("#non_member_info").on("click", () => {
            if ($("#od_agree").prop("checked") && $("#non_member_info").prop("checked")) {
                $("#chk_all").prop("checked", true);
            } else {
                $("#chk_all").prop("checked", false);
            }
        })

        $(".cp_btn").click(function () {
            $cp_btn_el = $(this);
            $cp_row_el = $(this).closest("tr");
            $("#cp_frm").remove();
            var it_id = $cp_btn_el.closest("tr").find("input[name^=it_id]").val();

            $.post(
                "./orderitemcoupon.php",
                {it_id: it_id, sw_direct: "<?php echo $sw_direct; ?>"},
                function (data) {
                    $cp_btn_el.after(data);
                }
            );
        });

        $(document).on("click", ".cp_apply", function () {
            var $el = $(this).closest("tr");
            var cp_id = $el.find("input[name='f_cp_id[]']").val();
            var price = $el.find("input[name='f_cp_prc[]']").val();
            var subj = $el.find("input[name='f_cp_subj[]']").val();
            var sell_price;

            if (parseInt(price) == 0) {
                if (!confirm(subj + "쿠폰의 할인 금액은 " + price + "원입니다.\n쿠폰을 적용하시겠습니까?")) {
                    return false;
                }
            }

            // 이미 사용한 쿠폰이 있는지
            var cp_dup = false;
            var cp_dup_idx;
            var $cp_dup_el;
            $("input[name^=cp_id]").each(function (index) {
                var id = $(this).val();

                if (id == cp_id) {
                    cp_dup_idx = index;
                    cp_dup = true;
                    $cp_dup_el = $(this).closest("tr");
                    ;

                    return false;
                }
            });

            if (cp_dup) {
                var it_name = $("input[name='it_name[" + cp_dup_idx + "]']").val();
                if (!confirm(subj + "쿠폰은 " + it_name + "에 사용되었습니다.\n" + it_name + "의 쿠폰을 취소한 후 적용하시겠습니까?")) {
                    return false;
                } else {
                    coupon_cancel($cp_dup_el);
                    $("#cp_frm").remove();
                    $cp_dup_el.find(".cp_btn").text("적용").focus();
                    $cp_dup_el.find(".cp_cancel2").remove();
                }
            }

            var $s_el = $cp_row_el.find(".total_price");
            ;
            sell_price = parseInt($cp_row_el.find("input[name^=it_price]").val());
            sell_price = sell_price - parseInt(price);
            if (sell_price < 0) {
                alert("쿠폰할인금액이 상품 주문금액보다 크므로 쿠폰을 적용할 수 없습니다.");
                return false;
            }
            $s_el.text(number_format(String(sell_price)));
            $cp_row_el.find("input[name^=cp_id]").val(cp_id);
            $cp_row_el.find("input[name^=cp_price]").val(price);

            calculate_total_price();
            $("#cp_frm").remove();
            $cp_btn_el.text("변경").focus();
            if (!$cp_row_el.find(".cp_cancel2").length)
                $cp_btn_el.after("<button type=\"button\" class=\"cp_cancel2\">취소</button>");
        });

        $(document).on("click", "#cp_close", function () {
            $("#cp_frm").remove();
            $cp_btn_el.focus();
        });

        $(document).on("click", ".cp_cancel2", function () {
            coupon_cancel($(this).closest("tr"));
            calculate_total_price();
            $("#cp_frm").remove();
            $(this).closest("tr").find(".cp_btn").text("적용").focus();
            $(this).remove();
        });

        $("#od_coupon_btn").click(function () {
            if ($("#od_coupon_frm").parent(".od_coupon_wrap").length) {
                $("#od_coupon_frm").parent(".od_coupon_wrap").remove();
            }
            $("#od_coupon_frm").remove();
            var $this = $(this);
            var price = parseInt($("input[name=org_od_price]").val()) - parseInt($("input[name=item_coupon]").val());
            if (price <= 0) {
                alert('상품금액이 0원이므로 쿠폰을 사용할 수 없습니다.');
                return false;
            }
            $.post(
                "./ordercoupon.php",
                {price: price},
                function (data) {
                    $this.after(data);
                }
            );
            return false
        });

        $(document).on("click", ".od_cp_apply", function () {
            var $el = $(this).closest("tr");
            var cp_id = $el.find("input[name='o_cp_id[]']").val();
            var price = parseInt($el.find("input[name='o_cp_prc[]']").val());
            var subj = $el.find("input[name='o_cp_subj[]']").val();
            var send_cost = $("input[name=od_send_cost]").val();
            var item_coupon = parseInt($("input[name=item_coupon]").val());
            var od_price = parseInt($("input[name=org_od_price]").val()) - item_coupon;

            if (price == 0) {
                if (!confirm(subj + "쿠폰의 할인 금액은 " + price + "원입니다.\n쿠폰을 적용하시겠습니까?")) {
                    return false;
                }
            }

            if (od_price - price <= 0) {
                alert("쿠폰할인금액이 주문금액보다 크므로 쿠폰을 적용할 수 없습니다.");
                return false;
            }

            $("input[name=sc_cp_id]").val("");
            $("#sc_coupon_btn").text("쿠폰적용");
            $("#sc_coupon_cancel").remove();

            $("input[name=od_price]").val(od_price);
            $("input[name=od_cp_id]").val(cp_id);
            $("input[name=od_coupon]").val(price);
            $("input[name=od_send_coupon]").val(0);
            $("#od_cp_price").text(number_format(String(price)));
            $("#sc_cp_price").text(0);
            calculate_order_price();
            if ($("#od_coupon_frm").parent(".od_coupon_wrap").length) {
                $("#od_coupon_frm").parent(".od_coupon_wrap").remove();
            }
            $("#od_coupon_frm").remove();
            $("#od_coupon_btn").text("쿠폰변경").focus();
            if (!$("#od_coupon_cancel").length)
                $("#od_coupon_btn").after("<button type=\"button\" id=\"od_coupon_cancel\" class=\"cp_cancel2\">쿠폰취소</button>");
        });

        $(document).on("click", "#od_coupon_close", function () {
            if ($("#od_coupon_frm").parent(".od_coupon_wrap").length) {
                $("#od_coupon_frm").parent(".od_coupon_wrap").remove();
            }
            $("#od_coupon_frm").remove();
            $("#od_coupon_btn").focus();
        });

        $(document).on("click", "#od_coupon_cancel", function () {
            var org_price = $("input[name=org_od_price]").val();
            var item_coupon = parseInt($("input[name=item_coupon]").val());
            $("input[name=od_price]").val(org_price);
            $("input[name=sc_cp_id]").val("");
            $("input[name=od_coupon]").val(0);
            $("input[name=od_send_coupon]").val(0);
            $("#od_cp_price").text(0);
            $("#sc_cp_price").text(0);
            calculate_order_price();
            if ($("#od_coupon_frm").parent(".od_coupon_wrap").length) {
                $("#od_coupon_frm").parent(".od_coupon_wrap").remove();
            }
            $("#od_coupon_frm").remove();
            $("#od_coupon_btn").text("쿠폰적용").focus();
            $(this).remove();
            $("#sc_coupon_btn").text("쿠폰적용");
            $("#sc_coupon_cancel").remove();
        });

        $("#sc_coupon_btn").click(function () {
            $("#sc_coupon_frm").remove();
            var $this = $(this);
            var price = parseInt($("input[name=od_price]").val());
            var send_cost = parseInt($("input[name=od_send_cost]").val());
            $.post(
                "./ordersendcostcoupon.php",
                {price: price, send_cost: send_cost},
                function (data) {
                    $this.after(data);
                }
            );
        });

        $(document).on("click", ".sc_cp_apply", function () {
            var $el = $(this).closest("tr");
            var cp_id = $el.find("input[name='s_cp_id[]']").val();
            var price = parseInt($el.find("input[name='s_cp_prc[]']").val());
            var subj = $el.find("input[name='s_cp_subj[]']").val();
            var send_cost = parseInt($("input[name=od_send_cost]").val());

            if (parseInt(price) == 0) {
                if (!confirm(subj + "쿠폰의 할인 금액은 " + price + "원입니다.\n쿠폰을 적용하시겠습니까?")) {
                    return false;
                }
            }

            $("input[name=sc_cp_id]").val(cp_id);
            $("input[name=od_send_coupon]").val(price);
            $("#sc_cp_price").text(number_format(String(price)));
            calculate_order_price();
            $("#sc_coupon_frm").remove();
            $("#sc_coupon_btn").text("변경").focus();
            if (!$("#sc_coupon_cancel").length)
                $("#sc_coupon_btn").after("<button type=\"button\" id=\"sc_coupon_cancel\" class=\"cp_cancel2\">취소</button>");
        });

        $(document).on("click", "#sc_coupon_close", function () {
            $("#sc_coupon_frm").remove();
            $("#sc_coupon_btn").focus();
        });

        $(document).on("click", "#sc_coupon_cancel", function () {
            $("input[name=od_send_coupon]").val(0);
            $("#sc_cp_price").text(0);
            calculate_order_price();
            $("#sc_coupon_frm").remove();
            $("#sc_coupon_btn").text("쿠폰적용").focus();
            $(this).remove();
        });

        $("#od_b_addr2").focus(function () {
            var zip = $("#od_b_zip").val().replace(/[^0-9]/g, "");
            if (zip == "")
                return false;

            var code = String(zip);

            if (zipcode == code)
                return false;

            zipcode = code;
            calculate_sendcost(code);
        });

        $("#od_settle_bank").on("click", function () {
            $("[name=od_deposit_name]").val($("[name=od_name]").val());
            $("#settle_bank").show();
        });

        $("#od_settle_iche,#od_settle_card,#od_settle_vbank,#od_settle_hp,#od_settle_easy_pay,#od_settle_kakaopay,#od_settle_nhnkcp_payco,#od_settle_nhnkcp_naverpay,#od_settle_nhnkcp_kakaopay,#od_settle_inicislpay,#od_settle_inicis_kakaopay").bind("click", function () {
            $("#settle_bank").hide();
        });

        // 배송지선택
        $("input[name=ad_sel_addr]").on("click", function () {
            var addr = $(this).val().split(String.fromCharCode(30));

            if (addr[0] == "same") {
                gumae2baesong();
            } else {
                if (addr[0] == "new") {
                    for (i = 0; i < 10; i++) {
                        addr[i] = "";
                    }
                }

                var f = document.MAINPAY_FORM;
                f.od_b_name.value = addr[0];
                f.od_b_tel.value = addr[1];
                f.od_b_hp.value = addr[2];
                f.od_b_zip.value = addr[3] + addr[4];
                f.od_b_addr1.value = addr[5];
                f.od_b_addr2.value = addr[6];
                f.od_b_addr3.value = addr[7];
                f.od_b_addr_jibeon.value = addr[8];
                f.ad_subject.value = addr[9];

                var zip1 = addr[3].replace(/[^0-9]/g, "");
                var zip2 = addr[4].replace(/[^0-9]/g, "");

                var code = String(zip1) + String(zip2);

                if (zipcode != code) {
                    calculate_sendcost(code);
                }
            }
        });

        // 배송지목록
        $("#order_address").on("click", function () {
            var url = this.href;
            window.open(url, "win_address", "left=100,top=100,width=800,height=600,scrollbars=1");
            return false;
        });
    });

    function coupon_cancel($el) {
        var $dup_sell_el = $el.find(".total_price");
        var $dup_price_el = $el.find("input[name^=cp_price]");
        var org_sell_price = $el.find("input[name^=it_price]").val();

        $dup_sell_el.text(number_format(String(org_sell_price)));
        $dup_price_el.val(0);
        $el.find("input[name^=cp_id]").val("");
    }

    function calculate_total_price() {
        var $it_prc = $("input[name^=it_price]");
        var $cp_prc = $("input[name^=cp_price]");
        var tot_sell_price = sell_price = tot_cp_price = 0;
        var it_price, cp_price, it_notax;
        var tot_mny = comm_tax_mny = comm_vat_mny = comm_free_mny = tax_mny = vat_mny = 0;
        var send_cost = parseInt($("input[name=od_send_cost]").val());

        $it_prc.each(function (index) {
            it_price = parseInt($(this).val());
            cp_price = parseInt($cp_prc.eq(index).val());
            sell_price += it_price;
            tot_cp_price += cp_price;
        });

        tot_sell_price = sell_price - tot_cp_price + send_cost;

        $("#ct_tot_coupon").text(number_format(String(tot_cp_price)));
        $("#ct_tot_price").text(number_format(String(tot_sell_price)));

        $("input[name=good_mny]").val(tot_sell_price);
        $("input[name=od_price]").val(sell_price);
        $("input[name=item_coupon]").val(tot_cp_price);
        $("input[name=od_coupon]").val(0);
        $("input[name=od_send_coupon]").val(0);
        <?php if($oc_cnt > 0) { ?>
        $("input[name=od_cp_id]").val("");
        $("#od_cp_price").text(0);
        if ($("#od_coupon_cancel").length) {
            $("#od_coupon_btn").text("쿠폰적용");
            $("#od_coupon_cancel").remove();
        }
        <?php } ?>
        <?php if($sc_cnt > 0) { ?>
        $("input[name=sc_cp_id]").val("");
        $("#sc_cp_price").text(0);
        if ($("#sc_coupon_cancel").length) {
            $("#sc_coupon_btn").text("쿠폰적용");
            $("#sc_coupon_cancel").remove();
        }
        <?php } ?>
        //$("input[name=od_temp_point]").val(0);
        <?php if($temp_point > 0 && $is_member) { ?>
        calculate_temp_point();
        <?php } ?>
        calculate_order_price();
    }

    function calculate_order_price() {
        var sell_price = parseInt($("input[name=od_price]").val());
        var send_cost = parseInt($("input[name=od_send_cost]").val());
        var send_cost2 = parseInt($("input[name=od_send_cost2]").val());
        var send_coupon = parseInt($("input[name=od_send_coupon]").val());
        var od_coupon = parseInt($("input[name=od_coupon]").val());
        var od_temp_point = parseInt($("input[name=od_temp_point]").val());
        var od_price = sell_price - od_coupon;
        var tot_price = sell_price + send_cost + send_cost2 - send_coupon - od_coupon - od_temp_point;
        var tot_send_cost = send_cost + send_cost2 - send_coupon
        
        $("input[name=good_mny]").val(tot_price);
        $("#tot_use_point").html(number_format(String(od_temp_point)) + "P");
        $("#tot_coupon").html(number_format(String(od_coupon)) + "원");
        $("#tot_send_cost").html(number_format(String(tot_send_cost)) + "원");
        $("#od_tot_price").html(number_format(String(sell_price)) + "원");
        $("#tot_sell_price").html(number_format(String(tot_price)) + "원");
        //$("input[name=od_price]").val(od_price)
        <?php if($temp_point > 0 && $is_member) { ?>
        calculate_temp_point();
        <?php } ?>
    }

    function calculate_temp_point() {
        var sell_price = parseInt($("input[name=od_price]").val());
        var mb_point = parseInt(<?php echo $member['mb_point']; ?>);
        var max_point = parseInt(<?php echo $default['de_settle_max_point']; ?>);
        var point_unit = parseInt(<?php echo $default['de_settle_point_unit']; ?>);
        var temp_point = max_point;

        if (temp_point > sell_price)
            temp_point = sell_price;

        if (temp_point > mb_point)
            temp_point = mb_point;

        temp_point = parseInt(temp_point / point_unit) * point_unit;

        $("#use_max_point").text(number_format(String(temp_point)) + "P");
        $("input[name=max_temp_point]").val(temp_point);
    }

    function calculate_sendcost(code) {
        $.post(
            "./ordersendcost.php",
            {zipcode: code},
            function (data) {
                $("input[name=send_cost2]").val(data);
                $("input[name=od_send_cost2]").val(data);
                //$("#od_send_cost2").text(number_format(String(data)));

                zipcode = code;

                calculate_order_price();
            }
        );
    }

    function calculate_tax() {
        var $it_prc = $("input[name^=it_price]");
        var $cp_prc = $("input[name^=cp_price]");
        var sell_price = tot_cp_price = 0;
        var it_price, cp_price, it_notax;
        var tot_mny = comm_free_mny = tax_mny = vat_mny = 0;
        var send_cost = parseInt($("input[name=od_send_cost]").val());
        var send_cost2 = parseInt($("input[name=od_send_cost2]").val());
        var od_coupon = parseInt($("input[name=od_coupon]").val());
        var send_coupon = parseInt($("input[name=od_send_coupon]").val());
        var temp_point = 0;

        $it_prc.each(function (index) {
            it_price = parseInt($(this).val());
            cp_price = parseInt($cp_prc.eq(index).val());
            sell_price += it_price;
            tot_cp_price += cp_price;
            it_notax = $("input[name^=it_notax]").eq(index).val();
            if (it_notax == "1") {
                comm_free_mny += (it_price - cp_price);
            } else {
                tot_mny += (it_price - cp_price);
            }
        });

        if ($("input[name=od_temp_point]").length)
            temp_point = parseInt($("input[name=od_temp_point]").val());

        tot_mny += (send_cost + send_cost2 - od_coupon - send_coupon - temp_point);
        if (tot_mny < 0) {
            comm_free_mny = comm_free_mny + tot_mny;
            tot_mny = 0;
        }

        tax_mny = Math.round(tot_mny / 1.1);
        vat_mny = tot_mny - tax_mny;
        $("input[name=comm_tax_mny]").val(tax_mny);
        $("input[name=comm_vat_mny]").val(vat_mny);
        $("input[name=comm_free_mny]").val(comm_free_mny);
    }

    function forderform_check(f) {
        // 재고체크
        /*var stock_msg = order_stock_check();
        if (stock_msg != "") {
            alert(stock_msg);
            return false;
        }*/
        
        errmsg = "";
        errfld = "";
        var deffld = "";

        check_field(f.od_name, "주문하시는 분 이름을 입력하십시오.");
        if (typeof (f.od_pwd) != 'undefined') {
            clear_field(f.od_pwd);
            if ((f.od_pwd.value.length < 3) || (f.od_pwd.value.search(/([^A-Za-z0-9]+)/) != -1))
                error_field(f.od_pwd, "회원이 아니신 경우 주문서 조회시 필요한 비밀번호를 3자리 이상 입력해 주십시오.");
        }
        check_field(f.od_hp, "주문하시는 분 핸드폰번호를 입력하십시오.");

        clear_field(f.od_email);
        if (f.od_email.value == '' || f.od_email.value.search(/(\S+)@(\S+)\.(\S+)/) == -1)
            error_field(f.od_email, "E-mail을 바르게 입력해 주십시오.");

        if (typeof (f.od_hope_date) != "undefined") {
            clear_field(f.od_hope_date);
            if (!f.od_hope_date.value)
                error_field(f.od_hope_date, "희망배송일을 선택하여 주십시오.");
        }

        check_field(f.od_b_name, "받으시는 분 이름을 입력하십시오.");
        check_field(f.od_b_hp, "받으시는 분 핸드폰번호를 입력하십시오.");
        check_field(f.od_b_addr1, "주소검색을 이용하여 받으시는 분 주소를 입력하십시오.");
        check_field(f.od_b_zip, "");

        var od_settle_bank = document.getElementById("od_settle_bank");
        if (od_settle_bank) {
            if (od_settle_bank.checked) {
                check_field(f.od_bank_account, "계좌번호를 선택하세요.");
                check_field(f.od_deposit_name, "입금자명을 입력하세요.");
            }
        }

        // 배송비를 받지 않거나 더 받는 경우 아래식에 + 또는 - 로 대입
        f.od_send_cost.value = parseInt(f.od_send_cost.value);

        if (errmsg) {
            alert(errmsg);
            errfld.focus();
            return false;
        }
        
        var settle_case = document.getElementsByName("od_settle_case");
        var settle_check = false;
        var settle_method = "";

        for (i = 0; i < settle_case.length; i++) {
            if (settle_case[i].checked) {
                settle_check = true;
                settle_method = settle_case[i].value;
                break;
            }
        }
        if (!settle_check) {
            alert("결제방식을 선택하십시오.");
            return false;
        }
        
        settle_method = $("input[name='od_settle_case']:checked").val();

        var od_price = parseInt(f.od_price.value);
        var send_cost = parseInt(f.od_send_cost.value);
        var send_cost2 = parseInt(f.od_send_cost2.value);
        var send_coupon = parseInt(f.od_send_coupon.value);

        var max_point = 0;
        if (typeof (f.max_temp_point) != "undefined")
            max_point = parseInt(f.max_temp_point.value);

        var temp_point = 0;
        if (typeof (f.od_temp_point) != "undefined") {
            var point_unit = parseInt(<?php echo $default['de_settle_point_unit']; ?>);
            temp_point = parseInt(f.od_temp_point.value) || 0;

            if (f.od_temp_point.value) {
                if (temp_point > od_price) {
                    alert("상품 주문금액(배송비 제외) 보다 많이 포인트결제할 수 없습니다.");
                    f.od_temp_point.select();
                    return false;
                }

                if (temp_point > <?php echo (int)$member['mb_point']; ?>) {
                    alert("회원님의 포인트보다 많이 결제할 수 없습니다.");
                    f.od_temp_point.select();
                    return false;
                }

                if (temp_point > max_point) {
                    alert(max_point + "점 이상 결제할 수 없습니다.");
                    f.od_temp_point.select();
                    return false;
                }

                if (parseInt(parseInt(temp_point / point_unit) * point_unit) != temp_point) {
                    alert("포인트를 " + String(point_unit) + "점 단위로 입력하세요.");
                    f.od_temp_point.select();
                    return false;
                }
            }

            // pg 결제 금액에서 포인트 금액 차감
            if (settle_method != "무통장") {
                ///f.good_mny.value = od_price + send_cost + send_cost2 - send_coupon - temp_point;
            }
        }

        var tot_price = od_price + send_cost + send_cost2 - send_coupon - temp_point;

        if (document.getElementById("od_settle_iche")) {
            if (document.getElementById("od_settle_iche").checked) {
                if (tot_price < 150) {
                    alert("계좌이체는 150원 이상 결제가 가능합니다.");
                    return false;
                }
            }
        }

        if (document.getElementById("od_settle_card")) {
            if (document.getElementById("od_settle_card").checked) {
                if (tot_price < 1000) {
                    alert("신용카드는 1000원 이상 결제가 가능합니다.");
                    return false;
                }
            }
        }

        if (document.getElementById("od_settle_hp")) {
            if (document.getElementById("od_settle_hp").checked) {
                if (tot_price < 350) {
                    alert("휴대폰은 350원 이상 결제가 가능합니다.");
                    return false;
                }
            }
        }
        
        if ($("#od_agree").prop("checked") !== true) {
            alert("약관에 동의해 주세요")
            return false;
        }
        
        if ($("#non_member_info").prop("checked") !== true) {
            alert("개인정보수집에 동의해 주세요")
            return false;
        }

        
        //if (f.adult_check.value != "OK") {
        //    jsSubmit2();
        //    return;
        //}

        <?php if($default['de_tax_flag_use']) { ?>
        calculate_tax();
        <?php } ?>

        <?php if($default['de_pg_service'] == 'inicis') { ?>
        if (f.action != form_action_url) {
            f.action = form_action_url;
            f.removeAttribute("target");
            f.removeAttribute("accept-charset");
        }
        <?php } ?>

        // 카카오페이 지불
        if (settle_method == "KAKAOPAY") {
            <?php if($default['de_tax_flag_use']) { ?>
            f.SupplyAmt.value = parseInt(f.comm_tax_mny.value) + parseInt(f.comm_free_mny.value);
            f.GoodsVat.value = parseInt(f.comm_vat_mny.value);
            <?php } ?>
            getTxnId(f);
            return false;
        }

        var form_order_method = '';

        if (settle_method == "lpay" || settle_method == "inicis_kakaopay") {      //이니시스 L.pay 또는 이니시스 카카오페이 이면 ( 이니시스의 삼성페이는 모바일에서만 단독실행 가능함 )
            form_order_method = 'samsungpay';
        } else if (settle_method == "간편결제") {
            if (jQuery("input[name='od_settle_case']:checked").attr("data-pay") === "naverpay") {
                form_order_method = 'nhnkcp_naverpay';
            }
        }


        if (jQuery(f).triggerHandler("form_sumbit_order_" + form_order_method) !== false) {


            if (settle_method != "무통장") {

                payment();
            } else {
                f.action = '/shop/orderformupdate.php';
                f.submit();
            }


        }

    }

    // 구매자 정보와 동일합니다.
    function gumae2baesong() {
        var f = document.MAINPAY_FORM;

        f.od_b_name.value = f.od_name.value;
        f.od_b_hp.value = f.od_hp.value;

        calculate_sendcost(String(f.od_b_zip.value));
    }

    <?php if ($default['de_hope_date_use']) { ?>
    $(function () {
        $("#od_hope_date").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: "yy-mm-dd",
            showButtonPanel: true,
            yearRange: "c-99:c+99",
            minDate: "+<?php echo (int)$default['de_hope_date_after']; ?>d;",
            maxDate: "+<?php echo (int)$default['de_hope_date_after'] + 6; ?>d;"
        });
    });
    <?php } ?>
    $(document).ready(() => {
        $(".sit_qty_minus").on("click", (e) => {
            var ct_id = $(e.srcElement).parent().data("ctid");
            if (ct_id == undefined) ct_id = $(e.srcElement).data("ctid");
            var qty = $("#qty_" + ct_id).val();
            if (qty - 1 == 0) {
                alert("최소 1개의 수량이 있어야 합니다.")
                return;
            }
            var price = $("#sell_price_" + ct_id).html().replace("원", "").replace(/,/g, "")
            var min_price = parseInt(price / qty, 10)
            qty--;
            $("#qty_" + ct_id).val(qty);
            $("#sell_price_" + ct_id).html((min_price * qty).toLocaleString() + "원")
            sendQty(ct_id, qty);
        });
        
        $(".sit_qty_plus").on("click", (e) => {
            var ct_id = $(e.srcElement).parent().data("ctid");
            if (ct_id == undefined) ct_id = $(e.srcElement).data("ctid");
            var qty = $("#qty_" + ct_id).val();
            var price = $("#sell_price_" + ct_id).html().replace("원", "").replace(/,/g, "")
            var min_price = parseInt(price / qty, 10)
            qty++;
            $("#qty_" + ct_id).val(qty);
            $("#sell_price_" + ct_id).html((min_price * qty).toLocaleString() + "원")
            sendQty(ct_id, qty);
        });
    });

    function checkqty(ct_id, price, qty) {
        console.log(ct_id, price, qty)
        $("#sell_price_" + ct_id).html((price * qty).toLocaleString() + "원")
        sendQty(ct_id, qty);
    }

    function sendQty(ct_id, qty) {
        if (ct_id == undefined) return;
        if (qty <= 0) return;
        $.ajax({
            url : "/shop/ajax.cartupdate_qty.php"
            , type : "POST"
            , data : { "ct_id" : ct_id, "qty" : qty, "sw_direct" : <?php echo $sw_direct; ?> }
        }).done((res) => {
            var json = JSON.parse(res);
            var od_coupon = parseInt($("input[name=od_coupon]").val());
            var od_temp_point = parseInt($("input[name=od_temp_point]").val());
            
            if (json.tot_price - od_coupon - od_temp_point < 0) {
                alert("최소 금액이하로 주문할 수 없습니다.")
                qty++
                $("#qty_" + ct_id).val(qty);
                sendQty(ct_id, qty);
                return false
            }
            
            <?php if($temp_point > 0 && $is_member) { ?>
            calculate_temp_point();
            <?php } ?>
            
            if (parseInt(json.tot_price - od_coupon, 10) < 50000) json.tot_send_cost = 5000;
            
            $("input[name=od_price]").val(json.tot_price);
            $("input[name=od_send_cost]").val(json.tot_send_cost);
            if ($("#od_b_zip").val()) {
                calculate_sendcost($("#od_b_zip").val())
            } else {
                $("input[name=od_send_cost2]").val(0);
                $("input[name=od_send_coupon]").val(od_coupon);
                $("input[name=od_coupon]").val(od_coupon);
                $("input[name=od_temp_point]").val(od_temp_point);
                
                $("#tot_use_point").html(number_format(String(od_temp_point)) + "P");
                $("#od_tot_price").html(parseInt(json.tot_price, 10).toLocaleString() + "원");
                $("#tot_send_cost").html(parseInt(json.tot_send_cost, 10).toLocaleString() + "원");
                $("#tot_coupon").html(number_format(String(od_coupon)) + "원");
                $("#tot_sell_price").html((parseInt(json.tot_price - od_coupon - od_temp_point, 10) + parseInt(json.tot_send_cost, 10)).toLocaleString() + "원")
                $("#tot_point").html(parseInt(json.tot_point, 10).toLocaleString() + "P");
                $("#od_price").val(parseInt(json.tot_price - od_coupon, 10))
            }
        })
    }
</script>
<form name="form1"></form>