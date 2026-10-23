<?php
// Isolated synthetic MariaDB only; never loads production settings or sends a notification.
$socket = getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D', $socket)) exit(2);
define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', 'g5_');
define('G5_LIB_PATH', dirname(__DIR__).'/www/lib');
define('G5_IS_MOBILE', false);
date_default_timezone_set('Asia/Seoul');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost','root','',null,0,$socket);
$name = 'redtoy_purchase_test_'.bin2hex(random_bytes(6));
$db->query("CREATE DATABASE $name CHARACTER SET utf8 COLLATE utf8_general_ci");
$db->select_db($name); $db->set_charset('utf8');
$g5 = array('connect_db'=>$db,'g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart',
    'g5_shop_item_table'=>'items','g5_shop_order_delete_table'=>'deleted');
require G5_LIB_PATH.'/redtoy_purchase.lib.php';
$root = dirname(__DIR__).'/www';
$checks = 0;
function check_purchase($ok,$message) { global $checks; $checks++; if (!$ok) throw new RuntimeException('FAIL: '.$message); }
function reject_purchase($fn,$code) {
    try { $fn(); } catch (Throwable $e) { check_purchase($e->getMessage()===$code, $code.' got '.$e->getMessage()); return; }
    throw new RuntimeException('Expected '.$code);
}
function set_purchase($text) { return redtoy_purchase_save($text,redtoy_purchase_revision(redtoy_purchase_codes()),'synthetic-admin'); }
$dir = sys_get_temp_dir().'/redtoy-purchase-controller-'.bin2hex(random_bytes(6));
mkdir($dir); mkdir($dir.'/plugin'); mkdir($dir.'/plugin/jquery-ui');
file_put_contents($dir.'/_common.php','<?php');
foreach (array('admin.head.php','admin.tail.php','plugin/jquery-ui/datepicker.php') as $file) file_put_contents($dir.'/'.$file,'<?php');
define('G5_ADMIN_PATH',$dir); define('G5_PLUGIN_PATH',$dir.'/plugin');
function auth_check_menu($auth,$menu,$mode) {
    $GLOBALS['purchase_auth_calls'][] = $mode;
    if (!$GLOBALS['purchase_permission']) throw new RuntimeException('permission');
}
function check_admin_token() { if (!$GLOBALS['purchase_csrf']) throw new RuntimeException('csrf'); }
function get_admin_token() { return 'synthetic-token'; }
function goto_url($url) { throw new RuntimeException('redirect:'.$url); }
function clean_xss_tags($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
function get_search_string($value) { return addslashes((string)$value); }
function sql_query($sql,$error=true) { return redtoy_purchase_query($sql); }
function sql_fetch($sql) { return mysqli_fetch_assoc(sql_query($sql)); }
function sql_fetch_array($result) { return mysqli_fetch_assoc($result); }
function get_checked($a,$b) { return $a===$b ? 'checked' : ''; }
function get_selected($a,$b) { return $a===$b ? 'selected' : ''; }
function query_purchase_list($params) {
    global $root,$dir,$g5;
    $config = array('cf_page_rows'=>2); $default = array('de_escrow_use'=>0); $auth=array();
    $page = isset($params['page']) ? max(1,(int)$params['page']) : 1;
    $save_search = isset($params['save_search']) ? $params['save_search'] : '';
    $_GET=$params; $_REQUEST=$params; $_SERVER['SCRIPT_NAME']='/adm/shop_admin/orderlist.php';
    $source=file_get_contents($root.'/adm/shop_admin/orderlist.php');
    $end=strpos($source,'?>');
    $old=getcwd(); chdir($dir);
    ob_start();
    try { eval(substr($source,5,$end-5)); }
    finally { ob_end_clean(); chdir($old); }
    return array('count'=>(int)$total_count,'ids'=>array_column(mysqli_fetch_all($result,MYSQLI_ASSOC),'od_id'),
        'query'=>$qstr,'notice'=>$purchase_notice,'context'=>$purchase_context,'sql'=>$sql,
        'vars'=>compact('doc','sort1','sort2','page','search','sel_field','purchase_only','purchase_toggle','purchase_context','od_status','od_settle_case','od_misu','od_cancel_price','od_refund_price','od_receipt_point','od_coupon','od_escrow','fr_date','to_date','default'));
}
function purchase_management($post=null,$allowed=true,$csrf=true) {
    global $root,$dir,$g5;
    $auth=array(); $member=array('mb_id'=>'synthetic-admin');
    $GLOBALS['purchase_permission']=$allowed; $GLOBALS['purchase_csrf']=$csrf;
    $_GET=array(); $_POST=$post===null?array():$post; $_REQUEST=$_POST; $_SERVER['REQUEST_METHOD']=$post===null?'GET':'POST';
    $old=getcwd(); chdir($dir); ob_start();
    try { include $root.'/adm/shop_admin/redtoy_purchase.php'; return ob_get_contents(); }
    catch (Throwable $e) { return $e->getMessage(); }
    finally { ob_end_clean(); chdir($old); }
}
try {
    $db->query('CREATE TABLE items(it_id varchar(20) PRIMARY KEY)');
    $migration=file_get_contents(dirname(__DIR__).'/docs/redtoy-purchase-migration.sql.example');
    preg_match('/CREATE TABLE.*?;/s',$migration,$match); $db->query($match[0]);
    $db->query("INSERT INTO items VALUES ('A-001'),('B002'),('C003'),('RARE'),('COMMON'),('ALWAYS')");
    check_purchase(redtoy_purchase_parse(" A-001\nB002\tA-001 a-001 ")===array('a-001','B002'),'whitespace and case-insensitive deduplication');
    foreach (array(array('x'),'A_1',"<script>",str_repeat('a',21),"a' OR 1=1") as $bad)
        reject_purchase(function()use($bad){redtoy_purchase_parse($bad);},'invalid_codes');
    $many=array();for($i=0;$i<501;$i++)$many[]='A'.$i;
    reject_purchase(function()use($many){redtoy_purchase_parse(implode(' ',$many));},'too_many_codes');
    check_purchase(count(redtoy_purchase_parse(implode(' ',array_slice($many,0,500))))===500,'maximum 500 codes accepted');
    check_purchase(isset(set_purchase('unknown')['unknown']),'unknown code not silently saved');
    check_purchase(redtoy_purchase_codes()===array(),'unknown code leaves list untouched');
    check_purchase(set_purchase('a-001 B002')['saved']===2,'existing product codes canonicalized');
    check_purchase(redtoy_purchase_codes()===array('A-001','B002'),'canonical codes stored');
    $stale=redtoy_purchase_revision(redtoy_purchase_codes()); set_purchase('C003');
    reject_purchase(function()use($stale){redtoy_purchase_save('A-001',$stale,'admin');},'stale_codes');
    check_purchase(redtoy_purchase_codes()===array('C003'),'concurrent save cannot overwrite later changes');
    $db->query("CREATE TRIGGER purchase_fail BEFORE INSERT ON g5_redtoy_purchase_item FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic'");
    reject_purchase(function(){set_purchase('A-001');},'purchase_database_error');
    check_purchase(redtoy_purchase_codes()===array('C003'),'failed save rolls back delete');
    $db->query('DROP TRIGGER purchase_fail');
    $db->query("DELETE FROM items WHERE it_id='C003'");
    check_purchase(set_purchase('C003')['saved']===1,'already registered deleted catalog code retained');
    $db->query('ALTER TABLE g5_redtoy_purchase_item ENGINE=MyISAM');
    reject_purchase(function(){set_purchase('A-001');},'purchase_schema_required');
    check_purchase(redtoy_purchase_codes()===array('C003'),'wrong settings engine refused without data loss');
    $db->query('ALTER TABLE g5_redtoy_purchase_item ENGINE=InnoDB');
    $GLOBALS['purchase_permission']=true; $GLOBALS['purchase_csrf']=true; $GLOBALS['purchase_auth_calls']=array();
    check_purchase(purchase_management(null,false)==='permission','management requires write permission');
    check_purchase(purchase_management(array('codes'=>'A-001','revision'=>redtoy_purchase_revision(redtoy_purchase_codes())),true,false)==='csrf','management requires CSRF');
    check_purchase(redtoy_purchase_codes()===array('C003'),'rejected management requests no changes');
    $html=purchase_management(array('codes'=>'<script>','revision'=>redtoy_purchase_revision(redtoy_purchase_codes())));
    check_purchase(strpos($html,'&lt;script&gt;')!==false && strpos($html,'<script>')===false,'bad code output escaped');
    check_purchase(strpos(purchase_management(array('codes'=>'A-001','revision'=>redtoy_purchase_revision(redtoy_purchase_codes()))),'redirect:')===0,'actual management controller saves');

    foreach (array('InnoDB','MyISAM') as $engine) {
        $db->query('DROP TABLE IF EXISTS orders,cart,deleted');
        $db->query("CREATE TABLE orders(od_id varchar(20) PRIMARY KEY,od_status varchar(16),od_time datetime,
            od_settle_case varchar(16),mb_id varchar(20),od_name varchar(32),od_b_name varchar(32),
            od_cart_price int DEFAULT 10000,od_receipt_price int DEFAULT 10000,od_cancel_price int DEFAULT 0,
            od_misu int DEFAULT 0,od_refund_price int DEFAULT 0,od_receipt_point int DEFAULT 0,
            od_cart_coupon int DEFAULT 0,od_coupon int DEFAULT 0,od_send_coupon int DEFAULT 0,od_escrow int DEFAULT 0,
            od_receipt_time datetime,od_invoice_time datetime,KEY status(od_status),KEY time(od_time)) ENGINE=$engine");
        $db->query("CREATE TABLE cart(ct_id int PRIMARY KEY AUTO_INCREMENT,od_id varchar(20),it_id varchar(20),ct_status varchar(16),KEY od_id(od_id)) ENGINE=$engine");
        $db->query('CREATE TABLE deleted(mb_id varchar(20))');
        $db->query("INSERT INTO orders(od_id,od_status,od_time,od_settle_case,mb_id,od_name,od_b_name) VALUES
            ('1001','배송','2026-10-01','무통장','customer','synthetic','synthetic'),
            ('1002','완료','2026-10-02','card','customer','synthetic','synthetic'),
            ('1003','배송','2026-10-03','무통장','customer','synthetic','synthetic'),
            ('1004','취소','2026-10-04','card','customer','synthetic','synthetic')");
        $db->query("INSERT INTO cart(od_id,it_id,ct_status) VALUES ('1001','A-001','배송'),('1001','A-001','배송'),('1001','B002','배송'),('1002','A-001','완료'),('1003','B002','배송'),('1004','A-001','취소')");
        $GLOBALS['purchase_permission']=true;
        set_purchase('A-001');
        $before=(int)$db->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch_assoc()['Value'];
        $r=query_purchase_list(array());
        $after=(int)$db->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch_assoc()['Value'];
        check_purchase($after-$before===4, "$engine OFF issues exactly the original three SQL queries");
        check_purchase($r['count']===4 && $r['ids']===array('1004','1003'),"$engine OFF unchanged, no covering index required");
        $r=query_purchase_list(array('purchase_only'=>'1'));
        check_purchase($r['count']===0 && $r['notice']!=='',"$engine missing index refuses filtered scan");
        $db->query('ALTER TABLE cart ADD INDEX short_code(it_id(5),od_id)');
        check_purchase(query_purchase_list(array('purchase_only'=>'1'))['notice']!=='',"$engine partial-column index refused");
        $db->query('ALTER TABLE cart DROP INDEX short_code');
        $db->query('ALTER TABLE cart ADD INDEX redtoy_purchase_item_order(it_id,od_id)');
        $r=query_purchase_list(array('purchase_only'=>'1'));
        check_purchase($r['count']===3 && $r['ids']===array('1004','1002'),"$engine ON unique orders including historical cancellation");
        $r=query_purchase_list(array('purchase_only'=>'1','page'=>'2'));
        check_purchase($r['ids']===array('1001'),"$engine paging");
        $r=query_purchase_list(array('purchase_only'=>'1','od_status'=>'완료'));
        check_purchase($r['count']===1 && $r['ids']===array('1002'),"$engine completed filter intersects");
        $r=query_purchase_list(array('purchase_only'=>'1','od_status'=>'배송','od_settle_case'=>'무통장','fr_date'=>'2026-10-01','to_date'=>'2026-10-03','sel_field'=>'od_id','search'=>'1001','save_search'=>'1001'));
        check_purchase($r['count']===1 && $r['ids']===array('1001'),"$engine all existing filters intersect");
        check_purchase(strpos($r['query'],'purchase_only=1')!==false,"$engine sort/page/detail query preserves ON");
        $r=query_purchase_list(array('purchase_only'=>'1','sort1'=>'od_id','sort2'=>'asc'));
        check_purchase($r['ids']===array('1001','1002'), "$engine explicit sorting retained");
        $db->query("UPDATE orders SET od_misu=500,od_refund_price=100,od_cancel_price=1,od_receipt_point=200,od_coupon=300,od_escrow=1 WHERE od_id='1001'");
        foreach (array('od_misu','od_refund_price','od_cancel_price','od_receipt_point','od_coupon','od_escrow') as $field) {
            $r=query_purchase_list(array('purchase_only'=>'1',$field=>'Y'));
            check_purchase($r['count']===1 && $r['ids']===array('1001'),"$engine existing $field checkbox retained");
        }
        $r=query_purchase_list(array('purchase_only'=>'1','od_status'=>'부분취소'));
        check_purchase($r['ids']===array('1001'),"$engine partial cancellation status retained");
        $r=query_purchase_list(array('purchase_only'=>'1','od_status'=>'전체취소'));
        check_purchase($r['ids']===array('1004'),"$engine full cancellation status retained");
        set_purchase('');
        check_purchase(query_purchase_list(array('purchase_only'=>'1'))['count']===0,"$engine empty configuration is empty result, not all orders");
        check_purchase(query_purchase_list(array())['count']===4,"$engine OFF independent of configuration");
        $db->query('RENAME TABLE g5_redtoy_purchase_item TO codes_before');
        check_purchase(query_purchase_list(array())['count']===4,"$engine OFF works without new table (zero config dependency)");
        check_purchase(query_purchase_list(array('purchase_only'=>'1'))['notice']!=='',"$engine ON missing schema safely warned");
        $db->query('RENAME TABLE codes_before TO g5_redtoy_purchase_item');
    }
    $context=array('purchase_only'=>'1','od_status'=>'배송','od_settle_case'=>'무통장','fr_date'=>'2026-10-01','to_date'=>'2026-10-31','search'=>'a"<&','page'=>'99','evil'=>'ignored');
    $url=redtoy_purchase_url($context);
    check_purchase(strpos($url,'page=')===false && strpos($url,'evil=')===false,'toggle resets page and ignores non-whitelisted keys');
    $hidden=redtoy_purchase_hidden($context,array('od_status'));
    check_purchase(strpos($hidden,'name="od_status"')===false && strpos($hidden,'&quot;&lt;&amp;')!==false,'hidden context escaped without duplicate form fields');
    $return=redtoy_purchase_return(array('purchase_context'=>http_build_query($context)));
    check_purchase(strpos($return,'purchase_only=1')!==false && strpos($return,'od_status=')!==false,'bulk update/delete return context preserved');
    check_purchase(redtoy_purchase_return(array('purchase_context'=>array()))==='','malformed navigation context ignored');
    check_purchase(redtoy_purchase_return(array('purchase_context'=>'purchase_only=0'))==='','OFF does not change legacy bulk redirect');
    foreach (array('orderlistupdate.php','orderlistdelete.php') as $file) {
        $source=file_get_contents($root.'/adm/shop_admin/'.$file);
        check_purchase(strpos($source,'redtoy_purchase_return($_POST)') < strpos($source,'goto_url("./orderlist.php?$qstr")'),'bulk return uses validated local context');
    }
    $_SESSION=array();
    $back=redtoy_purchase_detail_back('1001',array('purchase_only'=>'1','od_status'=>'배송','page'=>'3'));
    check_purchase(strpos($back,'purchase_only=1')!==false && strpos($back,'page=3')!==false,'detail back link retains ON and page');
    check_purchase(redtoy_purchase_detail_back('1001',array())===$back,'detail edit redirect retains navigation state without payment-handler changes');
    check_purchase(redtoy_purchase_detail_back('1001',array('purchase_only'=>'0'))==='','explicit OFF clears detail back state');
    for($i=1;$i<=20;$i++)redtoy_purchase_detail_back((string)$i,array('purchase_only'=>'1'));
    check_purchase(count($_SESSION['ss_redtoy_purchase_back'])===10,'detail context session bounded to ten orders');
    set_purchase('A-001');
    $r=query_purchase_list(array('purchase_only'=>'1','od_status'=>'배송','search'=>'1001','sel_field'=>'od_id','save_search'=>'1001','fr_date'=>'2026-10-01','to_date'=>'2026-10-31'));
    $source=file_get_contents($root.'/adm/shop_admin/orderlist.php');
    extract($r['vars'],EXTR_SKIP);
    $forms=array();
    foreach(array('<form name="frmorderlist"','<form class="local_sch03') as $marker) {
        $start=strpos($source,$marker);$end=strpos($source,'</form>',$start)+7;
        ob_start();eval('?>'.substr($source,$start,$end-$start));$forms[]=ob_get_clean();
    }
    foreach($forms as $html)check_purchase(strpos($html,'name="purchase_only" value="1"')!==false,'both actual search forms carry ON');
    check_purchase(strpos($forms[0],'aria-pressed="true"')!==false,'ON button accessible/visually active');
    preg_match('/href="([^"]*)"[^>]*role="button"/',$forms[0],$match);
    parse_str(parse_url(html_entity_decode($match[1],ENT_QUOTES,'UTF-8'),PHP_URL_QUERY),$toggle);
    check_purchase($toggle['purchase_only']==='0' && $toggle['od_status']==='배송' && $toggle['fr_date']==='2026-10-01','actual toggle switches OFF retaining filters');
    check_purchase(strpos($forms[1],'name="search" value="1001"')!==false,'lower status/date form carries top search');
    check_purchase(strpos($forms[0],'name="od_status" value="배송"')!==false,'top search carries lower status');
    $peer=new mysqli('localhost','root','',$name,0,$socket);
    $lock_name='redtoy_purchase_'.substr(hash('sha256',$name.'|`g5_redtoy_purchase_item`'),0,32);
    $peer->query("SELECT GET_LOCK('$lock_name',0)");
    reject_purchase(function(){set_purchase('A-001');},'purchase_busy');
    $peer->query("SELECT RELEASE_LOCK('$lock_name')");$peer->close();
    $start=microtime(true);
    reject_purchase(function(){redtoy_purchase_select('SELECT SLEEP(2)');},'purchase_database_error');
    check_purchase(microtime(true)-$start<1.8,'filtered SELECT timeout is bounded to one second');
    check_purchase((int)redtoy_purchase_rows('SELECT 1 AS ok')[0]['ok']===1,'timeout does not break subsequent normal work');
    echo "PASS: $checks purchase management/filter checks (InnoDB + MyISAM; no production DB/API)\n";
} finally {
    $db->query("DROP DATABASE `$name`"); $db->close();
    unlink($dir.'/plugin/jquery-ui/datepicker.php');rmdir($dir.'/plugin/jquery-ui');rmdir($dir.'/plugin');
    foreach (glob($dir.'/*') as $file) unlink($file);rmdir($dir);
}
