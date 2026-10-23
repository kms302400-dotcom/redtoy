<?php
// Isolated real-DB integration tests. Never loads production dbconfig.php or calls a PG.
$socket=getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D',$socket)) {
    fwrite(STDERR,"Set REDTOY_COMPLETION_TEST_SOCKET to the isolated test socket\n"); exit(2);
}
$root=dirname(__DIR__).'/www';
$g5_path=array('path'=>$root,'url'=>''); require $root.'/config.php';
$_SERVER['HTTP_HOST']='www.redtoy.co.kr'; $_SERVER['SCRIPT_NAME']='/test.php';
require $root.'/lib/hook.lib.php'; require $root.'/lib/get_data.lib.php';
add_replace('get_permission_debug_show', function () { return false; });
require $root.'/lib/common.lib.php';
require $root.'/lib/redtoy_order_completion.lib.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$redtoy_completion_db=new mysqli('localhost','root','',null,0,$socket);
$dbname='redtoy_completion_test_'.bin2hex(random_bytes(6));
$redtoy_completion_db->query("create database $dbname character set utf8mb4");
$redtoy_completion_db->select_db($dbname);$redtoy_completion_db->set_charset('utf8mb4');
$g5=array('connect_db'=>$redtoy_completion_db,'g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart','member_table'=>'members','point_table'=>'points','config_table'=>'config','g5_shop_item_table'=>'items');
$config=array('cf_use_point'=>1,'cf_point_term'=>0);$is_admin='';$checks=0;$fixture=null;
function verify_completion($ok,$label) {global $checks;$checks++;if(!$ok)throw new RuntimeException('FAIL: '.$label);}
function completion_fixture_order($id,$carts,$member='customer',$status='배송') {
    $id=redtoy_completion_escape($id);$member=redtoy_completion_escape($member);
    redtoy_completion_query("insert into orders values('$id','$member','$status','2000-01-01 00:00:00','2100-01-01 00:00:00','')");
    foreach($carts as $cart) {
        $state=redtoy_completion_escape($cart[0]);$date=redtoy_completion_escape($cart[1]);$point=isset($cart[2])?(int)$cart[2]:100;
        redtoy_completion_query("insert into cart(od_id,it_id,ct_status,ct_invoice_time,ct_point,ct_qty,ct_point_use) values('$id','item1','$state','$date',$point,2,0)");
    }
}
function completion_cli($fixture,$args,$socket) {
    $cmd=array_merge(array(PHP_BINARY,'-d','short_open_tag=1','-d','mysqli.default_socket='.$socket,'-d','disable_functions=curl_exec,mail,fsockopen',$fixture.'/adm/shop_admin/order_auto_complete.php'),$args);
    $p=proc_open($cmd,array(0=>array('file','/dev/null','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
    if($err)throw new RuntimeException('CLI error: '.$err);
    return array($exit,$out);
}
try {
    // Both storage engines are used by deployments of this legacy application.
    foreach(array('InnoDB','MyISAM') as $engine) {
        $redtoy_completion_db->query("create table orders(od_id varchar(64) primary key,mb_id varchar(20),od_status varchar(20),od_time datetime,od_invoice_time datetime,od_mod_history text) engine=$engine");
        $redtoy_completion_db->query("create table cart(ct_id int primary key auto_increment,od_id varchar(64),it_id varchar(20),ct_status varchar(20),ct_invoice_time varchar(19),ct_point int,ct_qty int,ct_point_use int, key(od_id)) engine=$engine");
        $redtoy_completion_db->query("create table members(mb_id varchar(20) primary key,mb_point int default 0,mb_leave_date varchar(8) default '') engine=$engine");
        $redtoy_completion_db->query("create table points(po_id int primary key auto_increment,mb_id varchar(20),po_datetime datetime,po_content text,po_point int,po_use_point int,po_mb_point int,po_expired int,po_expire_date date,po_rel_table varchar(64),po_rel_id varchar(64),po_rel_action varchar(128)) engine=$engine");
        $redtoy_completion_db->query("create table items(it_id varchar(20) primary key,it_sum_qty int) engine=$engine");
        $redtoy_completion_db->query("create table config(cf_use_point int,cf_point_term int) engine=$engine");
        redtoy_completion_query("insert into members(mb_id,mb_point) values('customer',0);"."");
        redtoy_completion_query("insert into items values('item1',0)");redtoy_completion_query('insert into config values(1,0)');
        $now='2026-10-23 10:00:00';$order=array('od_status'=>'배송');
        $cart=function($status,$date){return array('ct_status'=>$status,'ct_invoice_time'=>$date);};
        $cases=array(
            array('exact boundary',array($cart('배송','2026-10-16 10:00:00')),'eligible'),
            array('one second early',array($cart('배송','2026-10-16 10:00:01')),'waiting_7_days'),
            array('latest normal item',array($cart('배송','2026-10-01 00:00:00'),$cart('배송','2026-10-17 00:00:00')),'waiting_7_days'),
            array('ignore cancelled date',array($cart('배송','2026-10-01 00:00:00'),$cart('취소','')),'eligible'),
            array('ignore returned date',array($cart('배송','2026-10-01 00:00:00'),$cart('반품','2100-01-01 00:00:00')),'eligible'),
            array('ignore out of stock',array($cart('배송','2026-10-01 00:00:00'),$cart('품절','')),'eligible'),
            array('already completed item',array($cart('배송','2026-10-01 00:00:00'),$cart('완료','2026-10-15 00:00:00')),'eligible'),
            array('unshipped normal item',array($cart('배송','2026-10-01 00:00:00'),$cart('준비','')),'unshipped_item'),
            array('missing timestamp',array($cart('배송','')),'invalid_item_shipping_time'),
            array('zero timestamp',array($cart('배송','0000-00-00 00:00:00')),'invalid_item_shipping_time'),
            array('invalid date',array($cart('배송','2026-02-30 00:00:00')),'invalid_item_shipping_time'),
            array('future date',array($cart('배송','2100-01-01 00:00:00')),'future_item_shipping_time'),
            array('empty cart',array(),'no_normal_items'),
            array('all cancelled',array($cart('취소','')),'no_normal_items')
        );
        foreach($cases as $case)verify_completion(redtoy_completion_evaluate($order,$case[1],$now)['reason']===$case[2],$engine.' '.$case[0]);
        verify_completion(redtoy_completion_evaluate(array('od_status'=>'완료'),array(),$now)['reason']==='not_shipping','existing completed not targeted');
        completion_fixture_order('new',array(array('배송','2000-01-01 00:00:00'),array('취소','')));
        verify_completion(redtoy_completion_apply('new',$now,7)==='completed','old order and incorrect header date do not exclude');
        $carts=redtoy_completion_carts('new');verify_completion($carts[0]['ct_status']==='완료' && $carts[1]['ct_status']==='취소','cancelled state preserved');
        verify_completion(in_array('new',redtoy_completion_recovery_ids(100)),'completion without payment is recoverable');
        $pay=redtoy_completion_pay('new');verify_completion($pay['points']===200 && $pay['items']===1,'actual common insert_point awards only normal item');
        verify_completion(redtoy_completion_pay('new')['points']===0,'repeat cannot pay twice');
        verify_completion(!in_array('new',redtoy_completion_recovery_ids(100)),'paid order leaves recovery queue');
        verify_completion(redtoy_completion_order('new')['od_status']==='완료','order remains completed');
        verify_completion((int)redtoy_completion_query("select mb_point from members where mb_id='customer'")->fetch_assoc()['mb_point']===200,'member balance updated');
        completion_fixture_order('legacy',array(array('배송','2000-01-01 00:00:00')));
        insert_point('customer',333,'synthetic checkout','@member','legacy','상품구입');
        verify_completion(redtoy_completion_apply('legacy',$now,7)==='completed','legacy paid order still completes');
        verify_completion(redtoy_completion_pay('legacy')['points']===0,'checkout credit blocks additional award');
        verify_completion(!in_array('legacy',redtoy_completion_recovery_ids(100)),'legacy excluded from recovery');
        completion_fixture_order('delivery',array(array('배송','2000-01-01 00:00:00')));
        $row=redtoy_completion_carts('delivery')[0];insert_point('customer',200,'synthetic existing award','@delivery','customer','delivery,'.$row['ct_id']);
        redtoy_completion_apply('delivery',$now,7);verify_completion(redtoy_completion_pay('delivery')['points']===0,'delivery history wins over ct_point_use=0');
        completion_fixture_order('flag',array(array('배송','2000-01-01 00:00:00')));
        redtoy_completion_query("update cart set ct_point_use=1 where od_id='flag'");redtoy_completion_apply('flag',$now,7);
        verify_completion(redtoy_completion_pay('flag')['points']===200,'flag without real history does not lose credit');
        completion_fixture_order('guest',array(array('배송','2000-01-01 00:00:00')),'');redtoy_completion_apply('guest',$now,7);
        verify_completion(redtoy_completion_pay('guest')['reason']==='no_member','guest completes without point award');
        // Withdrawal retains a member row and can rename both member/order IDs.
        redtoy_completion_query("insert into members values('left_261023_out',0,'20261023')");
        completion_fixture_order('withdrawn',array(array('배송','2000-01-01 00:00:00')),'left_261023_out');
        verify_completion(redtoy_completion_point_plan(redtoy_completion_order('withdrawn'),redtoy_completion_carts('withdrawn'))['reason']==='withdrawn_member','retained withdrawn member excluded from preview');
        verify_completion(redtoy_completion_apply('withdrawn',$now,7)==='completed','withdrawn member order still completes');
        verify_completion(redtoy_completion_pay('withdrawn')['reason']==='withdrawn_member','withdrawn member receives no points');
        verify_completion(!in_array('withdrawn',redtoy_completion_recovery_ids(100)),'withdrawn order excluded from recovery');
        verify_completion((int)redtoy_completion_query("select mb_point from members where mb_id='left_261023_out'")->fetch_assoc()['mb_point']===0 && (int)redtoy_completion_query("select count(*) as n from points where mb_id='left_261023_out'")->fetch_assoc()['n']===0,'withdrawn member ledger and balance unchanged');
        redtoy_completion_query("insert into members(mb_id,mb_point) values('later_left',0)");
        completion_fixture_order('laterwithdrawn',array(array('배송','2000-01-01 00:00:00')),'later_left');
        redtoy_completion_apply('laterwithdrawn',$now,7);
        verify_completion(in_array('laterwithdrawn',redtoy_completion_recovery_ids(100)),'active member initially recoverable');
        redtoy_completion_query("update members set mb_leave_date='20261023' where mb_id='later_left'");
        verify_completion(redtoy_completion_pay('laterwithdrawn')['points']===0 && !in_array('laterwithdrawn',redtoy_completion_recovery_ids(100)),'withdrawal between completion and payout rechecked');
        completion_fixture_order('cardpaid',array(array('입금','2000-01-01 00:00:00')),'customer','입금');
        verify_completion(redtoy_completion_pay('cardpaid')['points']===0,'paid card/phone order cannot earn before shipping');
        redtoy_completion_query("update orders set od_status='배송' where od_id='cardpaid'");
        redtoy_completion_query("update cart set ct_status='배송' where od_id='cardpaid'");
        verify_completion(redtoy_completion_pay('cardpaid')['points']===0,'shipping alone cannot earn points');
        verify_completion(redtoy_completion_apply('cardpaid',$now,7)==='completed','paid order completes after last item shipping threshold');
        verify_completion(redtoy_completion_pay('cardpaid')['points']===200,'payment deferred credit issued at completion');
        verify_completion(redtoy_completion_pay('cardpaid')['points']===0,'deferred credit issued once');
        completion_fixture_order('disabled',array(array('배송','2000-01-01 00:00:00')));redtoy_completion_apply('disabled',$now,7);
        $config['cf_use_point']=0;verify_completion(redtoy_completion_pay('disabled')['reason']==='points_disabled','disabled points remain recoverable');$config['cf_use_point']=1;
        verify_completion(in_array('disabled',redtoy_completion_recovery_ids(100)),'reenabling can retry');redtoy_completion_pay('disabled');
        completion_fixture_order('historic',array(array('완료','2000-01-01 00:00:00')),'customer','완료');
        verify_completion(redtoy_completion_pay('historic')['reason']==='not_auto_completed' && !in_array('historic',redtoy_completion_recovery_ids(100)),'unmarked historical completions never paid');
        completion_fixture_order('changed',array(array('배송','2000-01-01 00:00:00')));
        redtoy_completion_query("update cart set ct_invoice_time='2100-01-01 00:00:00' where od_id='changed'");
        verify_completion(redtoy_completion_apply('changed',$now,7)==='changed','fresh shipping date rechecked before update');
        $other=new mysqli('localhost','root','',$dbname,0,$socket);
        $other->query("select GET_LOCK('redtoy_complete_order_changed',0)");
        verify_completion(redtoy_completion_apply('changed',$now,7)==='locked','concurrent completion blocked');$other->query("select RELEASE_LOCK('redtoy_complete_order_changed')");
        $other->query("select GET_LOCK('redtoy_save_order_point',0)");
        verify_completion(redtoy_completion_pay('new')['reason']==='locked','existing payout mutex respected');$other->close();
        if($engine==='InnoDB') {
            completion_fixture_order('failure',array(array('배송','2000-01-01 00:00:00')));redtoy_completion_apply('failure',$now,7);
            redtoy_completion_query('START TRANSACTION READ ONLY');$failed=false;
            try{redtoy_completion_pay('failure');}catch(Throwable $e){$failed=true;}
            redtoy_completion_query('ROLLBACK');verify_completion($failed,'failed insert cannot be reported as paid');
            verify_completion(in_array('failure',redtoy_completion_recovery_ids(100)),'failed insert recoverable');
            verify_completion(redtoy_completion_pay('failure')['points']===200,'retry after failure awards once');
        }
        // Execute the actual CLI against a synthetic web-root, not production settings.
        $fixture=sys_get_temp_dir().'/redtoy-completion-fixture-'.bin2hex(random_bytes(6));mkdir($fixture,0700);
        foreach(array('data','lib/Hook','adm/shop_admin') as $path)mkdir($fixture.'/'.$path,0700,true);
        foreach(array('config.php','version.php','lib/common.lib.php','lib/pbkdf2.compat.php','lib/hook.lib.php','lib/get_data.lib.php','lib/Hook/hook.class.php','lib/Hook/hook.extends.class.php','lib/redtoy_order_completion.lib.php','adm/shop_admin/order_auto_complete.php') as $file)copy($root.'/'.$file,$fixture.'/'.$file);
        $mapping=$g5;unset($mapping['connect_db']);
        file_put_contents($fixture.'/data/dbconfig.php',"<?php define('G5_MYSQL_HOST','localhost');define('G5_MYSQL_USER','root');define('G5_MYSQL_PASSWORD','');define('G5_MYSQL_DB',".var_export($dbname,true).");\n\$g5=".var_export($mapping,true).';');
        $before=redtoy_completion_query("select count(*) as n,coalesce(sum(po_point),0) as total from points")->fetch_assoc();
        $run=completion_cli($fixture,array('--dry-run'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"from=ALL\n")!==false,'actual CLI previews all order dates');
        verify_completion(!file_exists($fixture.'/data/order_auto_complete.lock') && !file_exists($fixture.'/data/order_auto_complete.log'),'dry run writes no lock or log');
        verify_completion($before===redtoy_completion_query("select count(*) as n,coalesce(sum(po_point),0) as total from points")->fetch_assoc(),'preview changes no points');
        // A newly eligible order is completed and credited in the same actual run.
        completion_fixture_order('cli',array(array('배송','2000-01-01 00:00:00')));
        $run=completion_cli($fixture,array('--limit=1'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"completed=1\n")!==false && strpos($run[1],"paid_points=200\n")!==false,'actual CLI completion and award');
        verify_completion(redtoy_completion_order('cli')['od_status']==='완료','CLI persisted completion');
        $run=completion_cli($fixture,array('--limit=1'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"paid_points=0\n")!==false,'actual CLI replay pays nothing');
        // User-sized backlog: all 981 shipping orders must be classified, not just a batch.
        foreach(array('orders','cart','points') as $table)redtoy_completion_query("delete from $table");
        redtoy_completion_query("update members set mb_point=0");
        for($i=0;$i<981;$i++) {
            $id=sprintf('bulk%04d',$i);
            if($i<700) $rows=array(array('배송','2000-01-01 00:00:00'));
            elseif($i<800) $rows=array(array('배송','2100-01-01 00:00:00'));
            elseif($i<881) $rows=array(array('배송',''));
            else $rows=array(array('배송','2000-01-01 00:00:00'),array('준비',''));
            completion_fixture_order($id,$rows,($i>=200 && $i<250)?'':'customer');
            if($i<200) insert_point('customer',100,'synthetic checkout','@member',$id,'상품구입');
        }
        $run=completion_cli($fixture,array('--dry-run'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"shipping_orders=981\n")!==false && strpos($run[1],"eligible=700\n")!==false,'981-order preview has no hidden batch truncation');
        verify_completion(strpos($run[1],"already_paid_at_checkout=200\n")!==false && strpos($run[1],"no_member=50\n")!==false && strpos($run[1],"preview_points=90000\n")!==false,'981-order payout sum excludes prior credits and guests');
        verify_completion((int)redtoy_completion_query("select count(*) as n from orders where od_status='배송'")->fetch_assoc()['n']===981,'981-order preview changes no statuses');
        $run=completion_cli($fixture,array('--limit=100'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"completed=100\n")!==false && strpos($run[1],"paid_points=0\n")!==false,'limit applies only to actual completion; legacy orders do not receive points');
        verify_completion((int)redtoy_completion_query("select count(*) as n from orders where od_status='배송'")->fetch_assoc()['n']===881,'remaining backlog preserved');
        $run=completion_cli($fixture,array('--limit=100'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"paid_points=0\n")!==false,'second legacy batch also excludes checkout credits');
        $run=completion_cli($fixture,array('--limit=100'),$socket);
        verify_completion($run[0]===0 && strpos($run[1],"paid_points=10000\n")!==false,'next batch pays only 50 eligible member orders');
        foreach(array('config.php','version.php','lib/common.lib.php','lib/pbkdf2.compat.php','lib/hook.lib.php','lib/get_data.lib.php','lib/Hook/hook.class.php','lib/Hook/hook.extends.class.php','lib/redtoy_order_completion.lib.php','adm/shop_admin/order_auto_complete.php','data/dbconfig.php','data/order_auto_complete.lock','data/order_auto_complete.log') as $file)if(is_file($fixture.'/'.$file))unlink($fixture.'/'.$file);
        foreach(array('adm/shop_admin','adm','lib/Hook','lib','data','') as $path)rmdir($fixture.'/'.$path);$fixture=null;
        foreach(array('orders','cart','members','points','items','config') as $table)$redtoy_completion_db->query("drop table $table");
    }
    echo "PASS: $checks assertions; InnoDB and MyISAM, real CLI and core point functions; no production DB/PG/API\n";
} finally {
    $redtoy_completion_db->query("drop database $dbname");
}
