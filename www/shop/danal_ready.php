<?php
	header("Pragma: No-Cache");
	include_once('./_common.php');
	
	/*
	print_r($_REQUEST);
	exit;
	Array
(
    [it_id] => Array
        (
            [0] => 1739281634
        )

    [it_name] => Array
        (
            [0] => test
        )

    [it_price] => Array
        (
            [0] => 1000
        )

    [cp_id] => Array
        (
            [0] => 
        )

    [cp_price] => Array
        (
            [0] => 0
        )

    [od_price] => 1000
    [org_od_price] => 1000
    [od_send_cost] => 3500
    [od_partner] => 
    [od_send_cost2] => 0
    [item_coupon] => 0
    [good_mny] => 4500
    [od_coupon] => 0
    [od_send_coupon] => 0
    [od_goods_name] => test
    [version] => 4
    [server] => 1
    [od_name] => 관리자
    [od_tel] => 0260099149
    [od_hp] => 010-4818-9149
    [od_zip] => 03992
    [od_addr1] => 서울 마포구 월드컵북로6길 26
    [od_addr2] => 4층
    [od_addr3] =>  (동교동, 삼기빌딩)
    [od_addr_jibeon] => R
    [od_email] => red@redcomm.kr
    [ad_sel_addr] => same
    [ad_subject] => 
    [od_b_name] => 관리자
    [od_b_tel] => 0260099149
    [od_b_hp] => 010-4818-9149
    [od_b_zip] => 03992
    [od_b_addr1] => 서울 마포구 월드컵북로6길 26
    [od_b_addr2] => 4층
    [od_b_addr3] =>  (동교동, 삼기빌딩)
    [od_b_addr_jibeon] => R
    [od_memo] => 
    [od_settle_case] => CARD
    [max_temp_point] => 1000
    [od_temp_point] => 0
    [od_bank_account] => 국민은행 032901-04-359256 (주)나인팩토리커뮤니케이션
    [od_deposit_name] => 
    [BILLTYPE] => 00
    [mbrNo] => 114515
    [mbrName] => 이노빅스
    [buyerName] => 관리자
    [buyerMobile] => 010-4818-9149
    [buyerEmail] => red@redcomm.kr
    [customerEmail] => red@redcomm.kr
    [mb_id] => admin
    [od_id] => 2025021601455444
    [send_cost] => 3500
    [send_cost2] => 0
    [tmp_cart_id] => 2025021601350484
    [goodsCode] => 1739281634
    [salesPrice] => 4500
    [productCount] => 1
    [CPCODE] => 
)
	*/
	
	$mb = get_member($_SESSION["ss_mb_id"]);

	/*[ 필수 데이터 ]***************************************/
	$REQ_DATA = array();

	/**************************************************
	 *Sub CP 정보
	**************************************************/
	$REQ_DATA["SUBCPID"] = "";
	
	/**************************************************
	 * 결제 정보
	**************************************************/
	$REQ_DATA["AMOUNT"] = $_POST["good_mny"];
	$REQ_DATA["CURRENCY"] = "410";
	
	$itemname_encoding = mb_detect_encoding($_POST["od_goods_name"], ["CP949", "UTF-8"]);
	$REQ_DATA["ITEMNAME"] = mb_convert_encoding($_POST["od_goods_name"], "CP949", $itemname_encoding);
	
	$REQ_DATA["ORDERID"] = $_POST["od_id"];
	
	/**************************************************
	 * 고객 정보
	**************************************************/
	if (!$is_member) {
	    $od_name_encoding = mb_detect_encoding($_POST["od_name"], ["CP949", "UTF-8"]);
    	$REQ_DATA["USERNAME"] = mb_convert_encoding($_POST["od_name"], "CP949", $od_name_encoding); // 구매자 이름
    	$REQ_DATA["USERID"] = "unactivateuser"; // 사용자 ID
    	$REQ_DATA["USEREMAIL"] = $_POST["od_email"]; // 소보법 email수신처
    } else {
        $od_name_encoding = mb_detect_encoding($mb["mb_name"], ["CP949", "UTF-8"]);
    	$REQ_DATA["USERNAME"] = mb_convert_encoding($mb["mb_name"], "CP949", $od_name_encoding); // 구매자 이름
    	$REQ_DATA["USERID"] = $mb["mb_id"]; // 사용자 ID
    	$REQ_DATA["USEREMAIL"] = $mb["mb_email"]; // 소보법 email수신처
    }
	
	/**************************************************
	 * URL 정보
	**************************************************/
	$REQ_DATA["CANCELURL"] = "https://" . $_SERVER["SERVER_NAME"] . "/shop/danal_cancel.php";
	$REQ_DATA["RETURNURL"] = "https://" . $_SERVER["SERVER_NAME"] . "/shop/orderformupdate.php";
	$REQ_DATA["NOTIURL"] = "https://" . $_SERVER["SERVER_NAME"] . "/shop/danal_noti.php";
	
	/**************************************************
	 * 기본 정보
	**************************************************/
	$REQ_DATA["TXTYPE"] = "AUTH";
	//$REQ_DATA["BYPASSVALUE"] = $merchantData // BILL응답 또는 Noti에서 돌려받을 값. '&'를 사용할 경우 값이 잘리게되므로 유의.
	
	$gopaymethod = strtolower($_POST["od_settle_case"]);
	
	switch($gopaymethod) {
	    case "directbank" :
	    case "bank":
	        //include "../lib/danal_function_wiretransfer.php";
	        $REQ_DATA["ISNOTI"] = "N";
	        $REQ_DATA["SERVICETYPE"] = "WIRETRANSFER";
	        $REQ_DATA["USERAGENT"] = is_mobile() ? "WM" : "WP";
	        $RES_DATA = CallDanalBank($REQ_DATA, false);
	        break;
	    case "vbank" :
	        //include "../lib/danal_function_vaccount.php";
	        $REQ_DATA["ISNOTI"] = "Y";
	        $REQ_DATA["SERVICETYPE"] = "DANALVACCOUNT";
	        $REQ_DATA["USERAGENT"] = is_mobile() ? "WM" : "WP";
	        $RES_DATA = CallVAccount($REQ_DATA, false);
	        break;
	    case "card" :
	        include "../lib/danal_function.php";
	        $REQ_DATA["ISNOTI"] = "N";
	        $REQ_DATA["SERVICETYPE"] = "DANALCARD";
	        $REQ_DATA["USERAGENT"] = is_mobile() ? "WM" : "WP";
	        $RES_DATA = CallCredit($REQ_DATA, false);
	        break;
	    case "naver" :
	        //include "../lib/danal_function_pay.php";
	        $REQ_DATA["ISNOTI"] = "N";
	        $REQ_DATA["USESKIPPAGE"] = "Y";
	        $REQ_DATA["QUOTA"] = "00";
	        $REQ_DATA["ALLIANCECODEBASE"] = "NAVER";
	        $REQ_DATA["SERVICETYPE"] = "DANALCARD";
	        $REQ_DATA["USERAGENT"] = is_mobile() ? "WM" : "WP";
	        $RES_DATA = CallCredit($REQ_DATA, false);
	        break;
	    case "kakao" :
	        //include "../lib/danal_function_pay.php";
	        $REQ_DATA["ISNOTI"] = "N";
	        $REQ_DATA["USESKIPPAGE"] = "Y";
	        $REQ_DATA["QUOTA"] = "00";
	        $REQ_DATA["ALLIANCECODEBASE"] = "KAKAO";
	        $REQ_DATA["SERVICETYPE"] = "DANALCARD";
	        $REQ_DATA["USERAGENT"] = is_mobile() ? "WM" : "WP";
	        $RES_DATA = CallCredit($REQ_DATA, false);
	        break;
	    case "payco" :
	        //include "../lib/danal_function_pay.php";
	        $REQ_DATA["ISNOTI"] = "N";
	        $REQ_DATA["USESKIPPAGE"] = "Y";
	        $REQ_DATA["QUOTA"] = "00";
	        $REQ_DATA["ALLIANCECODEBASE"] = "PAYCO";
	        $REQ_DATA["SERVICETYPE"] = "DANALCARD";
	        $REQ_DATA["USERAGENT"] = is_mobile() ? "WM" : "WP";
	        $RES_DATA = CallCredit($REQ_DATA, false);
	        break;
	    default :
	        //show_error_msg(headerbar(3, "결제 실패", true, false, array(), ""), "<b>결제방법</b>이<br />잘못되었습니다.", "다시 선택 후<br />결제시도 부탁드립니다.", "다시 시도", "/");
	        alert("오류가 발생했습니다.");
	        break;
	}
	
	$_SESSION["payinfo"] = array("paymethod" => $gopaymethod, "amount" => $REQ_DATA["AMOUNT"], "order_info" => $_REQUEST);
	if ( $RES_DATA['RETURNCODE'] == "0000" ) { 
?>
<form name="form" ACTION="<?= $RES_DATA["STARTURL"] ?>" METHOD="POST" >
<input TYPE="HIDDEN" NAME="STARTPARAMS"  	VALUE="<?= $RES_DATA["STARTPARAMS"] ?>">
</form>
<script>
	document.form.submit();
</script>
<?php
	} else {
		alert("관리자에게 문의 부탁드립니다.");
	}
?>

