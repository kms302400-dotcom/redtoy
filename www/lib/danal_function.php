<?php
	/*****************************************************
	 * 다날 신용카드 결제
	*****************************************************/
	
	/*****************************************************
	 * 연동에 필요한 Function 및 변수값 설정
	 *
	 * 연동에 대한 문의사항 있으시면 기술지원팀으로 연락 주십시오.
	 * DANAL Commerce Division Technique supporting Team
	 * EMail : credit_tech@danal.co.kr
	******************************************************/

	/******************************************************
	 *  DN_CREDIT_URL	: 결제 서버 정의
	******************************************************/
	$DN_CREDIT_URL = "https://tx-creditcard.danalpay.com/credit/";
	
	/******************************************************
	 *  Set Timeout
	******************************************************/
	$DN_CONNECT_TIMEOUT = 5000;
	$DN_TIMEOUT = 30000; //max-time setting.
	
	$ERC_NETWORK_ERROR = "-1";
	$ERM_NETWORK = "Network Error";
	
	/******************************************************
	 * CPID		: 다날에서 제공해 드린 CPID
	 * CRYPTOKEY	: 다날에서 제공해 드린 암복호화 PW
	******************************************************/
	$CPID = "A010078853"; // 실서비스를 위해서는 반드시 교체필요.
	$CRYPTOKEY = "8df3d2cc7fc575b8d09fa78fea38c874202fdc7324e1abe196a152e171d8c641";// 암호화KEy. 실서비스를 위해서는 반드시 교체필요.
	$IVKEY = "d7d02c92cb930b661f107cb92690fc83"; // IV 고정값.
	
	/******************************************************
	 * 다날 서버와 통신함수 CallTrans
	 *    - 다날 서버와 통신하는 함수입니다.
	 *    - Debug가 true일경우 웹브라우져에 debugging 메시지를 출력합니다.
	******************************************************/
	function CallCredit( $REQ_DATA, $Debug ){		
		global $CPID;
		global $DN_CREDIT_URL, $DN_CONNECT_TIMEOUT, $DN_TIMEOUT;
		global $ERC_NETWORK_ERROR, $ERM_NETWORK;
		
		$REQ_STR = toEncrypt( data2str($REQ_DATA) );
		$REQ_STR = urlencode( $REQ_STR );
		$REQ_STR = "CPID=".$CPID."&DATA=".$REQ_STR;
		
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_POST,1 );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER,0 );
		curl_setopt( $ch,CURLOPT_CONNECTTIMEOUT,$DN_CONNECT_TIMEOUT );
		curl_setopt( $ch,CURLOPT_TIMEOUT,$DN_TIMEOUT );
		curl_setopt( $ch,CURLOPT_URL,$DN_CREDIT_URL );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, array("Content-type:application/x-www-form-urlencoded; charset=euc-kr"));
		curl_setopt( $ch,CURLOPT_POSTFIELDS,$REQ_STR );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER,1 );
		curl_setopt( $ch,CURLINFO_HEADER_OUT,1 );
		//curl_setopt( $ch,CURLOPT_SSLVERSION, 'all' ); //ssl 관련 오류가 발생할 경우 주석을 해제하고 6( TLSv1.2) 또는 1(TLSv1)로 설정
		
		$RES_STR = curl_exec($ch);
		
		if( ($CURL_VAL=curl_errno($ch)) != 0 )
		{
			$RES_STR = "RETURNCODE=".$ERC_NETWORK_ERROR."&RETURNMSG=".$ERM_NETWORK."(" . $CURL_VAL . ":" . curl_error($ch) . ")";
		}
		
		if( $Debug )
		{
			$CURL_MSG = "";
			if( function_exists("curl_strerror") ){
				$CURL_MSG = curl_strerror($CURL_VAL);
			}
			else if( function_exists("curl_error") ){
				$CURL_MSG = curl_error($ch);
			}
				
			echo "REQ[" . $REQ_STR . "]<BR>";
			echo "RET[" . $CURL_VAL . ":" . $CURL_MSG . "]<BR>";
			echo "RES[" . urldecode($RES_STR) . "]<BR>";
			echo "<BR>" . print_r(curl_getinfo($ch));
			exit();
		}
		
		curl_close($ch);
		
		$RES_DATA = str2data( $RES_STR );
		if( isset($RES_DATA["DATA"]) ){
			$RES_DATA = str2data( toDecrypt( $RES_DATA["DATA"] ) );
		}
		
		return $RES_DATA;
	}
	
	/*******************************************************
	 * curl_init() 사용이 불가능할 때, 바이너리를 컴파일하여 실행
	 *******************************************************/
	function CallCreditExec( $REQ_DATA, $Debug ){
		
		$CP_CURL_PATH = "/usr/bin/curl ";
		
		global $CPID;
		global $DN_CREDIT_URL, $DN_CONNECT_TIMEOUT, $DN_TIMEOUT;
		global $ERC_NETWORK_ERROR, $ERM_NETWORK;
		
		$REQ_STR = toEncrypt( data2str($REQ_DATA) );
		$REQ_STR = urlencode( $REQ_STR );
		$REQ_STR = "CPID=".$CPID."&DATA=".$REQ_STR;
		
		$REQ_CMD = $CP_CURL_PATH;
		$REQ_CMD = $REQ_CMD . ' -k --connect-timeout ' . $DN_CONNECT_TIMEOUT;
		$REQ_CMD = $REQ_CMD . ' --max-time ' . $DN_TIMEOUT;
		$REQ_CMD = $REQ_CMD . ' --data ' . "\"" . $REQ_STR . "\"";
		$REQ_CMD = $REQ_CMD . ' '. "\"" . $DN_CREDIT_URL . "\"";
		
		exec($REQ_CMD, $RES_STR, $CURL_VAL);
		
		if($Debug){
			echo "Request : " . $REQ_CMD . "<BR>\n";
			echo "Ret : " . $CURL_VAL . "<BR>\n";
			echo "Out : " . $RES_STR[0] . "<BR>\n";
		}
		
		$RES_DATA = null;
		if($CURL_VAL != 0){
			$RES_STR = "RETURNCODE=" . $ERC_NETWORK_ERROR ."&RETURNMSG=" . $ERM_NETWORK ."( " . $CURL_VAL . " )";
			$RES_DATA = str2data( $RES_STR );
		}
		else{
			$RES_DATA = str2data( $RES_STR );
			$RES_DATA = str2data( toDecrypt( $RES_DATA["DATA"] ) );
		}
		
		return $RES_DATA;
	}
	
	function str2data($str){
		$data = array(); //return variable
		$in = "";
	
		if((string)$str == "Array"){
			for($i=0; $i<count($str);$i++){
				$in .= $str[$i];
			}
		}else{
			$in = $str;
		}
	
		$pairs = explode("&", $in);
	
		foreach($pairs as $line){
			$parsed = explode("=", $line, 2);
	
			if(count($parsed) == 2){
				$data[$parsed[0]] = urldecode( $parsed[1] );
			}
		}
	
		return $data;
	}
	
	function data2str($data){
	
		$pairs = array();
		foreach($data as $key => $value){
			array_push($pairs, $key . '=' . urlencode($value));
		}
	
		return implode('&', $pairs);
	}
	
	
	function toEncrypt($plaintext){
		global $CPID, $CRYPTOKEY, $IVKEY;
		
		$iv = convertHexToBin($IVKEY);
		$key = convertHexToBin($CRYPTOKEY);
		$ciphertext = openssl_encrypt($plaintext, "aes-256-cbc", $key, true, $iv);
		$ciphertext = base64_encode($ciphertext);
		
		return $ciphertext;
	}
	
	function toDecrypt($ciphertext){
		global $CPID, $CRYPTOKEY, $IVKEY;
		
		$iv = convertHexToBin($IVKEY);
		$key = convertHexToBin($CRYPTOKEY);
		$ciphertext = base64_decode($ciphertext);
		$plaintext = openssl_decrypt($ciphertext, "aes-256-cbc", $key, true, $iv);
	
		return $plaintext;
	}
	
	function convertHexToBin( $str ) {
		if( function_exists( 'hex2bin' ) ){
			return hex2bin( $str );
		}
		
		$sbin = "";
		$len = strlen( $str );
		for ( $i = 0; $i < $len; $i += 2 ) {
			$sbin .= pack( "H*", substr( $str, $i, 2 ) );
		}
	
		return $sbin;
	}
	
	function CancelCredit($tid, $amount, $username, $desc) {
	    //***** 신용카드 결제 취소 *****
	
    	/*[ 필수 데이터 ]***************************************/
    	$REQ_DATA = array();
    	
    	/**************************************************
    	 * 결제 정보
    	**************************************************/
    	$REQ_DATA["TID"] = $tid; //결제 완료 TID
    	
    	/**************************************************
    	 * 기본 정보
    	**************************************************/
    	$REQ_DATA["CANCELTYPE"] = "C";
    	$REQ_DATA["AMOUNT"] = $amount;
    	
    	/**************************************************
    	 * 취소 정보
    	**************************************************/
    	$REQ_DATA["CANCELREQUESTER"] = $username;
    	$REQ_DATA["CANCELDESC"] = $desc;
    	
    	$REQ_DATA["TXTYPE"] = "CANCEL";
    	$REQ_DATA["SERVICETYPE"] = "DANALCARD";
    	
    	$RES_DATA = CallCredit($REQ_DATA, false);
    	
    	if ( $RES_DATA['RETURNCODE'] == "0000" ) {
    		return true;
    	}
    	else{
    		return false;
    	}
	}
?>