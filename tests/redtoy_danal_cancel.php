<?php
// All transport responses are synthetic. No production config, API calls or member/order data.
$socket=getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D',$socket)) exit(2);
$root=dirname(__DIR__).'/www'; $g5_path=array('path'=>$root,'url'=>''); require $root.'/config.php';
$_SERVER['HTTP_HOST']='www.redtoy.co.kr'; $_SERVER['SCRIPT_NAME']='/test.php';
require $root.'/lib/hook.lib.php'; require $root.'/lib/get_data.lib.php';
add_replace('get_permission_debug_show',function () { return false; }); require $root.'/lib/common.lib.php';
require $root.'/lib/redtoy_order_completion.lib.php'; require_once $root.'/lib/redtoy_danal_cancel.lib.php'; require $root.'/lib/redtoy_danal_cancel_pg.lib.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('localhost','root','',null,0,$socket);$name='redtoy_cancel_test_'.bin2hex(random_bytes(6));$db->query("create database $name character set utf8mb4");$redtoy_completion_db=$db;$db->select_db($name);$db->set_charset('utf8mb4');
$g5=array('connect_db'=>$db,'member_table'=>'members','point_table'=>'points','g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart','g5_shop_item_table'=>'items','g5_shop_item_option_table'=>'options','g5_shop_coupon_table'=>'coupons','g5_shop_coupon_log_table'=>'coupon_logs');
define('G5_TABLE_PREFIX','g5_');
$config=array('cf_use_point'=>1,'cf_point_term'=>0);$is_admin='';$checks=0;$calls=0;
function check_cancel($ok,$label) { global $checks; $checks++; if (!$ok) throw new RuntimeException('FAIL: '.$label); }
function reject_cancel($fn,$code) { try {$fn();}catch(Throwable $e){check_cancel($e->getMessage()===$code,'reject '.$code.' (got '.$e->getMessage().')');return;}throw new RuntimeException('expected '.$code); }
function fixture_cancel($id,$phone=false,$used=10000,$coupons=true) {
    $when=G5_TIME_YMDHIS;$tid=$phone?str_repeat('2',18):str_repeat('1',24);$method=$phone?'hpp':'card';
    $ic=$coupons?6000:0;$oc=$coupons?4000:0;$sc=$coupons?1000:0;$cash=100000-$ic-$oc+3000-$sc-$used;
    redtoy_cancel_query("insert into orders values('$id','customer','danal','$method','$tid','$when','$when','入金',100000,$cash,$used,0,0,0,$ic,$oc,$sc,3000,0,0,0,0,0,'')");
    redtoy_cancel_query("update orders set od_status='입금' where od_id='$id'");
    foreach(array('A'=>60000,'B'=>40000) as $item=>$price)redtoy_cancel_query("insert into cart(od_id,it_id,ct_qty,ct_price,io_price,io_type,io_id,ct_status,ct_stock_use,ct_point_use,ct_notax,cp_price,ct_history) values('$id','$item',1,$price,0,0,'','입금',1,0,0,".($item==='A'?$ic:0).",'')");
    if($used)insert_point('customer',-$used,'주문번호 '.$id.' 결제');
    if($coupons) foreach(array('item'=>6000,'order'=>4000,'ship'=>1000) as $cp=>$amount)redtoy_cancel_query("insert into coupon_logs(cp_id,mb_id,od_id,cp_price) values('$cp','customer','$id',$amount)");
    return array_column(redtoy_cancel_carts($id),'ct_id');
}
function success_cancel($p) {global $calls;$calls++;return array('ok'=>true,'code'=>'0000','tid'=>str_repeat('3',24));}
function run_cancel($id,$ids,$mock='success_cancel',$op=null) {
    $p=redtoy_cancel_quote($id,$ids,G5_TIME_YMDHIS);$op=$op?:bin2hex(random_bytes(16));redtoy_cancel_execute($id,$ids,$p['fingerprint'],$op,'admin',$mock);return array($p,$op);
}
function cancel_test_function($name,$file=null) {
    $tokens=token_get_all(file_get_contents($file?:dirname(__DIR__).'/www/adm/admin.lib.php'));$capture=false;$found=false;$depth=0;$code='';
    foreach($tokens as $i=>$token) {
        if(is_array($token) && $token[0]===T_FUNCTION) {
            $j=$i+1;while(isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE)$j++;
            if(isset($tokens[$j][1]) && $tokens[$j][1]===$name) {$capture=true;$code='';$found=false;$depth=0;}
        }
        if(!$capture)continue;
        $text=is_array($token)?$token[1]:$token;$code.=$text;
        if((!is_array($token) && $token==='{') || (is_array($token) && in_array($token[0],array(T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES),true))){$depth++;$found=true;}
        if(!is_array($token) && $token==='}'){$depth--;if($found && !$depth)return $code;}
    }
    throw new RuntimeException('missing admin function');
}
function cancel_test_controller($fixture,$request,$socket,$role='w') {
    $p=proc_open(array(PHP_BINARY,'-d','short_open_tag=1','-d','disable_functions=curl_exec,exec,shell_exec',$fixture.'/request.php',json_encode($request),$role),array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
    if($exit || $err)throw new RuntimeException('controller fixture failed: '.$err);
    return $out;
}
try {
 foreach(array('InnoDB','MyISAM') as $engine) {
    $db->query("create table orders(od_id varchar(64) primary key,mb_id varchar(64),od_pg varchar(20),od_settle_case varchar(20),od_tno varchar(32),od_time datetime,od_receipt_time datetime,od_status varchar(20),od_cart_price int,od_receipt_price int,od_receipt_point int,od_refund_price int,od_cancel_price int,od_misu int,od_cart_coupon int,od_coupon int,od_send_coupon int,od_send_cost int,od_send_cost2 int,od_tax_flag int,od_tax_mny int,od_vat_mny int,od_free_mny int,od_mod_history text) engine=$engine");
    $db->query("create table cart(ct_id int auto_increment primary key,od_id varchar(64),it_id varchar(20),ct_qty int,ct_price int,io_price int,io_type int,io_id varchar(20),ct_status varchar(20),ct_stock_use int,ct_point_use int,ct_notax int,cp_price int,ct_history text,ct_point int default 0,ct_invoice_time varchar(19) default '2000-01-01 00:00:00',key(od_id)) engine=$engine");
    $db->query("create table members(mb_id varchar(64) primary key,mb_point int,mb_leave_date varchar(8) default '') engine=$engine");
    $db->query("create table points(po_id int auto_increment primary key,mb_id varchar(64),po_datetime datetime,po_content text,po_point int,po_use_point int,po_mb_point int,po_expired int,po_expire_date date,po_rel_table varchar(20),po_rel_id varchar(20),po_rel_action varchar(255)) engine=$engine");
    $db->query("create table items(it_id varchar(20) primary key,it_stock_qty int,it_sum_qty int,ca_id varchar(10),ca_id2 varchar(10),ca_id3 varchar(10),it_name varchar(100)) engine=$engine");
    $db->query("create table options(it_id varchar(20),io_id varchar(20),io_type int,io_stock_qty int) engine=$engine");
    $db->query("create table coupons(cp_id varchar(20) primary key,cp_method varchar(2),cp_target varchar(20),cp_end date) engine=$engine");
    $db->query("create table coupon_logs(cl_id int auto_increment primary key,cp_id varchar(20),mb_id varchar(64),od_id varchar(64),cp_price int) engine=$engine");
    $sql=file_get_contents(dirname(__DIR__).'/docs/redtoy-danal-cancel-migration.sql.example');
    $db->query($sql);
    redtoy_cancel_query("insert into members(mb_id,mb_point) values('customer',0)");
    redtoy_cancel_query("insert into items values('A',0,0,'cat','','','A <script>bad</script>'),('B',0,0,'cat','','','B')");
    redtoy_cancel_query("insert into coupons values('item','0','A','2000-01-01'),('order','2','','2000-01-01'),('ship','3','','2000-01-01')");
    insert_point('customer',1000000,'synthetic opening balance');
    $ids=fixture_cancel('full');$before=redtoy_cancel_order('full');$stock=redtoy_cancel_one("select it_stock_qty from items where it_id='A'");
    $p=redtoy_cancel_quote('full',$ids,G5_TIME_YMDHIS);
    check_cancel($p['cash']===82000 && $p['return_points']===10000 && $p['recover_points']===0 && count($p['coupon_ids'])===3,'full preview coupons/points reconciliation');
    check_cancel(redtoy_cancel_order('full')===$before && redtoy_cancel_one("select it_stock_qty from items where it_id='A'")===$stock,'preview changes no order or stock');
    list($p,$op)=run_cancel('full',$ids);$done=redtoy_cancel_order('full');
    check_cancel($done['od_status']==='취소' && (int)$done['od_refund_price']===82000 && (int)$done['od_receipt_point']===0,'full refund persisted');
    check_cancel((int)redtoy_cancel_one("select count(*) n from coupon_logs where od_id='full'")['n']===0,'full restores all coupon usage');
    check_cancel(redtoy_cancel_one("select cp_end from coupons where cp_id='item'")['cp_end']==='2000-01-01','expired coupon never extended');
    check_cancel((int)redtoy_cancel_one("select it_stock_qty from items where it_id='A'")['it_stock_qty']===1,'stock restored once');
    $count=$calls;redtoy_cancel_execute('full',$ids,'unused',$op,'admin','success_cancel');check_cancel($calls===$count,'same operation cannot call PG twice');
    reject_cancel(function()use($ids,$op){redtoy_cancel_execute('different',$ids,'unused',$op,'admin','success_cancel');},'invalid_operation');
    $ids=fixture_cancel('partial');$p=redtoy_cancel_quote('partial',array($ids[0]),G5_TIME_YMDHIS);
    check_cancel($p['full']===false && $p['cancel_type']==='P' && count($p['coupon_ids'])===1,'partial selects only product coupon');
    list($a)=run_cancel('partial',array($ids[0]));
    check_cancel(redtoy_cancel_order('partial')['od_status']==='입금','remaining product status retained');
    check_cancel((int)redtoy_cancel_one("select count(*) n from coupon_logs where od_id='partial'")['n']===2,'order/shipping coupons retained during partial');
    list($b)=run_cancel('partial',array($ids[1]));
    check_cancel($a['cash']+$b['cash']===82000 && $a['return_points']+$b['return_points']===10000,'successive partials sum exactly to full amount');
    check_cancel($b['cancel_type']==='P' && $b['full'] && count($b['coupon_ids'])===2,'final remainder uses partial PG type and returns remaining coupons');
    $ids=fixture_cancel('earned',false,0,false);$ct=$ids[0];insert_point('customer',3000,'synthetic delivered','@delivery','customer','earned,'.$ct);
    redtoy_cancel_query("update cart set ct_point_use=0 where od_id='earned'");
    list($p,$op)=run_cancel('earned',array($ct));
    check_cancel($p['recover_points']===3000,'actual delivery award recovered even stale flag=0');
    check_cancel((int)redtoy_cancel_one("select po_point from points where po_rel_table='@redtoy_cancel' and po_rel_action='{$op}:recover'")['po_point']===-3000,'recovery compensating debit recorded');
    check_cancel((int)redtoy_cancel_one("select count(*) n from points where po_rel_table='@delivery' and po_rel_action='earned,$ct'")['n']===1,'original award retained for audit');
    $ids=fixture_cancel('legacy',false,0,false);insert_point('customer',5000,'synthetic checkout','@member','legacy','상품구입');
    list($a)=run_cancel('legacy',array($ids[0]));list($b)=run_cancel('legacy',array($ids[1]));check_cancel($a['recover_points']===3000 && $b['recover_points']===2000,'checkout award apportioned to actual cancelled products');
    $ids=fixture_cancel('phone',true);$p=redtoy_cancel_quote('phone',$ids,G5_TIME_YMDHIS);check_cancel($p['method']==='phone' && $p['full'],'phone full supported');
    reject_cancel(function()use($ids){redtoy_cancel_quote('phone',array($ids[0]),G5_TIME_YMDHIS);},'phone_partial_unsupported');
    run_cancel('phone',$ids);check_cancel(redtoy_cancel_order('phone')['od_status']==='취소','phone full applies');
    $ids=fixture_cancel('unknown');$before=redtoy_cancel_order('unknown');$count=$calls;$p=redtoy_cancel_quote('unknown',$ids,G5_TIME_YMDHIS);$op=bin2hex(random_bytes(16));
    reject_cancel(function()use($ids,$p,$op){redtoy_cancel_execute('unknown',$ids,$p['fingerprint'],$op,'admin',function($p){global $calls;$calls++;return array('ok'=>false,'code'=>'transport_unknown','tid'=>'');});},'pg_review_required');
    check_cancel(redtoy_cancel_order('unknown')===$before && redtoy_cancel_carts('unknown')[0]['ct_status']==='입금','timeout leaves original business state unchanged');
    check_cancel(redtoy_cancel_pending('unknown')['state']==='review','unknown outcome durably blocks retries');
    reject_cancel(function()use($ids){redtoy_cancel_quote('unknown',$ids,G5_TIME_YMDHIS);},'reconciliation_required');check_cancel($calls===$count+1,'unknown PG outcome called only once');
    $ids=fixture_cancel('changed');$p=redtoy_cancel_quote('changed',$ids,G5_TIME_YMDHIS);redtoy_cancel_query("update cart set ct_qty=2 where od_id='changed' and it_id='A'");$count=$calls;
    reject_cancel(function()use($ids,$p){redtoy_cancel_execute('changed',$ids,$p['fingerprint'],bin2hex(random_bytes(16)),'admin','success_cancel');},'amount_reconciliation_required');check_cancel($count===$calls,'stale quote cannot reach PG');
    $ids=fixture_cancel('foreign');reject_cancel(function()use($ids){redtoy_cancel_quote('foreign',array(999999),G5_TIME_YMDHIS);},'invalid_selection');
    reject_cancel(function()use($ids){redtoy_cancel_quote('foreign',array($ids[0],$ids[0]),G5_TIME_YMDHIS);},'invalid_selection');
    $ids=fixture_cancel('period',true);redtoy_cancel_query("update orders set od_receipt_time='2000-01-01 00:00:00' where od_id='period'");reject_cancel(function()use($ids){redtoy_cancel_quote('period',$ids,G5_TIME_YMDHIS);},'phone_cancel_period');
    $ids=fixture_cancel('option',false,0,false);redtoy_cancel_query("insert into cart(od_id,it_id,ct_qty,ct_price,io_price,io_type,io_id,ct_status,ct_stock_use,ct_point_use,ct_notax,cp_price,ct_history) values('option','A',1,0,1000,1,'extra','입금',1,0,0,0,'')");redtoy_cancel_query("update orders set od_cart_price=101000,od_receipt_price=104000 where od_id='option'");redtoy_cancel_query("insert into options values('A','extra',1,0)");
    reject_cancel(function()use($ids){redtoy_cancel_quote('option',array($ids[0]),G5_TIME_YMDHIS);},'select_whole_product');
    $all=redtoy_cancel_carts('option');run_cancel('option',array($ids[0],$all[2]['ct_id']));check_cancel((int)redtoy_cancel_one("select io_stock_qty from options where io_id='extra'")['io_stock_qty']===1,'option stock restored');
    $other=new mysqli('localhost','root','',$name,0,$socket);$other->query("select GET_LOCK('redtoy_complete_order_foreign',0)");reject_cancel(function(){redtoy_cancel_locks('foreign');},'busy');$other->query("select RELEASE_LOCK('redtoy_complete_order_foreign')");$other->close();
    $ids=fixture_cancel('off',false,0,false);$config['cf_use_point']=0;insert_point('customer',100,'not inserted');$p=redtoy_cancel_quote('off',$ids,G5_TIME_YMDHIS);run_cancel('off',$ids);check_cancel($config['cf_use_point']===0,'financial reversal preserves global point setting');$config['cf_use_point']=1;
    // No transport configuration: fail before creating a request or changing the order.
    $ids=fixture_cancel('config');$p=redtoy_cancel_quote('config',$ids,G5_TIME_YMDHIS);putenv('REDTOY_PG_CANCEL_ENABLED=0');reject_cancel(function()use($ids,$p){redtoy_cancel_execute('config',$ids,$p['fingerprint'],bin2hex(random_bytes(16)),'admin');},'pg_not_configured');check_cancel(!redtoy_cancel_pending('config'),'missing config performs no PG/journal request');
    // An uncertain cancellation must also stop the already-deployed completion and payout job.
    $lockedPlan=redtoy_completion_point_plan(redtoy_completion_order('unknown'),redtoy_completion_carts('unknown'));
    check_cancel($lockedPlan['reason']==='cancel_pending','pending cancellation blocks automatic point plan');
    check_cancel(redtoy_completion_apply('unknown',G5_TIME_YMDHIS,7)==='changed','pending cancellation blocks automatic state transition');
    redtoy_cancel_query("update orders set od_status='완료',od_mod_history='[redtoy-auto-complete:v2]' where od_id='unknown'");
    redtoy_cancel_query("update cart set ct_status='완료',ct_point=100 where od_id='unknown'");
    check_cancel(!in_array('unknown',redtoy_completion_recovery_ids(1000),true),'pending cancellation excluded from payout recovery');
    check_cancel(redtoy_completion_pay('unknown')['points']===0,'pending cancellation cannot receive new credit');
    if (!function_exists('save_order_point')) eval(cancel_test_function('save_order_point',$root.'/lib/shop.lib.php'));
    check_cancel(save_order_point('완료','unknown')===0,'manual completion core payout also holds uncertain cancellation');
    $ids=fixture_cancel('dbfailure',false,0,false);$p=redtoy_cancel_quote('dbfailure',$ids,G5_TIME_YMDHIS);$op=bin2hex(random_bytes(16));
    $db->query("create trigger cancel_test_failure before update on cart for each row begin if old.od_id='dbfailure' and new.ct_status='취소' then signal sqlstate '45000' set message_text='synthetic failure'; end if; end");
    $beforeStock=(int)redtoy_cancel_one("select it_stock_qty from items where it_id='A'")['it_stock_qty'];
    reject_cancel(function()use($ids,$p,$op){redtoy_cancel_execute('dbfailure',$ids,$p['fingerprint'],$op,'admin','success_cancel');},'database_failure');
    $db->query('drop trigger cancel_test_failure');
    $audit=redtoy_cancel_one("select state,pg_cancel_tid from g5_redtoy_cancel where op_id='$op'");
    check_cancel($audit['state']==='review' && $audit['pg_cancel_tid']===str_repeat('3',24),'PG success followed by DB failure retains refund TID and review state');
    if($engine==='InnoDB') check_cancel((int)redtoy_cancel_one("select it_stock_qty from items where it_id='A'")['it_stock_qty']===$beforeStock && redtoy_cancel_carts('dbfailure')[0]['ct_stock_use']==='1','InnoDB failure rolls back financial/stock mutations');
    $n=$calls;reject_cancel(function()use($ids,$p,$op){redtoy_cancel_execute('dbfailure',$ids,$p['fingerprint'],$op,'admin','success_cancel');},'reconciliation_required');check_cancel($n===$calls,'DB failure after PG never resends refund');
    $ids=fixture_cancel('spent',false,0,false);insert_point('customer',3500,'synthetic award','@delivery','customer','spent,'.$ids[0]);
    redtoy_cancel_query("update points set po_use_point=3500,po_expired=100 where po_rel_action='spent,".$ids[0]."'");
    check_cancel(redtoy_cancel_quote('spent',array($ids[0]),G5_TIME_YMDHIS)['recover_points']===3500,'already-spent actual award still recovered');
    redtoy_cancel_query("update points set po_use_point=0,po_expired=1 where po_rel_action='spent,".$ids[0]."'");
    check_cancel(redtoy_cancel_quote('spent',array($ids[0]),G5_TIME_YMDHIS)['recover_points']===0,'expired unused award is not debited again');
    redtoy_cancel_query("update cart set ct_point_use=1 where od_id='spent' and it_id='B'");
    check_cancel(redtoy_cancel_quote('spent',array($ids[1]),G5_TIME_YMDHIS)['recover_points']===0,'stale paid flag without history causes no debit');
    redtoy_cancel_query("update points set po_use_point=100,po_expired=1 where po_rel_action='spent,".$ids[0]."'");
    reject_cancel(function()use($ids){redtoy_cancel_quote('spent',array($ids[0]),G5_TIME_YMDHIS);},'point_expiry_reconciliation_required');
    $ids=fixture_cancel('negative',false,0,false);insert_point('customer',2000,'synthetic award','@delivery','customer','negative,'.$ids[0]);
    $balance=get_point_sum('customer');insert_point('customer',-$balance,'synthetic spend all');
    list($p)=run_cancel('negative',array($ids[0]));check_cancel((int)get_point_sum('customer')===-2000,'spent award reversal can produce negative balance');
    insert_point('customer',1000000,'synthetic reset balance');
    redtoy_cancel_query("insert into members(mb_id,mb_point) values('expirycustomer',0)");
    insert_point('expirycustomer',1000,'synthetic older funds');
    $ids=fixture_cancel('expiry',false,0,false);redtoy_cancel_query("update orders set mb_id='expirycustomer' where od_id='expiry'");
    insert_point('expirycustomer',2000,'synthetic cancelled award','@delivery','expirycustomer','expiry,'.$ids[0]);
    list($p)=run_cancel('expiry',array($ids[0]));
    check_cancel((int)get_point_sum('expirycustomer')===1000,'recovery retains unrelated earlier funds');
    redtoy_cancel_query("update points set po_expire_date='2000-01-01' where mb_id='expirycustomer' and po_rel_table='@delivery'");
    $config['cf_point_term']=30;check_cancel((int)get_point_sum('expirycustomer')===1000,'cancelled award cannot expire and debit again');$config['cf_point_term']=0;
    $ids=fixture_cancel('returnoff',false,10000,false);$config['cf_use_point']=0;
    list($p,$op)=run_cancel('returnoff',$ids);check_cancel((int)redtoy_cancel_one("select po_point from points where po_rel_action='{$op}:return'")['po_point']===10000 && $config['cf_use_point']===0,'used points returned even if new awards globally disabled');$config['cf_use_point']=1;
    $ids=fixture_cancel('guest',false,0,false);redtoy_cancel_query("update orders set mb_id='' where od_id='guest'");list($p)=run_cancel('guest',$ids);check_cancel($p['return_points']===0 && $p['recover_points']===0,'guest full refund creates no invented points');
    $ids=fixture_cancel('left');redtoy_cancel_query("update members set mb_leave_date='20261023' where mb_id='customer'");reject_cancel(function()use($ids){redtoy_cancel_quote('left',$ids,G5_TIME_YMDHIS);},'point_refund_account_required');redtoy_cancel_query("update members set mb_leave_date='' where mb_id='customer'");
    // Exercise the real controller and existing admin permission/CSRF functions with a fake web root.
    $fixture=sys_get_temp_dir().'/redtoy-cancel-controller-'.bin2hex(random_bytes(6));mkdir($fixture,0700);
    mkdir($fixture.'/adm/shop_admin',0700,true);mkdir($fixture.'/bbs',0700);file_put_contents($fixture.'/bbs/alert.php','<?php echo $msg;');symlink($root.'/lib',$fixture.'/lib');copy($root.'/config.php',$fixture.'/config.php');copy($root.'/version.php',$fixture.'/version.php');
    file_put_contents($fixture.'/adm/admin.head.php','');file_put_contents($fixture.'/adm/admin.tail.php','');
    $mapping=$g5;unset($mapping['connect_db']);
    $bootstrap="<?php \$g5_path=array('path'=>".var_export($fixture,true).",'url'=>''); require \$g5_path['path'].'/config.php';define('G5_TABLE_PREFIX','g5_'); \$_SERVER['HTTP_HOST']='www.redtoy.co.kr';require G5_LIB_PATH.'/hook.lib.php';require G5_LIB_PATH.'/get_data.lib.php';add_replace('get_permission_debug_show',function(){return false;});require G5_LIB_PATH.'/common.lib.php';\$g5=".var_export($mapping,true).";\$g5['connect_db']=new mysqli('localhost','root','',".var_export($name,true).",0,".var_export($socket,true).");\$g5['connect_db']->set_charset('utf8mb4');\$member=array('mb_id'=>'admin');\$is_admin='';\$config=array('cf_use_point'=>1,'cf_point_term'=>0);\$auth=array('400400'=>\$argv[2]);\$_SESSION=array('ss_admin_token'=>'fixture_token');\n";
    foreach(array('auth_check_menu','auth_check','get_admin_token','check_admin_token') as $function)$bootstrap.=cancel_test_function($function)."\n";
    file_put_contents($fixture.'/adm/shop_admin/_common.php',$bootstrap);
    file_put_contents($fixture.'/request.php',"<?php \$r=json_decode(\$argv[1],true);\$_SERVER['REQUEST_METHOD']=\$r['method'];\$_REQUEST=\$r['data'];\$_POST=\$r['method']==='POST'?\$r['data']:array();chdir(".var_export($fixture.'/adm/shop_admin',true).");require ".var_export($root.'/adm/shop_admin/redtoy_cancel.php',true).";");
    $ids=fixture_cancel('controller',false,0,false);$before=redtoy_cancel_order('controller');
    $get=array('method'=>'GET','data'=>array('od_id'=>'controller','stage'=>'execute'));
    $out=cancel_test_controller($fixture,$get,$socket,'');check_cancel(strpos($out,'접근 권한이 없습니다')!==false,'anonymous/no permission rejected by real auth function');
    $out=cancel_test_controller($fixture,$get,$socket,'r');check_cancel(strpos($out,'권한이 없습니다')!==false,'read-only administrator cannot cancel');
    $out=cancel_test_controller($fixture,$get,$socket);check_cancel(strpos($out,'취소 금액 미리보기')!==false && redtoy_cancel_order('controller')===$before,'GET execute parameter is read only');
    $request=array('method'=>'POST','data'=>array('od_id'=>'controller','stage'=>'preview','token'=>'wrong','items'=>array($ids[0])));
    $out=cancel_test_controller($fixture,$request,$socket);check_cancel(strpos($out,'올바른 방법으로 이용')!==false,'invalid CSRF rejected by real token function');
    $request['data']['token']='fixture_token';$request['data']['cash']='1';$request['data']['recover_points']='999999999';
    $out=cancel_test_controller($fixture,$request,$socket);check_cancel(strpos($out,'60,000')!==false && strpos($out,'999,999,999')===false,'server ignores forged amount and recovery fields');
    check_cancel(strpos($out,'A &lt;script&gt;bad&lt;/script&gt;')!==false && strpos($out,'A <script>bad</script>')===false,'untrusted product names escaped in confirmation');
    check_cancel(redtoy_cancel_order('controller')===$before && !redtoy_cancel_pending('controller'),'preview does not save a cancellation or alter order');
    $request['data']['items']=array(999999);$out=cancel_test_controller($fixture,$request,$socket);check_cancel(strpos($out,'확인한 금액으로 취소')===false,'foreign cart ID never reaches confirmation');
    $request['data']=array('od_id'=>'controller','token'=>'fixture_token','stage'=>'execute','operation'=>str_repeat('0',32));$out=cancel_test_controller($fixture,$request,$socket);check_cancel(!redtoy_cancel_pending('controller'),'forged confirmation nonce cannot reach PG');
    foreach(array('bbs/alert.php','request.php','config.php','version.php','adm/admin.head.php','adm/admin.tail.php','adm/shop_admin/_common.php') as $file)unlink($fixture.'/'.$file);unlink($fixture.'/lib');rmdir($fixture.'/bbs');rmdir($fixture.'/adm/shop_admin');rmdir($fixture.'/adm');rmdir($fixture);
    foreach(array('orders','cart','members','points','items','options','coupons','coupon_logs',G5_TABLE_PREFIX.'redtoy_cancel') as $table)$db->query("drop table `$table`");
 }
 // Protocol validators use fixture values only.
 $p=array('base'=>array('order'=>array('od_tno'=>str_repeat('1',24))),'cash'=>1234,'expected_balance'=>4321,'cancel_type'=>'P');
 $r=array('RETURNCODE'=>'0000','TID'=>str_repeat('3',24),'O_TID'=>str_repeat('1',24),'AMOUNT'=>'1234','BALANCE'=>'4321');
 check_cancel(redtoy_cancel_card_result($r,$p)['ok'],'card success amount/balance verified');$r['BALANCE']='0';check_cancel(!redtoy_cancel_card_result($r,$p)['ok'],'wrong balance rejected');unset($r['BALANCE']);check_cancel(!redtoy_cancel_card_result($r,$p)['ok'],'missing balance uncertain');
 check_cancel(redtoy_cancel_card_request($p)['CANCELTYPE']==='P','partial request protocol');
 $p['base']['order']['od_tno']=str_repeat('2',18);$r=array('Result'=>'0','TID'=>str_repeat('2',18),'Date'=>'20261023120000');check_cancel(redtoy_cancel_phone_result($r,$p)['ok'],'phone TID verified');$r['TID']='wrong';check_cancel(!redtoy_cancel_phone_result($r,$p)['ok'],'wrong phone TID rejected');
 $shares=redtoy_cancel_allocate(2,array('a'=>1,'b'=>1,'c'=>1));check_cancel(array_sum($shares)===2 && $shares['a']===1 && $shares['b']===1,'stable remainder allocation');
 echo "PASS: $checks cancellation assertions; InnoDB and MyISAM; synthetic PG only\n";
} finally {$db->query("drop database `$name`");}
