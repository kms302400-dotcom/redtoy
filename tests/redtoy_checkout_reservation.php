<?php
// Synthetic local MariaDB only. No production bootstrap, credentials or network APIs.
$socket=getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D',$socket)) exit(2);
$_SERVER['SERVER_NAME']='synthetic.invalid';
define('_GNUBOARD_',true); define('G5_TABLE_PREFIX','g5_');
define('G5_LIB_PATH',dirname(__DIR__).'/www/lib'); define('G5_SHOP_URL','/shop'); define('G5_HTTPS_SHOP_URL','https://synthetic.invalid/shop');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('localhost','root','',null,0,$socket);$db->set_charset('utf8mb4');
$g5=array('connect_db'=>$db,'g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart');
require G5_LIB_PATH.'/redtoy_checkout.lib.php';
if (($argv[1] ?? '')==='allocate') {
    $name=$argv[2] ?? '';
    if (!preg_match('/^redtoy_reservation_test_[a-f0-9]{12}$/D',$name)) exit(2);
    $db->select_db($name);$ids=array();
    for($i=0;$i<25;$i++) $ids[]=redtoy_checkout_reserve_id();
    echo json_encode($ids);exit;
}
if (in_array($argv[1] ?? '',array('callback','callback-crash'),true)) {
    $name=$argv[2] ?? ''; $dir=$argv[3] ?? ''; $token=$argv[4] ?? ''; $sid=$argv[5] ?? '';
    if (!preg_match('/^redtoy_reservation_test_[a-f0-9]{12}$/D',$name) || !preg_match('#^/private/tmp/redtoy-reservation-session_[a-f0-9]{12}$#D',$dir) || !preg_match('/^[a-f0-9]{64}$/D',$token) || !preg_match('/^[a-f0-9]{32}$/D',$sid)) exit(2);
    $db->select_db($name);session_save_path($dir);session_id($sid);session_start();
    $is_member=true;$member=array('mb_id'=>'member-a');
    try { $a=redtoy_checkout_activate(array(),array('checkout_token'=>$token)); }
    catch (RuntimeException $e) { if ($e->getMessage()!=='checkout_busy') throw $e; session_write_close();echo 'busy';exit; }
    if (isset($a['replay_url'])) { session_write_close();echo 'replayed';exit; }
    redtoy_checkout_authorized($token,'card',array('TID'=>'parallel-card','ORDERID'=>$a['order_id']),30000);
    $db->query("INSERT INTO charge_spy(order_id) VALUES ('{$a['order_id']}')");
    if ($argv[1]==='callback-crash') {echo "charged\n";fflush(STDOUT);sleep(30);exit;}
    redtoy_checkout_payment_outcome($token,'card',array('RETURNCODE'=>'0000','TID'=>'parallel-card','ORDERID'=>$a['order_id'],'AMOUNT'=>'30000'));
    rc_save_synthetic($a,'parallel-card',30000);rc_end_request();session_write_close();echo 'billed';exit;
}
$parentSessionDir='/private/tmp/redtoy-reservation-session_'.bin2hex(random_bytes(6));mkdir($parentSessionDir,0700);$parentSid=bin2hex(random_bytes(16));
session_save_path($parentSessionDir);session_id($parentSid);session_start();
define('G5_DATA_PATH',$parentSessionDir);
file_put_contents(G5_DATA_PATH.'/redtoy_danal_phone.php',"<?php return array('id'=>'TEST000001','password'=>'Synthetic01','item_code'=>'SYNTHETIC');");
$name='redtoy_reservation_test_'.bin2hex(random_bytes(6));$db->query("CREATE DATABASE $name CHARACTER SET utf8mb4");$db->select_db($name);
$checks=0;
function rc_check($ok,$label) { global $checks; ++$checks; if (!$ok) throw new RuntimeException('FAIL: '.$label); }
function rc_reject($fn,$code) { try {$fn();}catch(Throwable $e){rc_check($e->getMessage()===$code,$code.' got '.$e->getMessage());return;}throw new RuntimeException('Expected '.$code); }
function get_session($key) { return $_SESSION[$key] ?? ''; }
function set_session($key,$value) { $_SESSION[$key]=$value; }
function alert($message) { throw new RuntimeException('frontend_rejected'); }
function rc_end_request() { redtoy_checkout_shutdown(); unset($GLOBALS['redtoy_checkout_active'],$GLOBALS['redtoy_checkout_before']); }
function rc_order_hash($id) { global $db; return hash('sha256',json_encode($db->query("SELECT * FROM orders WHERE od_id='$id'")->fetch_assoc())); }
function rc_post($a,$amount,$method='CARD') { return array('checkout_token'=>$a['token'],'od_id'=>$a['order_id'],'good_mny'=>(string)$amount,'od_settle_case'=>$method,'od_name'=>'Synthetic','od_email'=>'synthetic@example.invalid','od_b_zip'=>'00000'); }
function rc_save_synthetic($a,$tid,$amount) {
    global $db;
    redtoy_checkout_assert_persist($a['token'],$a['order_id'],$tid,$amount);
    $owner=redtoy_checkout_owner();$db->query("INSERT INTO orders VALUES ('{$a['order_id']}','$tid',$amount,'$owner')");
    redtoy_checkout_saved($a['token']); redtoy_checkout_complete($a['token'],'synthetic-uid',$tid,$amount);
}
$day=(new DateTimeImmutable('now',new DateTimeZone('Asia/Seoul')))->format('Y-m-d');
$prefix=(new DateTimeImmutable('now',new DateTimeZone('Asia/Seoul')))->format('ymd');
$old=$prefix.'001';
try {
    $db->query('CREATE TABLE orders (od_id BIGINT UNSIGNED PRIMARY KEY,od_tno VARCHAR(64),od_receipt_price INT,mb_id VARCHAR(20)) ENGINE='.(($argv[1] ?? '')==='innodb'?'InnoDB':'MyISAM'));
    $db->query('CREATE TABLE cart (ct_id INT PRIMARY KEY,od_id BIGINT UNSIGNED,it_id VARCHAR(20),ct_qty INT,ct_price INT,io_id VARCHAR(20),io_type INT,io_price INT,ct_option VARCHAR(40),ct_send_cost INT,ct_select INT,ct_status VARCHAR(20),KEY od_id(od_id)) ENGINE='.(($argv[1] ?? '')==='innodb'?'InnoDB':'MyISAM'));
    $db->query("INSERT INTO orders VALUES ('$old','synthetic-old',14900,'other')");$oldHash=rc_order_hash($old);
    for($i=1;$i<=15;$i++) $db->query("INSERT INTO cart VALUES ($i,$i,'item-$i',1,30000,'',0,0,'',0,1,'쇼핑')");
    try { redtoy_checkout_reserve_id(); throw new RuntimeException('missing table accepted'); }
    catch(mysqli_sql_exception $e) { rc_check(true,'missing reservation table fails closed'); }
    $migration=file_get_contents(dirname(__DIR__).'/docs/redtoy-checkout-reservation-migration.sql.example');preg_match('/CREATE TABLE.*?;/s',$migration,$m);$db->query($m[0]);
    $first=redtoy_checkout_reserve_id();rc_check($first===$prefix.'100001','separate 12-digit namespace');
    $db->begin_transaction();rc_reject(function(){redtoy_checkout_reserve_id();},'reservation_transaction');$db->rollback();
    $second=redtoy_checkout_reserve_id();rc_check($second===$prefix.'100002','reservation is not rolled back with a checkout');
    $locker=new mysqli('localhost','root','',$name,0,$socket);$locker->begin_transaction();$locker->query("SELECT * FROM g5_redtoy_order_sequence WHERE reservation_date='$day' FOR UPDATE");
    $started=microtime(true);$timedOut=false;try {redtoy_checkout_reserve_id();}catch(Throwable $e){$timedOut=true;}finally{$locker->rollback();$locker->close();}
    rc_check($timedOut && microtime(true)-$started<3.5,'contended reservation fails promptly instead of waiting 50 seconds');
    $db->query("INSERT INTO orders VALUES ('{$prefix}100003','synthetic-import',10,'other')");
    rc_check(redtoy_checkout_reserve_id()===$prefix.'100004','existing imported ID skipped without modification');
    $workers=array();
    for($i=0;$i<12;$i++) {
        $p=proc_open(array(PHP_BINARY,__FILE__,'allocate',$name),array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
        if (!is_resource($p)) throw new RuntimeException('worker start');$workers[]=array($p,$pipes);
    }
    $all=array();
    foreach($workers as $worker) {
        $out=stream_get_contents($worker[1][1]);$error=stream_get_contents($worker[1][2]);fclose($worker[1][1]);fclose($worker[1][2]);
        rc_check(proc_close($worker[0])===0,'allocator worker exits');$batch=json_decode($out,true,512,JSON_THROW_ON_ERROR);
        rc_check(count($batch)===25,'allocator batch complete');$all=array_merge($all,$batch);
    }
    rc_check(count($all)===300 && count(array_unique($all))===300,'300 allocations across 12 processes are unique');
    rc_check(rc_order_hash($old)===$oldHash,'existing paid order untouched by allocation');
    $db->query("UPDATE g5_redtoy_order_sequence SET last_serial=999999 WHERE reservation_date='$day'");
    rc_reject(function(){redtoy_checkout_reserve_id();},'reservation_exhausted');
    $db->query("UPDATE g5_redtoy_order_sequence SET last_serial=200000 WHERE reservation_date='$day'");
    $_SESSION=array();$is_member=true;$member=array('mb_id'=>'member-a');
    $a=redtoy_checkout_create('1','');$b=redtoy_checkout_create('2','1');
    rc_check($a['order_id']!==$b['order_id'] && $a['token']!==$b['token'],'new form gets its own ID and token');
    $again=redtoy_checkout_create('1','');rc_check($again['order_id']!==$a['order_id'],'refresh does not recycle an ID');
    $p=rc_post($a,30000);$p['RETURNPARAMS']='untrusted';$a=redtoy_checkout_begin_payment($p,'card');
    rc_check(!isset($a['payinfo']['order_info']['RETURNPARAMS']),'form cannot override return transport');
    redtoy_checkout_payment_ready($a['token'],array('RETURNCODE'=>'0000','TID'=>'card-a'));rc_end_request();
    rc_reject(function()use($again){redtoy_checkout_begin_payment(rc_post($again,30000),'card');},'cart_payment_busy');
    $b=redtoy_checkout_begin_payment(rc_post($b,31000,'HPP'),'hpp');redtoy_checkout_payment_ready($b['token'],array('Result'=>'0','ServerInfo'=>'synthetic'));rc_end_request();
    set_session('ss_order_id',$b['order_id']);set_session('ss_cart_direct','2');set_session('ss_direct','1');
    $_SESSION['payinfo']=array('wrong'=>'legacy-single-slot');
    $active=redtoy_checkout_activate(array('RETURNPARAMS'=>'synthetic'),array('checkout_token'=>$a['token']));
    rc_check($active['order_id']===$a['order_id'] && get_session('ss_order_id')===$a['order_id'],'callback restores A despite newer B form');
    rc_check($_SESSION['payinfo']['amount']===30000 && $_SESSION['payinfo']['paymethod']==='card','A owns its amount and request snapshot');
    rc_reject(function()use($a){redtoy_checkout_authorized($a['token'],'card',array('TID'=>'card-b','ORDERID'=>$a['order_id']),30000);},'payment_transaction_mismatch');
    rc_reject(function()use($a,$b){redtoy_checkout_authorized($a['token'],'card',array('TID'=>'card-a','ORDERID'=>$b['order_id']),30000);},'payment_order_mismatch');
    rc_reject(function()use($a){redtoy_checkout_require_amount($a['token'],1);},'payment_amount_mismatch');
    rc_reject(function()use($a){redtoy_checkout_saved($a['token']);},'invalid_payment_state');
    redtoy_checkout_authorized($a['token'],'card',array('TID'=>'card-a','ORDERID'=>$a['order_id']),30000);
    rc_check(redtoy_checkout_payment_outcome($a['token'],'card',array('RETURNCODE'=>'0000','TID'=>'card-a','ORDERID'=>$a['order_id'],'AMOUNT'=>'30000')),'verified card response accepted');
    rc_reject(function()use($a){redtoy_checkout_assert_persist($a['token'],$a['order_id'],'wrong-tid',30000);},'payment_result_mismatch');
    rc_reject(function()use($a,$old){redtoy_checkout_assert_persist($a['token'],$old,'card-a',30000);},'invalid_payment_state');
    rc_save_synthetic($a,'card-a',30000);set_session('ss_order_id','');rc_end_request();
    rc_check(get_session('ss_order_id')===$b['order_id'] && get_session('ss_cart_direct')==='2','finishing A keeps B context');
    rc_check(isset($_SESSION['redtoy_checkout_attempts'][$b['token']]['payinfo']),'finishing A preserves B payment');
    $before=rc_order_hash($a['order_id']);$replay=redtoy_checkout_activate(array(),array('checkout_token'=>$a['token']));
    rc_check(isset($replay['replay_url']) && rc_order_hash($a['order_id'])===$before,'completed replay redirects without writes/billing');
    $active=redtoy_checkout_activate(array('ServerInfo'=>'synthetic'),array('checkout_token'=>$b['token']));
    $confirm=array('Result'=>'0','ORDERID'=>$b['order_id'],'AMOUNT'=>'31000','TID'=>'phone-b');
    redtoy_checkout_authorized($b['token'],'hpp',$confirm,31000);
    rc_check(redtoy_checkout_payment_outcome($b['token'],'hpp',array('Result'=>'0','TID'=>'phone-b'),$confirm),'phone uses verified confirmation metadata');
    rc_save_synthetic($b,'phone-b',31000);rc_end_request();
    rc_check(rc_order_hash($old)===$oldHash,'existing paid customer remains untouched');
    rc_reject(function(){redtoy_checkout_activate(array(),array());},'invalid_checkout');
    rc_reject(function()use($a,$b){redtoy_checkout_request_token(array('checkout_token'=>$a['token']),array('checkout_token'=>$b['token']));},'invalid_checkout');
    $member['mb_id']='member-other';rc_reject(function()use($a){redtoy_checkout_attempt($a['token']);},'expired_checkout');$member['mb_id']='member-a';
    $c=redtoy_checkout_create('3','');$bad=rc_post($c,30000);$bad['od_id']=$old;
    rc_reject(function()use($bad){redtoy_checkout_begin_payment($bad,'card');},'order_id_mismatch');
    $db->query("UPDATE cart SET it_id='changed-item' WHERE ct_id=3");
    rc_reject(function()use($c){redtoy_checkout_begin_payment(rc_post($c,30000),'card');},'cart_changed');
    $d=redtoy_checkout_create('4','');$_SESSION['redtoy_checkout_attempts'][$d['token']]['created_at']=time()-7201;
    rc_reject(function()use($d){redtoy_checkout_begin_payment(rc_post($d,30000),'card');},'checkout_busy');
    $e=redtoy_checkout_create('5','');$e=redtoy_checkout_begin_payment(rc_post($e,30000),'card');redtoy_checkout_payment_ready($e['token'],array('RETURNCODE'=>'0000','TID'=>'card-e'));rc_end_request();
    rc_reject(function()use($e){redtoy_checkout_activate(array('checkout_token'=>$e['token']),array());},'missing_return_token');
    $otherBefore=$_SESSION['redtoy_checkout_attempts'][$a['token']];redtoy_checkout_cancel($e['token']);
    rc_check($_SESSION['redtoy_checkout_attempts'][$a['token']]===$otherBefore,'cancel only selected checkout');
    rc_reject(function()use($e){redtoy_checkout_activate(array(),array('checkout_token'=>$e['token']));},'checkout_busy');
    $f=redtoy_checkout_create('6','');$p=rc_post($f,30000,'무통장');redtoy_checkout_activate($p,array());
    rc_check($_SESSION['payinfo']===null && get_session('ss_order_id')===$f['order_id'],'bank request ignores PG snapshot from another form');
    rc_save_synthetic($f,'',0);rc_end_request();
    rc_check(isset(redtoy_checkout_activate($p,array())['replay_url']),'bank resubmission is read-only replay');
    $g=redtoy_checkout_create('7','');$g=redtoy_checkout_begin_payment(rc_post($g,30000),'card');redtoy_checkout_payment_ready($g['token'],array('RETURNCODE'=>'0000','TID'=>'card-g'));rc_end_request();
    redtoy_checkout_activate(array(),array('checkout_token'=>$g['token']));
    redtoy_checkout_authorized($g['token'],'card',array('TID'=>'card-g','ORDERID'=>$g['order_id']),30000);rc_end_request();
    rc_check($_SESSION['redtoy_checkout_attempts'][$g['token']]['state']==='review','interrupted BILL is quarantined');
    rc_reject(function()use($g){redtoy_checkout_activate(array(),array('checkout_token'=>$g['token']));},'checkout_busy');
    $h=redtoy_checkout_create('8','');$h=redtoy_checkout_begin_payment(rc_post($h,30000),'card');
    rc_check(redtoy_checkout_payment_ready($h['token'],array('RETURNCODE'=>'9999'))===false && $_SESSION['redtoy_checkout_attempts'][$h['token']]['state']==='failed','known prepare failure permits a fresh checkout');rc_end_request();
    $qty=redtoy_checkout_create('14','');$qtyBefore=$qty['fingerprint'];$db->query('UPDATE cart SET ct_qty=2 WHERE ct_id=14');
    $qty=redtoy_checkout_begin_payment(rc_post($qty,60000),'card');redtoy_checkout_payment_ready($qty['token'],array('RETURNCODE'=>'0000','TID'=>'quantity-tid'));rc_end_request();
    rc_check($qty['fingerprint']!==$qtyBefore && $qty['fingerprint']===redtoy_checkout_fingerprint('14') && $qty['amount']===60000,'quantity may be edited before payment');
    $db->query('UPDATE cart SET ct_qty=3 WHERE ct_id=14');
    rc_reject(function()use($qty){redtoy_checkout_activate(array(),array('checkout_token'=>$qty['token']));},'cart_changed');
    $is_member=false;$member=array();$guest=redtoy_checkout_create('9','');rc_check($guest['owner']==='','guest checkout supported');
    $_SESSION['redtoy_checkout_attempts'][$guest['token']]['created_at']=time()-86401;
    rc_reject(function()use($guest){redtoy_checkout_attempt($guest['token']);},'expired_checkout');
    // Run actual card/phone approval fragments with mocked transport only.
    $is_member=true;$member=array('mb_id'=>'member-a');
    $source=file_get_contents(dirname(__DIR__).'/www/shop/orderformupdate.php');
    $cardStart=strpos($source,'case "card" :');$phoneStart=strpos($source,'case "hpp" :',$cardStart);$end=strpos($source,'default :',$phoneStart);
    $card=substr($source,$cardStart,$phoneStart-$cardStart);$phone=substr($source,$phoneStart,$end-$phoneStart);
    $card=preg_replace('/include "\.\.\/lib\/danal_function\.php";/','',$card);
    $phone=preg_replace('/include "\.\.\/lib\/danal_function_teledit\.php";/','',$phone);
    function toDecrypt($unused){return json_encode($GLOBALS['rc_auth']);}
    function str2data($value){return json_decode($value,true);}
    function CallCredit($request,$unused){unset($request['CPID']);$GLOBALS['rc_calls'][]=$request;return $GLOBALS['rc_bill'];}
    function CallTeledit($request,$unused){unset($request['ID'],$request['PWD'],$request['CPID']);$GLOBALS['rc_calls'][]=$request;return $request['Command']==='NCONFIRM' ? $GLOBALS['rc_auth'] : $GLOBALS['rc_bill'];}
    function rc_fragment($case,$source,$a,$auth,$bill) {
        $GLOBALS['rc_auth']=$auth+array('RETURNMSG'=>'Synthetic');$GLOBALS['rc_bill']=$bill;$GLOBALS['rc_calls']=array();
        $redtoy_checkout=$a;$payAmount=30000;$tot_od_price=30000;$send_cost=$send_cost2=$tot_sc_cp_price=$od_temp_point=0;
        $_POST=array('RETURNPARAMS'=>'synthetic','ServerInfo'=>'synthetic');$data=array();$payMethod=$case;
        eval('switch ($payMethod) {'.$source.'}');return $data;
    }
    foreach(array('card','hpp') as $provider) {
        $cart=$provider==='card' ? '10' : '11';$x=redtoy_checkout_create($cart,'');$x=redtoy_checkout_begin_payment(rc_post($x,30000,$provider==='card'?'CARD':'HPP'),$provider);
        redtoy_checkout_payment_ready($x['token'],$provider==='card'?array('RETURNCODE'=>'0000','TID'=>'tx-'.$provider):array('Result'=>'0'));rc_end_request();redtoy_checkout_activate(array(),array('checkout_token'=>$x['token']));
        $auth=array('RETURNCODE'=>'0000','Result'=>'0','TID'=>'tx-'.$provider,'ORDERID'=>$x['order_id'],'AMOUNT'=>'30000');
        $bill=$auth+array('TRANDATE'=>'20261027','TRANTIME'=>'100000','CARDNAME'=>'Synthetic','DATE'=>'20261027100000');
        $data=rc_fragment($provider,$provider==='card'?$card:$phone,$x,$auth,$bill);
        rc_check($data['P_TID']==='tx-'.$provider && (int)$data['P_AMT']===30000,'real '.$provider.' fragment binds the result');
        $commands=array_column($GLOBALS['rc_calls'],$provider==='card'?'TXTYPE':'Command');
        rc_check($commands===($provider==='card'?array('BILL'):array('NCONFIRM','NBILL')),'real '.$provider.' API call sequence');
        rc_save_synthetic($x,'tx-'.$provider,30000);rc_end_request();$replay=redtoy_checkout_activate(array(),array('checkout_token'=>$x['token']));
        rc_check(isset($replay['replay_url']),'real '.$provider.' completed replay avoids a second BILL');
    }
    $bad=redtoy_checkout_create('12','');$bad=redtoy_checkout_begin_payment(rc_post($bad,30000),'card');redtoy_checkout_payment_ready($bad['token'],array('RETURNCODE'=>'0000','TID'=>'right-tid'));rc_end_request();redtoy_checkout_activate(array(),array('checkout_token'=>$bad['token']));
    rc_reject(function()use($card,$bad){rc_fragment('card',$card,$bad,array('RETURNCODE'=>'0000','TID'=>'right-tid','ORDERID'=>'123'),array());},'frontend_rejected');
    rc_check(count($GLOBALS['rc_calls'])===0,'wrong card order rejected before BILL');rc_end_request();
    $badPhone=redtoy_checkout_create('13','');$badPhone=redtoy_checkout_begin_payment(rc_post($badPhone,30000,'HPP'),'hpp');redtoy_checkout_payment_ready($badPhone['token'],array('Result'=>'0'));rc_end_request();redtoy_checkout_activate(array(),array('checkout_token'=>$badPhone['token']));
    rc_reject(function()use($phone,$badPhone){rc_fragment('hpp',$phone,$badPhone,array('Result'=>'0','TID'=>'phone-wrong','ORDERID'=>'123','AMOUNT'=>'30000'),array());},'frontend_rejected');
    rc_check(array_column($GLOBALS['rc_calls'],'Command')===array('NCONFIRM'),'wrong phone order rejected before NBILL');rc_end_request();
    redtoy_checkout_activate(array(),array('checkout_token'=>$badPhone['token']));
    rc_reject(function()use($phone,$badPhone){rc_fragment('hpp',$phone,$badPhone,array('Result'=>'0','TID'=>'phone-wrong','ORDERID'=>$badPhone['order_id'],'AMOUNT'=>'1'),array());},'frontend_rejected');
    rc_check(array_column($GLOBALS['rc_calls'],'Command')===array('NCONFIRM'),'wrong phone amount rejected before NBILL');rc_end_request();
    $db->query("INSERT INTO cart VALUES (16,16,'item-16',1,30000,'',0,0,'',0,1,'쇼핑'),(17,17,'item-17',1,30000,'',0,0,'',0,1,'쇼핑'),(18,18,'item-18',1,30000,'',0,0,'',0,1,'쇼핑'),(19,19,'item-19',1,30000,'',0,0,'',0,1,'쇼핑')");
    function get_member($unused) { return array('mb_id'=>'member-a','mb_name'=>'Synthetic','mb_email'=>'synthetic@example.invalid'); }
    function is_mobile() { return $GLOBALS['rc_mobile'] ?? false; }
    function MakeItemInfo($amount,$unused,$name) { return 'synthetic-item'; }
    function MakeFormInput($data,$unused=null) {}
    function rc_ready_source($path) {
        $source=file_get_contents($path);$out='';$skip=false;
        foreach(token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0],array(T_INCLUDE,T_INCLUDE_ONCE,T_REQUIRE,T_REQUIRE_ONCE),true)) { $skip=true;continue; }
            if ($skip) { if ($token===';') $skip=false;continue; }
            $out.=is_array($token)?$token[1]:$token;
        }
        // Never use the legacy merchant credential literals even in a mock request.
        return preg_replace('/(\$(?:REQ_DATA|TransR)\["(?:CPID|ID|PWD)"\]\s*=\s*)["\'][^"\']*["\']/', '$1"synthetic"', $out);
    }
    foreach(array(false,true) as $mobile) foreach(array('card','hpp') as $provider) {
        $cart=(string)(16+($mobile?2:0)+($provider==='hpp'?1:0));
        $attempt=redtoy_checkout_create($cart,'');$_POST=rc_post($attempt,30000,$provider==='card'?'CARD':'HPP');
        $_POST['od_goods_name']='Synthetic';$_SESSION['ss_mb_id']='member-a';
        $GLOBALS['rc_calls']=array();$GLOBALS['rc_mobile']=$mobile;
        $GLOBALS['rc_bill']=$provider==='card' ? array('RETURNCODE'=>'0000','TID'=>'ready-card-'.$cart,'STARTURL'=>'https://synthetic.invalid/start','STARTPARAMS'=>'synthetic') : array('Result'=>'0','ServerInfo'=>'synthetic');
        $code=rc_ready_source(dirname(__DIR__).'/www/shop/'.($provider==='card'?'danal_ready.php':'danal_ready_teledit.php'));
        $member=array('mb_id'=>'member-a');$is_member=true;ob_start();
        try { eval('?>'.$code);$html=ob_get_contents(); } finally { ob_end_clean(); }
        rc_check(count($GLOBALS['rc_calls'])===1,'ready '.$provider.' mock transport called once');
        rc_check($GLOBALS['rc_calls'][0]['ORDERID']===$attempt['order_id'],'ready '.$provider.' sends reserved ORDERID');
        $url=redtoy_checkout_callback_url($attempt['token']);
        rc_check($url==='https://synthetic.invalid/shop/orderformupdate.php?checkout_token='.$attempt['token'],'return URL binds the same attempt');
        if ($provider==='card') {
            rc_check($GLOBALS['rc_calls'][0]['RETURNURL']===$url && strpos($GLOBALS['rc_calls'][0]['CANCELURL'],$attempt['token'])!==false,'card return/cancel URLs keep nonce');
        } else {
            rc_check($ByPassValue['TargetURL']===$url && strpos($ByPassValue['BackURL'],$attempt['token'])!==false,'phone return/cancel URLs keep nonce');
        }
        rc_check($_SESSION['redtoy_checkout_attempts'][$attempt['token']]['state']==='pending','successful ready remains pending');
        rc_end_request();redtoy_checkout_cancel($attempt['token']);
    }
    $p=redtoy_checkout_create('15','');$p=redtoy_checkout_begin_payment(rc_post($p,30000),'card');redtoy_checkout_payment_ready($p['token'],array('RETURNCODE'=>'0000','TID'=>'uncertain'));rc_end_request();
    redtoy_checkout_activate(array(),array('checkout_token'=>$p['token']));redtoy_checkout_authorized($p['token'],'card',array('TID'=>'uncertain','ORDERID'=>$p['order_id']),30000);
    rc_reject(function()use($p){redtoy_checkout_payment_outcome($p['token'],'card',array());},'payment_uncertain');
    rc_check($_SESSION['redtoy_checkout_attempts'][$p['token']]['state']==='review','lost BILL response is blocked from retry');rc_end_request();
    $db->query("INSERT INTO cart VALUES (20,20,'item-20',1,30000,'',0,0,'',0,1,'쇼핑')");
    $db->query('CREATE TABLE charge_spy (id INT AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED)');
    $parallel=redtoy_checkout_create('20','');$parallel=redtoy_checkout_begin_payment(rc_post($parallel,30000),'card');redtoy_checkout_payment_ready($parallel['token'],array('RETURNCODE'=>'0000','TID'=>'parallel-card'));rc_end_request();
    $sessionDir=$parentSessionDir;$sid=$parentSid;session_write_close();
    $workers=array();
    for($i=0;$i<8;$i++) {
        $p=proc_open(array(PHP_BINARY,__FILE__,'callback',$name,$sessionDir,$parallel['token'],$sid),array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
        if (!is_resource($p)) throw new RuntimeException('callback worker start');$workers[]=array($p,$pipes);
    }
    $outcomes=array();
    foreach($workers as $worker) {
        $out=stream_get_contents($worker[1][1]);$error=stream_get_contents($worker[1][2]);fclose($worker[1][1]);fclose($worker[1][2]);
        rc_check(proc_close($worker[0])===0,'callback worker exits');$outcomes[]=$out;
    }
    rc_check(count(array_filter($outcomes,function($v){return $v==='billed';}))===1 && count(array_filter($outcomes,function($v){return in_array($v,array('replayed','busy'),true);}))===7,'8 parallel callbacks perform only one billing path');
    rc_check((int)$db->query('SELECT COUNT(*) n FROM charge_spy')->fetch_assoc()['n']===1,'durable mock charge count is one');
    rc_check((int)$db->query("SELECT COUNT(*) n FROM orders WHERE od_id='{$parallel['order_id']}'")->fetch_assoc()['n']===1,'only one order row persisted');
    session_start();
    $db->query("INSERT INTO cart VALUES (21,21,'item-21',1,30000,'',0,0,'',0,1,'쇼핑')");
    $crash=redtoy_checkout_create('21','');$crash=redtoy_checkout_begin_payment(rc_post($crash,30000),'card');redtoy_checkout_payment_ready($crash['token'],array('RETURNCODE'=>'0000','TID'=>'parallel-card'));rc_end_request();session_write_close();
    $p=proc_open(array(PHP_BINARY,__FILE__,'callback-crash',$name,$sessionDir,$crash['token'],$sid),array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    $line=fgets($pipes[1]);rc_check(trim($line)==='charged','crash fixture reached synthetic charge');
    proc_terminate($p,9);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);proc_close($p);
    $p=proc_open(array(PHP_BINARY,__FILE__,'callback',$name,$sessionDir,$crash['token'],$sid),array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    $out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    rc_check(proc_close($p)===0 && $out==='busy','process termination after charge cannot trigger a second BILL');
    rc_check((int)$db->query("SELECT COUNT(*) n FROM charge_spy WHERE order_id='{$crash['order_id']}'")->fetch_assoc()['n']===1,'synthetic charge remains one after killed process');
    session_start();rc_check($_SESSION['redtoy_checkout_attempts'][$crash['token']]['state']==='billing','in-flight marker survives termination before shutdown');
    rc_check(rc_order_hash($old)===$oldHash,'all scenarios preserve original order');
    $node=getenv('REDTOY_CHECKOUT_TEST_NODE');
    if ($node && is_executable($node)) {
        $javascriptTest = <<<'JS'
const vm = require('vm');
let input='';process.stdin.on('data', b=>input+=b);process.stdin.on('end',()=>{
    const p=JSON.parse(input);const form={action:'',target:'',submit(){submitted++}};
    for (const [key,value] of Object.entries(p.fields)) form[key]={value:String(value)};
    let submitted=0,opened=0,alerted=0;
    const context={document:{MAINPAY_FORM:form},window:{open(){opened++;return {}}},alert(){alerted++},isFinite};
    vm.createContext(context);vm.runInContext(p.code,context);context.payment();
    if (p.invalid) {if(submitted!==0 || alerted!==1) throw Error('invalid amount submitted');}
    else {
        if(submitted!==1 || alerted!==0 || form.good_mny.value!==String(p.amount)) throw Error('wrong payment amount');
        const route=p.fields.od_settle_case==='HPP'?'/shop/danal_ready_teledit.php':'/shop/danal_ready.php';
        if(form.action!==route) throw Error('wrong ready route');
        if(!p.mobile && (opened!==1 || form.target!=='danalpay_'+p.fields.checkout_token)) throw Error('shared popup');
        if(p.mobile && opened!==0) throw Error('mobile popup opened');
    }
    process.stdout.write('ok');
});
JS;
        $cases=array(
            array(array('od_price'=>69700,'od_send_cost'=>0),69700,'CARD'),
            array(array('od_price'=>30000,'od_send_cost'=>5000,'od_send_cost2'=>1000,'item_coupon'=>3000,'od_coupon'=>2000,'od_send_coupon'=>1000,'od_temp_point'=>5000),25000,'CARD'),
            array(array('od_price'=>60000,'od_send_cost'=>0),60000,'CARD'),
            array(array('od_price'=>30000,'od_send_cost'=>5000,'od_temp_point'=>1000),34000,'HPP')
        );
        foreach(array(false,true) as $mobile) foreach($cases as $case) {
            $GLOBALS['rc_mobile']=$mobile;$default=array('de_card_use'=>1,'de_iche_use'=>0,'de_vbank_use'=>0,'de_hp_use'=>0,'de_easy_pay_use'=>0);ob_start();include dirname(__DIR__).'/www/shop/danal/orderform.1.php';$rendered=ob_get_clean();
            preg_match('#<script[^>]*>(.*?)</script>#s',$rendered,$script);
            $fields=$case[0]+array('od_settle_case'=>$case[2],'checkout_token'=>str_repeat($mobile?'a':'b',64),'good_mny'=>1);
            $p=proc_open(array($node,'-e',$javascriptTest),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
            fwrite($pipes[0],json_encode(array('code'=>$script[1],'fields'=>$fields,'amount'=>$case[1],'mobile'=>$mobile)));fclose($pipes[0]);
            $out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
            rc_check(proc_close($p)===0 && $out==='ok','actual '.($mobile?'mobile':'PC').' payment JS synchronizes amount and popup');
        }
        $fields=array('od_price'=>0,'checkout_token'=>str_repeat('a',64),'od_settle_case'=>'CARD','good_mny'=>100);
        $p=proc_open(array($node,'-e',$javascriptTest),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
        fwrite($pipes[0],json_encode(array('code'=>$script[1],'fields'=>$fields,'invalid'=>true)));fclose($pipes[0]);
        $out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        rc_check(proc_close($p)===0 && $out==='ok','invalid amount never submits to PG');
    }
    // Exercise the actual entry prefix over HTTP with native PHP session files.
    $db->query("INSERT INTO cart VALUES (30,30,'http-card',1,30000,'',0,0,'',0,1,'쇼핑'),(31,31,'http-bank',1,30000,'',0,0,'',0,1,'쇼핑'),(32,32,'http-phone',1,30000,'',0,0,'',0,1,'쇼핑')");
    $is_member=true;$member=array('mb_id'=>'member-a');$_SESSION['ss_mb_id']='member-a';
    $httpCard=redtoy_checkout_create('30','');$httpCard=redtoy_checkout_begin_payment(rc_post($httpCard,30000),'card');
    redtoy_checkout_payment_ready($httpCard['token'],array('RETURNCODE'=>'0000','TID'=>'http-card'));rc_end_request();
    $httpBank=redtoy_checkout_create('31','');
    $httpPhone=redtoy_checkout_create('32','');$httpPhone=redtoy_checkout_begin_payment(rc_post($httpPhone,30000,'HPP'),'hpp');
    redtoy_checkout_payment_ready($httpPhone['token'],array('Result'=>'0'));rc_end_request();
    $httpDir='/private/tmp/redtoy-cookie-http.'.bin2hex(random_bytes(6));mkdir($httpDir,0700);
    $entry=file_get_contents(dirname(__DIR__).'/www/shop/orderformupdate.php');
    $prefix=substr($entry,0,strpos($entry,'$data = array();'));
    symlink(G5_LIB_PATH.'/redtoy_checkout.lib.php',$httpDir.'/redtoy_checkout.lib.php');
    file_put_contents($httpDir.'/mailer.lib.php','<?php');
    $bootstrap='<?php define("_GNUBOARD_",true);define("G5_TABLE_PREFIX","g5_");define("G5_LIB_PATH",__DIR__);define("G5_SHOP_URL","/shop");define("G5_HTTPS_SHOP_URL","https://synthetic.invalid/shop");';
    $bootstrap.='mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli("localhost","root","",'.var_export($name,true).',0,'.var_export($socket,true).');$db->set_charset("utf8mb4");';
    $bootstrap.='$g5=array("connect_db"=>$db,"g5_shop_order_table"=>"orders","g5_shop_cart_table"=>"cart");session_save_path('.var_export($parentSessionDir,true).');session_start();$is_member=!empty($_SESSION["ss_mb_id"]);$member=array("mb_id"=>$_SESSION["ss_mb_id"]??"");';
    $bootstrap.='function get_session($k){return $_SESSION[$k]??"";}function set_session($k,$v){$_SESSION[$k]=$v;}function alert($m){http_response_code(409);echo "rejected";exit;}function goto_url($u){echo "replayed";exit;}';
    file_put_contents($httpDir.'/_common.php',$bootstrap);
    $finish= <<<'FIXTURE'
$a=$redtoy_checkout;$provider=$a['provider'];$tid='http-'.$provider;
redtoy_checkout_authorized($a['token'],$provider,array('TID'=>$tid,'ORDERID'=>$a['order_id'],'AMOUNT'=>30000),30000);
$db->query("INSERT INTO charge_spy(order_id) VALUES ('{$a['order_id']}')");
redtoy_checkout_payment_outcome($a['token'],$provider,$provider==='card'?array('RETURNCODE'=>'0000','TID'=>$tid,'ORDERID'=>$a['order_id'],'AMOUNT'=>30000):array('Result'=>'0','TID'=>$tid,'ORDERID'=>$a['order_id'],'AMOUNT'=>30000));
redtoy_checkout_assert_persist($a['token'],$a['order_id'],$tid,30000);
$owner=redtoy_checkout_owner();$db->query("INSERT INTO orders VALUES ('{$a['order_id']}','$tid',30000,'$owner')");
redtoy_checkout_saved($a['token']);redtoy_checkout_complete($a['token'],'http-uid',$tid,30000);echo 'billed';
FIXTURE;
    $router="<?php if(isset(\$_GET['custom'])){define('G5_SESSION_NAME','SYNTHSID');session_name('SYNTHSID');} ?>".$prefix.$finish;
    file_put_contents($httpDir.'/router.php',$router);
    $listener=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);if(!$listener)throw new RuntimeException('HTTP listener');
    $address=stream_socket_get_name($listener,false);fclose($listener);
    $server=null;session_write_close();
    try {
        $server=proc_open(array(PHP_BINARY,'-d','short_open_tag=1','-d','display_errors=0','-d','opcache.enable_cli=0','-S',$address,'router.php'),array(0=>array('file','/dev/null','r'),1=>array('file',$httpDir.'/server.out','a'),2=>array('file',$httpDir.'/server.err','a')),$unused,$httpDir);
        if(!is_resource($server))throw new RuntimeException('HTTP server');
        $ready=false;for($i=0;$i<40;$i++){ $c=@stream_socket_client('tcp://'.$address,$errno,$errstr,.1);if($c){fclose($c);$ready=true;break;}usleep(50000); }
        rc_check($ready,'HTTP fixture ready');
        $send=function($a,$post,$cookie='',$extra='')use($address){
            $headers="Content-Type: application/x-www-form-urlencoded\r\n";if($cookie!=='')$headers.='Cookie: '.$cookie."\r\n";
            $ctx=stream_context_create(array('http'=>array('method'=>'POST','header'=>$headers,'content'=>http_build_query($post),'ignore_errors'=>true,'timeout'=>5)));
            $body=file_get_contents('http://'.$address.'/shop/orderformupdate.php?checkout_token='.$a['token'].$extra,false,$ctx);
            return array($body,$http_response_header);
        };
        $beforeCharges=(int)$db->query('SELECT COUNT(*) n FROM charge_spy')->fetch_assoc()['n'];
        // Control: the same callback without the bridge loses the session before activation.
        $oldEntry="<?php\n".substr($prefix,strpos($prefix,"include_once('./_common.php');"));
        file_put_contents($httpDir.'/router.php',$oldEntry.$finish);
        list($oldFailure,$oldHeaders)=$send($httpCard,array('RETURNPARAMS'=>'synthetic'));
        rc_check($oldFailure==='rejected' && strpos($oldHeaders[0],'409')!==false,'pre-fix callback without cookie reproduces rejection before mock billing');
        rc_check((int)$db->query('SELECT COUNT(*) n FROM charge_spy')->fetch_assoc()['n']===$beforeCharges,'pre-fix rejected callback did not charge');
        file_put_contents($httpDir.'/router.php',$router);
        foreach(array($httpCard,$httpPhone) as $attempt){
            $field=$attempt['provider']==='card'?'RETURNPARAMS':'ServerInfo';
            $payload='synthetic"</script>&+';$post=array($field=>$payload,'od_id'=>'999999','untrusted'=>'not-forwarded');
            list($body,$headers)=$send($attempt,$post);
            rc_check(strpos($body,'id="redtoy-cookie-return"')!==false,'missing '.$attempt['provider'].' cookie yields same-origin bridge');
            rc_check(!preg_match('/^Set-Cookie:/mi',implode("\n",$headers)),'bridge never replaces original cookie');
            rc_check(stripos(implode("\n",$headers),'Cache-Control: no-store')!==false && stripos(implode("\n",$headers),"form-action 'self'")!==false,'bridge is uncached and posts only to its origin');
            rc_check(strpos($body,'synthetic"</script>')===false && strpos($body,'name="untrusted"')===false && strpos($body,'name="od_id"')===false,'opaque payload escaped and arbitrary fields discarded');
            preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]*)">/',$body,$inputs,PREG_SET_ORDER);$retry=array();
            foreach($inputs as $input)$retry[$input[1]]=html_entity_decode($input[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
            rc_check(($retry[$field]??null)===$payload && ($retry['_redtoy_cookie_return']??null)==='1','transport payload preserved with one-shot marker');
            rc_check(!redtoy_checkout_existing($attempt['order_id']),'bridge does not create an order or charge');
            list($paid,$headers)=$send($attempt,$retry,'PHPSESSID='.$parentSid);
            rc_check($paid==='billed' && strpos($headers[0],'200')!==false,'same-origin '.$attempt['provider'].' POST recovers original session and verifies before mock billing');
            list($again,$headers)=$send($attempt,$retry,'PHPSESSID='.$parentSid);
            rc_check($again==='replayed','completed '.$attempt['provider'].' callback replay never bills again');
        }
        rc_check((int)$db->query('SELECT COUNT(*) n FROM charge_spy')->fetch_assoc()['n']===$beforeCharges+2,'one synthetic charge per recovered card/phone attempt');
        list($rejected,$headers)=$send($httpCard,array('RETURNPARAMS'=>'synthetic','_redtoy_cookie_return'=>'1'));
        rc_check($rejected==='rejected' && strpos($headers[0],'409')!==false,'disabled cookies stop after one retry');
        list($rejected,$headers)=$send($httpCard,array('RETURNPARAMS'=>'synthetic'),'PHPSESSID='.str_repeat('f',32));
        rc_check($rejected==='rejected' && strpos($headers[0],'409')!==false,'unrelated session cannot consume another checkout');
        list($rejected,$headers)=$send($httpCard,array('RETURNPARAMS'=>'synthetic','checkout_token'=>str_repeat('0',64)));
        rc_check($rejected==='rejected','conflicting GET/POST token is not bridged');
        list($rejected,$headers)=$send($httpBank,array('od_settle_case'=>'무통장','od_id'=>$httpBank['order_id']));
        rc_check($rejected==='rejected' && strpos($rejected,'redtoy-cookie-return')===false,'ordinary bank POST is not intercepted');
        list($again,$headers)=$send($httpCard,array('RETURNPARAMS'=>'synthetic'),'SYNTHSID='.$parentSid,'&custom=1');
        rc_check($again==='replayed','configured session cookie name is respected');
        rc_check((int)$db->query('SELECT COUNT(*) n FROM charge_spy')->fetch_assoc()['n']===$beforeCharges+2,'all invalid and replayed HTTP requests cause zero extra charges');
        $log=file_get_contents($httpDir.'/server.err');
        rc_check(strpos($log,'[redtoy-checkout]')!==false && strpos($log,'"code":"expired_checkout"')!==false,'activation rejection records a safe diagnostic code');
        rc_check(strpos($log,$httpCard['token'])===false && strpos($log,'synthetic"</script>')===false,'failure log excludes token and PG payload');
    } finally {
        if(is_resource($server)){proc_terminate($server);proc_close($server);}
        foreach(glob($httpDir.'/*') as $file)unlink($file);rmdir($httpDir);session_start();
    }
    $safeLog=$parentSessionDir.'/safe-errors';$oldErrorLog=ini_get('error_log');ini_set('error_log',$safeLog);
    $oldPost=$_POST;$oldGet=$_GET;$oldCookies=$_COOKIE;
    $_GET=array('checkout_token'=>$httpCard['token']);$_POST=array('RETURNPARAMS'=>'never-log-this-payload');$_COOKIE=array(session_name()=>'never-log-this-cookie');
    redtoy_checkout_report_rejection('activate',new RuntimeException('never-log-this-sql-or-key'));
    ini_set('error_log',$oldErrorLog);$safeText=file_get_contents($safeLog);
    rc_check(strpos($safeText,'"code":"unexpected_error"')!==false && strpos($safeText,'never-log-this')===false && strpos($safeText,$httpCard['token'])===false,'unknown exception messages, cookies and payloads are redacted');
    ini_set('error_log',$safeLog);
    redtoy_checkout_report_rejection('authorize_card',new RuntimeException('payment_transaction_mismatch'));
    redtoy_checkout_report_rejection('never-log-this-stage',new RuntimeException('payment_amount_mismatch'));
    ini_set('error_log',$oldErrorLog);$safeText=file_get_contents($safeLog);
    rc_check(strpos($safeText,'"stage":"authorize_card","code":"payment_transaction_mismatch"')!==false,'authorization failure stage and fixed reason are retained');
    rc_check(strpos($safeText,'"stage":"unknown","code":"payment_amount_mismatch"')!==false && strpos($safeText,'never-log-this-stage')===false,'unrecognized diagnostic stages are redacted');
    $_POST=$oldPost;$_GET=$oldGet;$_COOKIE=$oldCookies;
    $originalAttempts=$_SESSION['redtoy_checkout_attempts'];$_SESSION['redtoy_checkout_attempts']=array();
    for($i=0;$i<40;$i++) $_SESSION['redtoy_checkout_attempts'][hash('sha256',(string)$i)]=array('created_at'=>time(),'state'=>'pending');
    rc_reject(function(){redtoy_checkout_create('1','');},'too_many_checkouts');
    $_SESSION['redtoy_checkout_attempts'][hash('sha256','0')]['state']='open';
    $fresh=redtoy_checkout_create('1','');
    rc_check(count($_SESSION['redtoy_checkout_attempts'])===40 && !isset($_SESSION['redtoy_checkout_attempts'][hash('sha256','0')]),'new form evicts an old unsubmitted form instead of blocking checkout');
    rc_check(isset($_SESSION['redtoy_checkout_attempts'][hash('sha256','1')]),'active payment retained during pruning');
    $_SESSION['redtoy_checkout_attempts']=$originalAttempts;
    $form=file_get_contents(dirname(__DIR__).'/www/shop/orderform.sub.php');
    rc_check(strpos($form,'name="checkout_token"')!==false && strpos($form,'name="od_id"')!==false,'form carries reservation and nonce');
    rc_check(strpos($form,'$_SESSION["payinfo"] = null;')===false,'new form does not erase active payment');
    echo "PASS: $checks reservation and checkout checks; 300 parallel allocations; synthetic PG only\n";
} finally {
    unset($GLOBALS['redtoy_checkout_active'],$GLOBALS['redtoy_checkout_before']);
    $db->query("DROP DATABASE $name");$db->close();
    if (session_status()===PHP_SESSION_ACTIVE) {session_destroy();session_write_close();}
    foreach(glob($parentSessionDir.'/*') as $file) unlink($file);rmdir($parentSessionDir);
}
