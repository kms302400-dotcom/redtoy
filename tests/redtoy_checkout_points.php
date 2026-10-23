<?php
// Execute only post-approval accounting fragments, with a ledger spy. No app bootstrap, DB or PG.
$root = isset($argv[1]) ? $argv[1] : dirname(__DIR__).'/www';
$checks = 0;
$ledger = array();
function checkout_verify($ok, $label) {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
}
function checkout_fragment($source, $start, $end) {
    $a = strpos($source, $start);
    $b = $a === false ? false : strpos($source, $end, $a);
    if ($a === false || $b === false) throw new RuntimeException('Accounting boundaries changed; review test');
    return substr($source, $a, $b-$a);
}
function insert_point($member, $amount, $content='', $table='', $id='', $action='') {
    global $ledger;
    $ledger[] = array('amount'=>$amount,'table'=>$table,'id'=>$id,'action'=>$action);
    return 1;
}
function checkout_danal($code, $method) {
    global $ledger;
    $ledger = array();
    $member = array('mb_id'=>'synthetic'); $od_id='synthetic_order';
    $i_price=10000; $i_with_point_price=10000;
    $i_send_cost=3000; $i_send_cost2=1000; $i_temp_point=500; $i_send_coupon=1000;
    $tot_od_price=10000; $send_cost=3000; $send_cost2=1000; $tot_sc_cp_price=1000; $od_temp_point=500;
    $payMethod=$method; $od_price=12500;
    $data=array('P_TID'=>'synthetic_tid','P_AMT'=>12500,'P_AUTH_DT'=>'20261023100000','P_FN_NM'=>'synthetic_bank','P_UNAME'=>'synthetic','P_VACT_NUM'=>'000','P_VACT_NAME'=>'synthetic');
    eval($code);
    checkout_verify($ledger===array(), "$method payment creates no positive credit (including callback replay)");
    checkout_verify($i_price===12500 && $order_price===12500, "$method payment amount and discounts preserved");
    checkout_verify($od_receipt_point===500, "$method used-point amount preserved");
    if (in_array($method,array('card','hpp','mobile','bank'),true)) {
        checkout_verify($od_status==='입금' && $od_tno==='synthetic_tid' && $od_receipt_price===12500, "$method paid state and transaction preserved");
    } elseif ($method==='vbank') {
        checkout_verify($od_status==='주문' && $od_receipt_price===0, 'virtual bank waits for deposit');
    } else {
        checkout_verify($od_status==='주문' && $od_receipt_price===0, 'manual bank waits for deposit');
    }
}
function checkout_deduction($code, $is_member, $od_receipt_point) {
    global $ledger;
    $ledger=array(); $member=array('mb_id'=>'synthetic'); $od_id='synthetic_order';
    eval($code);
    return $ledger;
}
function checkout_card_setting($code, $enabled) {
    $od_status='입금'; $od_receipt_price=12500;
    $default=array('de_card_point'=>$enabled);
    eval($code);
    return $sql_card_point;
}
$pc=file_get_contents($root.'/shop/orderformupdate.php');
$accounting=checkout_fragment($pc, '    $i_price = $i_price + $i_send_cost', '    $od_pg = "danal";');
// These fragments must never perform external approval calls or load files.
foreach(token_get_all('<?php '.$accounting) as $token) {
    if (is_array($token) && in_array($token[0],array(T_INCLUDE,T_INCLUDE_ONCE,T_REQUIRE,T_REQUIRE_ONCE),true)) throw new RuntimeException('Unexpected include in accounting fragment');
}
foreach(array('card','hpp','mobile','bank','vbank','무통장','card','hpp') as $method) checkout_danal($accounting,$method);
foreach(array('shop/orderformupdate.php','mobile/shop/orderformupdate.php') as $file) {
    $source=file_get_contents($root.'/'.$file);
    $deduction=checkout_fragment($source,'// 회원이면서 포인트를 사용했다면','$od_memo =');
    checkout_verify(checkout_deduction($deduction,true,500)[0]['amount']===-500, "$file spent points still deducted");
    checkout_verify(checkout_deduction($deduction,true,0)===array(), "$file zero usage creates no point row");
    checkout_verify(checkout_deduction($deduction,false,500)===array(), "$file guest creates no point row");
    $setting=checkout_fragment($source,'$cart_status = $od_status;','// 회원 아이디 값 변경');
    checkout_verify(checkout_card_setting($setting,1)==='', "$file enabled non-cash points retain ct_point for completion");
    checkout_verify(strpos(checkout_card_setting($setting,0),"ct_point = '0'")!==false, "$file disabled non-cash points policy preserved");
    $calls=0;
    foreach(token_get_all($source) as $token) if(is_array($token) && $token[0]===T_STRING && strtolower($token[1])==='insert_point') $calls++;
    checkout_verify($calls===1, "$file only point mutation is existing usage deduction");
}
// Both Danal forms use shared ready scripts; both ready scripts return to the corrected handler.
foreach(array('shop/danal/orderform.1.php','mobile/shop/danal/orderform.1.php') as $file) {
    $source=file_get_contents($root.'/'.$file);
    checkout_verify(strpos($source,'/shop/danal_ready.php')!==false && strpos($source,'/shop/danal_ready_teledit.php')!==false, "$file shares Danal ready routes");
}
foreach(array('shop/danal_ready.php','shop/danal_ready_teledit.php') as $file) {
    checkout_verify(strpos(file_get_contents($root.'/'.$file),'/shop/orderformupdate.php')!==false, "$file returns to corrected handler");
}
// Danal callbacks restore session POST after common.php request extraction.
// Execute the real persistence assignments with stale globals and untrusted form values.
function checkout_shipping($code, $shipping, $extra) {
    $od_send_cost=0; $od_send_cost2=0;
    $send_cost=$shipping; $send_cost2=$extra;
    $data=array('od_send_cost'=>99999,'od_send_cost2'=>99999);
    $_POST=$data;
    eval($code);
    return array($od_send_cost,$od_send_cost2);
}
$shipping=checkout_fragment($pc,'    // The callback restores POST','    $od_email');
foreach(array(array(5000,0),array(3000,2000),array(0,0)) as $costs) {
    checkout_verify(checkout_shipping($shipping,$costs[0],$costs[1])===$costs,
        'Danal persists validated shipping, including free and remote delivery, despite stale request globals');
}
// A synthetic two-product order checks the refund policy using the actual planner.
define('_GNUBOARD_',true);
require_once $root.'/lib/redtoy_danal_cancel.lib.php';
$syntheticOrder=array('od_pg'=>'danal','od_settle_case'=>'card',
    'od_tno'=>str_repeat('1',24),'od_receipt_time'=>'2026-01-01 10:00:00',
    'od_coupon'=>0,'od_send_cost'=>5000,'od_send_cost2'=>0,'od_send_coupon'=>0,
    'od_receipt_point'=>0,'od_receipt_price'=>25900,'od_cart_price'=>20900,
    'od_cart_coupon'=>0,'od_tax_flag'=>0);
$syntheticRows=array();
foreach(array(15900,5000) as $i=>$price) $syntheticRows[]=array(
    'ct_id'=>$i+1,'it_id'=>'synthetic_'.$i,'ct_qty'=>1,'ct_price'=>$price,
    'io_price'=>0,'io_type'=>0,'io_id'=>'','ct_status'=>'입금','cp_price'=>0,'ct_notax'=>0);
$base=array('order'=>$syntheticOrder,'carts'=>$syntheticRows,'coupons'=>array());
$now='2026-01-02 10:00:00';
$partial=redtoy_cancel_plan($base,$syntheticRows,array(1),$now);
checkout_verify($partial['cash']===15900 && !$partial['full'],
    'first product refund excludes shipping');
$after=$syntheticRows; $after[0]['ct_status']='취소';
$final=redtoy_cancel_plan($base,$after,array(2),$now);
checkout_verify($final['cash']===10000 && $final['full'],
    'last product refund includes remaining product and shipping');
checkout_verify($partial['cash']+$final['cash']===25900,
    'sequential refunds equal original paid total');
$full=redtoy_cancel_plan($base,$syntheticRows,array(1,2),$now);
checkout_verify($full['cash']===25900,'initial full refund includes shipping');
$broken=$base; $broken['order']['od_send_cost']=0;
$blocked=false;
try { redtoy_cancel_plan($broken,$syntheticRows,array(1),$now); }
catch (RuntimeException $e) { $blocked=$e->getMessage()==='amount_reconciliation_required'; }
checkout_verify($blocked,'missing shipping remains blocked; no inferred refund amounts');
echo "PASS: $checks checkout assertions; real accounting fragments, ledger spy; no DB/PG/API\n";
