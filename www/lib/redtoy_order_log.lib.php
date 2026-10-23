<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_order_log_events()
{
    return array(
        'entered' => '주문서 진입 / 주문번호 발급',
        'submitted' => '주문 요청 서버 도착',
        'payment_ready' => '결제 요청 준비 완료 (결제창 열림 여부는 확인 불가)',
        'payment_confirmed' => '결제 승인 확인',
        'order_saved' => '주문내역 저장 완료',
        'completed' => '주문 처리 완료',
        'cancelled' => '결제 취소 반환',
        'failed' => '결제 또는 주문 처리 실패',
        'validation_failed' => '주문 입력 / 금액 검증 실패',
        'stock_failed' => '상품 / 재고 / 가격 검증 실패',
        'coupon_failed' => '쿠폰 금액 검증 실패',
        'shipping_failed' => '배송비 검증 실패',
        'point_failed' => '포인트 검증 실패',
        'save_failed' => '주문 저장 실패',
        'changed' => '장바구니 변경으로 기존 주문서 종료',
        'expired' => '진행 중 결제 세션 만료'
    );
}

function redtoy_order_log_table()
{
    $table = G5_TABLE_PREFIX.'redtoy_order_log';
    if (!preg_match('/^[A-Za-z0-9_]+$/D', $table)) throw new RuntimeException('invalid_table');
    return $table;
}

function redtoy_order_log_partner_display($row)
{
    // 접수된 주문은 실제 주문 귀속값을 우선한다 (빈 값도 그대로 반영).
    $id = !empty($row['saved_order']) ? ($row['od_partner'] ?? '') : ($row['partner_id'] ?? '');
    return (string) $id !== '' ? (string) $id : '-';
}

// 이미 조회한 선택 장바구니에서 표시용 필드만 추출한다.
function redtoy_order_log_products($rows)
{
    $products = array();
    foreach ($rows as $row) {
        $products[] = array('id' => (string) ($row['it_id'] ?? ''),
            'name' => (string) ($row['it_name'] ?? ''),
            'option' => (string) ($row['ct_option'] ?? ''),
            'quantity' => (int) ($row['ct_qty'] ?? 0));
    }
    return $products;
}

function redtoy_order_log_product_summary($products)
{
    $ids = array();
    foreach ($products as $product) $ids[(string) $product['id']] = true;
    $name = $products[0]['name'] !== '' ? $products[0]['name'] : $products[0]['id'];
    return $name.(count($ids) > 1 ? ' 외 '.(count($ids) - 1).'개 상품' : '');
}

// User-Agent 원문은 저장하지 않고 접속 기기 구분만 남긴다.
function redtoy_order_log_device()
{
    $ua = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ($ua === '') return '';
    return preg_match('/phone|mobile|android|ipad|ipod|tablet|blackberry|BB10/i', $ua) ? 'mobile' : 'pc';
}

function redtoy_order_log_device_label($device)
{
    return $device === 'mobile' ? '모바일' : ($device === 'pc' ? 'PC' : '기록 없음');
}

function redtoy_order_log_status($row, $now = null)
{
    if (!empty($row['saved_order'])) {
        if (substr($row['event'], -6) === 'failed') return '주문 존재 / 오류 기록 확인 필요';
        if ($row['od_status'] === '취소') return '접수된 주문 / 취소';
        if ((float) $row['od_misu'] > 0) return '주문 접수 / 입금 대기';
        return '주문 접수 / 결제 완료';
    }
    if (in_array($row['event'], array('order_saved', 'completed', 'payment_confirmed'), true)) return '확인 필요: 승인·완료 기록 있으나 주문 없음';
    if ($row['event'] === 'cancelled') return '결제 취소';
    if (substr($row['event'], -6) === 'failed') return '오류 확인';
    if ($row['event'] === 'changed') return '장바구니 변경';
    if ($row['event'] === 'expired') return '결제 세션 만료';
    if (strtotime($row['created_at']) < ($now === null ? time() : $now) - 3600) return '미완료 / 이탈 추정';
    return '진행 중 / 미완료';
}

// Statistics must never expose SQL errors or change the checkout result.
function redtoy_order_log_query($sql, $unused = false)
{
    global $g5;
    try {
        if (preg_match('/^SELECT\b/i', ltrim($sql))) $sql = 'SET STATEMENT max_statement_time=1 FOR '.$sql;
        return @mysqli_query($g5['connect_db'], $sql);
    }
    catch (Throwable $e) { return false; }
}
function redtoy_order_log_ready()
{
    $table = redtoy_order_log_table();
    return redtoy_order_log_query("SELECT id FROM `$table` LIMIT 0") !== false;
}
function redtoy_order_log($order_id, $event, $snapshot_rows = array())
{
    global $g5, $is_member;
    if (!is_scalar($order_id) || !preg_match('/^[0-9]{1,20}$/D', (string)$order_id) ||
        !is_string($event) || !isset(redtoy_order_log_events()[$event])) return false;
    try {
        $table = redtoy_order_log_table();
        $kind = empty($is_member) ? 'guest' : 'member';
        $partner = $_SESSION['set_partner_id'] ?? '';
        $partner = is_string($partner) && preg_match('/^[A-Za-z0-9_-]{1,20}$/D', $partner) ? $partner : '';
        $products = $event === 'entered' ? redtoy_order_log_products(array_slice($snapshot_rows,0,100)) : array();
        foreach ($products as &$product) {
            $product['id'] = mb_substr($product['id'],0,20);
            $product['name'] = mb_substr($product['name'],0,255);
            $product['option'] = mb_substr($product['option'],0,255);
        }
        unset($product);
        $json = json_encode($products, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $device = redtoy_order_log_device();
        $time = (new DateTimeImmutable('now',new DateTimeZone('Asia/Seoul')))->format('Y-m-d H:i:s');
        $stmt = @mysqli_prepare($g5['connect_db'], "INSERT INTO `$table` (order_id,event,customer_kind,partner_id,products_json,device_type,created_at) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=id");
        if (!$stmt) return false;
        try {
            mysqli_stmt_bind_param($stmt,'sssssss',$order_id,$event,$kind,$partner,$json,$device,$time);
            return @mysqli_stmt_execute($stmt);
        } finally { mysqli_stmt_close($stmt); }
    } catch (Throwable $e) { return false; }
}
function redtoy_order_log_entered($order_id, $cart_id)
{
    global $g5;
    try {
        $table = $g5['g5_shop_cart_table'];
        if (!preg_match('/^[A-Za-z0-9_]+$/D',$table) || !is_scalar($cart_id) || !preg_match('/^[0-9]{1,20}$/D',(string)$cart_id)) return;
        $result = redtoy_order_log_query("SELECT it_id,it_name,ct_option,ct_qty FROM `$table` WHERE od_id='$cart_id' AND ct_select=1 ORDER BY ct_id LIMIT 100");
        $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        redtoy_order_log($order_id,'entered',$rows);
    } catch (Throwable $e) { /* Never interrupt checkout. */ }
}
function redtoy_order_log_error_event($message)
{
    foreach (array('주문정보 입력 오류'=>'save_failed','쿠폰'=>'coupon_failed','배송비'=>'shipping_failed',
        '포인트'=>'point_failed','장바구니'=>'stock_failed','재고'=>'stock_failed','가격'=>'stock_failed') as $word=>$event) {
        if (is_string($message) && strpos($message,$word)!==false) return $event;
    }
    return 'failed';
}
function redtoy_order_log_delete($ids)
{
    if (!is_array($ids) || !$ids || count($ids)>50) throw new RuntimeException('invalid_selection');
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/^[0-9]{1,20}$/D',$id)) throw new RuntimeException('invalid_order');
    $table=redtoy_order_log_table();
    if (!redtoy_order_log_query("DELETE FROM `$table` WHERE order_id IN ('".implode("','",array_unique($ids))."')")) throw new RuntimeException('delete_failed');
}
function redtoy_order_log_purge()
{
    $table=redtoy_order_log_table();
    $cutoff=(new DateTimeImmutable('-90 days',new DateTimeZone('Asia/Seoul')))->format('Y-m-d H:i:s');
    if (!redtoy_order_log_query("DELETE FROM `$table` WHERE created_at < '$cutoff' ORDER BY created_at LIMIT 1000")) throw new RuntimeException('purge_failed');
}

function redtoy_order_log_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function redtoy_order_log_date($key, $fallback) {
    $value = isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : '';
    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)) return $fallback;
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
}
