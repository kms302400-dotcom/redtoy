<?php
// Repeatable synthetic benchmark; loopback-only DB, no production bootstrap/API.
$socket=getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D',$socket)) exit(2);
define('_GNUBOARD_',true); define('G5_TABLE_PREFIX','g5_');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('localhost','root','',null,0,$socket);
$name='redtoy_purchase_perf_'.bin2hex(random_bytes(6));
$db->query("CREATE DATABASE $name CHARACTER SET utf8 COLLATE utf8_general_ci");$db->select_db($name);$db->set_charset('utf8');
$g5=array('connect_db'=>$db,'g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart');
require dirname(__DIR__).'/www/lib/redtoy_purchase.lib.php';
function bench_purchase($filter,$suffix) {
    $start=microtime(true);
    $part=$filter?redtoy_purchase_filter():array('from'=>' from orders','where'=>'1=1');
    $where=$part['from']." WHERE (".$part['where'].") $suffix";
    $query='SELECT COUNT(*) AS n'.$where;
    $row=mysqli_fetch_assoc($filter?redtoy_purchase_select($query):redtoy_purchase_query($query));
    $query='SELECT od_id'.$where.' ORDER BY od_id DESC LIMIT 20';
    $result=$filter?redtoy_purchase_select($query):redtoy_purchase_query($query);
    mysqli_fetch_all($result,MYSQLI_ASSOC);
    return array('ms'=>round((microtime(true)-$start)*1000,3),'orders'=>(int)$row['n']);
}
$reports=array();
try {
    $db->query('CREATE TABLE g5_redtoy_purchase_item(it_id varchar(20) PRIMARY KEY) ENGINE=InnoDB');
    foreach (array('InnoDB','MyISAM') as $engine) {
        $db->query('DROP TABLE IF EXISTS orders,cart');
        $db->query("CREATE TABLE orders(od_id varchar(20) PRIMARY KEY,od_status varchar(16),od_time datetime,KEY status(od_status),KEY time(od_time)) ENGINE=$engine");
        $db->query("CREATE TABLE cart(ct_id int PRIMARY KEY AUTO_INCREMENT,od_id varchar(20),it_id varchar(20),KEY od_id(od_id),KEY redtoy_purchase_item_order(it_id,od_id)) ENGINE=$engine");
        for ($batch=0;$batch<100000;$batch+=1000) {
            $orders=array();$carts=array();
            for($i=$batch+1;$i<=$batch+1000;$i++) {
                $id=sprintf('%012d',$i);$status=$i%3===0?'배송':($i%3===1?'완료':'입금');$day=sprintf('%02d',$i%28+1);
                $orders[]="('$id','$status','2026-10-$day 00:00:00')";
                $item=$i%1000===0?'RARE':($i%10===0?'COMMON':'NORMAL');
                foreach(array($item,$item,'ALWAYS','STANDARD') as $code)$carts[]="('$id','$code')";
            }
            $db->query('INSERT INTO orders VALUES '.implode(',',$orders));
            $db->query('INSERT INTO cart(od_id,it_id) VALUES '.implode(',',$carts));
        }
        $db->query('ANALYZE TABLE orders,cart');
        $off=array();bench_purchase(false,'');
        for($i=0;$i<3;$i++)$off[]=bench_purchase(false,'')['ms'];sort($off);
        $reports[$engine]=array('orders'=>100000,'cart_rows'=>400000,'OFF_median_ms'=>$off[1],'profiles'=>array());
        foreach(array(array('RARE',''),array('COMMON',''),array('ALWAYS',''),array('500-CODES',''),array('RARE',"AND od_status='배송'"),array('COMMON',"AND od_status='배송' AND od_time BETWEEN '2026-10-01' AND '2026-10-16'")) as $profile) {
            list($code,$suffix)=$profile;
            $db->query('DELETE FROM g5_redtoy_purchase_item');if ($code==='500-CODES') {
                $values=array("('RARE')","('COMMON')","('ALWAYS')","('NORMAL')","('STANDARD')");
                for($j=0;$j<495;$j++)$values[]="('UNUSED$j')";
                $db->query('INSERT INTO g5_redtoy_purchase_item VALUES '.implode(',',$values));
            } else $db->query("INSERT INTO g5_redtoy_purchase_item VALUES ('$code')");
            bench_purchase(true,$suffix);$runs=array();$n=0;
            for($i=0;$i<3;$i++){$run=bench_purchase(true,$suffix);$runs[]=$run['ms'];$n=$run['orders'];}sort($runs);
            $part=redtoy_purchase_filter();
            $plan=redtoy_purchase_rows('EXPLAIN SELECT COUNT(*)'.$part['from'].' WHERE '.$part['where'].' '.$suffix);
            $cart_plan=array_values(array_filter($plan,function($row){return $row['table']==='pc';}));
            if (!$cart_plan || $cart_plan[0]['type']==='ALL' || strpos($cart_plan[0]['Extra'],'Using index')===false)
                throw new RuntimeException('Cart covering-index access was not selected');
            $order_plan=array_values(array_filter($plan,function($row){return $row['table']==='orders';}));
            if (!$order_plan || !in_array($order_plan[0]['type'],array('eq_ref','ref'),true) || (int)$order_plan[0]['rows']!==1
                || strpos($order_plan[0]['ref'],'redtoy_purchase_orders.redtoy_purchase_order_id')===false)
                throw new RuntimeException('Indexed single-order lookup was not selected');
            $reports[$engine]['profiles'][]=array('code'=>$code,'filter'=>$suffix,'matches'=>$n,'ON_median_ms'=>$runs[1],
                'order_key'=>$order_plan[0]['key'],'cart_access'=>$cart_plan[0]['type'],'cart_key'=>$cart_plan[0]['key'],'cart_extra'=>$cart_plan[0]['Extra']);
        }
        // Cancellation leaves no session-wide timeout change.
        $timeout=$db->query("SHOW SESSION VARIABLES LIKE 'max_statement_time'")->fetch_assoc();
        if ((float)$timeout['Value']!==0.0) throw new RuntimeException('Query timeout leaked into session');
    }
    echo json_encode($reports,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
    echo "PASS: covering-index EXPLAIN plans and bounded-query benchmark; 3 warm runs per profile; no production data\n";
} finally {$db->query("DROP DATABASE `$name`");$db->close();}
