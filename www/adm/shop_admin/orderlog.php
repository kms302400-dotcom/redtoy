<?php
$sub_menu = '400405';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');
include_once(G5_LIB_PATH.'/redtoy_order_log.lib.php');
$table = redtoy_order_log_table();
$notice = '';
$cutoff = date('Y-m-d H:i:s', strtotime('-90 days'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_check_menu($auth, $sub_menu, 'd');
    check_admin_token();
    try {
        if (($_POST['action'] ?? '') === 'delete') {
            redtoy_order_log_delete($_POST['order_ids'] ?? null);
        } elseif (($_POST['action'] ?? '') === 'purge') {
            redtoy_order_log_purge();
        } else {
            throw new RuntimeException('invalid_action');
        }
    } catch (Throwable $e) {
        alert('로그 정리에 실패했습니다. 선택 항목과 DB 상태를 확인해 주세요.');
    }
    goto_url('./orderlog.php');
    exit;
}
$ready = redtoy_order_log_ready();
$from = redtoy_order_log_date('from', date('Y-m-d', strtotime('-7 days')));
$to = redtoy_order_log_date('to', date('Y-m-d'));
if ($from > $to) $from = $to;
$from = max($from, substr($cutoff, 0, 10));
$order_id = isset($_GET['order_id']) && is_string($_GET['order_id']) && preg_match('/^[0-9]{1,20}$/D', $_GET['order_id']) ? $_GET['order_id'] : '';
$page = max(1, min(10000, isset($_GET['page']) && is_scalar($_GET['page']) ? (int) $_GET['page'] : 1));
$offset = ($page - 1) * 50;
$g5['title'] = '주문내역 로그';
include_once(G5_ADMIN_PATH.'/admin.head.php');
$labels = redtoy_order_log_events();
?>
<div class="local_desc01 local_desc">
    <p>주문번호는 주문서 진입 시 발급됩니다. 번호 발급 수는 주문 완료 수나 방문자 수와 다릅니다.</p>
    <p>동일 주문번호의 각 단계는 최초 1회만 기록합니다. 새로운 단계의 기록 없이 1시간 동안 주문이 없으면 ‘이탈 추정’으로 표시하며 실제 이탈을 확정하지 않습니다. 브라우저 내부 입력 오류, 통신 단절, 결제창 닫기는 서버에 도착하지 않으면 기록되지 않습니다.</p>
    <p>선택 상품은 기능 적용 후 새 주문번호 발급 시 한 번만 저장합니다. 이전 로그의 상품은 ‘기록 없음’으로 표시합니다.</p>
    <p>접속 환경은 주문서 최초 진입 시 브라우저 정보 기준입니다. 기존 기록은 소급 판별하지 않으며, 기기나 데스크톱 모드에 따라 실제 기기와 다를 수 있습니다.</p>
    <p>최근 90일 기록을 조회합니다. 오래된 기록은 ‘90일 이전 로그 정리’ 버튼으로 최대 1,000건씩 삭제할 수 있습니다. 입금·취소 상태는 현재 주문내역을 조회한 결과이며, 상세 단계의 시각은 최초 기록 시각입니다.</p>
</div>
<?php if ($notice) { ?><p class="local_desc01"><?php echo redtoy_order_log_html($notice); ?></p><?php } ?>
<?php if (!$ready) { ?>
<div class="local_desc01"><p>별도 DB 마이그레이션 적용이 필요합니다. 이전 주문의 진행 단계는 소급 복원되지 않습니다.</p></div>
<?php } else { ?>
<form method="get" class="local_sch01 local_sch">
<label for="from">기록 시작일</label> <input type="date" name="from" id="from" value="<?php echo $from; ?>" class="frm_input">
<label for="to">종료일</label> <input type="date" name="to" id="to" value="<?php echo $to; ?>" class="frm_input">
<label for="order_id">주문번호</label> <input type="text" name="order_id" id="order_id" value="<?php echo redtoy_order_log_html($order_id); ?>" class="frm_input" inputmode="numeric">
<button class="btn_submit" type="submit">조회</button>
</form>
<?php
$where = "created_at >= '{$from} 00:00:00' AND created_at <= '{$to} 23:59:59' AND created_at >= '{$cutoff}'";
if ($order_id !== '') $where .= " AND order_id = '{$order_id}'";
// 날짜 인덱스로 한정하고, 페이지에 표시할 50개 주문만 원 주문과 연결한다.
$result = redtoy_order_log_query("SELECT l.*, o.od_id AS saved_order, o.od_status, o.od_misu, o.od_partner
 FROM (SELECT order_id, MAX(id) AS last_id FROM `{$table}` WHERE {$where}
 GROUP BY order_id ORDER BY last_id DESC LIMIT {$offset}, 51) p
 JOIN `{$table}` l ON l.id = (SELECT MAX(latest.id) FROM `{$table}` latest WHERE latest.order_id = p.order_id)
 LEFT JOIN {$g5['g5_shop_order_table']} o ON o.od_id = CAST(l.order_id AS UNSIGNED)
 ORDER BY l.id DESC", false);
$rows = array();
if ($result) while ($row = sql_fetch_array($result)) $rows[] = $row;
$more = count($rows) > 50;
$rows = array_slice($rows, 0, 50);
$details = array();
$products_by_order = array();
$devices_by_order = array();
if ($rows) {
    $ids = array();
    foreach ($rows as $row) $ids[] = "'".sql_escape_string($row['order_id'])."'";
    $detail_result = redtoy_order_log_query("SELECT * FROM `{$table}` WHERE order_id IN (".implode(',', $ids).") ORDER BY id", false);
    if ($detail_result) while ($event = sql_fetch_array($detail_result)) {
        $details[$event['order_id']][] = $event;
        if ($event['event'] === 'entered') $devices_by_order[$event['order_id']] = $event['device_type'] ?? '';
        if ($event['event'] === 'entered' && !empty($event['products_json'])) {
            $products = json_decode($event['products_json'], true);
            if (is_array($products) && $products) $products_by_order[$event['order_id']] = $products;
        }
    }
}
?>
<form method="post" onsubmit="return confirm('90일 이전 로그를 최대 1,000건 삭제할까요? 실제 주문은 유지됩니다.');">
<input type="hidden" name="action" value="purge">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
<button type="submit" class="btn btn_02">90일 이전 로그 정리</button>
</form>
<form method="post" id="orderlog_delete" onsubmit="return confirmOrderLogDelete(this);">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
<div class="local_sch01"><button type="submit" class="btn btn_02">선택 삭제</button>
<span>선택한 주문번호의 모든 로그를 영구 삭제합니다. 실제 주문내역은 유지됩니다.</span></div>
<div class="tbl_head01 tbl_wrap"><table>
<caption>주문번호별 주요 진행 단계</caption>
<thead><tr><th><input type="checkbox" aria-label="현재 페이지 전체 선택" onclick="this.form.querySelectorAll('input[name=&quot;order_ids[]&quot;]').forEach(function(box) { box.checked = this.checked; }, this);"></th><th>주문번호</th><th>구분<br>(파트너)</th><th>접속 환경</th><th>진입 당시 선택 상품</th><th>현재 상태</th><th>마지막 기록 단계</th><th>기록 시각</th><th>진행 기록</th></tr></thead><tbody>
<?php foreach ($rows as $row) { ?>
<tr>
<td><input type="checkbox" name="order_ids[]" value="<?php echo redtoy_order_log_html($row['order_id']); ?>" aria-label="<?php echo redtoy_order_log_html($row['order_id']); ?> 로그 선택"></td>
<td><?php if ($row['saved_order']) { ?><a href="./orderform.php?od_id=<?php echo rawurlencode($row['order_id']); ?>"><?php echo redtoy_order_log_html($row['order_id']); ?></a><?php } else { echo redtoy_order_log_html($row['order_id']); } ?></td>
<td><?php echo $row['customer_kind'] === 'member' ? '회원' : '비회원'; ?><br>(<?php echo redtoy_order_log_html(redtoy_order_log_partner_display($row)); ?>)</td>
<td><?php echo redtoy_order_log_device_label($devices_by_order[$row['order_id']] ?? ''); ?></td>
<td style="text-align:left;max-width:320px;overflow-wrap:anywhere">
<?php $products = $products_by_order[$row['order_id']] ?? array(); if ($products) { ?>
<details><summary><?php echo redtoy_order_log_html(redtoy_order_log_product_summary($products)); ?></summary>
<ul style="padding:10px 10px 10px 20px">
<?php foreach ($products as $product) { ?>
<li><a href="<?php echo redtoy_order_log_html(G5_SHOP_URL.'/item.php?it_id='.rawurlencode($product['id'])); ?>" target="_blank" rel="noopener noreferrer"><?php echo redtoy_order_log_html($product['name'] !== '' ? $product['name'] : $product['id']); ?></a><br>
상품번호: <?php echo redtoy_order_log_html($product['id']); ?><br>
옵션: <?php echo redtoy_order_log_html($product['option'] !== '' ? $product['option'] : '-'); ?> / 수량: <?php echo (int) $product['quantity']; ?>개</li>
<?php } ?></ul></details>
<?php } else { ?>기록 없음<?php } ?>
</td>
<td><?php echo redtoy_order_log_html(redtoy_order_log_status($row)); ?></td>
<td><?php echo redtoy_order_log_html(isset($labels[$row['event']]) ? $labels[$row['event']] : $row['event']); ?></td>
<td><?php echo redtoy_order_log_html($row['created_at']); ?></td>
<td><details><summary>단계 보기</summary><ol style="text-align:left;padding:10px 10px 10px 25px;min-width:240px">
<?php foreach (isset($details[$row['order_id']]) ? $details[$row['order_id']] : array() as $event) { ?>
<li><?php echo redtoy_order_log_html($event['created_at'].' · '.(isset($labels[$event['event']]) ? $labels[$event['event']] : $event['event'])); ?></li>
<?php } ?></ol></details></td>
</tr>
<?php } if (!$rows) { ?><tr><td colspan="9" class="empty_table"><?php echo $result ? '조건에 맞는 기록이 없습니다.' : '로그 조회에 실패했습니다. DB 상태를 확인해 주세요.'; ?></td></tr><?php } ?>
</tbody></table></div>
</form>
<script>
function confirmOrderLogDelete(form) {
    var count = form.querySelectorAll('input[name="order_ids[]"]:checked').length;
    if (!count) {
        alert('삭제할 로그를 선택해 주세요.');
        return false;
    }
    return confirm('선택한 ' + count + '개 주문번호의 모든 로그를 영구 삭제하시겠습니까?\n실제 주문내역은 삭제되지 않습니다.');
}
</script>
<div class="local_sch01">
<?php $query = http_build_query(array('from'=>$from, 'to'=>$to, 'order_id'=>$order_id)); ?>
<?php if ($page > 1) { ?><a class="btn btn_02" href="?<?php echo redtoy_order_log_html($query.'&page='.($page-1)); ?>">이전</a><?php } ?>
<span><?php echo $page; ?> 페이지</span>
<?php if ($more) { ?><a class="btn btn_02" href="?<?php echo redtoy_order_log_html($query.'&page='.($page+1)); ?>">다음</a><?php } ?>
</div>
<?php }
include_once(G5_ADMIN_PATH.'/admin.tail.php');
