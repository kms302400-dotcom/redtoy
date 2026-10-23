<?php
if (!defined('_GNUBOARD_')) exit;

// Integer largest-remainder allocation. Stable keys make successive cancellations add up exactly.
function redtoy_cancel_allocate($amount, $weights)
{
    if (!is_int($amount) || $amount < 0 || $amount > 2000000000) throw new RuntimeException('invalid_amount');
    $sum=0;
    foreach ($weights as $w) { if (!is_int($w) || $w<0 || $w>2000000000) throw new RuntimeException('invalid_weight'); $sum+=$w; }
    if ($sum>2000000000 || $amount>$sum) throw new RuntimeException('invalid_allocation');
    $out=array_fill_keys(array_keys($weights),0); $remainders=array(); $used=0;
    if (!$sum) return $out;
    foreach ($weights as $k=>$w) { $out[$k]=intdiv($amount*$w,$sum); $used+=$out[$k]; $remainders[$k]=($amount*$w)%$sum; }
    arsort($remainders,SORT_NUMERIC);
    foreach ($remainders as $k=>$v) { if ($used===$amount) break; $out[$k]++; $used++; }
    return $out;
}
function redtoy_cancel_money($value)
{
    if (!preg_match('/^[0-9]+$/D',(string)$value) || (float)$value>2000000000) throw new RuntimeException('invalid_money');
    return (int)$value;
}
function redtoy_cancel_table($key)
{
    global $g5;
    $name=$key==='journal' ? G5_TABLE_PREFIX.'redtoy_cancel' : (isset($g5[$key])?$g5[$key]:'');
    if (!preg_match('/^[a-zA-Z0-9_]+$/D',$name)) throw new RuntimeException('invalid_table');
    return '`'.$name.'`';
}
function redtoy_cancel_query($sql)
{
    global $g5;
    try { $r=mysqli_query($g5['connect_db'],$sql); } catch (Throwable $e) { throw new RuntimeException('database_failure'); }
    if ($r===false) throw new RuntimeException('database_failure');
    return $r;
}
function redtoy_cancel_escape($v) { global $g5; return mysqli_real_escape_string($g5['connect_db'],(string)$v); }
function redtoy_cancel_rows($sql) { return mysqli_fetch_all(redtoy_cancel_query($sql),MYSQLI_ASSOC); }
function redtoy_cancel_one($sql) { return mysqli_fetch_assoc(redtoy_cancel_query($sql)); }
function redtoy_cancel_order($id)
{
    $id=redtoy_cancel_escape($id);
    return redtoy_cancel_one('select od_id,mb_id,od_pg,od_settle_case,od_tno,od_time,od_receipt_time,od_status,od_cart_price,od_receipt_price,od_receipt_point,od_refund_price,od_cancel_price,od_misu,od_cart_coupon,od_coupon,od_send_coupon,od_send_cost,od_send_cost2,od_tax_flag,od_tax_mny,od_vat_mny,od_free_mny from '.redtoy_cancel_table('g5_shop_order_table')." where od_id='$id'");
}
function redtoy_cancel_carts($id)
{
    $id=redtoy_cancel_escape($id);
    return redtoy_cancel_rows('select ct_id,it_id,ct_qty,ct_price,io_price,io_type,io_id,ct_status,ct_stock_use,ct_point_use,ct_notax,cp_price from '.redtoy_cancel_table('g5_shop_cart_table')." where od_id='$id' order by ct_id");
}
function redtoy_cancel_method($order)
{
    if ($order['od_pg']!=='danal') throw new RuntimeException('unsupported_pg');
    if (in_array($order['od_settle_case'],array('card','신용카드'),true)) return 'card';
    if (in_array($order['od_settle_case'],array('hpp','mobile','휴대폰'),true)) return 'phone';
    throw new RuntimeException('unsupported_method');
}
function redtoy_cancel_locks($id)
{
    $held=array();
    foreach (array('redtoy_complete_order_'.$id,'redtoy_save_order_point') as $name) {
        $n=redtoy_cancel_escape($name);
        $row=redtoy_cancel_one("select GET_LOCK('$n',0) as ok");
        if (empty($row['ok'])) { redtoy_cancel_unlock($held); throw new RuntimeException('busy'); }
        $held[]=$name;
    }
    return $held;
}
function redtoy_cancel_unlock($held)
{
    foreach (array_reverse($held) as $name) { $n=redtoy_cancel_escape($name); redtoy_cancel_query("select RELEASE_LOCK('$n')"); }
}
function redtoy_cancel_pending($id)
{
    $id=redtoy_cancel_escape($id);
    return redtoy_cancel_one('select op_id,state from '.redtoy_cancel_table('journal')." where od_id='$id' and state<>'done' order by created_at limit 1");
}
// Also used by old mutation routes: never let a second cancellation run while outcome is uncertain.
function redtoy_cancel_guard_order($id)
{
    $held=redtoy_cancel_locks($id);
    try { if (redtoy_cancel_pending($id)) throw new RuntimeException('reconciliation_required'); }
    catch (Throwable $e) { redtoy_cancel_unlock($held); throw $e; }
    register_shutdown_function(function () use ($held) { redtoy_cancel_unlock($held); });
}
function redtoy_cancel_baseline($order,$carts)
{
    $id=redtoy_cancel_escape($order['od_id']);
    $old=redtoy_cancel_one('select plan_json from '.redtoy_cancel_table('journal')." where od_id='$id' and state='done' order by created_at,op_id limit 1");
    if ($old) {
        $saved=json_decode($old['plan_json'],true);
        if (!isset($saved['base'])) throw new RuntimeException('invalid_journal');
        return $saved['base'];
    }
    if ((int)$order['od_refund_price']!==0 || (int)$order['od_cancel_price']!==0 || (int)$order['od_misu']!==0) throw new RuntimeException('prior_adjustment');
    if (!$carts) throw new RuntimeException('empty_order');
    foreach ($carts as $c) if (!in_array($c['ct_status'],array('주문','입금','준비','배송','완료'),true)) throw new RuntimeException('prior_adjustment');
    // No names, addresses, contact details or free-text customer content in the journal.
    $base=array('order'=>$order,'carts'=>$carts,'coupons'=>array());
    if ($order['mb_id']!=='') {
        $mb=redtoy_cancel_escape($order['mb_id']);
        $logs=redtoy_cancel_rows('select l.cl_id,l.cp_id,l.cp_price,c.cp_method,c.cp_target from '.redtoy_cancel_table('g5_shop_coupon_log_table').' l left join '.redtoy_cancel_table('g5_shop_coupon_table')." c on c.cp_id=l.cp_id where l.od_id='$id' and l.mb_id='$mb' order by l.cl_id");
        foreach ($logs as $log) {
            if ($log['cp_method']===null) throw new RuntimeException('coupon_mapping_required');
            $log['it_id']='';
            if (in_array((string)$log['cp_method'],array('0','1'),true)) {
                $candidates=array();
                foreach ($carts as $c) {
                    if ((int)$c['cp_price']!==(int)$log['cp_price'] || (int)$c['cp_price']<=0) continue;
                    $item=redtoy_cancel_escape($c['it_id']);
                    if ((string)$log['cp_method']==='0' && $log['cp_target']===$c['it_id']) $candidates[$c['it_id']]=true;
                    if ((string)$log['cp_method']==='1') {
                        $cat=redtoy_cancel_one('select ca_id,ca_id2,ca_id3 from '.redtoy_cancel_table('g5_shop_item_table')." where it_id='$item'");
                        if ($cat && in_array($log['cp_target'],array_values($cat),true)) $candidates[$c['it_id']]=true;
                    }
                }
                if (count($candidates)!==1) throw new RuntimeException('coupon_mapping_required');
                $log['it_id']=array_keys($candidates)[0];
            } elseif (!in_array((string)$log['cp_method'],array('2','3'),true)) throw new RuntimeException('coupon_mapping_required');
            $base['coupons'][]=$log;
        }
    }
    return $base;
}
// Immutable original allocations avoid rounding drift across A, then B, then final shipping refund.
function redtoy_cancel_plan($base,$current,$selected,$now)
{
    $order=$base['order']; $method=redtoy_cancel_method($order);
    if (!$order['od_tno'] || !$selected || count($selected)!==count(array_unique($selected))) throw new RuntimeException('invalid_selection');
    $selected=array_map('strval',$selected); $live=array();
    foreach ($current as $c) $live[(string)$c['ct_id']]=$c;
    $weights=array(); $gross=array(); $baseRows=array(); $chosen=array(); $remaining=array();
    foreach ($base['carts'] as $c) {
        $key=(string)$c['ct_id']; $baseRows[$key]=$c;
        if (!isset($live[$key])) throw new RuntimeException('order_changed');
        foreach (array('it_id','ct_qty','ct_price','io_price','io_type','io_id','cp_price','ct_notax') as $field) if ((string)$c[$field] !== (string)$live[$key][$field]) throw new RuntimeException('order_changed');
        $qty=redtoy_cancel_money($c['ct_qty']);
        if ($qty<1) throw new RuntimeException('invalid_quantity');
        $price=redtoy_cancel_money($c['ct_price']); $option=(int)$c['io_price'];
        $gross[$key]=redtoy_cancel_money((string)(($c['io_type']==1?$option:$price+$option)*$qty));
        $weights[$key]=$gross[$key]-redtoy_cancel_money($c['cp_price']);
        if ($weights[$key]<0) throw new RuntimeException('invalid_discount');
        $active=in_array($live[$key]['ct_status'],array('주문','입금','준비','배송','완료'),true);
        if (in_array($key,$selected,true)) { if (!$active) throw new RuntimeException('already_cancelled'); $chosen[$key]=$live[$key]; }
        elseif ($active) $remaining[$key]=$live[$key];
        elseif ($live[$key]['ct_status']!=='취소') throw new RuntimeException('order_changed');
    }
    if (count($current)!==count($baseRows) || count($chosen)!==count($selected)) throw new RuntimeException('invalid_selection');
    // Options belonging to the same product are cancelled together. Quantity edits are not accepted.
    foreach ($chosen as $c) foreach ($remaining as $r) if ($c['it_id']===$r['it_id']) throw new RuntimeException('select_whole_product');
    $full=!$remaining;
    if ($method==='phone' && !$full) throw new RuntimeException('phone_partial_unsupported');
    if (!preg_match($method==='card'?'/^[0-9]{24}$/D':'/^[0-9]{18}$/D',$order['od_tno'])) throw new RuntimeException('invalid_transaction');
    $paidAt=strtotime($order['od_receipt_time']);
    if (!$paidAt || $paidAt>strtotime($now)) throw new RuntimeException('invalid_payment_time');
    if ($method==='card' && strtotime($now)-$paidAt>365*86400) throw new RuntimeException('card_cancel_period');
    if ($method==='phone' && substr($order['od_receipt_time'],0,7)!==substr($now,0,7)) throw new RuntimeException('phone_cancel_period');
    $discount=redtoy_cancel_allocate(redtoy_cancel_money($order['od_coupon']),$weights);
    foreach ($weights as $k=>$v) $weights[$k]-=$discount[$k];
    $goods=$weights;
    $weights['shipping']=redtoy_cancel_money($order['od_send_cost'])+redtoy_cancel_money($order['od_send_cost2'])-redtoy_cancel_money($order['od_send_coupon']);
    if ($weights['shipping']<0) throw new RuntimeException('invalid_discount');
    $used=redtoy_cancel_allocate(redtoy_cancel_money($order['od_receipt_point']),$weights);
    $cash=0; $points=0; $cancelGross=0; $couponReturn=array();
    foreach ($chosen as $k=>$c) { $cash+=$weights[$k]-$used[$k]; $points+=$used[$k]; }
    if ($full) { $cash+=$weights['shipping']-$used['shipping']; $points+=$used['shipping']; }
    if (array_sum($weights)-(int)$order['od_receipt_point']!==(int)$order['od_receipt_price'] || array_sum($gross)!==(int)$order['od_cart_price'] || array_sum(array_column($base['carts'],'cp_price'))!==(int)$order['od_cart_coupon']) throw new RuntimeException('amount_reconciliation_required');
    foreach ($gross as $k=>$v) if (!isset($remaining[$k])) $cancelGross+=$v;
    foreach ($base['coupons'] as $log) {
        $restore=$full;
        if ($log['it_id']!=='') { $restore=true; foreach ($remaining as $r) if ($r['it_id']===$log['it_id']) $restore=false; }
        if ($restore) $couponReturn[]=(int)$log['cl_id'];
    }
    $remainingCoupons=0; $remainingOrderCoupon=0; $remainingValue=0;
    foreach ($remaining as $k=>$r) { $remainingCoupons+=(int)$r['cp_price']; $remainingOrderCoupon+=$discount[$k]; $remainingValue+=$weights[$k]; }
    $taxable=0; $free=0;
    foreach ($remaining as $k=>$r) {
        $net=$weights[$k]-$used[$k];
        if (!empty($order['od_tax_flag']) && !empty($r['ct_notax'])) $free+=$net; else $taxable+=$net;
    }
    if (!$full) $taxable+=$weights['shipping']-$used['shipping'];
    return array('base'=>$base,'ids'=>array_map('intval',array_keys($chosen)),'full'=>$full,'method'=>$method,'cash'=>$cash,'return_points'=>$points,'recover_points'=>0,'coupon_ids'=>$couponReturn,'cancel_gross'=>$cancelGross,'remaining_cart_coupon'=>$remainingCoupons,'remaining_order_coupon'=>$remainingOrderCoupon,'remaining_value'=>$remainingValue,'goods_weights'=>$goods,'used_allocations'=>$used,'tax_mny'=>(int)round($taxable/1.1),'vat_mny'=>$taxable-(int)round($taxable/1.1),'free_mny'=>$free);
}
function redtoy_cancel_quote($id,$selected,$now)
{
    $order=redtoy_cancel_order($id);
    if (!$order) throw new RuntimeException('order_missing');
    if (redtoy_cancel_pending($id)) throw new RuntimeException('reconciliation_required');
    $carts=redtoy_cancel_carts($id); $base=redtoy_cancel_baseline($order,$carts);
    $plan=redtoy_cancel_plan($base,$carts,$selected,$now);
    if ($order['mb_id']!==$base['order']['mb_id'] || $order['od_tno']!==$base['order']['od_tno'] || $order['od_pg']!=='danal' || $order['od_settle_case']!==$base['order']['od_settle_case']) throw new RuntimeException('order_changed');
    $oid=redtoy_cancel_escape($id);
    $done=redtoy_cancel_one('select coalesce(sum(cash_amount),0) cash,coalesce(sum(return_points),0) points from '.redtoy_cancel_table('journal')." where od_id='$oid' and state='done'");
    if ((int)$order['od_refund_price']!==(int)$done['cash'] || (int)$order['od_receipt_point']!==(int)$base['order']['od_receipt_point']-(int)$done['points'] || (int)$order['od_misu']!==0) throw new RuntimeException('amount_reconciliation_required');
    $cancelled=array(); $restoredCoupons=array();
    foreach (redtoy_cancel_rows('select plan_json from '.redtoy_cancel_table('journal')." where od_id='$oid' and state='done'") as $row) {
        $prior=json_decode($row['plan_json'],true);
        foreach ($prior['ids'] as $ct) $cancelled[(int)$ct]=true;
        foreach ($prior['coupon_ids'] as $cl) $restoredCoupons[(int)$cl]=true;
    }
    $liveCoupons=array_column(redtoy_cancel_rows('select cl_id from '.redtoy_cancel_table('g5_shop_coupon_log_table')." where od_id='$oid'"),'cl_id');
    $expectedCoupons=array();
    foreach ($base['coupons'] as $cl) if (!isset($restoredCoupons[(int)$cl['cl_id']])) $expectedCoupons[]=(int)$cl['cl_id'];
    $actualCoupons=array_map('intval',$liveCoupons); sort($actualCoupons); sort($expectedCoupons);
    if ($actualCoupons!==$expectedCoupons) throw new RuntimeException('coupon_mapping_required');
    $plan['coupon_ids']=array_values(array_intersect($plan['coupon_ids'],$actualCoupons));
    foreach ($carts as $ct) if (($ct['ct_status']==='취소')!==isset($cancelled[(int)$ct['ct_id']])) throw new RuntimeException('order_changed');
    foreach (array('od_cart_price','od_receipt_price','od_tax_flag','od_time','od_receipt_time') as $field) if ((string)$order[$field] !== (string)$base['order'][$field]) throw new RuntimeException('order_changed');
    $activeGross=0; $activeCoupon=0;
    foreach ($carts as $ct) if ($ct['ct_status']!=='취소') {
        $activeGross+=((int)$ct['io_type']===1?(int)$ct['io_price']:(int)$ct['ct_price']+(int)$ct['io_price'])*(int)$ct['ct_qty'];
        $activeCoupon+=(int)$ct['cp_price'];
    }
    $activeNet=$activeGross-$activeCoupon-(int)$order['od_coupon']+(int)$order['od_send_cost']+(int)$order['od_send_cost2']-(int)$order['od_send_coupon'];
    if ($activeCoupon!==(int)$order['od_cart_coupon'] || $activeNet!==(int)$order['od_receipt_price']-(int)$order['od_refund_price']+(int)$order['od_receipt_point']) throw new RuntimeException('amount_reconciliation_required');
    if ($plan['cash']>(int)$order['od_receipt_price']-(int)$order['od_refund_price']) throw new RuntimeException('refund_exceeds_balance');
    $plan['cancel_type']=$plan['full'] && (int)$done['cash']===0 ? 'C' : 'P';
    $plan['expected_balance']=(int)$order['od_receipt_price']-(int)$order['od_refund_price']-$plan['cash'];
    if ((int)$base['order']['od_receipt_point']>0) {
        $usageMb=redtoy_cancel_escape($base['order']['mb_id']);
        $content=redtoy_cancel_escape('주문번호 '.$id.' 결제');
        $usage=redtoy_cancel_one('select coalesce(sum(-po_point),0) amount from '.redtoy_cancel_table('point_table')." where mb_id='$usageMb' and po_point<0 and po_content='$content'");
        if ((int)$usage['amount']!==(int)$base['order']['od_receipt_point']) throw new RuntimeException('point_usage_reconciliation_required');
    }
    $plan['recover_lots']=redtoy_cancel_recovery_lots($plan);
    $plan['recover_points']=array_sum($plan['recover_lots']);
    $mb=redtoy_cancel_escape($order['mb_id']);
    $member=$mb===''?null:redtoy_cancel_one('select mb_id,mb_leave_date from '.redtoy_cancel_table('member_table')." where mb_id='$mb'");
    if ($plan['return_points'] && (!$member || trim((string)$member['mb_leave_date'])!=='')) throw new RuntimeException('point_refund_account_required');
    if ($plan['recover_points'] && !$member) throw new RuntimeException('point_recovery_account_required');
    if (($plan['return_points'] || $plan['recover_points']) && strlen($id)>20) throw new RuntimeException('point_relation_length');
    // Include live statuses/stock flags and current order totals in the confirmation fingerprint.
    $plan['apply_fingerprint']=hash('sha256',json_encode(array($order,$carts),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    $plan['fingerprint']=hash('sha256',json_encode(array($order,$carts,$plan),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    return $plan;
}
function redtoy_cancel_recovery_lots($plan)
{
    $order=$plan['base']['order']; $mb=redtoy_cancel_escape($order['mb_id']); $id=redtoy_cancel_escape($order['od_id']);
    if ($mb==='') return array();
    $rows=redtoy_cancel_rows('select po_id,po_point,po_use_point,po_expired,po_expire_date,po_rel_table,po_rel_id,po_rel_action from '.redtoy_cancel_table('point_table')." where mb_id='$mb' and po_point>0 and ((po_rel_table='@member' and po_rel_id='$id' and po_rel_action='상품구입') or po_rel_table='@delivery') order by po_id");
    $lots=array(); $ids=array_map('strval',$plan['ids']);
    foreach ($rows as $po) {
        $relevant=$po['po_rel_table']==='@member';
        if (!$relevant) foreach ($ids as $ct) if ($po['po_rel_action']===$order['od_id'].','.$ct) $relevant=true;
        if (!$relevant) continue;
        $expired=(int)$po['po_expired']===1 || $po['po_expire_date']<G5_TIME_YMD;
        // Old expiry records do not distinguish actual spending from expiry's FIFO bookkeeping.
        if ($expired && (int)$po['po_use_point']>0) throw new RuntimeException('point_expiry_reconciliation_required');
        $amount=$expired?0:(int)$po['po_point'];
        $recover=0;
        if ($po['po_rel_table']==='@member') {
            $shares=redtoy_cancel_allocate($amount,$plan['goods_weights']);
            foreach ($ids as $ct) $recover+=$shares[$ct];
        } else foreach ($ids as $ct) if ($po['po_rel_action']===$order['od_id'].','.$ct) $recover=$amount;
        if ($recover) $lots[(int)$po['po_id']]=$recover;
    }
    return $lots;
}
function redtoy_cancel_journal($op,$state,$code='',$tid='')
{
    if (!preg_match('/^[a-f0-9]{32}$/D',$op) || !in_array($state,array('requesting','pg_ok','applying','done','review'),true)) throw new RuntimeException('invalid_journal_state');
    $code=redtoy_cancel_escape(preg_replace('/[^a-zA-Z0-9_]/','',substr($code,0,40))); $tid=redtoy_cancel_escape($tid);
    redtoy_cancel_query('update '.redtoy_cancel_table('journal')." set state='$state',result_code='$code',pg_cancel_tid=if('$tid'='',pg_cancel_tid,'$tid'),updated_at=now() where op_id='$op'");
}
// The callable is supplied only by isolated tests. HTTP requests never select or supply a transport.
function redtoy_cancel_execute($id,$ids,$expected,$op,$actor,$transport=null)
{
    if (!preg_match('/^[a-f0-9]{32}$/D',$op)) throw new RuntimeException('invalid_operation');
    $locks=redtoy_cancel_locks($id); $inserted=false; $transaction=false;
    try {
        $existing=redtoy_cancel_one('select od_id,actor_id,state from '.redtoy_cancel_table('journal')." where op_id='$op'");
        if ($existing) { if ($existing['od_id']!==$id || $existing['actor_id']!==$actor) throw new RuntimeException('invalid_operation'); if ($existing['state']==='done') return 'done'; throw new RuntimeException('reconciliation_required'); }
        $plan=redtoy_cancel_quote($id,$ids,G5_TIME_YMDHIS);
        if (!hash_equals($plan['fingerprint'],$expected)) throw new RuntimeException('quote_changed');
        if ($transport===null && $plan['cash']>0) { require_once __DIR__.'/redtoy_danal_cancel_pg.lib.php'; redtoy_cancel_pg_ready($plan); }
        $transactional=redtoy_cancel_transactional();
        $json=redtoy_cancel_escape(json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        $oid=redtoy_cancel_escape($id); $actor=redtoy_cancel_escape($actor);
        redtoy_cancel_query('insert into '.redtoy_cancel_table('journal')." (op_id,od_id,actor_id,state,cash_amount,return_points,recover_points,plan_json,created_at,updated_at) values('$op','$oid','$actor','requesting',".$plan['cash'].','.$plan['return_points'].','.$plan['recover_points'].",'$json',now(),now())");
        $inserted=true;
        $result=$plan['cash']===0?array('ok'=>true,'code'=>'NO_CASH','tid'=>''):($transport===null?redtoy_cancel_pg($plan):call_user_func($transport,$plan));
        if (!isset($result['ok']) || $result['ok']!==true) { redtoy_cancel_journal($op,'review',isset($result['code'])?$result['code']:'unknown'); throw new RuntimeException('pg_review_required'); }
        redtoy_cancel_journal($op,'pg_ok',$result['code'],$result['tid']);
        redtoy_cancel_journal($op,'applying',$result['code'],$result['tid']);
        if ($transactional) { redtoy_cancel_query('START TRANSACTION'); $transaction=true; redtoy_cancel_query('select od_id from '.redtoy_cancel_table('g5_shop_order_table')." where od_id='$oid' for update"); redtoy_cancel_query('select ct_id from '.redtoy_cancel_table('g5_shop_cart_table')." where od_id='$oid' for update"); }
        $fresh=hash('sha256',json_encode(array(redtoy_cancel_order($id),redtoy_cancel_carts($id)),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        if (!hash_equals($plan['apply_fingerprint'],$fresh)) throw new RuntimeException('order_changed_after_pg');
        redtoy_cancel_apply($plan,$op);
        redtoy_cancel_journal($op,'done',$result['code'],$result['tid']);
        if ($transaction) { redtoy_cancel_query('COMMIT'); $transaction=false; }
        return 'done';
    } catch (Throwable $e) {
        if ($transaction) { try { redtoy_cancel_query('ROLLBACK'); } catch (Throwable $ignored) {} }
        if ($inserted) { try { redtoy_cancel_journal($op,'review',in_array($e->getMessage(),array('pg_review_required','database_failure','order_changed_after_pg','point_write_failed','point_balance_failed','stock_write_failed','order_write_failed','cart_write_failed'),true)?$e->getMessage():'reconciliation_required'); } catch (Throwable $ignored) {} }
        throw $e;
    } finally { redtoy_cancel_unlock($locks); }
}
function redtoy_cancel_point($mb,$amount,$op,$action,$order,$lots=array())
{
    global $config;
    if (!$amount) return;
    if (strlen($order)>20) throw new RuntimeException('point_relation_length');
    $relation=$op.':'.$action;
    $old=$config['cf_use_point']; $config['cf_use_point']=1;
    try { $r=$amount<0?redtoy_cancel_debit($mb,-$amount,$op,$order,$lots):insert_point($mb,$amount,'주문번호 '.$order.' 취소 '.($amount<0?'적립 회수':'사용 포인트 반환'),'@redtoy_cancel',$order,$relation); }
    finally { $config['cf_use_point']=$old; }
    if ($r!==1 && $r!==-1) throw new RuntimeException('point_write_failed');
    $memberId=$mb; $mb=redtoy_cancel_escape($mb); $oid=redtoy_cancel_escape($order);
    $saved=redtoy_cancel_one('select po_point from '.redtoy_cancel_table('point_table')." where mb_id='$mb' and po_rel_table='@redtoy_cancel' and po_rel_id='$oid' and po_rel_action='$relation'");
    if (!$saved || (int)$saved['po_point']!==$amount) throw new RuntimeException('point_write_failed');
    $wallet=redtoy_cancel_one('select mb_point from '.redtoy_cancel_table('member_table')." where mb_id='$mb'");
    if (!$wallet || (int)$wallet['mb_point']!==(int)get_point_sum($memberId)) throw new RuntimeException('point_balance_failed');
}
function redtoy_cancel_apply($plan,$op)
{
    $order=redtoy_cancel_order($plan['base']['order']['od_id']); $oid=redtoy_cancel_escape($order['od_id']);
    $cart=redtoy_cancel_table('g5_shop_cart_table');
    foreach ($plan['ids'] as $id) {
        $c=redtoy_cancel_one("select ct_id,it_id,io_id,io_type,ct_stock_use,ct_qty,ct_status from $cart where od_id='$oid' and ct_id=".(int)$id);
        if (!$c || !in_array($c['ct_status'],array('주문','입금','준비','배송','완료'),true)) throw new RuntimeException('order_changed');
        $item=redtoy_cancel_escape($c['it_id']); $option=redtoy_cancel_escape($c['io_id']);
        if ((int)$c['ct_stock_use']) {
            if ($option!=='') {
                $table=redtoy_cancel_table('g5_shop_item_option_table');
                redtoy_cancel_query("update $table i join $cart c on c.it_id=i.it_id and c.io_id=i.io_id and c.io_type=i.io_type set i.io_stock_qty=i.io_stock_qty+c.ct_qty,c.ct_stock_use=0 where c.od_id='$oid' and c.ct_id=".(int)$id.' and c.ct_stock_use=1');
            } else {
                $table=redtoy_cancel_table('g5_shop_item_table');
                redtoy_cancel_query("update $table i join $cart c on c.it_id=i.it_id set i.it_stock_qty=i.it_stock_qty+c.ct_qty,c.ct_stock_use=0 where c.od_id='$oid' and c.ct_id=".(int)$id.' and c.ct_stock_use=1');
            }
            $fresh=redtoy_cancel_one("select ct_stock_use from $cart where ct_id=".(int)$id);
            if ((int)$fresh['ct_stock_use']!==0) throw new RuntimeException('stock_write_failed');
        }
        $history=redtoy_cancel_escape("\n취소|redtoy-pg-cancel|".$op.'|'.G5_TIME_YMDHIS);
        redtoy_cancel_query("update $cart set ct_status='취소',ct_point_use=0,ct_history=concat(coalesce(ct_history,''),'$history') where od_id='$oid' and ct_id=".(int)$id);
    }
    // Keep award history; record a compensating debit only for actual paid awards.
    redtoy_cancel_point($order['mb_id'],-$plan['recover_points'],$op,'recover',$order['od_id'],$plan['recover_lots']);
    redtoy_cancel_point($order['mb_id'],$plan['return_points'],$op,'return',$order['od_id']);
    if ($plan['coupon_ids']) {
        $logs=implode(',',array_map('intval',$plan['coupon_ids'])); $mb=redtoy_cancel_escape($order['mb_id']);
        redtoy_cancel_query('delete from '.redtoy_cancel_table('g5_shop_coupon_log_table')." where cl_id in ($logs) and od_id='$oid' and mb_id='$mb'");
    }
    $o=redtoy_cancel_table('g5_shop_order_table');
    $full=$plan['full']; $shipping=$full?0:(int)$order['od_send_cost']; $shipping2=$full?0:(int)$order['od_send_cost2']; $sendCoupon=$full?0:(int)$order['od_send_coupon'];
    $refund=(int)$order['od_refund_price']+$plan['cash']; $used=(int)$order['od_receipt_point']-$plan['return_points'];
    $history=redtoy_cancel_escape("\n[redtoy-pg-cancel:$op] ".G5_TIME_YMDHIS);
    $status=$full?'취소':$order['od_status']; $status=redtoy_cancel_escape($status);
    // Original receipt price is retained; cumulative refunds and remaining used points reconcile to od_misu=0.
    redtoy_cancel_query("update $o set od_status='$status',od_refund_price=$refund,od_receipt_point=$used,od_cancel_price=".$plan['cancel_gross'].",od_cart_coupon=".$plan['remaining_cart_coupon'].",od_coupon=".$plan['remaining_order_coupon'].",od_send_cost=$shipping,od_send_cost2=$shipping2,od_send_coupon=$sendCoupon,od_misu=0,od_tax_mny=".$plan['tax_mny'].",od_vat_mny=".$plan['vat_mny'].",od_free_mny=".$plan['free_mny'].",od_mod_history=concat(coalesce(od_mod_history,''),'$history') where od_id='$oid'");
    $saved=redtoy_cancel_order($order['od_id']);
    foreach (array('od_refund_price'=>$refund,'od_receipt_point'=>$used,'od_cancel_price'=>$plan['cancel_gross'],'od_cart_coupon'=>$plan['remaining_cart_coupon'],'od_coupon'=>$plan['remaining_order_coupon'],'od_misu'=>0,'od_tax_mny'=>$plan['tax_mny'],'od_vat_mny'=>$plan['vat_mny'],'od_free_mny'=>$plan['free_mny']) as $field=>$value) if ((int)$saved[$field]!==$value) throw new RuntimeException('order_write_failed');
    foreach ($plan['ids'] as $ct) {
        $savedCart=redtoy_cancel_one("select ct_status,ct_stock_use,ct_point_use from $cart where od_id='$oid' and ct_id=".(int)$ct);
        if (!$savedCart || $savedCart['ct_status']!=='취소' || (int)$savedCart['ct_stock_use']!==0 || (int)$savedCart['ct_point_use']!==0) throw new RuntimeException('cart_write_failed');
    }
    $items=array_unique(array_column($plan['base']['carts'],'it_id'));
    foreach ($items as $item) {
        $it=redtoy_cancel_escape($item); $total=redtoy_cancel_one("select coalesce(sum(ct_qty),0) qty from $cart where it_id='$it' and ct_status='완료'");
        redtoy_cancel_query('update '.redtoy_cancel_table('g5_shop_item_table').' set it_sum_qty='.(int)$total['qty']." where it_id='$it'");
    }
}

// Older deployments have no journal yet. A failed lookup must never permit an award.
function redtoy_cancel_hold_table($db)
{
    if (!defined('G5_TABLE_PREFIX') || !preg_match('/^[a-zA-Z0-9_]+$/D',G5_TABLE_PREFIX)) throw new RuntimeException('invalid_table');
    $name=G5_TABLE_PREFIX.'redtoy_cancel';
    $result=mysqli_query($db,"select count(*) n from information_schema.tables where table_schema=database() and table_name='".mysqli_real_escape_string($db,$name)."'");
    if (!$result) throw new RuntimeException('database_failure');
    $row=mysqli_fetch_assoc($result);
    return (int)$row['n'] ? '`'.$name.'`' : false;
}
function redtoy_cancel_hold($id,$db)
{
    $table=redtoy_cancel_hold_table($db);
    if (!$table) return false;
    $id=mysqli_real_escape_string($db,$id);
    $result=mysqli_query($db,"select op_id from $table where od_id='$id' and state<>'done' limit 1");
    if (!$result) throw new RuntimeException('database_failure');
    return (bool)mysqli_fetch_assoc($result);
}

function redtoy_cancel_transactional()
{
    $tables=array();
    foreach (array('g5_shop_order_table','g5_shop_cart_table','member_table','point_table','g5_shop_item_table','g5_shop_item_option_table','g5_shop_coupon_log_table','journal') as $key) $tables[]=trim(redtoy_cancel_table($key),'`');
    $names=array(); foreach ($tables as $name) $names[]="'".redtoy_cancel_escape($name)."'";
    $rows=redtoy_cancel_rows('select table_name,engine from information_schema.tables where table_schema=database() and table_name in ('.implode(',',$names).')');
    if (count($rows)!==count($tables)) throw new RuntimeException('database_setup_required');
    foreach ($rows as $row) if (strtoupper($row['engine'])!=='INNODB') return false;
    return true;
}

// Consume the cancelled award first. Ordinary FIFO spending would leave that award to expire
// later and could deduct the same cancelled credit twice. Spent awards use other funds or debt.
function redtoy_cancel_debit($member,$amount,$op,$order,$lots)
{
    global $config,$g5;
    $balance=(int)get_point_sum($member); $mb=redtoy_cancel_escape($member); $oid=redtoy_cancel_escape($order);
    $table=redtoy_cancel_table('point_table'); $relation=$op.':recover';
    $existing=redtoy_cancel_one("select po_point from $table where mb_id='$mb' and po_rel_table='@redtoy_cancel' and po_rel_id='$oid' and po_rel_action='$relation'");
    if ($existing) return -1;
    if (array_sum($lots)!==$amount) throw new RuntimeException('point_write_failed');
    $content=redtoy_cancel_escape('주문번호 '.$order.' 취소 적립 회수'); $next=$balance-$amount;
    redtoy_cancel_query("insert into $table (mb_id,po_datetime,po_content,po_point,po_use_point,po_mb_point,po_expired,po_expire_date,po_rel_table,po_rel_id,po_rel_action) values('$mb','".G5_TIME_YMDHIS."','$content',".(-$amount).",0,$next,1,'".G5_TIME_YMD."','@redtoy_cancel','$oid','$relation')");
    $left=$amount;
    foreach ($lots as $poid=>$share) {
        $row=redtoy_cancel_one("select po_id,po_point,po_use_point,po_expired from $table where mb_id='$mb' and po_id=".(int)$poid);
        if (!$row) throw new RuntimeException('point_write_failed');
        if ((int)$row['po_expired']!==0) continue;
        $take=min($share,(int)$row['po_point']-(int)$row['po_use_point']);
        if ($take>0) { redtoy_cancel_consume($table,$mb,$row,$take); $left-=$take; }
    }
    if ($left>0) {
        $ordering=!empty($config['cf_point_term'])?'po_expire_date,po_id':'po_id';
        foreach (redtoy_cancel_rows("select po_id,po_point,po_use_point,po_expired from $table where mb_id='$mb' and po_expired=0 and po_point>po_use_point order by $ordering") as $row) {
            $take=min($left,(int)$row['po_point']-(int)$row['po_use_point']);
            if ($take>0) { redtoy_cancel_consume($table,$mb,$row,$take); $left-=$take; }
            if (!$left) break;
        }
    }
    redtoy_cancel_query('update '.redtoy_cancel_table('member_table')." set mb_point=$next where mb_id='$mb'");
    return 1;
}
function redtoy_cancel_consume($table,$mb,$row,$take)
{
    global $g5;
    $new=(int)$row['po_use_point']+$take; $expired=$new===(int)$row['po_point']?100:0;
    redtoy_cancel_query("update $table set po_use_point=$new,po_expired=$expired where mb_id='$mb' and po_id=".(int)$row['po_id'].' and po_expired=0 and po_use_point='.(int)$row['po_use_point']);
    if (mysqli_affected_rows($g5['connect_db'])!==1) throw new RuntimeException('point_write_failed');
}
