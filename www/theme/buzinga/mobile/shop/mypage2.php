<?php
include_once('./_common.php');

$g5['title'] = '마이 페이지';

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');

set_session("ss_mb_id", $member["mb_id"]);

///////////////////////////////////
//                               //
//                               //
//    회원정보 수정 임시 페이지    //
//                               //
//                               //
///////////////////////////////////
if ($config['cf_use_addr'])
    add_javascript(G5_POSTCODE_JS, 0);    //다음 주소 js

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
?>
<link rel="stylesheet" href="https://www.redtoy.co.kr/theme/buzinga/mobile/skin/member/basic/style.css">

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
            <li><a href="/shop/mypage.php">주문 배송 조회</a></li>
            <!-- 회원정보 수정 페이지 임시로 만들어놓음 -->
            <li class="active"><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage2.php">회원 정보 수정</a></li>
            <li><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage4.php">리뷰 관리</a></li>
            <li><a href="/bbs/qalist.php">1:1 문의</a></li>
        </ul>
    </div>

    <div id="smb_my" class="member_edit">
        <form id="fregisterform" name="fregisterform" action="/bbs/register_form_update.php" method="POST">
        <input type="hidden" name="mb_id" value="<?=$member["mb_id"]?>" />
            <div class="form_01">
                <ul>
                    <li class="id">
                        <div class="item">
                            <label for="reg_mb_id">아이디</label>
                        </div>
                        <div class="write">
                            <?=$member["mb_id"]?>
                        </div>
                    </li>
                    <li class="password">
                        <div class="item">
                            <label for="reg_mb_password">비밀번호</label>
                        </div>
                        <div class="write">
                            <div class="txt">
                                <span>현재 비밀번호</span>
                                <span>새 비밀번호</span>
                                <span>새 비밀번호 확인</span>
                            </div>
                            <div class="pw_input">
                                <input type="password" name="mb_password_ori" id="reg_mb_password1" class="frm_input" minlength="8" maxlength="20" size="40">
                                <input type="password" name="mb_password" id="reg_mb_password2" class="frm_input" minlength="8" maxlength="20" size="40">
                                <input type="password" name="mb_password_re" id="reg_mb_password3" class="frm_input" minlength="8" maxlength="20" size="40">
                            </div>
                        </div>
                    </li>
                    <li class="rgs_name_li">
                        <div class="item">
                            <label for="reg_mb_name">이름</label>
                        </div>
                        <div class="write">                    
                            <input type="text" value="<?=$member["mb_name"]?>" readonly="" class="frm_input readonly" size="40">
                        </div>
                    </li>
                    <li>
                        <div class="item">
                            <label for="reg_mb_hp">휴대폰 번호</label>
                        </div>
                        <div class="write">                    
                            <input type="text" value="<?=$member["mb_hp"]?>" readonly="" class="frm_input readonly" maxlength="20" size="40">
                            <input type="hidden" name="old_mb_hp" value="">
                            <div class="chk_box">
                                <input type="checkbox" name="mb_sms" value="1" id="reg_mb_sms" checked="" class="selec_chk">
                                <label for="reg_mb_sms">
                                    <span></span>
                                    SMS 수신 동의
                                    <b class="sound_only">SMS 수신여부</b>
                                </label>        
                            </div>
                        </div>
                    </li>
                    <li class="formemail">
                        <div class="item">
                            <label for="reg_mb_email">이메일</label>
                        </div>
                        <div class="write">                    
                            <input type="email" name="mb_email" value="<?=$member["mb_email"]?>" id="reg_mb_email" required="" class="frm_input email" maxlength="100" size="40">
                            
                            <div class="chk_box">
                                <input type="checkbox" name="mb_mailling" value="1" id="reg_mb_mailling" checked="" class="selec_chk">
                                <label for="reg_mb_mailling">
                                    <span></span>
                                    이메일 수신 동의
                                    <b class="sound_only">메일링서비스</b>
                                </label>
                            </div>
                        </div>
                    </li>
                    <li class="address">
                        <div class="item">
                            <label for="reg_mb_name">주소</label>
                        </div>
                        <div class="write">                    
                            <button type="button" class="btn_address" onclick="win_zip('fregisterform', 'ad_zip', 'ad_addr1', 'ad_addr2', 'ad_addr3', 'ad_addr_jibeon');">주소 찾기</button>
                            <input type="text" name="ad_zip" id="ad_zip" value="<?=$ad_zip1 . $ad_zip2?>" value="<?=$member["mb_zip1"] . $member["mb_zip2"]?>" required="" class="frm_input" size="21" maxlength="6" placeholder="우편번호" readonly="">
                            <br>
                            <input type="text" name="ad_addr1" id="ad_addr1" value="<?=$ad_addr1?>" required="" class="frm_input frm_address" size="60" placeholder="기본주소" readonly="">
                            <br>
                            <input type="text" name="ad_addr2" id="ad_addr2" value="<?=$ad_addr2?>" class="frm_input frm_address" size="60" placeholder="상세주소">
                            <input type="hidden" name="ad_addr3" id="ad_addr3" value="<?=$ad_addr3?>" />
                            <input type="hidden" name="ad_addr_jibeon" id="ad_addr_jibeon" value="<?=$ad_jibeon?>" />
                        </div>
                    </li>
                </ul>
            </div>

            <div class="btn_confirm">
                <a href="https://www.redtoy.co.kr/" class="btn_cancel">취소</a>
                <button type="submit" id="btn_submit" class="btn_submit" accesskey="s">정보 수정</button>
            </div>
        </form>        
    </div>
</div>

<script>
    
</script>

<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>