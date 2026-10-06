<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_tg_events()
{
    return array('order_created'=>'주문 접수', 'order_paid'=>'입금 확인 / 결제 완료', 'qa_created'=>'1:1문의 접수', 'qa_answered'=>'1:1문의 답변 등록');
}

// Never propagate a notification/storage failure to the business request or log SQL/secrets.
function redtoy_tg_query($sql)
{
    try { return sql_query($sql, false); }
    catch (Throwable $e) { return false; }
}

function redtoy_tg_row($sql)
{
    $result = redtoy_tg_query($sql);
    return $result ? sql_fetch_array($result) : array();
}

function redtoy_tg_config()
{
    return redtoy_tg_row('select * from '.G5_TABLE_PREFIX.'redtoy_telegram_config where id=1');
}

function redtoy_tg_key()
{
    $key = base64_decode((string)getenv('REDTOY_TELEGRAM_KEY'), true);
    if ($key === false || strlen($key) !== 32 || !function_exists('openssl_encrypt')) throw new RuntimeException('암호화 키 설정을 확인해 주세요.');
    return $key;
}

function redtoy_tg_encrypt($token)
{
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($token, 'aes-256-gcm', redtoy_tg_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) throw new RuntimeException('토큰 암호화 실패');
    return base64_encode($iv.$tag.$cipher);
}

function redtoy_tg_decrypt($cipher)
{
    $data = base64_decode($cipher, true);
    if ($data === false || strlen($data) < 29) throw new RuntimeException('토큰 설정을 확인해 주세요.');
    $plain = openssl_decrypt(substr($data, 28), 'aes-256-gcm', redtoy_tg_key(), OPENSSL_RAW_DATA, substr($data, 0, 12), substr($data, 12, 16));
    if ($plain === false) throw new RuntimeException('토큰 복호화 실패');
    return $plain;
}

function redtoy_tg_clean($text)
{
    $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $text);
    $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', '[이메일 생략]', $text);
    $text = preg_replace('/(?<!\d)(?:\+82[ -]?)?0?1[016789][ -]?\d{3,4}[ -]?\d{4}(?!\d)/u', '[연락처 생략]', $text);
    return function_exists('mb_substr') ? mb_substr(trim($text), 0, 160, 'UTF-8') : substr(trim($text), 0, 160);
}

function redtoy_tg_paid($order)
{
    return isset($order['od_status'], $order['od_misu'])
        && in_array($order['od_status'], array('입금','준비','배송','완료'), true)
        && (float)$order['od_misu'] <= 0;
}

function redtoy_tg_enqueue($event, $id, $message, $test = false)
{
    try {
        $config = redtoy_tg_config();
        if (!$config || empty($config['enabled']) || empty($config['token_cipher']) || empty($config['chat_id'])) return false;
        if (!$test && (!isset(redtoy_tg_events()[$event]) || empty($config[$event]))) return false;
        if ($test && $event !== 'test') return false;
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', (string)$id)) return false;
        $key = sql_real_escape_string($event.':'.$id);
        $event = sql_real_escape_string($event);
        $id = sql_real_escape_string((string)$id);
        $message = sql_real_escape_string($message);
        $chat = sql_real_escape_string($config['chat_id']);
        // A duplicate NEVER changes sent/failed/uncertain state or its original payload.
        $result = redtoy_tg_query("insert into ".G5_TABLE_PREFIX."redtoy_telegram_queue
            (event_key,event_type,object_id,message,chat_id,available_at,created_at,updated_at)
            values ('$key','$event','$id','$message','$chat',NOW(),NOW(),NOW())
            on duplicate key update event_key=event_key");
        if (!$result) error_log('redtoy_telegram: queue_write_failed');
        return (bool)$result;
    } catch (Throwable $e) {
        error_log('redtoy_telegram: enqueue_failed');
        return false;
    }
}

// Called ONLY after the caller's order/cart writes and error paths have completed.
function redtoy_tg_order($id, $created = false)
{
    global $g5;
    try {
        if (!is_scalar($id) || !preg_match('/^[0-9]{1,32}$/D', (string)$id)) return;
        $id = sql_real_escape_string((string)$id);
        $od = redtoy_tg_row("select od_id,od_status,od_misu,od_time,od_receipt_time,od_cart_price,od_send_cost,od_send_cost2,od_cart_coupon,od_coupon,od_send_coupon,od_receipt_point,od_receipt_price,od_settle_case from {$g5['g5_shop_order_table']} where od_id='$id'");
        if (!$od) return;
        $cart = redtoy_tg_row("select it_name from {$g5['g5_shop_cart_table']} where od_id='$id' and ct_select=1 and ct_status<>'쇼핑' order by ct_id limit 1");
        if (!$cart) return;
        $events = $created ? array('order_created') : array();
        if (redtoy_tg_paid($od)) $events[] = 'order_paid';
        foreach ($events as $event) {
            $amount = $event === 'order_paid' ? $od['od_receipt_price'] : max(0, $od['od_cart_price'] + $od['od_send_cost'] + $od['od_send_cost2'] - $od['od_cart_coupon'] - $od['od_coupon'] - $od['od_send_coupon'] - $od['od_receipt_point']);
            $time = $event === 'order_created' ? $od['od_time'] : $od['od_receipt_time'];
            if (!$time || substr($time, 0, 4) === '0000') $time = G5_TIME_YMDHIS;
            $text = '[레드토이] '.redtoy_tg_events()[$event]."\n주문번호: ".$id
                ."\n대표 상품: ".redtoy_tg_clean($cart['it_name'])."\n결제금액: ".number_format($amount).'원'
                ."\n결제수단: ".redtoy_tg_clean($od['od_settle_case'])."\n처리 시각: ".$time
                ."\n".G5_ADMIN_URL.'/shop_admin/orderform.php?od_id='.rawurlencode($id);
            redtoy_tg_enqueue($event, $id, $text);
        }
    } catch (Throwable $e) { error_log('redtoy_telegram: order_event_failed'); }
}

function redtoy_tg_qa($id, $write, $w, $qaconfig, $answer_id = null)
{
    global $g5;
    try {
        if (!in_array($w, array('', 'r', 'a'), true)) return; // Edits never create an event.
        $lookup = (int)($w === 'a' ? $answer_id : $id);
        if (!$lookup) return;
        $qa = redtoy_tg_row("select qa_id,qa_parent,qa_type,qa_subject,qa_datetime from {$g5['qa_content_table']} where qa_id=$lookup");
        if (!$qa || (int)$qa['qa_type'] !== ($w === 'a' ? 1 : 0)) return;
        $event = $w === 'a' ? 'qa_answered' : 'qa_created';
        $object_id = $w === 'a' ? (int)$qa['qa_parent'] : (int)$qa['qa_id'];
        // One initial answer per question, including delete/re-add or duplicate requests.
        $text = '[레드토이] '.redtoy_tg_events()[$event]."\n문의번호: ".$object_id
            ."\n제목: ".redtoy_tg_clean($qa['qa_subject'])."\n처리 시각: ".$qa['qa_datetime']
            ."\n".G5_BBS_URL.'/qaview.php?qa_id='.$object_id;
        redtoy_tg_enqueue($event, $object_id, $text);
    } catch (Throwable $e) { error_log('redtoy_telegram: qa_event_failed'); }
}

// Deliberately log only controlled codes, NEVER curl errors, response bodies or request URLs.
function redtoy_tg_classify($errno, $http, $body)
{
    if ($errno) {
        return array('status'=>in_array($errno, array(5,6,7,35,60), true) ? 'failed' : 'uncertain', 'code'=>'transport_'.$errno, 'delay'=>60, 'message_id'=>'');
    }
    $data = json_decode($body, true);
    if ($http === 200 && !empty($data['ok']) && isset($data['result']['message_id'])) {
        return array('status'=>'sent', 'code'=>'', 'delay'=>0, 'message_id'=>(string)$data['result']['message_id']);
    }
    if (is_array($data) && isset($data['ok']) && $data['ok'] === false && $http >= 400 && $http < 500) {
        $delay = isset($data['parameters']['retry_after']) ? max(1, min(86400, (int)$data['parameters']['retry_after'])) : 60;
        return array('status'=>'failed', 'code'=>'telegram_'.$http, 'delay'=>$delay, 'message_id'=>'');
    }
    return array('status'=>'uncertain', 'code'=>'response_unknown', 'delay'=>0, 'message_id'=>'');
}

function redtoy_tg_send($token, $chat, $message)
{
    if (!function_exists('curl_init')) return array('status'=>'failed','code'=>'curl_unavailable','delay'=>60,'message_id'=>'','http'=>0);
    $ch = curl_init('https://api.telegram.org/bot'.$token.'/sendMessage');
    curl_setopt_array($ch, array(CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>2, CURLOPT_TIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_POSTFIELDS=>http_build_query(array('chat_id'=>$chat, 'text'=>$message, 'disable_web_page_preview'=>'true'))));
    $body = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result = redtoy_tg_classify(curl_errno($ch), $http, is_string($body) ? $body : '');
    curl_close($ch);
    $result['http'] = $http;
    return $result;
}

function redtoy_tg_worker($limit = 10, $transport = null)
{
    $table = G5_TABLE_PREFIX.'redtoy_telegram_queue';
    $lockname = sql_real_escape_string('redtoy_tg_'.substr(hash('sha256', G5_TABLE_PREFIX), 0, 32));
    $lock = redtoy_tg_row("select GET_LOCK('$lockname',0) as acquired");
    if (empty($lock['acquired'])) return 0;
    $count = 0;
    try {
        // A worker may have died after Telegram accepted the message. Never auto-resend it.
        redtoy_tg_query("update $table set status='uncertain',error_code='worker_interrupted',updated_at=NOW() where status='sending'");
        for ($i=0; $i<min(20, max(1, (int)$limit)); $i++) {
            $config = redtoy_tg_config();
            if (empty($config['enabled'])) break;
            $token = redtoy_tg_decrypt($config['token_cipher']);
            $events = array("'test'");
            foreach (redtoy_tg_events() as $event=>$label) if (!empty($config[$event])) $events[] = "'$event'";
            $job = redtoy_tg_row("select * from $table where status in ('pending','failed') and attempts<5 and available_at<=NOW() and event_type in (".implode(',', $events).") order by id limit 1");
            if (!$job) break;
            $id = (int)$job['id'];
            if (!redtoy_tg_query("update $table set status='sending',attempts=attempts+1,updated_at=NOW() where id=$id and status in ('pending','failed')")) break;
            // Check persisted claim before any external side effect.
            $claim = redtoy_tg_row("select status from $table where id=$id");
            if (!$claim || $claim['status'] !== 'sending') break;
            $result = $transport ? call_user_func($transport, $token, $job['chat_id'], $job['message']) : redtoy_tg_send($token, $job['chat_id'], $job['message']);
            $status = sql_real_escape_string($result['status']);
            $code = sql_real_escape_string($result['code']);
            $message_id = sql_real_escape_string($result['message_id']);
            $http = (int)$result['http'];
            $delay = max((int)$result['delay'], min(3600, 60 * pow(2, (int)$job['attempts'])));
            $sent = $status === 'sent' ? 'NOW()' : 'NULL';
            if (!redtoy_tg_query("update $table set status='$status',error_code='$code',http_code=$http,message_id='$message_id',sent_at=$sent,available_at=DATE_ADD(NOW(), INTERVAL $delay SECOND),updated_at=NOW() where id=$id and status='sending'")) break;
            $count++;
            if ($http === 429) break;
        }
    } catch (Throwable $e) { error_log('redtoy_telegram: worker_configuration_or_storage_failed'); }
    finally { redtoy_tg_query("select RELEASE_LOCK('$lockname')"); }
    return $count;
}
