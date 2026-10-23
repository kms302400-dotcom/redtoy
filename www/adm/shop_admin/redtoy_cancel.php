<?php
$sub_menu='400400';
include_once './_common.php';
auth_check_menu($auth,$sub_menu,'w');
require_once G5_LIB_PATH.'/redtoy_danal_cancel.lib.php';
// Core point routines must not print SQL/customer data on an error in this financial operation.
add_replace('get_permission_debug_show',function () { return false; });
function redtoy_cancel_html($v) { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function redtoy_cancel_message($code)
{
    $messages=array(
        'pg_not_configured'=>'다날 취소 전용 환경설정이 준비되지 않았습니다.',
        'reconciliation_required'=>'미확정 취소 기록이 있습니다. 다날 거래내역과 처리 기록을 대조한 뒤 확인해야 합니다. 재취소하지 마세요.',
        'pg_review_required'=>'다날 취소 결과를 확정할 수 없습니다. 주문 상태를 바꾸지 않았습니다. 다날 거래내역 확인이 필요하며 재취소는 차단됩니다.',
        'quote_changed'=>'미리보기 이후 주문 또는 포인트 이력이 변경됐습니다. 새로 조회해 주세요.',
        'busy'=>'해당 주문 또는 포인트가 처리 중입니다. 잠시 후 다시 조회해 주세요.',
        'phone_partial_unsupported'=>'휴대폰 결제는 남은 상품 전체 취소만 지원합니다.',
        'phone_cancel_period'=>'휴대폰 결제월이 지난 거래입니다. 별도 환불 확인이 필요합니다.',
        'card_cancel_period'=>'카드 승인일로부터 365일이 지난 거래입니다.',
        'select_whole_product'=>'같은 상품의 옵션을 모두 선택해야 합니다. 수량 부분취소는 지원하지 않습니다.',
        'point_refund_account_required'=>'사용 포인트를 반환할 활성 회원 계정이 없습니다. 별도 정산 확인이 필요합니다.',
        'point_expiry_reconciliation_required'=>'만료와 사용 이력이 섞인 적립금입니다. 중복 회수를 피하기 위해 별도 정산 확인이 필요합니다.',
        'coupon_mapping_required'=>'상품과 쿠폰 사용 이력을 확정할 수 없습니다. 쿠폰 이력 대조가 필요합니다.',
        'prior_adjustment'=>'기존 취소·환불 또는 미수 조정이 있는 주문입니다. 이전 처리 내역을 먼저 대조해야 합니다.'
    );
    return isset($messages[$code])?$messages[$code]:'안전하게 취소를 완료하지 못했습니다. 취소 기록 및 다날 거래내역을 확인해 주세요. 처리 중인 기록이 있으면 재취소하지 마세요.';
}
$pgReady=true; $error=''; $plan=null; $completed=false; $order=null; $carts=array(); $pending=null;
$id=isset($_REQUEST['od_id']) && is_string($_REQUEST['od_id'])?$_REQUEST['od_id']:'';
if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D',$id)) alert('주문번호가 올바르지 않습니다.');
try {
    $order=redtoy_cancel_order($id);
    if (!$order) throw new RuntimeException('order_missing');
    redtoy_cancel_method($order);
    $carts=redtoy_cancel_carts($id);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        check_admin_token();
        $stage=isset($_POST['stage']) && is_string($_POST['stage'])?$_POST['stage']:'preview';
        if ($stage==='execute') {
            $op=isset($_POST['operation']) && is_string($_POST['operation'])?$_POST['operation']:'';
            $key='redtoy_cancel_quote_'.$op;
            $quote=get_session($key);
            if (!$quote || !preg_match('/^[a-f0-9]{32}$/D',$op) || $quote['actor']!==$member['mb_id'] || $quote['order']!==$id || $quote['expires']<time()) throw new RuntimeException('quote_expired');
            set_session($key,null);
            redtoy_cancel_execute($id,$quote['ids'],$quote['fingerprint'],$op,$member['mb_id']);
            $completed=true;
            require_once './redtoy_statistics.lib.php';
            redtoy_statistics_record_cancel_event($order,redtoy_cancel_order($id),'PG취소','admin_redtoy_cancel',$member['mb_id']);
        } elseif ($stage==='preview') {
            $ids=array();
            if (isset($_POST['items']) && is_array($_POST['items'])) $ids=$_POST['items'];
            elseif (isset($_POST['ct_chk'],$_POST['ct_id']) && is_array($_POST['ct_chk']) && is_array($_POST['ct_id'])) {
                foreach ($_POST['ct_chk'] as $index) {
                    if (!is_scalar($index) || !ctype_digit((string)$index) || !isset($_POST['ct_id'][$index])) throw new RuntimeException('invalid_selection');
                    $ids[]=$_POST['ct_id'][$index];
                }
            }
            if (!$ids || count($ids)>count($carts)) throw new RuntimeException('invalid_selection');
            foreach ($ids as $ct) if (!is_scalar($ct) || !ctype_digit((string)$ct) || (int)$ct<1) throw new RuntimeException('invalid_selection');
            $ids=array_map('intval',$ids);
            if (count($ids)!==count(array_unique($ids))) throw new RuntimeException('invalid_selection');
            // The product, including all its option rows, is the cancellation unit.
            $products=array(); $matched=array();
            foreach ($carts as $ct) if (in_array((int)$ct['ct_id'],$ids,true)) { $products[$ct['it_id']]=true; $matched[]=(int)$ct['ct_id']; }
            if (count($matched)!==count($ids)) throw new RuntimeException('invalid_selection');
            $ids=array();
            foreach ($carts as $ct) if (isset($products[$ct['it_id']]) && in_array($ct['ct_status'],array('주문','입금','준비','배송','완료'),true)) $ids[]=(int)$ct['ct_id'];
            $locks=redtoy_cancel_locks($id);
            try { $plan=redtoy_cancel_quote($id,$ids,G5_TIME_YMDHIS); } finally { redtoy_cancel_unlock($locks); }
            {
                require_once G5_LIB_PATH.'/redtoy_danal_cancel_pg.lib.php';
                try { redtoy_cancel_pg_ready($plan); } catch (Throwable $ignored) { $pgReady=false; }
            }
            $op=bin2hex(random_bytes(16));
            set_session('redtoy_cancel_quote_'.$op,array('actor'=>$member['mb_id'],'order'=>$id,'ids'=>$ids,'fingerprint'=>$plan['fingerprint'],'expires'=>time()+600));
        } else throw new RuntimeException('invalid_stage');
    } elseif ($_SERVER['REQUEST_METHOD']!=='GET') throw new RuntimeException('invalid_method');
    $pending=redtoy_cancel_pending($id);
} catch (Throwable $e) { $error=redtoy_cancel_message($e->getMessage());
    if ($order) { try { $pending=redtoy_cancel_pending($id); } catch (Throwable $ignored) {} } }
$g5['title']='다날 상품 취소·환불';
include_once G5_ADMIN_PATH.'/admin.head.php';
?>
<div class="local_desc01 local_desc"><p>주문번호: <?php echo redtoy_cancel_html($id); ?>. 같은 상품의 옵션 전체가 함께 취소됩니다. 상품 수량 일부 취소는 지원하지 않습니다.</p></div>
<?php if ($error) { ?><div class="local_desc01 local_desc"><p><?php echo redtoy_cancel_html($error); ?></p></div><?php } ?>
<?php if ($completed) { ?><p>PG 취소와 상품·포인트·쿠폰 처리가 완료됐습니다.</p><?php } ?>
<?php if ($pending) { ?><p>확인 필요 기록: <?php echo redtoy_cancel_html($pending['op_id']); ?> (<?php echo redtoy_cancel_html($pending['state']); ?>). 다날 거래내역과 대조하기 전에는 재요청할 수 없습니다.</p><?php } ?>
<?php if ($plan && !$error) { ?>
<div class="tbl_frm01 tbl_wrap"><table><caption>취소 미리보기</caption><tbody>
<tr><th>취소 상품</th><td><?php
$names=array(); foreach ($plan['base']['carts'] as $ct) if (in_array((int)$ct['ct_id'],$plan['ids'],true)) {
    $it=redtoy_cancel_escape($ct['it_id']); $row=redtoy_cancel_one('select it_name from '.redtoy_cancel_table('g5_shop_item_table')." where it_id='$it'");
    $names[$ct['it_id']]=$row?$row['it_name']:$ct['it_id'];
} echo redtoy_cancel_html(implode(', ',$names)); ?> (옵션 포함)</td></tr>
<tr><th>구분</th><td><?php echo $plan['full']?'남은 상품 전체 취소':'선택 상품 부분취소'; ?></td></tr>
<tr><th>PG 환불액</th><td><?php echo number_format($plan['cash']); ?>원</td></tr>
<tr><th>사용 포인트 반환</th><td><?php echo number_format($plan['return_points']); ?>P</td></tr>
<tr><th>지급 포인트 회수</th><td><?php echo number_format($plan['recover_points']); ?>P</td></tr>
<tr><th>쿠폰 반환</th><td><?php echo count($plan['coupon_ids']); ?>건 (원래 유효기간·사용조건 유지)</td></tr>
</tbody></table></div>
<p>부분취소 시 원래 할인액·사용 포인트를 상품금액 비율로 배분합니다. 배송비는 마지막 상품 취소 때 반환하며, 남은 상품에 무료배송 조건을 다시 적용하지 않습니다. 이미 사용한 적립 포인트 회수 시 잔액이 음수가 될 수 있습니다.</p>
<form method="post" action="redtoy_cancel.php" onsubmit="return confirm('표시된 금액으로 실제 PG 취소와 포인트·쿠폰 처리를 진행하시겠습니까?');">
<input type="hidden" name="od_id" value="<?php echo redtoy_cancel_html($id); ?>">
<input type="hidden" name="token" value="<?php echo redtoy_cancel_html(get_admin_token()); ?>">
<input type="hidden" name="stage" value="execute"><input type="hidden" name="operation" value="<?php echo redtoy_cancel_html($op); ?>">
<?php if (!$pgReady) { ?><p>다날 취소 연동 설정이 준비되지 않아 실행할 수 없습니다. 미리보기에서는 실제 취소가 발생하지 않습니다.</p><?php } ?><button type="submit" class="btn_submit btn" <?php echo $pgReady?'':'disabled'; ?>>확인한 금액으로 취소·환불</button></form>
<?php } elseif (!$completed && !$pending && $order && !$error) { ?>
<form method="post" action="redtoy_cancel.php">
<input type="hidden" name="od_id" value="<?php echo redtoy_cancel_html($id); ?>">
<input type="hidden" name="token" value="<?php echo redtoy_cancel_html(get_admin_token()); ?>"><input type="hidden" name="stage" value="preview">
<div class="tbl_head01 tbl_wrap"><table><caption>취소 상품 선택</caption><thead><tr><th>선택</th><th>상품</th><th>상품행</th><th>상태</th></tr></thead><tbody>
<?php $shown=array(); foreach ($carts as $ct) { if (isset($shown[$ct['it_id']]) || !in_array($ct['ct_status'],array('주문','입금','준비','배송','완료'),true)) continue; $shown[$ct['it_id']]=true;
$it=redtoy_cancel_escape($ct['it_id']); $item=redtoy_cancel_one('select it_name from '.redtoy_cancel_table('g5_shop_item_table')." where it_id='$it'"); ?>
<tr><td><input type="checkbox" name="items[]" value="<?php echo (int)$ct['ct_id']; ?>" aria-label="취소 상품 선택"></td><td><?php echo redtoy_cancel_html($item?$item['it_name']:$ct['it_id']); ?></td><td><?php echo (int)$ct['ct_id']; ?></td><td><?php echo redtoy_cancel_html($ct['ct_status']); ?></td></tr>
<?php } ?></tbody></table></div><button type="submit" class="btn_submit btn">취소 금액 미리보기</button></form>
<?php } ?>
<p><a class="btn btn_02" href="orderform.php?od_id=<?php echo rawurlencode($id); ?>">주문 상세로 돌아가기</a></p>
<?php include_once G5_ADMIN_PATH.'/admin.tail.php'; ?>
