<?php
// Offline integration harness. No common.php, production configuration, DB or network is loaded.
error_reporting(E_ALL);
define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', 'g5_');
define('G5_TIME_YMDHIS', '2026-10-06 12:34:56');
define('G5_PATH', dirname(__DIR__).'/www');
define('G5_LIB_PATH', G5_PATH.'/lib');
define('G5_ADMIN_URL', 'https://shop.invalid/adm');
define('G5_BBS_URL', 'https://shop.invalid/bbs');
define('G5_SHOP_URL', 'https://shop.invalid/shop');
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$g5 = array('g5_shop_order_table'=>'orders', 'g5_shop_cart_table'=>'cart', 'qa_content_table'=>'qa', 'g5_shop_item_use_table'=>'reviews', 'g5_shop_item_use_image_table'=>'images', 'auth_table'=>'auth');
$sessions = array();
$fail_queue = false;
$checks = 0;
function verify($value, $label) { global $checks; $checks++; if (!$value) throw new RuntimeException('FAIL: '.$label); }
function sql_real_escape_string($s) { return str_replace("'", "''", (string)$s); }
function sql_query($sql, $error = true) {
    global $db, $fail_queue;
    if ($fail_queue && strpos($sql, 'redtoy_telegram') !== false) throw new RuntimeException('simulated storage outage');
    if (strpos($sql, 'GET_LOCK') !== false) return $db->query('select 1 as acquired');
    if (strpos($sql, 'RELEASE_LOCK') !== false) return $db->query('select 1');
    $sql = preg_replace('/DATE_ADD\(NOW\(\), INTERVAL (\d+) SECOND\)/i', "datetime('now', '+$1 seconds')", $sql);
    $sql = str_replace('NOW()', "datetime('now')", $sql);
    $sql = str_replace('on duplicate key update event_key=event_key', 'ON CONFLICT(event_key) DO NOTHING', $sql);
    // Translate only MySQL INSERT ... SET for the legacy review controller fixture.
    if (preg_match('/^insert (\w+)\s+set\s+(.*)$/is', trim($sql), $m)) {
        preg_match_all("/(\\w+)\\s*=\\s*('(?:''|[^'])*'|[0-9]+)/s", $m[2], $pairs, PREG_SET_ORDER);
        $cols = $values = array();
        foreach ($pairs as $pair) { $cols[]=$pair[1]; $values[]=$pair[2]; }
        $sql='insert into '.$m[1].' ('.implode(',',$cols).') values ('.implode(',',$values).')';
    }
    return $db->query($sql);
}
function sql_fetch_array($res) { return $res->fetch(PDO::FETCH_ASSOC); }
function sql_fetch($sql, $error = true) { return sql_fetch_array(sql_query($sql, $error)); }
function sql_insert_id() { global $db; return $db->lastInsertId(); }
function get_session($key) { global $sessions; return isset($sessions[$key]) ? $sessions[$key] : ''; }
function set_session($key, $value) { global $sessions; $sessions[$key]=$value; }
class ReviewAlert extends RuntimeException {}
function alert($message, $url = '') { throw new ReviewAlert($message); }
function alert_close($message) { throw new ReviewAlert($message); }
function safe_replace_regex($s, $mode) { return preg_replace('/[^a-zA-Z0-9_-]/', '', $s); }
function html_purifier($s) { return strip_tags($s, '<p><br><b>'); }
function get_shop_item($id, $unused = null) { return $id==='item1' ? array('it_id'=>$id,'it_name'=>'상품') : array(); }
function check_itemuse_write($id, $member) { global $purchase_allowed; if (!$purchase_allowed) alert('구매 필요'); }
function get_token() { return 'fixture'; }
function update_use_avg($id) { global $averages; $averages[]=$id; }
function update_use_cnt($id) { global $counts; $counts[]=$id; }
function insert_point() { global $points; $points++; }
function run_event() {}
function check_demo() {}
function auth_check($permission, $mode) { if (strpos($permission, $mode) === false) alert('관리자 권한 없음'); }
function check_admin_token() { if (empty($_POST['token']) || $_POST['token'] !== get_session('ss_admin_token')) alert('관리자 CSRF 거부'); }
function goto_url($url) { throw new ReviewAlert('redirect'); }
require G5_PATH.'/lib/redtoy_review.lib.php';
require G5_PATH.'/lib/redtoy_telegram.lib.php';
$db->exec("create table auth(mb_id text, au_menu text, au_auth text);
create table orders(od_id text primary key,od_status text,od_misu int,od_time text,od_receipt_time text,od_cart_price int,od_send_cost int,od_send_cost2 int,od_cart_coupon int,od_coupon int,od_send_coupon int,od_receipt_point int,od_receipt_price int,od_settle_case text);
create table cart(ct_id int,od_id text,mb_id text,it_id text,it_name text,ct_select int,ct_status text);
create table qa(qa_id int,qa_parent int,qa_type int,qa_subject text,qa_datetime text);
create table g5_redtoy_telegram_config(id int primary key,enabled int,token_cipher text,chat_id text,order_created int,order_paid int,qa_created int,qa_answered int,updated_at text);
create table g5_redtoy_telegram_queue(id integer primary key autoincrement,event_key text unique,event_type text,object_id text,message text,chat_id text,status text default 'pending',attempts int default 0,available_at text,created_at text,updated_at text,sent_at text,http_code int default 0,error_code text default '',message_id text default '',retry_by text default '');
create table reviews(is_id integer primary key autoincrement,it_id text,mb_id text,ct_id int,is_score int,is_name text,is_password text,is_subject text,is_content text,is_time text,is_ip text,is_confirm int default 0,is_provided int default 0,is_registered_by text default '',is_registered_at text,is_reply_subject text default '',is_reply_content text default '',is_reply_name text default '');
create table images(is_id int,bf_file text);");
// Ephemeral synthetic credentials are generated at runtime and never printed.
putenv('REDTOY_TELEGRAM_KEY='.base64_encode(random_bytes(32)));
$synthetic = bin2hex(random_bytes(20));
$cipher = redtoy_tg_encrypt($synthetic);
verify(redtoy_tg_decrypt($cipher)===$synthetic, 'encrypted secret round trip');
verify(strpos($cipher,$synthetic)===false, 'cipher does not expose token');
$tampered=base64_decode($cipher); $tampered[15]=chr(ord($tampered[15])^1);
try { redtoy_tg_decrypt(base64_encode($tampered)); verify(false,'tamper rejected'); } catch (RuntimeException $e) { verify(true,'tamper rejected'); }
$stmt=$db->prepare('insert into g5_redtoy_telegram_config values(1,1,?, ?,1,1,1,1,?)');
$stmt->execute(array($cipher,'-1',G5_TIME_YMDHIS));
$db->exec("insert into orders values('101','주문',12000,'2026-10-06 12:00:00','0000-00-00 00:00:00',10000,2000,0,0,0,0,0,0,'무통장');
insert into cart values(1,'101','customer','item1','테스트 상품',1,'주문');
insert into qa values(10,10,0,'문의 제목','2026-10-06 12:00:00');
insert into qa values(11,10,1,'답변 제목','2026-10-06 12:10:00');");
redtoy_tg_order('999',true);
verify($db->query('select count(*) from g5_redtoy_telegram_queue')->fetchColumn()==0,'order number alone never alerts');
redtoy_tg_order('101',true); redtoy_tg_order('101',true);
verify($db->query('select count(*) from g5_redtoy_telegram_queue')->fetchColumn()==1,'duplicate order submits deduplicated');
redtoy_tg_qa(10,array(),'u',array(),null);
verify($db->query('select count(*) from g5_redtoy_telegram_queue')->fetchColumn()==1,'QA edits ignored');
redtoy_tg_qa(10,array(),'',array(),null);
redtoy_tg_qa(10,array(),'a',array(),11);
redtoy_tg_qa(10,array(),'a',array(),11);
$db->exec("update orders set od_status='입금',od_misu=0,od_receipt_price=12000,od_receipt_time='2026-10-06 12:20:00' where od_id='101'; update cart set ct_status='입금'");
redtoy_tg_order('101'); redtoy_tg_order('101');
verify($db->query('select count(*) from g5_redtoy_telegram_queue')->fetchColumn()==4,'all four events exactly once');
$messages=$db->query('select message from g5_redtoy_telegram_queue')->fetchAll(PDO::FETCH_COLUMN);
verify(strpos(implode('\n',$messages),'shop.invalid/adm/shop_admin/orderform.php?od_id=101')!==false,'order admin link');
verify(strpos(implode('\n',$messages),'shop.invalid/bbs/qaview.php?qa_id=10')!==false,'QA detail link');
verify(strpos(implode('\n',$messages),'12,000원')!==false,'payment amount');
verify(strpos(redtoy_tg_clean('연락 010-1234-5678 test@example.invalid'),'010-1234')===false,'title phone redaction');
$transport_calls=0;
$transport=function($token,$chat,$message) use (&$transport_calls,$synthetic) { $transport_calls++; verify($token===$synthetic,'worker decrypt only'); return array('status'=>'sent','code'=>'','http'=>200,'delay'=>0,'message_id'=>(string)$transport_calls); };
verify(redtoy_tg_worker(10,$transport)===4,'worker sends four queued events');
redtoy_tg_order('101',true);redtoy_tg_qa(10,array(),'a',array(),11);
verify(redtoy_tg_worker(10,$transport)===0 && $transport_calls===4,'callback replay after delivery never sends again');
$db->exec("update g5_redtoy_telegram_config set qa_created=0");
verify(!redtoy_tg_enqueue('qa_created','20','disabled'),'per-event disable');
$db->exec("update g5_redtoy_telegram_config set enabled=0");
verify(!redtoy_tg_enqueue('order_created','202','disabled'),'global disable');
$db->exec("update g5_redtoy_telegram_config set enabled=1");
redtoy_tg_enqueue('test','failure','test',true);
redtoy_tg_worker(10,function(){ return array('status'=>'failed','code'=>'telegram_429','http'=>429,'delay'=>600,'message_id'=>''); });
$failed=sql_fetch("select * from g5_redtoy_telegram_queue where object_id='failure'");
verify($failed['status']==='failed' && $failed['attempts']==1,'definite failure tracked');
verify(redtoy_tg_worker(10,$transport)===0,'backoff respected');
$db->exec("update g5_redtoy_telegram_queue set available_at='2000-01-01' where object_id='failure'");
verify(redtoy_tg_worker(10,$transport)===1,'failed notification retried');
redtoy_tg_enqueue('test','timeout','test',true);
redtoy_tg_worker(10,function(){ return array('status'=>'uncertain','code'=>'transport_28','http'=>0,'delay'=>0,'message_id'=>''); });
verify(redtoy_tg_worker(10,$transport)===0,'timeout never auto duplicates');
redtoy_tg_enqueue('test','crash','test',true);
$db->exec("update g5_redtoy_telegram_queue set status='sending' where object_id='crash'");
redtoy_tg_worker(10,$transport);
verify(sql_fetch("select status from g5_redtoy_telegram_queue where object_id='crash'")['status']==='uncertain','interrupted worker recovery');
verify(redtoy_tg_classify(28,0,'')['status']==='uncertain','curl timeout classifier');
verify(redtoy_tg_classify(6,0,'')['status']==='failed','DNS error retryable');
verify(redtoy_tg_classify(0,502,'')['status']==='uncertain','upstream response unknown');
verify(redtoy_tg_classify(0,429,'{"ok":false,"parameters":{"retry_after":90}}')['delay']===90,'rate-limit retry-after');
redtoy_tg_enqueue('test','exhausted','test',true);
$db->exec("update g5_redtoy_telegram_queue set status='failed',attempts=5 where object_id='exhausted'");
verify(redtoy_tg_worker(10,$transport)===0,'retry limit respected');
$db->exec("update g5_redtoy_telegram_queue set attempts=0,status='pending' where object_id='exhausted'; update g5_redtoy_telegram_config set enabled=0");
verify(redtoy_tg_worker(10,$transport)===0,'disabling pauses already queued work');
$db->exec("update g5_redtoy_telegram_config set enabled=1");
verify(redtoy_tg_worker(10,$transport)===1,'manual reset can resend failed event');
$db->exec("insert into orders select '102','입금',0,od_time,od_receipt_time,od_cart_price,od_send_cost,od_send_cost2,od_cart_coupon,od_coupon,od_send_coupon,od_receipt_point,12000,'신용카드' from orders where od_id='101'");
redtoy_tg_order('102',true);
verify($db->query("select count(*) from g5_redtoy_telegram_queue where object_id='102'")->fetchColumn()==0,'incomplete order without saved cart suppressed');
$db->exec("insert into cart values(2,'102','customer','item1','카드결제 상품',1,'입금')");
redtoy_tg_order('102',true);
verify($db->query("select count(*) from g5_redtoy_telegram_queue where object_id='102'")->fetchColumn()==2,'PG paid-at-creation emits both events');
$db->exec("update orders set od_status='취소' where od_id='102'");
verify(!redtoy_tg_paid(sql_fetch("select * from orders where od_id='102'")), 'cancelled order does not qualify for payment event');
verify(!redtoy_tg_paid(array('od_status'=>'주문','od_misu'=>0)), 'zero outstanding alone does not qualify');
$fail_queue=true;
redtoy_tg_order('101',true);redtoy_tg_qa(10,array(),'',array(),null);
verify(sql_fetch("select od_status from orders where od_id='101'")['od_status']==='입금','notification storage failure leaves saved order intact');
verify(sql_fetch('select qa_id from qa where qa_id=10')['qa_id']==10,'notification storage failure leaves saved QA intact');
$fail_queue=false;
// Exercise the actual review controller with an empty common.php fixture, not production bootstrap.
$fixture=sys_get_temp_dir().'/redtoy-review-test-'.bin2hex(random_bytes(6));
mkdir($fixture,0700);file_put_contents($fixture.'/_common.php','<?php');
$previous=getcwd();chdir($fixture);
$is_member=true;$is_admin='super';$member=array('mb_id'=>'operator','mb_name'=>'관리자','mb_password'=>'fixture');
$config=array('cf_editor'=>'');$default=array('de_item_use_use'=>0);$is_mobile_shop=false;$points=0;$counts=$averages=array();$purchase_allowed=false;
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['DOCUMENT_ROOT']=$fixture;$_SERVER['REMOTE_ADDR']='127.0.0.1';$_FILES=array();
$input=array('it_id'=>'item1','w'=>'','is_score'=>'5','is_subject'=>'고객 후기','is_content'=>'사용 후 전달한 내용','review_provided'=>'1','review_nickname'=>'고객 별명','review_date'=>'2024-02-29','ct_id'=>'1','redtoy_review_token'=>redtoy_review_token());
function submit_review($input) {
    global $is_member,$is_admin,$member,$config,$default,$is_mobile_shop,$g5;
    $_POST=$input;$_REQUEST=$input;
    try { include G5_PATH.'/shop/itemuseformupdate.php'; } catch (ReviewAlert $e) { return $e->getMessage(); }
    return '';
}
function submit_admin_review($input) {
    global $is_admin,$member,$g5,$auth,$config;
    $_POST=$input;$_REQUEST=$input;$w=$input['w'];$sca='';$qstr='';
    try { include G5_PATH.'/adm/shop_admin/itemuseformupdate.php'; } catch (ReviewAlert $e) { return $e->getMessage(); }
    return '';
}
try {
    $result=submit_review($input);
    $review=sql_fetch('select * from reviews order by is_id desc limit 1');
    verify($review && $review['is_name']==='고객 별명' && $review['is_time']==='2024-02-29 12:34:56','admin backdated creation persisted');
    verify($review['mb_id']==='operator' && $review['is_registered_by']==='operator' && $review['is_registered_at']===G5_TIME_YMDHIS,'real administrator audit separated');
    verify($review['is_provided']==1 && $review['ct_id']==0 && $points===0,'provided review has no fake purchase or points');
    verify(count($averages)===1 && count($counts)===1,'normal review aggregate updates retained');
    $input['w']='u';$input['is_id']=$review['is_id'];$input['review_nickname']='수정 별명';$input['review_date']='2025-01-02';$input['is_content']='수정한 후기 내용';
    submit_review($input);
    $edited=sql_fetch('select * from reviews where is_id=1');
    verify($edited['is_name']==='수정 별명' && substr($edited['is_time'],0,10)==='2025-01-02' && $edited['is_content']==='수정한 후기 내용','admin edit nickname/date/content');
    verify($edited['is_registered_at']===G5_TIME_YMDHIS,'edit keeps original audit');
    $input['review_date']='2025-02-29';
    verify(strpos(submit_review($input),'올바른 등록일')!==false,'invalid date rejected');
    $input['review_date']='2027-01-01';verify(strpos(submit_review($input),'올바른 등록일')!==false,'future date rejected');
    $input['review_date']='2025-01-01';$input['redtoy_review_token']='bad';verify(strpos(submit_review($input),'잘못된 요청')!==false,'CSRF rejected before save');
    $input['redtoy_review_token']=redtoy_review_token();$is_admin='';$member=array('mb_id'=>'customer','mb_name'=>'실제 회원명','mb_password'=>'fixture');$purchase_allowed=true;
    verify(strpos(submit_review($input),'권한')!==false,'ordinary user cannot edit proxy');
    $input['w']='';unset($input['is_id']);$input['review_date']=array('attack');$input['review_nickname']='<script>attack</script>';
    submit_review($input);
    $regular=sql_fetch('select * from reviews order by is_id desc limit 1');
    verify($regular['is_name']==='실제 회원명' && $regular['is_time']===G5_TIME_YMDHIS && $regular['is_provided']==0,'forged admin fields ignored for member creation');
    $input['w']='u';$input['is_id']=$regular['is_id'];$input['is_content']='회원 수정 내용';submit_review($input);
    $regular=sql_fetch('select * from reviews order by is_id desc limit 1');
    verify($regular['is_name']==='실제 회원명' && $regular['is_time']===G5_TIME_YMDHIS && $regular['is_content']==='회원 수정 내용','ordinary edit preserves identity/date');
    ob_start();redtoy_review_fields($regular);$html=ob_get_clean();verify(strpos($html,'review_nickname')===false,'ordinary form hides proxy fields');
    $is_admin='super';ob_start();redtoy_review_fields($edited,true);$html=ob_get_clean();verify(strpos($html,'수정 별명')!==false && strpos($html,'2025-01-02')!==false,'admin form loads persisted fields');
    verify(strpos(redtoy_review_notice($edited),'상품 제공 리뷰')!==false,'provided review disclosure');
    $member=array('mb_id'=>'operator','mb_name'=>'관리자','mb_nick'=>'운영자','mb_password'=>'fixture');
    $auth=array('400650'=>'rw');set_session('ss_admin_token','admin-fixture');
    $admin_input=array('w'=>'u','is_id'=>1,'it_id'=>'forged-item','is_subject'=>'관리자 화면 제목','is_content'=>'관리자 화면 수정 내용','review_nickname'=>'관리자 화면 별명','review_date'=>'2023-03-05','redtoy_review_token'=>redtoy_review_token(),'token'=>'admin-fixture','is_confirm'=>'1');
    submit_admin_review($admin_input);
    $admin_saved=sql_fetch('select * from reviews where is_id=1');
    verify($admin_saved['is_name']==='관리자 화면 별명' && substr($admin_saved['is_time'],0,10)==='2023-03-05' && $admin_saved['is_content']==='관리자 화면 수정 내용','admin backoffice controller updates proxy');
    verify(end($averages)==='item1','aggregate uses stored item, not forged POST');
    verify($admin_saved['is_registered_by']==='operator' && $admin_saved['is_registered_at']===G5_TIME_YMDHIS,'backoffice preserves original audit');
    $admin_input['token']='bad';verify(submit_admin_review($admin_input)==='관리자 CSRF 거부','admin backoffice requires native CSRF');
    $admin_input['token']='admin-fixture';$auth=array('400650'=>'r');verify(submit_admin_review($admin_input)==='관리자 권한 없음','backoffice requires write permission');
    $is_admin='';$member=array('mb_id'=>'delegated','mb_name'=>'담당자','mb_password'=>'fixture');
    $db->exec("insert into auth values('delegated','400650','rw')");
    verify(redtoy_review_admin(),'delegated review write permission accepted');
    $db->exec("update auth set au_auth='r'");verify(!redtoy_review_admin(),'read-only review manager denied proxy creation');
    try { include G5_PATH.'/adm/shop_admin/redtoy_telegram.php'; verify(false,'telegram settings denies non-super'); }
    catch (ReviewAlert $e) { verify(strpos($e->getMessage(),'최고관리자')!==false,'telegram settings denies non-super before reading settings'); }
} finally { chdir($previous);unlink($fixture.'/_common.php');rmdir($fixture); }
// Verify every existing order creation entry converges on the queued-notification boundary.
foreach (array('shop/orderformupdate.php','mobile/shop/orderformupdate.php','shop/mainpay/serverReturn.php','mobile/shop/mainpay/serverReturn.php','plugin/mainpay/pc/_3_approval.php','plugin/mainpay/mobile/_3_approval.php','plugin/mainpay/server_return.php','plugin/payster/payResult.php') as $path) {
    $source=file_get_contents(G5_PATH.'/'.$path);
    verify(strpos($source,"'/ordermail1.inc.php'")!==false,'creation path reaches shared hook: '.$path);
}
foreach (array('shop/settle_kcp_common.php','shop/settle_lg_common.php','shop/settle_inicis_common.php','shop/personalpayformupdate.php','mobile/shop/personalpayformupdate.php','adm/shop_admin/orderformreceiptupdate.php','adm/shop_admin/orderlistupdate.php','adm/shop_admin/inorderformupdate.php') as $path) {
    verify(strpos(file_get_contents(G5_PATH.'/'.$path),'redtoy_tg_order(')!==false,'payment/admin path wired: '.$path);
}
echo "PASS: $checks offline assertions; no production DB or network used\n";
