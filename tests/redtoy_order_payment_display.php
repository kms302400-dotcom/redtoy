<?php
// Render the actual admin payment summary with synthetic amounts only. No app/DB/PG bootstrap.
$root = isset($argv[1]) ? $argv[1] : dirname(__DIR__).'/www';
$source = file_get_contents($root.'/adm/shop_admin/orderform.php');
$start = strpos($source, '<section id="anc_sodr_pay">');
$end = $start === false ? false : strpos($source, '</section>', $start);
if ($start === false || $end === false) throw new RuntimeException('Payment summary boundaries changed');
$fragment = substr($source, $start, $end + strlen('</section>') - $start);
foreach (token_get_all('<?php ?>'.$fragment) as $token) {
    if (is_array($token) && in_array($token[0], [T_INCLUDE,T_INCLUDE_ONCE,T_REQUIRE,T_REQUIRE_ONCE], true)) {
        throw new RuntimeException('Unexpected bootstrap in payment summary');
    }
}
function display_price($value) { return number_format($value).'원'; }
function display_point($value) { return number_format($value).'점'; }
function check_pay_name_replace($method, $order) { return $method; }
function render_payment_summary($fragment, $changes) {
    $pg_anchor = '';
    $od = array_merge([
        'od_id'=>'synthetic', 'od_settle_case'=>'card',
        'od_cart_price'=>20900, 'od_send_cost'=>5000, 'od_send_cost2'=>0,
        'od_receipt_price'=>25900, 'od_receipt_point'=>0,
        'od_cart_coupon'=>0, 'od_coupon'=>0, 'od_send_coupon'=>0,
        'od_cancel_price'=>0, 'od_misu'=>0
    ], $changes);
    ob_start();
    try {
        eval('?>'.$fragment);
        $html = ob_get_contents();
    } finally { ob_end_clean(); }
    preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $body);
    preg_match_all('/<td\b[^>]*>(.*?)<\/td>/s', $body[1], $matches);
    return [$html, array_map(fn($v) => trim(strip_tags($v)), $matches[1]), $amount];
}
$checks = 0;
function verify_display($condition, $message) {
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException('FAIL: '.$message);
}
$cases = [
    [[], ['20,900원','5,000원','0점','25,900원','0원','0원']],
    [['od_send_cost'=>0,'od_receipt_price'=>20900], ['20,900원','0원','0점','20,900원','0원','0원']],
    [['od_send_cost'=>3000,'od_send_cost2'=>2000], ['20,900원','5,000원','0점','25,900원','0원','0원']],
    [['od_receipt_point'=>1000,'od_receipt_price'=>22900,'od_coupon'=>2000], ['20,900원','5,000원','1,000점','23,900원','2,000원','0원']],
    [['od_send_coupon'=>1000,'od_receipt_price'=>24900], ['20,900원','5,000원','0점','24,900원','1,000원','0원']],
    [['od_cancel_price'=>15900], ['20,900원','5,000원','0점','25,900원','0원','15,900원']]
];
foreach ($cases as $i => [$changes, $expected]) {
    [$html, $cells, $amount] = render_payment_summary($fragment, $changes);
    verify_display(count($cells)===8, "case $i retains all summary columns");
    verify_display(array_slice($cells,2)===$expected, "case $i renders product, shipping, points, receipts, coupons and cancellation correctly");
    verify_display(strpos($html,'<th scope="col">주문금액</th>')!==false, "case $i labels product subtotal clearly");
    verify_display($amount['order']===20900+($changes['od_send_cost']??5000)+($changes['od_send_cost2']??0), "case $i preserves existing order-total calculation");
}
echo "PASS: $checks payment-summary assertions; actual HTML rendered; no DB/PG/API\n";
