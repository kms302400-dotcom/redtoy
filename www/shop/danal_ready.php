<?php
	header("Pragma: No-Cache");
	include_once('./_common.php');
require_once G5_LIB_PATH.'/redtoy_checkout.lib.php';
try {
    $redtoy_checkout = redtoy_checkout_begin_payment($_POST, strtolower((string)($_POST['od_settle_case'] ?? '')));
} catch (Throwable $e) {
    redtoy_checkout_reject();
}



	
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
	
	$REQ_DATA["ORDERID"] = $redtoy_checkout['order_id'];
	
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
	$REQ_DATA["CANCELURL"] = redtoy_checkout_callback_url($redtoy_checkout['token'], true);
	$REQ_DATA["RETURNURL"] = redtoy_checkout_callback_url($redtoy_checkout['token']);
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
	
	redtoy_checkout_payment_ready($redtoy_checkout['token'], $RES_DATA);
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

