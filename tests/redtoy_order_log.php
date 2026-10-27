<?php
// Synthetic local DB only. Never load production settings or invoke PG/notification APIs.
$socket=getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D',$socket)) exit(2);
define('_GNUBOARD_',true); define('G5_TABLE_PREFIX','g5_');
define('G5_PATH',dirname(__DIR__).'/www'); define('G5_LIB_PATH',G5_PATH.'/lib');
define('G5_ADMIN_URL','/adm'); define('G5_SHOP_URL','/shop');
date_default_timezone_set('Asia/Seoul');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db=new mysqli('localhost','root','',null,0,$socket);
$name='redtoy_orderlog_test_'.bin2hex(random_bytes(5)); $db->query("CREATE DATABASE $name CHARACTER SET utf8mb4"); $db->select_db($name); $db->set_charset('utf8mb4');
$g5=array('connect_db'=>$db,'g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart');
$is_member=true;
require G5_LIB_PATH.'/redtoy_order_log.lib.php';
$checks=0;
function ol_check($ok,$message) { global $checks; ++$checks; if (!$ok) throw new RuntimeException('FAIL: '.$message); }
function ol_count() { global $db; return (int)$db->query('SELECT COUNT(*) n FROM g5_redtoy_order_log')->fetch_assoc()['n']; }
function get_session($key) { return $_SESSION[$key] ?? ''; }
function add_replace(...$args) {}
function add_event(...$args) { $GLOBALS['ol_hooks'][]=$args[0]; }
function sql_fetch_array($r) { return mysqli_fetch_assoc($r); }
function sql_escape_string($s) { global $db; return $db->real_escape_string($s); }
function auth_check_menu($auth,$menu,$mode) {
    $GLOBALS['ol_auth'][]=$mode;
    if ($menu!=='400405' || !in_array($mode,$GLOBALS['ol_permissions'],true)) throw new RuntimeException('denied');
}
function check_admin_token() { if (($_POST['token'] ?? '')!=='synthetic-token') throw new RuntimeException('csrf'); }
function get_admin_token() { return 'synthetic-token'; }
function goto_url($url) { throw new RuntimeException('redirect'); }
function alert($message) { throw new RuntimeException('alert'); }
$dir=sys_get_temp_dir().'/redtoy-orderlog-view-'.bin2hex(random_bytes(5));mkdir($dir);
foreach (array('_common.php','admin.head.php','admin.tail.php') as $f) file_put_contents($dir.'/'.$f,'<?php');
define('G5_ADMIN_PATH',$dir);
function ol_page($get=array(),$post=array(),$permissions=array('r','d')) {
    global $dir,$g5; $auth=array(); $is_admin='super'; $_GET=$get;$_POST=$post;
    $_SERVER['REQUEST_METHOD']=$post?'POST':'GET';$GLOBALS['ol_permissions']=$permissions;$GLOBALS['ol_auth']=array();
    $cwd=getcwd();chdir($dir);ob_start();
    try { include G5_PATH.'/adm/shop_admin/orderlog.php'; return ob_get_contents(); }
    finally { ob_end_clean();chdir($cwd); }
}
function ol_reject($fn,$message) { try {$fn();}catch(RuntimeException $e){ol_check($e->getMessage()===$message,$message);return;} throw new RuntimeException('expected rejection'); }
try {
    ol_check(!redtoy_order_log('123','entered'),'missing table does not block checkout');
    ol_check(strpos(ol_page(),'마이그레이션')!==false,'missing table UI');
    preg_match('/CREATE TABLE.*?;/s',file_get_contents(dirname(__DIR__).'/docs/redtoy-order-log-migration.sql.example'),$m);$db->query($m[0]);
    $db->query('CREATE TABLE orders (od_id BIGINT UNSIGNED PRIMARY KEY,od_status VARCHAR(20),od_misu INT,od_partner VARCHAR(20)) ENGINE=MyISAM');
    $db->query('CREATE TABLE cart (ct_id INT PRIMARY KEY,od_id BIGINT UNSIGNED,it_id VARCHAR(20),it_name VARCHAR(255),ct_option VARCHAR(255),ct_qty INT,ct_select INT,KEY od_id(od_id))');
    $db->query("INSERT INTO cart VALUES (1,123,'demo','<script>unsafe</script>','옵션',2,1),(2,123,'other','Unselected','',1,0)");
    $_SESSION=array('set_partner_id'=>'partner_demo');$_SERVER['HTTP_USER_AGENT']='Android Mobile';
    redtoy_order_log_entered('100',123);
    ol_check(ol_count()===1,'entry stored');$first=$db->query('SELECT * FROM g5_redtoy_order_log')->fetch_assoc();
    $products=json_decode($first['products_json'],true);
    ol_check(count($products)===1 && $products[0]['quantity']===2,'selected cart only');
    ol_check($first['partner_id']==='partner_demo' && $first['device_type']==='mobile','partner and device');
    redtoy_order_log('100','entered',array());
    ol_check($db->query('SELECT * FROM g5_redtoy_order_log')->fetch_assoc()===$first,'duplicate preserves snapshot and timestamp');
    foreach (array('submitted','payment_ready','payment_confirmed','order_saved','completed') as $event) redtoy_order_log('100',$event);
    ol_check(ol_count()===6,'each stage once');
    ol_check(!redtoy_order_log("1' OR 1=1",'entered') && !redtoy_order_log('101','unrecognized'),'invalid ID and event');
    $db->query("INSERT INTO orders VALUES (100,'입금',0,'actual_partner')");
    $_GET=array('from'=>"2026-10-23\0");
    ol_check(redtoy_order_log_date('from','2026-10-23')==='2026-10-23','invalid date safely falls back');
    $html=ol_page(array('order_id'=>'100'));
    ol_check(strpos($html,'&lt;script&gt;unsafe&lt;/script&gt;')!==false && strpos($html,'<script>unsafe</script>')===false,'product XSS escaped');
    ol_check(strpos($html,'actual_partner')!==false && strpos($html,'orderform.php?od_id=100')!==false,'actual partner and order link');
    ol_check(strpos($html,'결제 완료')!==false && strpos($html,'단계 보기')!==false,'current status and stages');
    ol_reject(function(){ol_page(array(),array(),array());},'denied');
    foreach (array(array('action'=>'delete','order_ids'=>array('100')),array('action'=>'purge')) as $post) ol_reject(function()use($post){ol_page(array(),$post);},'csrf');
    ol_check(ol_count()===6,'CSRF failures leave records');
    ol_reject(function(){ol_page(array(),array('action'=>'delete','token'=>'synthetic-token','order_ids'=>array('100')),array('r'));},'denied');
    foreach (array('100',array("100' OR 1=1"),array_fill(0,51,'100'),array()) as $ids) ol_reject(function()use($ids){ol_page(array(),array('action'=>'delete','token'=>'synthetic-token','order_ids'=>$ids));},'alert');
    ol_check(ol_count()===6,'invalid selection leaves records');
    for($i=101;$i<=155;$i++) redtoy_order_log((string)$i,'entered');
    $html=ol_page();ol_check(substr_count($html,'name="order_ids[]" value=')===50 && strpos($html,'>다음</a>')!==false,'50 rows plus next page');
    $html=ol_page(array('page'=>2));ol_check(substr_count($html,'name="order_ids[]" value=')===6,'second page');
    $base=array('saved_order'=>'','event'=>'entered','created_at'=>date('Y-m-d H:i:s',time()-3601));
    ol_check(redtoy_order_log_status($base)==='미완료 / 이탈 추정','abandonment is estimate');
    $base['event']='payment_confirmed';ol_check(strpos(redtoy_order_log_status($base),'확인 필요')!==false,'approval without order warning');
    $base['saved_order']='100';$base['od_status']='취소';$base['od_misu']=0;
    ol_check(redtoy_order_log_status($base)==='접수된 주문 / 취소','current canceled order');
    ol_check(redtoy_order_log_error_event('민감정보 포함 쿠폰 오류')==='coupon_failed','fixed error classification');
    $db->query("UPDATE g5_redtoy_order_log SET created_at=DATE_SUB(NOW(),INTERVAL 100 DAY) WHERE order_id='155'");
    $before=ol_count();ol_page();ol_check(ol_count()===$before,'GET never purges data');
    ol_reject(function(){ol_page(array(),array('action'=>'purge','token'=>'synthetic-token'));},'redirect');
    ol_check(ol_count()===$before-1,'explicit cleanup deletes old only');
    ol_reject(function(){ol_page(array(),array('action'=>'delete','token'=>'synthetic-token','order_ids'=>array('100')));},'redirect');
    ol_check((int)$db->query("SELECT COUNT(*) n FROM g5_redtoy_order_log WHERE order_id='100'")->fetch_assoc()['n']===0,'all selected stages deleted');
    ol_check((int)$db->query('SELECT COUNT(*) n FROM orders')->fetch_assoc()['n']===1,'actual order preserved');
    $member=array('mb_id'=>'synthetic-owner');$_SESSION['redtoy_checkout_attempts'][str_repeat('a',64)]=array('order_id'=>'900','owner'=>'synthetic-owner','created_at'=>time());$_POST['checkout_token']=str_repeat('a',64);$_SESSION['ss_order_id']='900';$_POST['od_id']='999';$_SERVER['SCRIPT_FILENAME']=G5_PATH.'/shop/danal_ready.php';
    require G5_PATH.'/extend/redtoy_order_log.extend.php';
    $GLOBALS['RES_DATA']=array('RETURNCODE'=>'0000');redtoy_order_log_shutdown();
    ol_check((int)$db->query("SELECT COUNT(*) n FROM g5_redtoy_order_log WHERE order_id='900'")->fetch_assoc()['n']===2,'ready hook uses server ID');
    ol_check((int)$db->query("SELECT COUNT(*) n FROM g5_redtoy_order_log WHERE order_id='999'")->fetch_assoc()['n']===0,'request ID ignored');
    $GLOBALS['redtoy_order_log_context']=array('id'=>'901','path'=>'shop/danal_ready_teledit.php');$GLOBALS['Res']=array('Result'=>'0');redtoy_order_log_shutdown();
    ol_check((int)$db->query("SELECT COUNT(*) n FROM g5_redtoy_order_log WHERE order_id='901' AND event='payment_ready'")->fetch_assoc()['n']===1,'phone ready');
    redtoy_order_log_alert('배송비 SQL secret');
    ol_check((int)$db->query("SELECT COUNT(*) n FROM g5_redtoy_order_log WHERE order_id='901' AND event='shipping_failed'")->fetch_assoc()['n']===1,'alert fixed category');
    $menus=array('menu400'=>array(array('400400','orders')));$menus=redtoy_order_log_menu($menus);
    ol_check(count(redtoy_order_log_menu($menus)['menu400'])===2 && $menus['menu400'][1][0]==='400405','menu once after order list');
    $t=microtime(true);ol_check(redtoy_order_log_query('SELECT SLEEP(2)')===false && microtime(true)-$t<1.8,'read query timeout');
    foreach(array('shop/orderformupdate.php','mobile/shop/orderformupdate.php') as $path) {
        $source=file_get_contents(G5_PATH.'/'.$path);
        ol_check(strpos($source,"'order_saved'")>strpos($source,'// 주문삭제'),'saved hook follows rollback checks '.$path);
        ol_check(strpos($source,"'completed'")>strpos($source,"include_once(G5_SHOP_PATH.'/orderalimtalk.inc.php')"),'completion after downstream work '.$path);
    }
    $db->query("INSERT INTO orders SELECT seq+100000,'입금',0,'' FROM seq_1_to_20000");
    $db->query("INSERT INTO g5_redtoy_order_log (order_id,event,customer_kind,created_at) SELECT CAST(seq+100000 AS CHAR), e.event,'guest',NOW() FROM seq_1_to_20000 CROSS JOIN (SELECT 'entered' event UNION ALL SELECT 'submitted' UNION ALL SELECT 'order_saved' UNION ALL SELECT 'completed') e");
    $t=microtime(true);$html=ol_page();$elapsed=(microtime(true)-$t)*1000;
    ol_check(substr_count($html,'name="order_ids[]" value=')===50,'80000 log rows render one page');
    echo 'Synthetic 20,000 orders / 80,000 stages, first-page PHP render: '.round($elapsed,1)." ms\n";
    $db->query('DROP TABLE g5_redtoy_order_log');
    ol_check(!redtoy_order_log('902','completed'),'write failure remains nonfatal');
    echo "PASS: $checks order log checks\n";
} finally {
    unset($GLOBALS['redtoy_order_log_context']);
    $db->query("DROP DATABASE $name");$db->close();
    foreach(array('_common.php','admin.head.php','admin.tail.php') as $f) unlink($dir.'/'.$f);rmdir($dir);
}
