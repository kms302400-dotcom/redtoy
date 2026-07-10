<?php
include_once('./_common.php');

$cart_action_url = G5_SHOP_URL.'/cartupdate.php';

// 테마에 cart.php 있으면 include
if(defined('G5_THEME_MSHOP_PATH')) {
    $theme_cart_file = G5_THEME_MSHOP_PATH.'/cart.php';
    if(is_file($theme_cart_file)) {
        include_once($theme_cart_file);
        return;
        unset($theme_cart_file);
    }
}

$g5['title'] = '장바구니';
include_once(G5_MSHOP_PATH.'/_head.php');

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
                a.io_id
           from {$g5['g5_shop_cart_table']} a left join {$g5['g5_shop_item_table']} b on ( a.it_id = b.it_id )
          where a.od_id = '$s_cart_id' ";
//$sql .= " group by a.it_id ";
$sql .= " order by a.ct_id ";
$result = sql_query($sql);

$cart_count = sql_num_rows($result);
?>

    <script src="<?php echo G5_JS_URL; ?>/shop.js?ver=<?php echo G5_JS_VER; ?>"></script>
    <script src="<?php echo G5_JS_URL; ?>/shop.override.js?ver=<?php echo G5_JS_VER; ?>"></script>

<div id="sod_bsk" class="cartpage">

    <form name="frmcartlist" id="sod_bsk_list" class="2017_renewal_itemform" method="post" action="<?php echo $cart_action_url; ?>">

        
        <ul class="sod_list">
            <?php if($cart_count) { ?>
            <li id="sod_chk" class="chk_box">
                <input type="checkbox" name="ct_all" value="1" id="ct_all" class="selec_chk" checked>
                <label for="ct_all"><span></span>모두 선택</label>
                <button type="button" onclick="return form_check('seldelete');" class="btn01">선택 삭제</button>
            </li>
            <?php } ?>
        <?php
        $tot_point = 0;
        $tot_sell_price = 0;
        $it_send_cost = 0;

        for ($i=0; $row=sql_fetch_array($result); $i++)
        {
            if ($i==0) { // 계속쇼핑
                $continue_ca_id = $row['ca_id'];
            }

            $a1 = '<a href="'.shop_item_url($row['it_id']).'"><strong>';
            $a2 = '</strong></a>';
            $image_width = 65;
            $image_height = 65;
            $image = get_it_image($row['it_id'], $image_width, $image_height);

            $it_name = $a1 . stripslashes($row['it_name']) . $a2;
            $it_options = print_item_options($row['it_id'], $s_cart_id);
            if($it_options) {
                $mod_options = '<button type="button" id="mod_opt_'.$row['it_id'].'" class="mod_btn mod_options">선택사항수정</button>';
               // $it_name .= ;
            }

            // 배송비
            switch($row['ct_send_cost'])
            {
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
            if($row['it_sc_type'] == 2) {
                $sendcost = get_item_sendcost($row['it_id'], $sum['price'], $sum['qty'], $s_cart_id);

                if($sendcost == 0)
                    $ct_send_cost = '무료';
            }

            $point      = $row['point'];
            $sell_price = $row['price'];
            $io_id  = $row["io_id"];
        ?>

        <li class="sod_li ct_<?php echo $i; ?> checked">
            <input type="hidden" name="ct_id[<?php echo $i; ?>]"    value="<?php echo $row['ct_id']; ?>">
            <input type="hidden" name="it_id[<?php echo $i; ?>]"    value="<?php echo $row['it_id']; ?>">
            <input type="hidden" name="it_name[<?php echo $i; ?>]"  value="<?php echo get_text($row['it_name']); ?>">

            <div class="li_chk chk_box">
                <input type="checkbox" name="ct_chk[<?php echo $i; ?>]" value="1" id="ct_chk_<?php echo $i; ?>" class="selec_chk" checked>
                <label for="ct_chk_<?php echo $i; ?>"><span></span><b class="sound_only">상품선택</b></label>
            </div> 
            <div class="li_op_wr">
                <div class="total_img"><?php echo $image; ?></div>
                <div class="li_name">
                    <?php echo $it_name; ?>
                    <? if ($io_id) { ?>
                    <p><span>옵션</span><?=$io_id?></p>
                    <? } ?>
                </div>
                <div id="sit_opt_added" class="li_mod sit_opt_added">
                    <button type="button" class="sit_qty_minus" data-ctid="<?php echo $row['ct_id']; ?>"><i class="fa fa-minus" aria-hidden="true"></i><span class="sound_only">감소</span></button>
                    <label for="ct_qty_0" class="sound_only">수량</label>
                    <input type="text" onchange="checkqty('<?php echo $row['ct_id']; ?>', '<?php echo $row["sell_price"]; ?>', this.value)" onkeypress="this.onchange();" onpaste="this.onchange();" oninput="this.onchange();" value="<?php echo $row['ct_qty']; ?>" id="qty_<?php echo $row['ct_id']; ?>" class="num_input" size="5">
                    <button type="button" class="sit_qty_plus" data-ctid="<?php echo $row['ct_id']; ?>"><i class="fa fa-plus" aria-hidden="true"></i><span class="sound_only">증가</span></button>
                </div>
            </div> 
             <div class="total_price total_span">
                <strong id="sell_price_<?php echo $row["ct_id"]; ?>"><?php echo number_format($sell_price); ?>원</strong>
            </div>
        </li>
        <?php
            $tot_point      += $point;
            $tot_sell_price += $sell_price;
        } // for 끝

        if ($i == 0) {
            echo '<li class="empty_list">장바구니에 담긴 상품이 없습니다.</li>';
        } else {
            // 배송비 계산
            $send_cost = get_sendcost($s_cart_id, 0);
        }
        ?>
    </ul>

    <!-- <div class="btn_del_wr">
        <button type="button" onclick="return form_check('seldelete');" class="btn01">선택삭제</button>
        <button type="button" onclick="return form_check('alldelete');" class="btn01">비우기</button>
    </div> -->

    <?php if ($i == 0) { ?>
    <div class="go_shopping"><a href="<?php echo G5_SHOP_URL; ?>/" class="btn01">쇼핑 계속하기</a></div>
    <?php } else { ?>
    <div class="sod_ta_wr">
        <div class="ct_totbox">
            <h2>장바구니</h2>
            <?php
                $tot_price = $tot_sell_price + $send_cost; // 총계 = 주문상품금액합계 + 배송비
                if ($tot_price > 0 || $send_cost > 0) {
            ?>
            <ul>
                <li>
                    <span>총 상품 금액</span>
                    <span id="tot_price"><?php echo number_format($tot_sell_price); ?>원</span>
                </li>
                <li>
                    <span>총 배송비</span>
                    <span id="tot_send_cost"><?php echo number_format($send_cost); ?>원</span>
                </li>
                <!-- li>
                    <span>쿠폰 사용</span>
                    <span>0원</span>
                </li>
                <li>
                    <span>포인트 사용</span>
                    <span>0원</span>
                </li -->
            </ul>
            <div class="total">
                <span>최종 결제 금액</span>
                <span class="total_cnt" id="tot_sell_price"><?php echo number_format($tot_price); ?>원</span>
                <p><span class="point" id="tot_point"><?php echo number_format($tot_point); ?>P</span>적립</p>
            </div>
        </div>
        <!-- <dl id="m_sod_bsk_tot">
            <?php if ($send_cost > 0) { // 배송비가 0 보다 크다면 (있다면) ?>
            <dt class="sod_bsk_dvr">배송비</dt>
            <dd class="sod_bsk_dvr"><strong><?php echo number_format($send_cost); ?> 원</strong></dd>
            <?php } ?>

            <?php if ($tot_price > 0) { ?>
            <dt>회원 적립 포인트</dt>
            <dd><strong><?php echo number_format($tot_point); ?> 점</strong></dd>
            <dt class="sod_bsk_cnt">총계</dt>
            <dd class="sod_bsk_cnt"><strong><?php echo number_format($tot_price); ?></strong> 원</dd>
            <?php } ?>
        </dl>
        <?php } ?> -->

        <div id="sod_bsk_act" class="btn_confirm">
            <input type="hidden" name="url" value="<?php echo G5_SHOP_URL; ?>/orderform.php">
            <input type="hidden" name="act" value="">
            <input type="hidden" name="records" value="<?php echo $i; ?>">
            <input type="hidden" name="sw_direct" value="<?php echo $sw_direct; ?>"> <!-- 다이렉트 구매 추가 필드 (gnuwiz) -->
            <button type="button" onclick="return form_check('buy');" class="btn_submit"><span><?php echo number_format($cart_count); ?></span>개 상품 구매하기</button>
        </div>
    </div>
    <?php } ?>
        <?php if ($naverpay_button_js) { ?>
        <div class="naverpay-cart"><?php echo $naverpay_request_js.$naverpay_button_js; ?></div>
        <?php } ?>
    </form>
</div>

<script>
$(function() {
    var close_btn_idx;

    // 선택사항수정
    $(".mod_options").click(function() {
        var it_id = $(this).attr("id").replace("mod_opt_", "");
        var $this = $(this);
        close_btn_idx = $(".mod_options").index($(this));

        $.post(
            "./cartoption.php",
            { it_id: it_id },
            function(data) {
                $("#mod_option_frm").remove();
                $this.after("<div id=\"mod_option_frm\"></div><div class=\"mod_option_bg\"></div>");
                $("#mod_option_frm").html(data);
                price_calculate();
            }
        );
    });

    // 모두선택
    $("input[name=ct_all]").click(function() {
        if($(this).is(":checked")){
            $("input[name^=ct_chk]").attr("checked", true);
            $("li.sod_li").addClass("checked");
        } else {
            $("input[name^=ct_chk]").attr("checked", false);
            $("li.sod_li").removeClass("checked");
        }
    });

    // 체크박스 효과
    $('.li_chk .selec_chk').on('click', function() {        
        const name = $(this).attr('name');
        const index = name.match(/\d+/)[0];        
        const targetElement = $('.ct_' + index);
        
        if ($(this).prop('checked')) {
          targetElement.addClass('checked'); // checked 클래스 추가
        } else {
          targetElement.removeClass('checked'); // checked 클래스 제거
        }
      });

    // 옵션수정 닫기
    $(document).on("click", "#mod_option_close", function() {
        $("#mod_option_frm, .mod_option_bg").remove();
        $("#win_mask, .window").hide();
        $(".mod_options").eq(close_btn_idx).focus();
    });
    $("#win_mask").click(function () {
        $("#mod_option_frm").remove();
        $("#win_mask").hide();
        $(".mod_options").eq(close_btn_idx).focus();
    });

});

function fsubmit_check(f) {
    if($("input[name^=ct_chk]:checked").length < 1) {
        alert("구매하실 상품을 하나이상 선택해 주십시오.");
        return false;
    }

    return true;
}

function form_check(act) {
    var f = document.frmcartlist;
    var cnt = f.records.value;

    if (act == "buy")
    {
        f.act.value = act;
        f.submit();
    }
    else if (act == "alldelete")
    {
        f.act.value = act;
        f.submit();
    }
    else if (act == "seldelete")
    {
        if($("input[name^=ct_chk]:checked").length < 1) {
            alert("삭제하실 상품을 하나이상 선택해 주십시오.");
            return false;
        }

        f.act.value = act;
        f.submit();
    }

    return true;
}
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
        $("#tot_price").html(parseInt(json.tot_price, 10).toLocaleString() + "원");
        if (parseInt(json.tot_price, 10) < 50000) json.tot_send_cost = 5000;
        $("#tot_send_cost").html(parseInt(json.tot_send_cost, 10).toLocaleString() + "원");
        $("#tot_sell_price").html((parseInt(json.tot_price, 10) + parseInt(json.tot_send_cost, 10)).toLocaleString() + "원")
        $("#tot_point").html(parseInt(json.tot_point, 10).toLocaleString() + "P");
    })
}
</script>

<?php
include_once(G5_MSHOP_PATH.'/_tail.php');