<?php
if (!defined('_GNUBOARD_')) exit;
// Dedicated cancellation adapter; never loads the legacy files containing embedded credentials.
function redtoy_cancel_pg_ready($plan)
{
    if (getenv('REDTOY_PG_CANCEL_ENABLED')!=='1') throw new RuntimeException('pg_not_configured');
    if (isset($plan['cash']) && $plan['cash']===0) return;
    $method=$plan['method'];
    if ($method==='card') {
        foreach (array('REDTOY_DANAL_CARD_CPID'=>10,'REDTOY_DANAL_CARD_KEY'=>64,'REDTOY_DANAL_CARD_IV'=>32) as $name=>$length) {
            $value=getenv($name);
            if (!$value || strlen($value)!==$length || !preg_match($length===10?'/^[a-zA-Z0-9]+$/D':'/^[a-fA-F0-9]+$/D',$value)) throw new RuntimeException('pg_not_configured');
        }
        if (!function_exists('curl_init') || !function_exists('openssl_encrypt')) throw new RuntimeException('pg_not_configured');
    } else {
        $binary=getenv('REDTOY_DANAL_PHONE_BINARY');
        if (!getenv('REDTOY_DANAL_PHONE_ID') || !getenv('REDTOY_DANAL_PHONE_PASSWORD') || !$binary || $binary[0]!=='/' || !is_executable($binary) || !function_exists('proc_open')) throw new RuntimeException('pg_not_configured');
    }
}
function redtoy_cancel_card_request($plan)
{
    return array('TID'=>$plan['base']['order']['od_tno'],'CANCELTYPE'=>$plan['cancel_type'],'AMOUNT'=>(string)$plan['cash'],'CANCELREQUESTER'=>'redtoy-admin','CANCELDESC'=>'Selected product cancellation','TXTYPE'=>'CANCEL','SERVICETYPE'=>'DANALCARD');
}
function redtoy_cancel_card_result($r,$plan)
{
    $bad=array('ok'=>false,'code'=>'unverified_card_response','tid'=>'');
    if (!isset($r['RETURNCODE']) || $r['RETURNCODE']!=='0000') return $bad;
    foreach (array('AMOUNT','BALANCE') as $f) if (!isset($r[$f]) || !preg_match('/^[0-9]+$/D',(string)$r[$f])) return $bad;
    if (!isset($r['TID']) || !preg_match('/^[0-9]{24}$/D',$r['TID'])) return $bad;
    if ((int)$r['AMOUNT']!==$plan['cash'] || (int)$r['BALANCE']!==$plan['expected_balance']) return $bad;
    if (isset($r['O_TID']) && $r['O_TID']!==$plan['base']['order']['od_tno']) return $bad;
    return array('ok'=>true,'code'=>'0000','tid'=>$r['TID']);
}
function redtoy_cancel_phone_result($r,$plan)
{
    if (!isset($r['Result'],$r['TID'],$r['Date']) || $r['Result']!=='0' || $r['TID']!==$plan['base']['order']['od_tno'] || !preg_match('/^[0-9]{14}$/D',$r['Date'])) return array('ok'=>false,'code'=>'unverified_phone_response','tid'=>'');
    return array('ok'=>true,'code'=>'0','tid'=>$r['TID']);
}
function redtoy_cancel_pg($plan)
{
    redtoy_cancel_pg_ready($plan);
    if ($plan['method']==='card') {
        $key=hex2bin(getenv('REDTOY_DANAL_CARD_KEY')); $iv=hex2bin(getenv('REDTOY_DANAL_CARD_IV'));
        $data=openssl_encrypt(http_build_query(redtoy_cancel_card_request($plan),'','&',PHP_QUERY_RFC1738),'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv);
        if ($data===false) throw new RuntimeException('pg_encryption_failed');
        $ch=curl_init('https://tx-creditcard.danalpay.com/credit/');
        curl_setopt_array($ch,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(array('CPID'=>getenv('REDTOY_DANAL_CARD_CPID'),'DATA'=>base64_encode($data)),'','&',PHP_QUERY_RFC1738),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>array('Content-Type: application/x-www-form-urlencoded; charset=euc-kr')));
        $raw=curl_exec($ch); $status=curl_getinfo($ch,CURLINFO_HTTP_CODE); $error=curl_errno($ch); curl_close($ch);
        if ($error || $status!==200 || !is_string($raw) || strlen($raw)>65536) return array('ok'=>false,'code'=>'transport_unknown','tid'=>'');
        parse_str($raw,$response);
        if (isset($response['DATA'])) {
            $decoded=base64_decode($response['DATA'],true);
            $plain=$decoded===false?false:openssl_decrypt($decoded,'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv);
            if ($plain===false) return array('ok'=>false,'code'=>'unverified_card_response','tid'=>'');
            parse_str($plain,$response);
        }
        return redtoy_cancel_card_result($response,$plan);
    }
    $fields=array('ID'=>getenv('REDTOY_DANAL_PHONE_ID'),'PWD'=>getenv('REDTOY_DANAL_PHONE_PASSWORD'),'TID'=>$plan['base']['order']['od_tno'],'Command'=>'BILL_CANCEL','OUTPUTOPTION'=>'3');
    $proc=proc_open(array(getenv('REDTOY_DANAL_PHONE_BINARY'),http_build_query($fields,'',';',PHP_QUERY_RFC1738)),array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('file','/dev/null','w')),$pipes);
    if (!is_resource($proc)) return array('ok'=>false,'code'=>'transport_unknown','tid'=>'');
    stream_set_blocking($pipes[1],false); $raw=''; $deadline=microtime(true)+20; $state=null;
    do {
        $raw.=stream_get_contents($pipes[1]); $state=proc_get_status($proc);
        if (!$state['running']) break;
        if (strlen($raw)>65536 || microtime(true)>$deadline) { proc_terminate($proc); usleep(100000); if (proc_get_status($proc)['running']) proc_terminate($proc,9); break; }
        usleep(20000);
    } while (true);
    $raw.=stream_get_contents($pipes[1]); fclose($pipes[1]); proc_close($proc);
    if ($state['running'] || strlen($raw)>65536 || $state['exitcode']!==0) return array('ok'=>false,'code'=>'transport_unknown','tid'=>'');
    parse_str(str_replace(array("\r","\n"),'&',trim($raw)),$response);
    return redtoy_cancel_phone_result($response,$plan);
}
