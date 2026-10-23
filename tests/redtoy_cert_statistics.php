<?php
// Only synthetic local MariaDB; no production config or authentication API calls.
$socket = getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D', $socket)) exit(2);
define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', 'g5_');
define('G5_LIB_PATH', dirname(__DIR__).'/www/lib');
define('G5_SERVER_TIME', time());
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost','root','',null,0,$socket);
$name = 'redtoy_cert_test_'.bin2hex(random_bytes(6));
$db->query("CREATE DATABASE $name");
$db->select_db($name);
$g5 = array('connect_db'=>$db, 'member_table'=>'members', 'visit_sum_table'=>'visits', 'g5_shop_order_table'=>'orders');
require G5_LIB_PATH.'/redtoy_cert_statistics.lib.php';
require dirname(__DIR__).'/www/adm/shop_admin/redtoy_statistics.lib.php';
$checks = 0;
function cert_check($ok,$label) { global $checks; ++$checks; if (!$ok) throw new RuntimeException('FAIL: '.$label); }
function sql_real_escape_string($value) { return addslashes($value); }
function sql_query($sql,$error=true) {
    if (strpos($sql,'redtoy_order_cancel_event') !== false) return false;
    if (strpos($sql,'from visits') !== false) return new ArrayIterator(array(array('vs_date'=>'2026-10-23','vs_count'=>10)));
    if (strpos($sql,'substring(mb_datetime') !== false) return new ArrayIterator(array(array('stat_date'=>'2026-10-23','cnt'=>2)));
    return new ArrayIterator(array());
}
function sql_fetch_array($result) { if (!$result->valid()) return null; $value=$result->current(); $result->next(); return $value; }
function sql_fetch($sql) { return array('mb_id'=> $GLOBALS['test_member_match'] ? 'synthetic-member' : ''); }
function get_session($key) { return $GLOBALS['test_session'][$key] ?? ''; }
function set_session($key,$value) { $GLOBALS['test_session'][$key]=$value; }
function set_cookie($key,$value,$time) { $GLOBALS['test_cookies'][$key]=$value; }
function clean_xss_tags($value) { return $value; }
function hyphen_hp_number($value) { return $value; }
function alert_close($message) { throw new RuntimeException('callback_rejected'); }
function alert($message,$url) { throw new RuntimeException('callback_rejected'); }
function is_mobile() { return $GLOBALS['test_mobile']; }
function cert_mock_curl_exec($handle) { return $GLOBALS['test_response']; }
class Crypto { static function decryptJson($data,$rv,$key,$site) { return $GLOBALS['test_decrypted']; } }
$callback = file_get_contents(dirname(__DIR__).'/www/plugin/kcpcert_v2/kcpcert_result.php');
$callback = substr($callback,5,strpos($callback,"\$g5['title']")-5);
$callback = str_replace(array("include_once('./_common.php');", "include_once(G5_KCPCERT_V2_PATH.'/kcpcert_config.php');"), '', $callback);
$start = strpos($callback,'function kcpcert_v2_clear_session()');
$end = strpos($callback,"\n}\n",$start)+3;
eval(substr($callback,$start,$end-$start));
$callback = substr_replace($callback,'',$start,$end-$start);
// Replace only network transport. All response validation and success recording run unchanged.
$callback = str_replace('curl_exec($ch)', 'cert_mock_curl_exec($ch)', $callback);
function run_cert_callback($case,$page='main',$mobile=false) {
    global $callback,$g5;
    $GLOBALS['test_mobile']=$mobile;
    $GLOBALS['test_member_match']=$page==='find';
    $GLOBALS['test_session']=array('ss_kcpcert_v2_reg_cert_key'=>'synthetic-'.$case.'-'.$page.'-'.(int)$mobile,
        'ss_kcpcert_v2_ordr_idxx'=>'synthetic-order','ss_kcpcert_v2_page_type'=>$page);
    $GLOBALS['test_cookies']=array();
    $_POST=array('res_cd'=>'0000','reg_cert_key'=>$GLOBALS['test_session']['ss_kcpcert_v2_reg_cert_key']);
    $GLOBALS['test_response']=json_encode(array('res_cd'=>'0000','enc_cert_data'=>'synthetic','rv'=>'synthetic'));
    $GLOBALS['test_decrypted']=json_encode(array('phone_no'=>'000-0000-0000','user_name'=>'Synthetic',
        'birth_day'=>'19900101','ci'=>'synthetic-ci','di'=>'synthetic-di'));
    if ($case==='bad-key') $_POST['reg_cert_key']='mismatch';
    if ($case==='cancel') $_POST['res_cd']='9999';
    if ($case==='transport') $GLOBALS['test_response']=false;
    if ($case==='provider-failure') $GLOBALS['test_response']='{"res_cd":"9999"}';
    if ($case==='decrypt') $GLOBALS['test_decrypted']='invalid-json';
    if ($case==='missing-field') $GLOBALS['test_decrypted']='{"user_name":"Synthetic"}';
    if ($case==='duplicate-member') $GLOBALS['test_member_match']=true;
    $member=array('mb_id'=>'','mb_certify'=>'','mb_dupinfo'=>'');
    $g_conf_dec_url='https://invalid.example/'; $g_conf_site_cd='synthetic'; $g_conf_ENC_KEY='synthetic';
    try { eval($callback); return true; }
    catch (RuntimeException $e) { if ($e->getMessage()==='callback_rejected') return false; throw $e; }
}
function get_text($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function auth_check_menu($auth,$menu,$mode) {
    cert_check($menu==='500130' && $mode==='r','statistics read permission required');
}
function cert_count() { global $db; return (int)$db->query('SELECT COUNT(*) n FROM g5_redtoy_cert_success')->fetch_assoc()['n']; }
try {
    cert_check(redtoy_cert_success_daily('2026-10-23','2026-10-24')===null,'missing table is unavailable, not zero');
    cert_check(redtoy_cert_record_success('synthetic-before-migration')===false,'missing table write fails safely');
    cert_check(run_cert_callback('missing-table')===true,'authentication completes without statistics table');
    cert_check(get_session('ss_cert_adult')==='OK','authentication session preserved on logging failure');
    $migration=file_get_contents(dirname(__DIR__).'/docs/redtoy-cert-statistics-migration.sql.example');
    preg_match('/CREATE TABLE.*?;/s',$migration,$match); $db->query($match[0]);
    foreach (array('',null,array(),'') as $bad) cert_check(redtoy_cert_record_success($bad)===false,'invalid transaction rejected');
    cert_check(redtoy_cert_record_success('synthetic-duplicate')===true,'insert');
    $before=$db->query('SELECT * FROM g5_redtoy_cert_success')->fetch_assoc();
    cert_check(redtoy_cert_record_success('synthetic-duplicate')===true && cert_count()===1,'duplicate callback is one event');
    cert_check($before===$db->query('SELECT * FROM g5_redtoy_cert_success')->fetch_assoc(),'duplicate preserves first timestamp');
    cert_check($before['event_key']!== 'synthetic-duplicate' && strlen($before['event_key'])===64,'only digest stored');
    $db->query('TRUNCATE g5_redtoy_cert_success');
    foreach (array('bad-key','cancel','transport','provider-failure','decrypt','missing-field') as $case) {
        cert_check(run_cert_callback($case)===false && cert_count()===0,$case.' is not counted');
    }
    foreach (array(false,true) as $mobile) foreach (array('main','register','find','pay') as $page) {
        $before=cert_count();
        cert_check(run_cert_callback('success',$page,$mobile)===true && cert_count()===$before+1,'success '.$page.' mobile='.(int)$mobile);
        cert_check(get_session('ss_kcpcert_v2_reg_cert_key')==='', 'consumed transaction cleared');
        cert_check(run_cert_callback('success',$page,$mobile)===true && cert_count()===$before+1,'replayed transaction not recounted');
    }
    $before=cert_count();
    cert_check(!run_cert_callback('duplicate-member','register') && cert_count()===$before+1,'verified identity counts even if signup is rejected');
    cert_check(get_session('ss_kcpcert_v2_reg_cert_key')==='','existing signup rejection unchanged');
    $db->query('TRUNCATE g5_redtoy_cert_success');
    foreach (array('2026-10-22 23:59:59','2026-10-23 00:00:00','2026-10-23 23:59:59','2026-10-24 00:00:00') as $i=>$time) {
        $key=hash('sha256','boundary-'.$i); $db->query("INSERT INTO g5_redtoy_cert_success VALUES ('$key','$time')");
    }
    cert_check(redtoy_cert_success_daily('2026-10-23 00:00:00','2026-10-24 00:00:00')===array('2026-10-23'=>2),'KST day boundaries inclusive/exclusive');
    cert_check(redtoy_cert_success_daily('2026-10-25 00:00:00','2026-10-26 00:00:00')===array(),'empty day');
    $stats=redtoy_statistics_daily('2026-10-22','2026-10-24');
    cert_check($stats['cert_success_ready'] && array_sum(array_column($stats['rows'],'certifications'))===4,'daily integration and total');
    cert_check($stats['rows']['2026-10-23']['joins']===2 && $stats['rows']['2026-10-23']['visits']===10,'existing joins/visits preserved');
    $dir=sys_get_temp_dir().'/redtoy-cert-view-'.bin2hex(random_bytes(6));
    mkdir($dir); mkdir($dir.'/jquery-ui');
    foreach (array('_common.php','admin.head.php','admin.tail.php','jquery-ui/datepicker.php') as $file) file_put_contents($dir.'/'.$file,'<?php');
    define('G5_ADMIN_PATH',$dir); define('G5_PLUGIN_PATH',$dir);
    $_GET=array('fr_date'=>'2026-10-22','to_date'=>'2026-10-24');
    // Avoid requesting a future date when this test runs on October 23.
    $_GET['to_date']='2026-10-23';
    $source=file_get_contents(dirname(__DIR__).'/www/adm/shop_admin/redtoy_statistics.php');
    $source=str_replace("include_once('./redtoy_statistics.lib.php');",'', $source);
    $auth=array(); $cwd=getcwd(); chdir($dir); ob_start();
    try { eval('?>'.$source); $html=ob_get_clean(); }
    finally { chdir($cwd); foreach (array('_common.php','admin.head.php','admin.tail.php','jquery-ui/datepicker.php') as $file) unlink($dir.'/'.$file); rmdir($dir.'/jquery-ui'); rmdir($dir); }
    cert_check(strpos($html,'인증건수</th>')<strpos($html,'회원가입수</th>'),'certification column before joins');
    preg_match('/<tfoot>(.*?)<\/tfoot>/s',$html,$footer);
    preg_match_all('/<td[^>]*>(.*?)<\/td>/s',$footer[1],$cells);
    cert_check(count($cells[1])===18 && $cells[1][2]==='3' && $cells[1][3]==='2','rendered total and 18 aligned columns');
    cert_check(strpos($html,'과거 인증 이력은 소급 집계하지 않습니다')!==false,'coverage limitation shown');
    $db->query('DROP TABLE g5_redtoy_cert_success');
    cert_check(!redtoy_statistics_daily('2026-10-23','2026-10-23')['cert_success_ready'],'missing table warning state');
    echo "PASS: $checks certification statistics checks\n";
} finally { $db->query("DROP DATABASE $name"); $db->close(); }
