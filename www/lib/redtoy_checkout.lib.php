<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_checkout_table($key)
{
    global $g5;
    $name = $key === 'sequence' ? G5_TABLE_PREFIX.'redtoy_order_sequence' : ($g5[$key] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $name)) throw new RuntimeException('checkout_configuration');
    return '`'.$name.'`';
}
function redtoy_checkout_db()
{
    global $g5;
    return $g5['connect_db'];
}
// Each allocation is committed immediately; neither abandoned nor failed IDs are recycled.
function redtoy_checkout_reserve_id()
{
    $db = redtoy_checkout_db();
    $table = redtoy_checkout_table('sequence');
    $order = redtoy_checkout_table('g5_shop_order_table');
    $mode = @mysqli_query($db, 'SELECT @@autocommit AS a, @@in_transaction AS t');
    if (!$mode || ($m=mysqli_fetch_assoc($mode))['a'] != 1 || $m['t'] != 0) throw new RuntimeException('reservation_transaction');
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Seoul'));
    $day = $now->format('Y-m-d');
    for ($i=0; $i<20; $i++) {
        if (!@mysqli_query($db, "SET STATEMENT innodb_lock_wait_timeout=2, lock_wait_timeout=2 FOR INSERT INTO $table (reservation_date,last_serial) VALUES ('$day',100000) ON DUPLICATE KEY UPDATE reservation_date=reservation_date")) throw new RuntimeException('reservation_failed');
        if (!@mysqli_query($db, "SET STATEMENT innodb_lock_wait_timeout=2, lock_wait_timeout=2 FOR UPDATE $table SET last_serial=LAST_INSERT_ID(last_serial+1) WHERE reservation_date='$day' AND last_serial < 999999") || mysqli_affected_rows($db)!==1) throw new RuntimeException('reservation_exhausted');
        $serial = mysqli_insert_id($db);
        if ($serial < 100001 || $serial > 999999) throw new RuntimeException('reservation_failed');
        $id = $now->format('ymd').(string)$serial;
        $result = @mysqli_query($db, "SELECT od_id FROM $order WHERE od_id='$id' LIMIT 1");
        if ($result === false) throw new RuntimeException('reservation_failed');
        if (mysqli_num_rows($result)===0) return $id;
    }
    throw new RuntimeException('reservation_collision');
}
// Deployment extracts the existing values to this ignored, private configuration file.
function redtoy_checkout_phone_config()
{
    static $config=null;
    if ($config!==null) return $config;
    $path=G5_DATA_PATH.'/redtoy_danal_phone.php';
    if (!is_file($path)) throw new RuntimeException('phone_configuration_missing');
    $values=require $path;
    if (!is_array($values) || !isset($values['id'],$values['password'],$values['item_code']) ||
        !is_string($values['id']) || !preg_match('/^[A-Za-z0-9]{10}$/D',$values['id']) ||
        !is_string($values['password']) || !preg_match('/^[A-Za-z0-9]{1,128}$/D',$values['password']) ||
        !is_string($values['item_code']) || !preg_match('/^[A-Za-z0-9]{1,32}$/D',$values['item_code'])) throw new RuntimeException('phone_configuration_invalid');
    $config=$values;
    return $config;
}
function redtoy_checkout_owner()
{
    global $is_member,$member;
    return empty($is_member) ? '' : (string)$member['mb_id'];
}
function redtoy_checkout_fingerprint($cart_id, &$selection=null)
{
    if (!is_scalar($cart_id) || !preg_match('/^[0-9]{1,20}$/D',(string)$cart_id)) throw new RuntimeException('invalid_cart');
    $table = redtoy_checkout_table('g5_shop_cart_table');
    $result = @mysqli_query(redtoy_checkout_db(), "SELECT ct_id,it_id,ct_qty,ct_price,io_id,io_type,io_price,ct_option,ct_send_cost FROM $table WHERE od_id='$cart_id' AND ct_select=1 AND ct_status='쇼핑' ORDER BY ct_id");
    if (!$result) throw new RuntimeException('cart_unavailable');
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    if (!$rows) throw new RuntimeException('empty_cart');
    $keys=array();
    foreach ($rows as $row) $keys[]=array_intersect_key($row,array_flip(array('ct_id','it_id','io_id','io_type','ct_option')));
    $selection=hash('sha256',json_encode($keys,JSON_THROW_ON_ERROR));
    return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
}
function redtoy_checkout_prune()
{
    if (!isset($_SESSION['redtoy_checkout_attempts']) || !is_array($_SESSION['redtoy_checkout_attempts'])) $_SESSION['redtoy_checkout_attempts']=array();
    foreach ($_SESSION['redtoy_checkout_attempts'] as $token=>$attempt) {
        if (($attempt['created_at'] ?? 0) < time()-86400) unset($_SESSION['redtoy_checkout_attempts'][$token]);
    }
}
function redtoy_checkout_create($cart_id,$direct)
{
    redtoy_checkout_prune();
    // Keep active/uncertain payments; evict only old, unsubmitted or terminal forms.
    foreach ($_SESSION['redtoy_checkout_attempts'] as $old_token=>$old_attempt) {
        if (count($_SESSION['redtoy_checkout_attempts'])<40) break;
        if (in_array($old_attempt['state'],array('open','complete','failed','cancelled'),true)) unset($_SESSION['redtoy_checkout_attempts'][$old_token]);
    }
    if (count($_SESSION['redtoy_checkout_attempts'])>=40) throw new RuntimeException('too_many_checkouts');
    $fingerprint = redtoy_checkout_fingerprint($cart_id,$selection);
    $token = bin2hex(random_bytes(32));
    $attempt = array('token'=>$token,'order_id'=>redtoy_checkout_reserve_id(),'owner'=>redtoy_checkout_owner(),
        'cart_id'=>(string)$cart_id,'direct'=>(string)$direct,'fingerprint'=>$fingerprint,'selection'=>$selection,
        'state'=>'open','created_at'=>time());
    $_SESSION['redtoy_checkout_attempts'][$token]=$attempt;
    return $attempt;
}
function redtoy_checkout_request_token($post=null,$get=null)
{
    $post = $post === null ? $_POST : $post; $get = $get === null ? $_GET : $get;
    $p = $post['checkout_token'] ?? ''; $g = $get['checkout_token'] ?? '';
    if (!is_string($p) || !is_string($g) || ($p!=='' && $g!=='' && !hash_equals($p,$g))) throw new RuntimeException('invalid_checkout');
    $token = $g!=='' ? $g : $p;
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new RuntimeException('invalid_checkout');
    return $token;
}
function redtoy_checkout_attempt($token)
{
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D',$token)) throw new RuntimeException('invalid_checkout');
    $a = $_SESSION['redtoy_checkout_attempts'][$token] ?? null;
    if (!is_array($a) || $a['owner']!==redtoy_checkout_owner() || $a['created_at']<time()-86400) throw new RuntimeException('expired_checkout');
    return $a;
}
function redtoy_checkout_existing($id)
{
    $table = redtoy_checkout_table('g5_shop_order_table');
    if (!preg_match('/^[0-9]{1,20}$/D',(string)$id)) throw new RuntimeException('invalid_checkout');
    $result = @mysqli_query(redtoy_checkout_db(), "SELECT od_id,od_tno,od_receipt_price,mb_id FROM $table WHERE od_id='$id'");
    if (!$result) throw new RuntimeException('order_unavailable');
    return mysqli_fetch_assoc($result);
}
function redtoy_checkout_assert_open_cart(&$a,$refresh=false)
{
    $fingerprint=redtoy_checkout_fingerprint($a['cart_id'],$selection);
    if (!hash_equals($a['selection'],$selection) || (!$refresh && !hash_equals($a['fingerprint'],$fingerprint))) throw new RuntimeException('cart_changed');
    if ($refresh) $a['fingerprint']=$fingerprint;
    foreach ($_SESSION['redtoy_checkout_attempts'] as $other) {
        if ($other['token']!==$a['token'] && $other['cart_id']===$a['cart_id'] &&
            in_array($other['state'],array('pending','validating','billing','billed','saved','review'),true)) throw new RuntimeException('cart_payment_busy');
    }
}
function redtoy_checkout_restore($a)
{
    if (!isset($GLOBALS['redtoy_checkout_active'])) {
        $before=array();
        foreach (array('ss_order_id','ss_direct','ss_cart_id','ss_cart_direct') as $key) $before[$key]=get_session($key);
        $GLOBALS['redtoy_checkout_before']=$before;
        register_shutdown_function('redtoy_checkout_shutdown');
    }
    $GLOBALS['redtoy_checkout_active']=$a['token'];
    set_session('ss_order_id',$a['order_id']); set_session('ss_direct',$a['direct']);
    set_session($a['direct'] ? 'ss_cart_direct' : 'ss_cart_id',$a['cart_id']);
    if (isset($GLOBALS['redtoy_order_log_context'])) $GLOBALS['redtoy_order_log_context']['id']=$a['order_id'];
}
function redtoy_checkout_begin_payment($post,$method)
{
    $token = redtoy_checkout_request_token($post,array()); $a = redtoy_checkout_attempt($token);
    if ($a['state']!=='open' || $a['created_at']<time()-7200) throw new RuntimeException('checkout_busy');
    if (($post['od_id'] ?? '')!==$a['order_id']) throw new RuntimeException('order_id_mismatch');
    $method = strtolower($method);
    if (!in_array($method,array('card','hpp'),true)) throw new RuntimeException('unsupported_payment');
    $amount = $post['good_mny'] ?? '';
    if (!is_scalar($amount) || !preg_match('/^[0-9]{1,10}$/D',(string)$amount) || (int)$amount<=0) throw new RuntimeException('invalid_amount');
    redtoy_checkout_assert_open_cart($a,true);
    if (redtoy_checkout_existing($a['order_id'])) throw new RuntimeException('order_already_exists');
    redtoy_checkout_restore($a);
    $a['state']='pending'; $a['provider']=$method; $a['amount']=(int)$amount;
    unset($post['RETURNPARAMS'],$post['ServerInfo']);
    if (strlen(serialize($post))>131072) throw new RuntimeException('checkout_payload_too_large');
    $a['payinfo']=array('paymethod'=>$method,'amount'=>$a['amount'],'order_info'=>$post);
    $_SESSION['redtoy_checkout_attempts'][$token]=$a;
    return $a;
}
function redtoy_checkout_payment_ready($token,$response)
{
    $a=redtoy_checkout_attempt($token);
    $ok=$a['provider']==='hpp' ? (isset($response['Result']) && (string)$response['Result']==='0') : (isset($response['RETURNCODE']) && (string)$response['RETURNCODE']==='0000');
    if (!$ok) { $_SESSION['redtoy_checkout_attempts'][$token]['state']='failed'; return false; }
    if (!empty($response['TID'])) $_SESSION['redtoy_checkout_attempts'][$token]['auth_tid']=(string)$response['TID'];
    return true;
}
function redtoy_checkout_callback_url($token,$cancel=false)
{
    redtoy_checkout_attempt($token);
    return G5_HTTPS_SHOP_URL.($cancel ? '/danal_cancel.php' : '/orderformupdate.php').'?checkout_token='.rawurlencode($token);
}
function redtoy_checkout_activate($post,$get)
{
    $token=redtoy_checkout_request_token($post,$get);$a=redtoy_checkout_attempt($token);
    if ($a['state']==='complete') {
        $order=redtoy_checkout_existing($a['order_id']);
        if (!$order || (string)$order['mb_id']!==$a['owner'] || (string)$order['od_tno']!==($a['paid_tid'] ?? '') || (int)$order['od_receipt_price']!==$a['paid_amount']) throw new RuntimeException('completed_order_changed');
        set_session('ss_orderview_uid',$a['uid']);
        return $a + array('replay_url'=>G5_SHOP_URL.'/orderinquiryview.php?od_id='.$a['order_id'].'&uid='.rawurlencode($a['uid']));
    }
    $callback=isset($a['payinfo']);
    if ($a['state']!==($callback ? 'pending' : 'open')) throw new RuntimeException('checkout_busy');
    if (!$callback && (($post['od_id'] ?? '')!==$a['order_id'] || ($post['od_settle_case'] ?? '')!=='무통장')) throw new RuntimeException('invalid_checkout');
    if (!$callback && $a['created_at']<time()-7200) throw new RuntimeException('expired_checkout');
    if ($callback && !isset($get['checkout_token'])) throw new RuntimeException('missing_return_token');
    redtoy_checkout_assert_open_cart($a,!$callback);
    if (redtoy_checkout_existing($a['order_id'])) throw new RuntimeException('order_already_exists');
    redtoy_checkout_restore($a);
    $_SESSION['redtoy_checkout_attempts'][$token]['fingerprint']=$a['fingerprint'];
    $_SESSION['redtoy_checkout_attempts'][$token]['state']='validating';
    $_SESSION['payinfo']=$callback ? $a['payinfo'] : null;
    return $a;
}
function redtoy_checkout_require_amount($token,$amount)
{
    $a=redtoy_checkout_attempt($token);
    if (isset($a['payinfo']) && (!is_numeric($amount) || (int)$amount!==$a['amount'])) throw new RuntimeException('payment_amount_mismatch');
}
function redtoy_checkout_authorized($token,$provider,$response,$amount)
{
    $a=redtoy_checkout_attempt($token); redtoy_checkout_require_amount($token,$amount);
    if ($a['state']!=='validating' || $a['provider']!==$provider) throw new RuntimeException('invalid_payment_state');
    $order = isset($response['ORDERID']) ? (string)$response['ORDERID'] : '';
    $tid = isset($response['TID']) ? (string)$response['TID'] : '';
    if ($tid==='' || ($order!=='' && $order!==$a['order_id'])) throw new RuntimeException('payment_order_mismatch');
    if ($provider==='hpp' && ($order!==$a['order_id'] || !isset($response['AMOUNT']) || (int)$response['AMOUNT']!==$a['amount'])) throw new RuntimeException('payment_order_mismatch');
    if ($provider==='card' && (!empty($a['auth_tid']) ? !hash_equals($a['auth_tid'],$tid) : $order!==$a['order_id'])) throw new RuntimeException('payment_transaction_mismatch');
    if (isset($response['AMOUNT']) && (int)$response['AMOUNT']!==$a['amount']) throw new RuntimeException('payment_amount_mismatch');
    $_SESSION['redtoy_checkout_attempts'][$token]['state']='billing';
    $_SESSION['redtoy_checkout_attempts'][$token]['verified_tid']=$tid;
    // Persist the in-flight marker BEFORE money can move, including process termination.
    if (session_status()!==PHP_SESSION_ACTIVE) throw new RuntimeException('checkout_session_unavailable');
    $session_owner=$_SESSION['ss_mb_id'] ?? '';
    if (!@session_write_close() || !@session_start()) throw new RuntimeException('checkout_session_unavailable');
    $fresh=redtoy_checkout_attempt($token);
    if ($fresh['state']!=='billing' || !hash_equals($fresh['verified_tid'],$tid) || ($_SESSION['ss_mb_id'] ?? '')!==$session_owner) throw new RuntimeException('checkout_busy');
    redtoy_checkout_assert_open_cart($fresh);
    if (redtoy_checkout_existing($fresh['order_id'])) throw new RuntimeException('order_already_exists');
    redtoy_checkout_restore($fresh);
    $_SESSION['payinfo']=$fresh['payinfo'];
}
function redtoy_checkout_payment_outcome($token,$provider,$response,$confirmed=array())
{
    $a=redtoy_checkout_attempt($token);
    if ($a['state']!=='billing' || $a['provider']!==$provider) throw new RuntimeException('invalid_payment_state');
    $field=$provider==='hpp' ? 'Result' : 'RETURNCODE'; $success=$provider==='hpp' ? '0' : '0000';
    if (!isset($response[$field])) { $_SESSION['redtoy_checkout_attempts'][$token]['state']='review'; throw new RuntimeException('payment_uncertain'); }
    if ((string)$response[$field]!==$success) { $_SESSION['redtoy_checkout_attempts'][$token]['state']='failed'; return false; }
    $tid=(string)($response['TID'] ?? ($confirmed['TID'] ?? ''));
    $order=(string)($response['ORDERID'] ?? ($confirmed['ORDERID'] ?? ''));
    $amount=$response['AMOUNT'] ?? ($confirmed['AMOUNT'] ?? null);
    if ($tid==='' || $tid!==$a['verified_tid'] || ($order!=='' && $order!==$a['order_id']) || $amount===null || (int)$amount!==$a['amount']) {
        $_SESSION['redtoy_checkout_attempts'][$token]['state']='review'; throw new RuntimeException('payment_result_mismatch');
    }
    $_SESSION['redtoy_checkout_attempts'][$token]['state']='billed';
    $_SESSION['redtoy_checkout_attempts'][$token]['paid_tid']=$tid;
    $_SESSION['redtoy_checkout_attempts'][$token]['paid_amount']=(int)$amount;
    return true;
}
function redtoy_checkout_assert_persist($token,$order_id,$tid,$amount)
{
    $a=redtoy_checkout_attempt($token);
    $callback=isset($a['payinfo']);
    if ((string)$order_id!==$a['order_id'] || $a['state']!==($callback ? 'billed' : 'validating')) throw new RuntimeException('invalid_payment_state');
    if ($callback ? ((string)$tid!==$a['paid_tid'] || (int)$amount!==$a['paid_amount']) : ((string)$tid!=='' || (int)$amount!==0)) throw new RuntimeException('payment_result_mismatch');
    if (redtoy_checkout_existing($a['order_id'])) throw new RuntimeException('order_already_exists');
}
function redtoy_checkout_saved($token)
{
    $a=redtoy_checkout_attempt($token);
    if ($a['state']!==(isset($a['payinfo']) ? 'billed' : 'validating')) throw new RuntimeException('invalid_payment_state');
    $_SESSION['redtoy_checkout_attempts'][$token]['state']='saved';
}
function redtoy_checkout_complete($token,$uid,$tid,$amount)
{
    $a=redtoy_checkout_attempt($token);
    if ($a['state']!=='saved') throw new RuntimeException('invalid_payment_state');
    if (isset($a['payinfo']) && ((string)$tid!==$a['paid_tid'] || (int)$amount!==$a['paid_amount'])) throw new RuntimeException('payment_result_mismatch');
    $_SESSION['redtoy_checkout_attempts'][$token]['state']='complete';
    $_SESSION['redtoy_checkout_attempts'][$token]['uid']=(string)$uid;
    $_SESSION['redtoy_checkout_attempts'][$token]['paid_tid']=(string)$tid;
    $_SESSION['redtoy_checkout_attempts'][$token]['paid_amount']=(int)$amount;
    unset($_SESSION['redtoy_checkout_attempts'][$token]['payinfo']);
}
function redtoy_checkout_cancel($token)
{
    $a=redtoy_checkout_attempt($token);
    if (!in_array($a['state'],array('open','pending','failed'),true)) throw new RuntimeException('checkout_busy');
    $_SESSION['redtoy_checkout_attempts'][$token]['state']='cancelled';
    return $a;
}
function redtoy_checkout_shutdown()
{
    $token=$GLOBALS['redtoy_checkout_active'] ?? '';
    $a=$_SESSION['redtoy_checkout_attempts'][$token] ?? null;
    if (!$a) return;
    if ($a['state']==='validating') $_SESSION['redtoy_checkout_attempts'][$token]['state']=isset($a['payinfo']) ? 'pending' : 'open';
    if (in_array($a['state'],array('billing','billed'),true)) $_SESSION['redtoy_checkout_attempts'][$token]['state']='review';
    $before=$GLOBALS['redtoy_checkout_before'] ?? array();
    foreach (array('ss_cart_id','ss_cart_direct') as $key) if (!empty($before[$key]) && (string)$before[$key]!==$a['cart_id']) set_session($key,$before[$key]);
    if (!empty($before['ss_order_id']) && (string)$before['ss_order_id']!==$a['order_id']) {
        set_session('ss_order_id',$before['ss_order_id']); set_session('ss_direct',$before['ss_direct']);
    }
    $_SESSION['payinfo']=null;
}
function redtoy_checkout_log_id()
{
    try { $a=redtoy_checkout_attempt(redtoy_checkout_request_token()); return $a['order_id']; }
    catch (Throwable $e) { return ''; }
}
// Log fixed diagnostic codes/flags only, never PG payloads, keys, TIDs or cookies.
function redtoy_checkout_report_rejection($stage,$error)
{
    try {
        $allowed=array('invalid_checkout','expired_checkout','checkout_busy','invalid_cart','cart_unavailable',
            'empty_cart','cart_changed','cart_payment_busy','order_already_exists','order_unavailable',
            'missing_return_token','completed_order_changed','payment_amount_mismatch','invalid_payment_state',
            'payment_order_mismatch','payment_transaction_mismatch','checkout_session_unavailable','payment_uncertain','payment_result_mismatch');
        $code=$error instanceof RuntimeException && in_array($error->getMessage(),$allowed,true) ? $error->getMessage() : 'unexpected_error';
        $token=null;
        try { $token=redtoy_checkout_request_token(); } catch (Throwable $ignored) {}
        $attempt=is_string($token) ? ($_SESSION['redtoy_checkout_attempts'][$token] ?? null) : null;
        $cookie=defined('G5_SESSION_NAME') ? G5_SESSION_NAME : session_name();
        $stages=array('activate','amount','authorize_card','result_card','authorize_phone','result_phone','persist');
        $flags=array('stage'=>in_array($stage,$stages,true)?$stage:'unknown','code'=>$code,
            'cookie'=>isset($_COOKIE[$cookie]) && is_string($_COOKIE[$cookie]) && $_COOKIE[$cookie]!=='',
            'attempt'=>is_array($attempt),'owner_match'=>is_array($attempt) && ($attempt['owner'] ?? null)===redtoy_checkout_owner(),
            'cookie_return'=>isset($_POST['_redtoy_cookie_return']));
        error_log('[redtoy-checkout] '.json_encode($flags,JSON_UNESCAPED_SLASHES));
    } catch (Throwable $ignored) {}
}
function redtoy_checkout_reject($callback=false)
{
    $message=$callback ? '결제 결과를 안전하게 처리할 수 없습니다. 이미 결제됐을 수 있으므로 재결제하지 말고 고객센터에 문의해 주세요.' : '주문서가 만료되었거나 결제가 진행 중입니다. 열린 결제창을 확인하거나 주문서를 다시 열어 주세요.';
    alert($message);
    exit;
}
