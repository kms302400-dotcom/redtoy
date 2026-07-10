<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once(G5_THEME_LIB_PATH.'/theme.shop.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_SHOP_CSS_URL.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.bxslider.js"></script>', 10);
?>

<style>
    h3 {display: none;}
    #container {padding: 0;}
    #container_title {display: none;}
    @media (max-width: 970px){
        #sit_ov, #sit_ov_bottom {padding: 15px 0;}
        .ft_fix {display:none;}
    }
</style>

<form name="fitem" action="<?php echo $action_url; ?>" method="post" onsubmit="return fitem_submit(this);">
    <input type="hidden" name="it_id[]" value="<?php echo $it['it_id']; ?>">
    <input type="hidden" name="sw_direct">
    <input type="hidden" name="url">

    <div id="sit_ov_wrap">
        <div class="sit_ov_innr">
            <div id="sit_pvi">
                <ul id="sit_pvi_big">
                    <?php
                    $big_img_count = 0;
                    $thumbnails = array();
                    for($i=1; $i<=10; $i++) {
                        if(!$it['it_img'.$i])
                            continue;

                        $img = get_it_thumbnail($it['it_img'.$i], 460, 460);

                        if($img) {
                            // 썸네일
                            $thumb = get_it_thumbnail($it['it_img'.$i], 70, 70);
                            $thumbnails[] = $thumb;
                            $big_img_count++;

                            echo '<li><a href="'.G5_SHOP_URL.'/largeimage.php?it_id='.$it['it_id'].'&amp;no='.$i.'" target="_blank" class="popup_item_image">'.$img.'</a></li>';
                        }
                    }

                    if($big_img_count == 0) {
                        echo '<img src="'.G5_SHOP_URL.'/img/no_image.gif" alt="">';
                    }
                    ?>
                </ul>
                <?php
                // 썸네일
                $thumb1 = true;
                $thumb_count = 0;
                $total_count = count($thumbnails);
                if($total_count > 0) {
                    echo '<div class="sit_pvi_thumb">';
                    foreach($thumbnails as $val) {
                        $sit_pvi_last ='';
                        if ($thumb_count % 5 == 0) $sit_pvi_last = 'class="li_last"';
                        echo '<a href="" data-slide-index="'.$thumb_count.'">'.$val.'</a>';
                        $thumb_count++;
                    }
                    echo '</div>';
                }
                ?>

                <script>
                    $(document).ready(function(){
                        $('#sit_pvi_big').show().bxSlider({
                            speed: 300,
                            pagerCustom: '.sit_pvi_thumb',
                            controls:false,
                            auto: true,
                            mode: 'fade'
                        });
                    });
                </script>
            </div>

            <section id="sit_ov" class="2017_renewal_itemform">
                <h2>상품간략정보 및 구매기능</h2>
                <div class="sit_ov_wr">
                    <?php if ($it['it_brand']) { ?>
                        <div class="it_brand"><?php echo $it['it_brand']; ?></div>
                    <?php } ?>
                    <strong id="sit_title"><?php echo stripslashes($it['it_name']); ?></strong>
                    <?php if($it['it_basic']) { ?><p id="sit_desc"><?php echo $it['it_basic']; ?></p><?php } ?>
                    <?php if($is_orderable) { ?>
                        <p id="sit_opt_info">상품 선택옵션 <?php echo $option_count; ?> 개, 추가옵션 <?php echo $supply_count; ?> 개</p>
                    <?php } ?>

                    <div class="sit_ov_tbl">
                        <table>
                            <colgroup>
                                <col class="grid_2">
                                <col>
                            </colgroup>
                            <tbody>
                            <?php if ($it['it_maker']) { ?>
                                <tr>
                                    <th scope="row">제조사</th>
                                    <td><?php echo $it['it_maker']; ?></td>
                                </tr>
                            <?php } ?>

                            <?php if ($it['it_origin']) { ?>
                                <tr>
                                    <th scope="row">원산지</th>
                                    <td><?php echo $it['it_origin']; ?></td>
                                </tr>
                            <?php } ?>

                            <?php if ($it['it_model']) { ?>
                                <tr>
                                    <th scope="row">모델</th>
                                    <td><?php echo $it['it_model']; ?></td>
                                </tr>
                            <?php } ?>
                            <?php if (!$it['it_use']) { // 판매가능이 아닐 경우 ?>
                                <tr>
                                    <th scope="row">판매가격</th>
                                    <td>판매중지</td>
                                </tr>
                            <?php } else if ($it['it_tel_inq']) { // 전화문의일 경우 ?>
                                <tr>
                                    <th scope="row">판매가격</th>
                                    <td>전화문의</td>
                                </tr>
                            <?php } else { // 전화문의가 아닐 경우?>
                                <!-- 리뷰 있을경우 -->
                                <?php if ($score = get_star_image($it['it_id'])) { ?>
                                    <tr>
                                        <td colspan="2" class="ifs_score">                                        
                                            <img src="<?php echo G5_SHOP_URL; ?>/img/s_star<?php echo $score?>.png" alt="고객평점 <?php echo $score?>개" class="sit_star" width="70">
                                            <span><?php echo get_use_count($it['it_id']); ?>개 리뷰</span>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if ($it['it_cust_price']) { // 1.00.03?>
                                    <tr>
                                        <td colspan="2">
                                            <span class="ifs_price"><?php echo display_price($it['it_cust_price']); ?></span>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="2">
                                        <?php if ($it['it_cust_price']) { // 1.00.03?>
                                        <span class="ifs_per"><?php echo round((1-(get_price($it)/$it['it_cust_price']))*100,0)."%"; ?></span>
                                        <?php } ?>
                                        <span class="cs_price"> <?php echo display_price(get_price($it)); ?></span>
                                        <input type="hidden" id="it_price" value="<?php echo get_price($it); ?>">

                                        
                                    </td>
                                </tr>
                            <?php } ?>

                            <!-- 쿠폰 적용 가격 -->
                            <!-- <tr class="cp_price">
                                <td colspan="2">
                                    <span class="tit">쿠폰 적용시</span>
                                    <span class="txt">79,000원</span>
                                    <div id="showPopup">
                                        <div class="coupon_btn">
                                            쿠폰받기 
                                        </div>
                                    </div>
                                    <div id="popupContainer" class="popup-container">
                                        <div class="popup">
                                            <span id="closePopup" class="close-btn">×</span>
                                            <div>
                                                <?php
                                                // include_once(G5_MSHOP_SKIN_PATH.'/couponzone.20.skin.php');
                                                ?>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr> -->

                            <?php
                            /* 재고 표시하는 경우 주석 해제
                            <tr>
                                <th scope="row">재고수량</th>
                                <td><?php echo number_format(get_it_stock_qty($it_id)); ?> 개</td>
                            </tr>
                            */
                            ?>

                            <?php if ($config['cf_use_point']) { // 포인트 사용한다면 ?>
                                <!--<tr>
                    <th scope="row"><label for="disp_point">포인트</label></th>
                    <td>
                        <?php
                                if($it['it_point_type'] == 2) {
                                    $it_point = get_item_point($it);
                                    echo number_format($it_point).'점';
                                } else {
                                    $it_point = get_item_point($it);
                                    echo number_format($it_point).'점';
                                }
                                ?>
                    </td>
                </tr>-->
                            <?php } ?>
                            <?php
                            $ct_send_cost_label = '배송비';

                            if($it['it_sc_type'] == 1)
                                $sc_method = '무료배송';
                            else {
                                if($it['it_sc_method'] == 1)
                                    $sc_method = '수령후 지불';
                                else if($it['it_sc_method'] == 2) {
                                    $ct_send_cost_label = '<label for="ct_send_cost">배송비</label>';
                                    $sc_method = '<select name="ct_send_cost" id="ct_send_cost">
                                          <option value="0">주문시 결제</option>
                                          <option value="1">수령후 지불</option>
                                      </select>';
                                }
                                else
                                    $sc_method = '5,000원 (5만원 이상 구매시 무료배송)';
                            }
                            ?>
                            <tr style="display: none">
                                <th><?php echo $ct_send_cost_label; ?></th>
                                <td><?php echo $sc_method; ?></td>
                            </tr>
                            <?php if($it['it_buy_min_qty']) { ?>
                                <tr>
                                    <th>최소구매수량</th>
                                    <td><?php echo number_format($it['it_buy_min_qty']); ?> 개</td>
                                </tr>
                            <?php } ?>
                            <?php if($it['it_buy_max_qty']) { ?>
                                <tr>
                                    <th>최대구매수량</th>
                                    <td><?php echo number_format($it['it_buy_max_qty']); ?> 개</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($it['it_use'] && !$it['it_tel_inq'] && !$is_soldout) { ?>
                    <div id="sit_pr_info">
                        <div>
                            <p class="tit">구매혜택</p>
                            <p class="txt">
                                <span><?=number_format($it_point)?>P 포인트 적립</span>
                                <span>일반 리뷰 작성 시 100P 적립</span>
                                <span>포토 리뷰 작성 시 500P 적립</span>
                            </p>
                        </div>
                        <div>
                            <p class="tit">배송안내</p>
                            <p class="txt">
                                <span>5,000원 (50,000원 이상 주문 시 무료배송)</span>
                                <span>16시 이전 주문 시 당일 출고</span>
                            </p>
                        </div>
                    </div>

                    <?php
                    if($option_item) {
                        ?>
                        <section class="sit_option">
                            <h3>선택옵션</h3>
                            <table class="sit_op_sl">
                                <colgroup>
                                    <col class="grid_2">
                                    <col>
                                </colgroup>
                                <tbody>
                                <?php // 선택옵션
                                echo $option_item;
                                ?>
                                </tbody>
                            </table>
                        </section>
                        <?php
                    }
                    ?>

                    <?php
                    if($supply_item) {
                        ?>
                        <section class="sit_option">
                            <h3>추가옵션</h3>
                            <table class="sit_op_sl">
                                <colgroup>
                                    <col class="grid_2">
                                    <col>
                                </colgroup>
                                <tbody>
                                <?php // 추가옵션
                                echo $supply_item;
                                ?>
                                </tbody>
                            </table>
                        </section>
                        <?php
                    }
                    ?>
                    
                    <div class="sit_sel_option">
                        <?php
                        if(!$option_item) {
                            if(!$it['it_buy_min_qty'])
                                $it['it_buy_min_qty'] = 1;
                            ?>
                            <ul class="sit_opt_added">
                                <li class="sit_opt_list">
                                    <input type="hidden" name="io_type[<?php echo $it_id; ?>][]" value="0">
                                    <input type="hidden" name="io_id[<?php echo $it_id; ?>][]" value="">
                                    <input type="hidden" name="io_value[<?php echo $it_id; ?>][]" value="<?php echo $it['it_name']; ?>">
                                    <input type="hidden" class="io_price" value="0">
                                    <input type="hidden" class="io_stock" value="<?php echo $it['it_stock_qty']; ?>">
                                    <div class="opt_name">
                                        <span class="sit_opt_subj"><?php echo $it['it_name']; ?></span>
                                    </div>
                                    <div class="opt_count">
                                        <label for="ct_qty_<?php echo $i; ?>" class="sound_only">수량</label>
                                        <button type="button" class="sit_qty_minus"><i class="fa fa-minus" aria-hidden="true"></i><span class="sound_only">감소</span></button>
                                        <input type="text" name="ct_qty[<?php echo $it_id; ?>][]" value="<?php echo $it['it_buy_min_qty']; ?>" id="ct_qty_<?php echo $i; ?>" class="num_input input_qty ct_qty" size="5">
                                        <button type="button" class="sit_qty_plus"><i class="fa fa-plus" aria-hidden="true"></i><span class="sound_only">증가</span></button>
                                        <span style="display:block; text-align:right; line-height:30px;">
                                            <?php echo display_price(get_price($it)); ?>
                                        </span>
                                    </div>
                                </li>
                            </ul>
                            <script>
                                $(function() {
                                    price_calculate();
                                });
                            </script>
                        <?php } ?>
                    </div>
                    <div class="sit_tot_price"></div>
                <?php } ?>

                <?php if($is_soldout) { ?>
                    <p id="sit_ov_soldout">상품의 재고가 부족하여 구매할 수 없습니다.</p>
                <?php } ?>

                <div id="sit_ov_btn">
                    <?php if ($is_orderable) { ?>
                        <button type="submit" onclick="document.pressed=this.value;" value="장바구니" id="sit_btn_cart">장바구니</button>
                        <button type="submit" onclick="document.pressed=this.value;" value="바로구매" id="sit_btn_buy">바로구매</button>
                    <?php } ?>
                    <?php if(!$is_orderable && $it['it_soldout'] && $it['it_stock_sms']) { ?>
                        <a href="javascript:popup_stocksms('<?php echo $it['it_id']; ?>');" id="sit_btn_phone">재입고알림</a>
                    <?php } ?>
                    <a href="javascript:item_wish(document.fitem, '<?php echo $it['it_id']; ?>');" id="sit_btn_wish"><span class="material-symbols-outlined">favorite</span></a>
                    <?php if ($naverpay_button_js) { ?>
                        <div class="naverpay-item"><?php echo $naverpay_request_js.$naverpay_button_js; ?></div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </div>

    <!-- 다른 상품 보기 시작 { -->
    <!--div id="sit_siblings">
	    <?php
    if ($prev_href || $next_href) {
        echo $prev_href.$prev_title.$prev_href2;
        echo $next_href.$next_title.$next_href2;
    } else {
        echo '<span class="sound_only">이 분류에 등록된 다른 상품이 없습니다.</span>';
    }
    ?>
	</div-->
    <!-- } 다른 상품 보기 끝 -->
<div style="position:relative; padding-bottom:50px; background:#fff;">
    <span class="tab_menu_sit_inf"></span>
    <section id="tab_menu">
        <ul>
            <li onclick="tabMenu('sit_inf')" class="tab_sit_inf active">상품 상세정보</li>
            <li onclick="tabMenu('sit_use')" class="tab_sit_use">리얼 리뷰 [ <span class="item_use_count"><?php echo $item_use_count; ?> ]</span></li>
            <li onclick="tabMenu('sit_dvr')" class="tab_sit_dvr">안심비밀배송</li>
            <li onclick="tabMenu('sit_ex')" class="tab_sit_ex">반품 및 교환</li>
        </ul>
    </section>
    <div id="sit_ov_bottom">
        <section id="sit_inf" class="sit_con">
            <!-- 모바일 일때 구매하기 버튼 -->
            <div class="mo_pur_fix">
                <div>
                    <a href="javascript:item_wish(document.fitem, '<?php echo $it['it_id']; ?>');"><span class="material-symbols-outlined">favorite</span></a>
                    <span class="show_pur_btn" onclick="showPurBox();">구매하기</span>
                </div>
            </div>
            <!-- 사이드 구매박스 -->
            <div id="pur_box_bg">
                <div id="pur_box">
                    <?php
                   if($option_item) {
                       ?>
                       <section class="sit_option">
                           <h3>선택옵션</h3>
                           <table class="sit_op_sl">
                               <colgroup>
                                   <col class="grid_2">
                                   <col>
                               </colgroup>
                               <tbody>
                               <?php // 선택옵션
                               echo str_replace("it_option", "it_option2", $option_item);
                               ?>
                               </tbody>
                           </table>
                       </section>
                       <?php
                   }
                   ?>

                   <?php
                   if($supply_item) {
                       ?>
                       <section class="sit_option">
                           <h3>추가옵션</h3>
                           <table class="sit_op_sl">
                               <colgroup>
                                   <col class="grid_2">
                                   <col>
                               </colgroup>
                               <tbody>
                               <?php // 추가옵션
                               echo str_replace("it_supply", "it_supply2", $supply_item);
                               ?>
                               </tbody>
                           </table>
                       </section>
                       <?php
                   }
                   ?>
                    <div class="sit_sel_option sit_sel_option2">
                       <?php
                       if(!$option_item) {
                           if(!$it['it_buy_min_qty'])
                               $it['it_buy_min_qty'] = 1;
                           ?>
                           <ul class="sit_opt_added">
                               <li class="sit_opt_list">
                                   <input type="hidden" name="io_type[<?php echo $it_id; ?>][]" value="0">
                                   <input type="hidden" name="io_id[<?php echo $it_id; ?>][]" value="">
                                   <input type="hidden" name="io_value[<?php echo $it_id; ?>][]" value="<?php echo $it['it_name']; ?>">
                                   <input type="hidden" class="io_price" value="0">
                                   <input type="hidden" class="io_stock" value="<?php echo $it['it_stock_qty']; ?>">
                                   <div class="opt_name">
                                       <span class="sit_opt_subj"><?php echo $it['it_name']; ?></span>
                                   </div>
                                   <div class="opt_count">
                                       <label for="ct_qty_<?php echo $i; ?>" class="sound_only">수량</label>
                                       <button type="button" class="sit_qty_minus"><i class="fa fa-minus" aria-hidden="true"></i><span class="sound_only">감소</span></button>
                                       <input type="text" name="ct_qty[<?php echo $it_id; ?>][]" value="<?php echo $it['it_buy_min_qty']; ?>" id="ct_qty_<?php echo $i; ?>" class="num_input input_qty ct_qty" size="5">
                                       <button type="button" class="sit_qty_plus"><i class="fa fa-plus" aria-hidden="true"></i><span class="sound_only">증가</span></button>
                                       <span style="display:block; text-align:right; line-height:30px;">
                                           <?php echo display_price(get_price($it)); ?>
                                       </span>
                                   </div>
                               </li>
                           </ul>
                           <script>
                               $(function() {
                                   price_calculate();
                               });
                           </script>
                       <?php } ?>
                    </div>                   
                    <div class="sit_tot_price sit_tot_price2"></div>
                    <!-- <div class="cp_box">
                        <span>받지 않은 쿠폰이 더 있어요!</span>
                        <div id="cp_pop">쿠폰받기</div>
                    </div> -->
                    <div id="sit_ov_btn">
                        <?php if ($is_orderable) { ?>
                            <button type="submit" onclick="document.pressed=this.value;" value="장바구니" id="sit_btn_cart">장바구니</button>
                            <button type="submit" onclick="document.pressed=this.value;" value="바로구매" id="sit_btn_buy">바로구매</button>
                        <?php } ?>
                        <?php if(!$is_orderable && $it['it_soldout'] && $it['it_stock_sms']) { ?>
                            <a href="javascript:popup_stocksms('<?php echo $it['it_id']; ?>');" id="sit_btn_phone">재입고알림</a>
                        <?php } ?>
                        <a href="javascript:item_wish(document.fitem, '<?php echo $it['it_id']; ?>');" id="sit_btn_wish"><span class="material-symbols-outlined">favorite</span></a>
                        <?php if ($naverpay_button_js) { ?>
                            <div class="naverpay-item"><?php echo $naverpay_request_js.$naverpay_button_js; ?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- 상품 정보 시작 { -->
            <?php if ($it['it_explan'] || $it['it_mobile_explan']) { // 상품 상세설명 ?>
                <h3>상품 정보</h3>
                <div id="sit_inf_explan">
                    <?php echo ($it['it_mobile_explan'] ? conv_content($it['it_mobile_explan'], 1) : conv_content($it['it_explan'], 1)); ?>
                </div>
                <div class="hidden_img">
                    <span></span>
                    <button type="button" class="more_img">상품 상세 정보 더 보기 &#9660;</button>
                </div>
            <?php } ?>

            <?php
            if ($it['it_info_value']) { // 상품 정보 고시
                $info_data = unserialize(stripslashes($it['it_info_value']));
                if(is_array($info_data)) {
                    $gubun = $it['it_info_gubun'];
                    $info_array = $item_info[$gubun]['article'];
                    ?>

                    <!--h3>상품 정보 고시</h3-->
                    <table id="sit_inf_open">
                        <colgroup>
                            <col class="grid_4">
                            <col>
                        </colgroup>
                        <tbody>
                        <?php
                        foreach($info_data as $key=>$val) {
                            $ii_title = $info_array[$key][0];
                            $ii_value = $val;
                            ?>
                            <tr>
                                <th scope="row"><?php echo $ii_title; ?></th>
                                <td><?php echo $ii_value; ?></td>
                            </tr>
                        <?php } //foreach?>
                        </tbody>
                    </table>
                    <!-- 상품정보고시 end -->
                    <?php
                } else {
                    if($is_admin) {
                        echo '<p>상품 정보 고시 정보가 올바르게 저장되지 않았습니다.<br>config.php 파일의 G5_ESCAPE_FUNCTION 설정을 addslashes 로<br>변경하신 후 관리자 &gt; 상품정보 수정에서 상품 정보를 다시 저장해주세요. </p>';
                    }
                }
            } //if
            ?>
        </section>
        <!-- } 상품 정보 끝 -->
        <span class="tab_menu_sit_use tab"></span>
        <section id="tab_menu">
            <ul>
                <li onclick="tabMenu('sit_inf')" class="tab_sit_inf">상품 상세정보</li>
                <li onclick="tabMenu('sit_use')" class="tab_sit_use active">리얼 리뷰 [ <span class="item_use_count"><?php echo $item_use_count; ?> ]</span></li>
                <li onclick="tabMenu('sit_dvr')" class="tab_sit_dvr">안심비밀배송</li>
                <li onclick="tabMenu('sit_ex')" class="tab_sit_ex">반품 및 교환</li>
            </ul>
        </section>
        </form>
        <!-- 사용후기 시작 { -->
        <section id="sit_use" class="sit_con">
            <h2>상품 후기</h2>
            <div id="itemuse">
                <?php include_once(G5_SHOP_PATH.'/itemuse.php'); ?>
            </div>
        </section>
        <!-- } 사용후기 끝 -->
        <span class="tab_menu_sit_dvr tab"></span>
        <section id="tab_menu">
            <ul>
                <li onclick="tabMenu('sit_inf')" class="tab_sit_inf">상품 상세정보</li>
                <li onclick="tabMenu('sit_use')" class="tab_sit_use">리얼 리뷰 [ <span class="item_use_count"><?php echo $item_use_count; ?> ]</span></li>
                <li onclick="tabMenu('sit_dvr')" class="tab_sit_dvr active">안심비밀배송</li>
                <li onclick="tabMenu('sit_ex')" class="tab_sit_ex">반품 및 교환</li>
            </ul>
        </section>

        <!-- 상품문의 시작 { -->
        <section id="sit_dvr" class="sit_con">
            <h2>안심비밀배송</h2>
            <div id="itemdvr">
                <?php echo conv_content($default['de_baesong_content'], 1); ?>
            </div>
        </section>
        <!-- } 상품문의 끝 -->

        <?php if ($default['de_baesong_content']) { // 배송정보 내용이 있다면 ?>
            <span class="tab_menu_sit_ex tab"></span>
            <section id="tab_menu">
                <ul>
                    <li onclick="tabMenu('sit_inf')" class="tab_sit_inf">상품 상세정보</li>
                    <li onclick="tabMenu('sit_use')" class="tab_sit_use">상품 후기 [ <span class="item_use_count"><?php echo $item_use_count; ?> ]</span></li>
                    <li onclick="tabMenu('sit_dvr')" class="tab_sit_dvr">안심비밀배송</li>
                    <li onclick="tabMenu('sit_ex')" class="tab_sit_ex active">반품 및 교환</li>
                </ul>
            </section>
            
            <!-- 배송정보 시작 { -->
            <section id="sit_ex" class="sit_con">
                <h2>반품 및 교환</h2>
                <div id="itemex">
                    <?php if ($default['de_change_content']) { // 교환/반품 내용이 있다면 ?>
                        <?php echo conv_content($default['de_change_content'], 1); ?>
                    <?php } ?>
                </div>
            </section>
            <!-- } 배송정보 끝 -->
        <?php } ?>
    </div>
</div>

<div id="popupContainer2" class="popup-container review_write_popup" >
    <div class="popup">
        <span id="closePopup2" class="close-btn">×</span>
        <div>
            <?php
            include_once(G5_MSHOP_SKIN_PATH.'/itemuseform.skin.php');
            ?>
        </div>
    </div>
</div>
<div id="popupContainer3" class="popup-container" >
    <div class="popup">
        <span id="closePopup3" class="close-btn">×</span>
        <div>
            <?php
            include_once(G5_MSHOP_SKIN_PATH.'/itemusephoto.skin.php');
            ?>
        </div>
    </div>
</div>
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
<div id="popupContainer5" class="popup-container review_edit_popup" >
    <div class="popup">
        <span id="closePopup5" class="close-btn">×</span>
        <div id="sit_use_modify"></div>
    </div>  
</div>

<script>
    $(window).bind("pageshow", function(event) {
        if (event.originalEvent.persisted) {
            document.location.reload();
        }

        var imgHeight = 0;
        $('#sit_inf_explan img').each(function() {
            imgHeight += $(this).height();
        });
        // console.log(imgHeight);
        if(imgHeight <= 1200) $('.hidden_img').hide();


        if (window.innerWidth >= 970) calculateRightOffset();
    });
    // pur_box 컨텐츠 오른쪽에 맞추기
    function calculateRightOffset() {
        var $element = $('#sit_inf_explan');
        if ($element.length === 0) return;

        var elementRight = $element.offset().left + $element.outerWidth();
        var viewportWidth = document.documentElement.clientWidth; // 스크롤바 제외한 너비

        var rightOffset = viewportWidth - elementRight;
        $('#pur_box').css('right', rightOffset + 'px');
        // console.log('오른쪽 여백:', rightOffset + 'px');
    }
    // 창 크기 변경될 때 다시 실행
    $(window).on('resize', function () {
        if (window.innerWidth >= 1120) {
            $('#pur_box_bg').show();
            $('#pur_box').show();
            calculateRightOffset(); 
        }else{
            $('#pur_box_bg').hide();
            $('#pur_box').hide();            
        }
            
    });

    window.addEventListener('scroll', function() {
        const element = document.getElementById('pur_box');
        const element2 = document.getElementById('sit_ex');
        const elementTop = element.offsetTop;
        const scrollPosition = window.scrollY;

        const rect = element2.getBoundingClientRect();
        const bottomPosition = rect.bottom;

        if (window.innerWidth <= 970) {
            element.style.position = 'fixed';
            element.style.top = 'auto';
        } else{
            if (bottomPosition < 450 || scrollPosition < 876) {
                element.style.position = 'absolute';
                element.style.top = '100px';
            } else if (scrollPosition >= 876) {
                element.style.position = 'fixed';
                element.style.top = '80px';
            }
        }
        
    });

    function showPurBox(){
        $('#ft_to_top').toggle();
        $('#pur_box_bg').toggle();
        $('#pur_box').slideToggle(300);
    }

    $(".more_img").click(function(){
        $('#sit_inf_explan').css('max-height','none');
        $(".hidden_img").hide();
    });

    $('#pur_box_bg').on('click', function(event) {
        if (event.target === this) {
            $('#pur_box').slideToggle(300);
            
            setTimeout(() => {
                $(this).hide();
                $('#ft_to_top').toggle();
            }, 300);
        }
    });

    $(function(){
        // 상품이미지 크게보기
        $(".popup_item_image").click(function() {
            var url = $(this).attr("href");
            var top = 10;
            var left = 10;
            var opt = 'scrollbars=yes,top='+top+',left='+left;
            popup_window(url, "largeimage", opt);

            return false;
        });
    });

    function tabMenu(e){
        let offsetTop = $('.tab_menu_' + e).offset().top;

        if (e == 'sit_inf') {
            offsetTop -= 80;
        }

        $('html, body').animate({
            scrollTop: offsetTop
        }, 500);
    }
    

    // 상품보관
    function item_wish(f, it_id)
    {
        f.url.value = "<?php echo G5_SHOP_URL; ?>/wishupdate.php?it_id="+it_id;
        f.action = "<?php echo G5_SHOP_URL; ?>/wishupdate.php";
        f.submit();
    }

    // 추천메일
    function popup_item_recommend(it_id)
    {
        if (!g5_is_member)
        {
            if (confirm("회원만 추천하실 수 있습니다."))
                document.location.href = "<?php echo G5_BBS_URL; ?>/login.php?url=<?php echo urlencode(shop_item_url($it_id)); ?>";
        }
        else
        {
            url = "<?php echo G5_SHOP_URL; ?>/itemrecommend.php?it_id=" + it_id;
            opt = "scrollbars=yes,width=616,height=420,top=10,left=10";
            popup_window(url, "itemrecommend", opt);
        }
    }

    // 재입고SMS 알림
    function popup_stocksms(it_id)
    {
        url = "<?php echo G5_SHOP_URL; ?>/itemstocksms.php?it_id=" + it_id;
        opt = "scrollbars=yes,width=616,height=420,top=10,left=10";
        popup_window(url, "itemstocksms", opt);
    }

    function fsubmit_check(f)
    {
        // 판매가격이 0 보다 작다면
        if (document.getElementById("it_price").value < 0) {
            alert("전화로 문의해 주시면 감사하겠습니다.");
            return false;
        }

        if($(".sit_opt_list").size() < 1) {
            alert("상품의 선택옵션을 선택해 주십시오.");
            return false;
        }

        var val, io_type, result = true;
        var sum_qty = 0;
        var min_qty = parseInt(<?php echo $it['it_buy_min_qty']; ?>);
        var max_qty = parseInt(<?php echo $it['it_buy_max_qty']; ?>);
        var $el_type = $("input[name^=io_type]");

        $("input[name^=ct_qty]").each(function(index) {
            val = $(this).val();

            if(val.length < 1) {
                alert("수량을 입력해 주십시오.");
                result = false;
                return false;
            }

            if(val.replace(/[0-9]/g, "").length > 0) {
                alert("수량은 숫자로 입력해 주십시오.");
                result = false;
                return false;
            }

            io_type = $el_type.eq(index).val();
            if(io_type == "0")
                sum_qty += parseInt(val);
        });

        if(!result) {
            return false;
        }

        if(min_qty > 0 && sum_qty < min_qty) {
            alert("선택옵션 개수 총합 "+number_format(String(min_qty))+"개 이상 주문해 주십시오.");
            return false;
        }

        if(max_qty > 0 && sum_qty > max_qty) {
            alert("선택옵션 개수 총합 "+number_format(String(max_qty))+"개 이하로 주문해 주십시오.");
            return false;
        }

        return true;
    }
    
    // 바로구매, 장바구니 폼 전송
    function fitem_submit(f) {
        f.action = "<?php echo $action_url; ?>";
        f.target = "";

        if (document.pressed == "장바구니") {
            f.sw_direct.value = 0;
        } else { // 바로구매
            f.sw_direct.value = 1;
        }

        // 판매가격이 0 보다 작다면
        if (document.getElementById("it_price").value < 0) {
            alert("전화로 문의해 주시면 감사하겠습니다.");
            return false;
        }

        if ($(".sit_opt_list").size() < 1) {
            alert("상품의 선택옵션을 선택해 주십시오.");
            return false;
        }

        var val, io_type, result = true;
        var sum_qty = 0;
        var min_qty = parseInt(<?php echo $it['it_buy_min_qty']; ?>);
        var max_qty = parseInt(<?php echo $it['it_buy_max_qty']; ?>);
        var $el_type = $("input[name^=io_type]");

        $("input[name^=ct_qty]").each(function (index) {
            val = $(this).val();

            if (val.length < 1) {
                alert("수량을 입력해 주십시오.");
                result = false;
                return false;
            }

            if (val.replace(/[0-9]/g, "").length > 0) {
                alert("수량은 숫자로 입력해 주십시오.");
                result = false;
                return false;
            }

            io_type = $el_type.eq(index).val();
            if (io_type == "0")
                sum_qty += parseInt(val);
        });

        if (!result) {
            return false;
        }

        if (min_qty > 0 && sum_qty < min_qty) {
            alert("선택옵션 개수 총합 " + number_format(String(min_qty)) + "개 이상 주문해 주십시오.");
            return false;
        }

        if (max_qty > 0 && sum_qty > max_qty) {
            alert("선택옵션 개수 총합 " + number_format(String(max_qty)) + "개 이하로 주문해 주십시오.");
            return false;
        }
        
        if ($("#pur_box_bg input").length > 0) {
            for(let i=0;i<$("#pur_box_bg input").length;i++) {
                $($("#pur_box_bg input")[i]).attr("name", "")
            }
        }

        if (document.pressed == "장바구니") {
            $.post(
                "<?php echo G5_SHOP_URL; ?>/ajax.cartupdate.php",
                $(f).serialize(),
                function (error) {
                    if (error != "OK") {
                        alert(error.replace(/\\n/g, "\n"));
                        return false;
                    }

                    var marginLeft = $(".modalContent").outerWidth() / 2;
                    var marginTop = $(".modalContent").outerHeight() / 2;

                    $("#modalLayer").fadeIn("slow");
                    $(".modalContent").css({"margin-top": -marginTop, "margin-left": -marginLeft});
                    $(this).blur();
                    $(".modalContent > a").focus();
                    return false;


                }
            );

            return false;
        } else {
            return true;
        }
    }

    $(function(){
        $(".gnb_close_btn").click(function(){
            $("#modalLayer").fadeOut("slow");
        });
    });

    //class 추가
    $("#container_title").addClass("con_tit_view");
    $("#container").addClass("con_item_veiw");

    // 공유
    $(".btn_sns_share").click(function(){
        $(".sns_area").show();
    });
    $(".btn_snscl").click(function(){
        $(".sns_area").hide();
    });

    $(window).on("load", function() {
        $("#sit_inf_explan").viewimageresize2();
    });
    
    $("#detail_image").on("error", () => {
        $("#detail_image").attr("src", "/img/img_error.png");
    })

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

    // 레이어 팝업 열기
    // document.getElementById('showPopup').addEventListener('click', function() {
    //     document.getElementById('popupContainer').style.display = 'block';
    // });
    // document.getElementById('cp_pop').addEventListener('click', function() {
    //     document.getElementById('popupContainer').style.display = 'block';
    // });
    document.getElementById('photo_all_popup').addEventListener('click', function() {
        document.getElementById('popupContainer3').style.display = 'block';
    });


    // 레이어 팝업 닫기
    // document.getElementById('closePopup').addEventListener('click', function() {
    //     document.getElementById('popupContainer').style.display = 'none';
    // });
    document.getElementById('closePopup2').addEventListener('click', function() {
        document.getElementById('popupContainer2').style.display = 'none';
    });
    document.getElementById('closePopup3').addEventListener('click', function() {
        document.getElementById('popupContainer3').style.display = 'none';
    });
    document.getElementById('closePopup4').addEventListener('click', function() {
        document.getElementById('popupContainer4').style.display = 'none';
    });
    document.getElementById('closePopup5').addEventListener('click', function() {
        document.getElementById('popupContainer5').style.display = 'none';
    });
    
    $(document).ready(() => {
        $(document).on("keydown", (e) => {
            if (event.keyCode == 27) {
                $("#popupContainer1").hide();
                $("#popupContainer2").hide();
                $("#popupContainer3").hide();
                $("#popupContainer4").hide();
                $("#popupContainer5").hide();
            }
        })
    })
    
    var fileIndex = 0
    var fileArray = new Array();
    var fileList = null;
    var dataTransfer = new DataTransfer();
    $(document).ready(() => {
        $("#review_file_load").on("change", fileChangeUpload);
    })

    function fileChangeUpload() {
        <? if (is_mobile()) { ?>
        alert("포토후기는 PC에서만 등록 가능합니다")
        <? } else { ?>
    	readURL(this);
    	
    	let fileArr = document.getElementById("review_file_load").files

    	if(fileArr != null && fileArr.length > 0){
    		for(var i=0; i<fileArr.length; i++){
    			dataTransfer.items.add(fileArr[i])
    		}
    		document.getElementById("review_file_load").files = dataTransfer.files;
    	}
        <? } ?>
    }

    function fileChangeUpload2() {
    	readURL2(this);
    	
    	let fileArr = document.getElementById("edit_review_file_load").files

    	if(fileArr != null && fileArr.length > 0){
    		for(var i=0; i<fileArr.length; i++){
    			dataTransfer.items.add(fileArr[i])
    		}
    		document.getElementById("edit_review_file_load").files = dataTransfer.files;
    	}
    }

    function reviewUpload() {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $("#review_file_load").click();
    }

    function reviewUpload2() {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $("#edit_review_file_load").click();
    }

    function readURL(input) {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $(input.files).each((i, e, v) => {
            $(".review_img").show()
            if (input.files[i]) {
                if (fileIndex > 4) {
                    alert("최대 5개까지 업로드 가능합니다")
                    return
                } else {
                    let uuid = uuidv4();
                    $(".review_img").append('<span id="previewImageIndent' + uuid + '"><span class="close" onclick="deleteImage(\'' + uuid + '\', \'\')">&#10005;</span><img id="previewImage' + uuid + '" style="width:70px;height:70px" /></span>')
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage' + uuid).attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[i]);
                    fileArray.push({ "uuid" : uuid, "idx" : fileIndex })
                    
                    fileIndex++;
                    $("#file_count").html(fileIndex);
                }
            }
        })
    }

    function readURL2(input) {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $(input.files).each((i, e, v) => {
            $(".edit_review_img").show()
            console.log(input.files[i])
            if (input.files[i]) {
                if (fileIndex > 4) {
                    alert("최대 5개까지 업로드 가능합니다")
                    return
                } else {
                    let uuid = uuidv4();
                    $(".edit_review_img").append('<span id="previewImageIndent' + uuid + '"><span class="close" onclick="deleteImage2(\'' + uuid + '\', \'\')">&#10005;</span><img id="previewImage' + uuid + '" style="width:70px;height:70px" /></span>')
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage' + uuid).attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[i]);
                    fileArray.push({ "uuid" : uuid, "idx" : fileIndex })
                    
                    fileIndex++;
                    $(".edit_file_count").html(fileIndex);
                }
            }
        })
    }

    function deleteImage(uuid, bf_file) {
        $("#previewImageIndent" + uuid).remove();
        var fileObject = fileArray.find(x => x.uuid == uuid)
        if (fileObject) {
            var fileIdxs = fileObject.idx
            
        	dataTransfer.items.remove(fileIdxs)
            document.getElementById("review_file_load").files = dataTransfer.files;
        }
        fileIndex--;
        $("#file_count").html(fileIndex);
        if (fileIndex == 0) $(".review_img").hide()
    }

    function deleteImage2(uuid, bf_file) {
        $("#previewImageIndent" + uuid).remove();
        var fileObject = fileArray.find(x => x.uuid == uuid)
        if (fileObject) {
            var fileIdxs = fileObject.idx
            
        	dataTransfer.items.remove(fileIdxs)
            $(".edit_review_file_load").files = dataTransfer.files;
        }
        fileIndex--;
        $(".edit_file_count").html(fileIndex);
        if (fileIndex == 0) $(".review_img").hide()
        if (bf_file) {
            $input = $("<input type='hidden' name='delfile[]' value='" + bf_file + "' />");
            $(document.fitemuseedit).append($input)
        }
    }
</script>

<?php /* 2017 리뉴얼한 테마 적용 스크립트입니다. 기존 스크립트를 오버라이드 합니다. */ ?>
<script src="<?php echo G5_JS_URL; ?>/shop.override.js"></script>


<div id="modalLayer">
    <div class="mask">
        <div class="modalContent">
            <button type="button" class="gnb_close_btn"><i class="fa fa-times" aria-hidden="true"></i></button>
            <!-- 입력폼 시작 -->
            <div class="cont">
                <p>선택하신 상품이 장바구니에 담겼습니다.</p>
                <a href="<?php echo G5_SHOP_URL; ?>/list.php?ca_id=<?php echo $it['ca_id']; ?>" class="shopping">계속쇼핑하기</a>
                <a href="<?php echo G5_SHOP_URL; ?>/cart.php" class="carting">장바구니보기</a>
            </div>
            <!-- 입력폼 끝// -->
        </div>
    </div>
</div>

<?php
// 최신 리뷰 1건을 가져옴 (승인된 리뷰만)
$review_sql = "SELECT is_name, is_content, is_score, is_time 
               FROM g5_shop_item_use 
               WHERE it_id = '{$it['it_id']}' AND is_confirm = 1 
               ORDER BY is_time DESC 
               LIMIT 1";
$review = sql_fetch($review_sql);
?>

<!-- JSON-LD 구조화 데이터: 상품 정보를 검색엔진에 전달 -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<?= htmlspecialchars($it['it_name']) ?>",
  "image": ["<?= G5_URL . '/data/item/' . $it['it_img1'] ?>"],
  "description": "<?= htmlspecialchars(strip_tags($it['it_9'])) ?>",
  "sku": "<?= $it['it_id'] ?>",
  "brand": {
    "@type": "Brand",
    "name": "<?= htmlspecialchars($it['it_maker'] ?: '레드토이') ?>"
  },
  "offers": {
    "@type": "Offer",
    "url": "<?= G5_SHOP_URL . '/item.php?it_id=' . $it['it_id'] ?>",
    "priceCurrency": "KRW",
    "price": "<?= number_format($it['it_price'], 0, '.', '') ?>",
    "availability": "<?= ($it['it_stock_qty'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' ?>"
  }<?php if ($review) { ?>,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?= $review['is_score'] ?>",
    "reviewCount": "1"
  },
  "review": [
    {
      "@type": "Review",
      "author": {
        "@type": "Person",
        "name": "<?= htmlspecialchars($review['is_name']) ?>"
      },
      "datePublished": "<?= date('Y-m-d', strtotime($review['is_time'])) ?>",
      "reviewBody": "<?= htmlspecialchars(strip_tags($review['is_content'])) ?>",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "<?= intval($review['is_score']) ?>",
        "bestRating": "5"
      }
    }
  ]
<?php } ?>
}
</script>