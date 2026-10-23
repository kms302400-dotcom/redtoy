<?php
// Isolated synthetic MariaDB fixtures. No production bootstrap/config or network transport.
$socket = getenv('REDTOY_COMPLETION_TEST_SOCKET');
if (!$socket || !preg_match('#^/private/tmp/redtoy-completion-db\.[A-Za-z0-9]+/db\.sock$#D', $socket)) exit(2);
define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', 'g5_');
date_default_timezone_set('Asia/Seoul');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('localhost', 'root', '', null, 0, $socket);
$name = 'redtoy_bank_test_'.bin2hex(random_bytes(6));
$db->query("create database $name character set utf8mb4");
$db->select_db($name);
$db->set_charset('utf8mb4');
$root = dirname(__DIR__).'/www';
$g5 = array('connect_db'=>$db,'g5_shop_order_table'=>'orders','g5_shop_cart_table'=>'cart');
require $root.'/lib/redtoy_bank_receipt.lib.php';
$checks = 0;
function verify_bank($ok, $label) {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
}
function bank_reject($call, $code) {
    try { $call(); } catch (Throwable $e) {
        verify_bank($e->getMessage() === $code, $code.' got '.$e->getMessage()); return;
    }
    throw new RuntimeException('Expected '.$code);
}
function bank_fixture($overrides = array()) {
    global $db;
    $db->query('delete from orders'); $db->query('delete from cart'); $db->query('delete from g5_redtoy_cancel');
    $db->query("insert into orders (od_id,od_settle_case,od_status,od_cart_price,od_send_cost,od_send_cost2,od_cart_coupon,od_coupon,od_send_coupon,od_receipt_point,od_receipt_price,od_refund_price,od_cancel_price,od_misu,od_receipt_time,od_shop_memo,od_bank_account,od_delivery_company,od_invoice,od_invoice_time) values ('900001','무통장','주문',20900,5000,0,0,0,0,0,0,0,0,25900,'0000-00-00 00:00:00','keep memo','keep bank','keep company','keep invoice','2000-01-01 00:00:00')");
    $db->query("insert into cart values (1,'900001','주문',1,15900,0,0,0,0),(2,'900001','주문',1,5000,0,0,0,0)");
    foreach ($overrides as $field=>$value) {
        if (!preg_match('/^od_[a-z_0-9]+$/D', $field)) throw new RuntimeException();
        $db->query("update orders set `$field`='".$db->real_escape_string((string)$value)."'");
    }
}
function bank_snapshot() {
    return array(redtoy_cancel_rows('select * from orders'),redtoy_cancel_rows('select * from cart'));
}
// Run the real controller with only its bootstrap and transports stubbed.
$dir = sys_get_temp_dir().'/redtoy-bank-controller-'.bin2hex(random_bytes(6));
mkdir($dir); mkdir($dir.'/lib');
define('G5_LIB_PATH', $dir.'/lib');
file_put_contents($dir.'/_common.php', '<?php global $auth, $g5;');
file_put_contents($dir.'/admin.shop.lib.php', '<?php');
file_put_contents($dir.'/redtoy_statistics.lib.php', '<?php');
foreach (array('ordermail.inc.php','ordersms.inc.php','orderalimtalk2.inc.php') as $stub)
    file_put_contents($dir.'/'.$stub, '<?php $GLOBALS["bank_legacy_includes"][] = basename(__FILE__);');
file_put_contents($dir.'/lib/mailer.lib.php', '<?php');
symlink($root.'/lib/redtoy_bank_receipt.lib.php', $dir.'/lib/redtoy_bank_receipt.lib.php');
// Fake include asserts that the controller saved state before requesting Alimtalk.
file_put_contents($dir.'/orderalimtalk.inc.php', <<<'STUB'
<?php
if ($od_send_alimtalk !== 1 || !defined('_ORDERALIMTALK_')) throw new RuntimeException('notification_flag');
$order = redtoy_cancel_one("select * from orders where od_id='$od_id'");
if ($order['od_status'] !== '입금' || (int)$order['od_misu'] !== 0) throw new RuntimeException('notification_before_save');
$GLOBALS['bank_alimtalk']++;
if ($GLOBALS['bank_alimtalk_fail']) throw new RuntimeException('synthetic_transport_failure');
STUB
);
function auth_check_menu($auth, $menu, $permission) {
    if (!$GLOBALS['bank_auth'] || $menu !== '400400' || $permission !== 'w') throw new RuntimeException('permission');
}
function check_admin_token() {
    if (!$GLOBALS['bank_token']) throw new RuntimeException('csrf');
    $GLOBALS['bank_token'] = false;
}
function safe_replace_regex($v, $key) { return preg_replace('/[^0-9]/', '', $v); }
function get_search_string($v) { return $v; }
function clean_xss_tags($v) { return $v; }
function sql_query($sql) { return redtoy_cancel_query($sql); }
function sql_fetch($sql) { return redtoy_cancel_one($sql); }
function check_datetime($time) { return (bool)strtotime($time); }
function redtoy_statistics_record_cancel_event() { $GLOBALS['bank_statistics']++; }
function get_order_info($id) {
    $order = redtoy_cancel_one("select * from orders where od_id='$id'");
    return array('od_misu'=>$order['od_cart_price']+$order['od_send_cost']+$order['od_send_cost2']
        -$order['od_cart_coupon']-$order['od_coupon']-$order['od_send_coupon']
        -$order['od_receipt_price']-$order['od_receipt_point']+$order['od_refund_price'],
        'od_tax_mny'=>0,'od_vat_mny'=>0,'od_free_mny'=>0);
}
function alert($text, $url = '') { throw new RuntimeException('alert:'.$text); }
function goto_url($url) { throw new RuntimeException('redirect:'.$url); }
function redtoy_tg_order($id) {
    $order = redtoy_cancel_one("select * from orders where od_id='$id'");
    if ($order['od_status'] !== '입금' || (int)$order['od_misu'] !== 0) throw new RuntimeException('notification_before_save');
    $GLOBALS['bank_telegram']++;
    if ($GLOBALS['bank_tg_fail']) throw new RuntimeException('synthetic_telegram_failure');
}
function bank_controller($extra = array(), $auth = true, $token = true) {
    global $root, $dir, $g5;
    $member = array('mb_id'=>'synthetic-admin');
    $page = 1;
    $od_send_alimtalk = 1;
    $GLOBALS['bank_auth'] = $auth; $GLOBALS['bank_token'] = $token;
    $_POST = array_merge(array('od_id'=>'900001','receipt_action'=>'confirm_bank'), $extra);
    $_REQUEST = $_POST; $_SERVER['REQUEST_METHOD'] = 'POST';
    $old = getcwd(); chdir($dir);
    try { include $root.'/adm/shop_admin/orderformreceiptupdate.php'; }
    catch (Throwable $e) { return $e->getMessage(); }
    finally { chdir($old); }
    throw new RuntimeException('Expected redirect/alert');
}
try {
    foreach (array('InnoDB','MyISAM') as $engine) {
        $db->query('drop table if exists orders,cart,g5_redtoy_cancel');
        $db->query("create table orders (od_id varchar(32) primary key,od_settle_case varchar(32),od_status varchar(16),od_cart_price int,od_send_cost int,od_send_cost2 int,od_cart_coupon int,od_coupon int,od_send_coupon int,od_receipt_point int,od_receipt_price int,od_refund_price int,od_cancel_price int,od_misu int,od_receipt_time datetime,od_shop_memo text,od_bank_account varchar(64),od_delivery_company varchar(64),od_invoice varchar(64),od_invoice_time varchar(19),od_deposit_name varchar(64) default '',od_pg varchar(16) default 'manual',od_tax_mny int default 0,od_vat_mny int default 0,od_free_mny int default 0) engine=$engine");
        $db->query("create table cart (ct_id int primary key,od_id varchar(32),ct_status varchar(16),ct_qty int,ct_price int,io_price int,io_type int,cp_price int,ct_stock_use int) engine=$engine");
        $db->query('create table g5_redtoy_cancel (op_id varchar(32),od_id varchar(32),state varchar(32),created_at datetime) engine=InnoDB');
        bank_fixture(); $before = bank_snapshot(); $start = date('Y-m-d H:i:s');
        redtoy_bank_receipt_confirm('900001'); $after = bank_snapshot();
        verify_bank((int)$after[0][0]['od_receipt_price'] === 25900, "$engine full cash including shipping");
        verify_bank($after[0][0]['od_status'] === '입금' && (int)$after[0][0]['od_misu'] === 0, "$engine paid order");
        verify_bank($after[0][0]['od_receipt_time'] >= $start && $after[0][0]['od_receipt_time'] <= date('Y-m-d H:i:s'), "$engine actual server time");
        $expected = $before;
        foreach (array('od_receipt_price','od_receipt_time','od_misu','od_status') as $field) $expected[0][0][$field] = $after[0][0][$field];
        foreach ($expected[1] as &$row) $row['ct_status'] = '입금'; unset($row);
        verify_bank($expected === $after, "$engine unrelated fields and stock unchanged");
        bank_reject(function () { redtoy_bank_receipt_confirm('900001'); }, 'bank_not_eligible');
        verify_bank(bank_snapshot() === $after, "$engine duplicate no mutation");
        bank_fixture(array('od_receipt_price'=>5000,'od_misu'=>20900));
        redtoy_bank_receipt_confirm('900001');
        verify_bank((int)bank_snapshot()[0][0]['od_receipt_price'] === 25900, "$engine partial deposit cumulative");
        bank_fixture(array('od_receipt_point'=>1000,'od_cart_coupon'=>500,'od_coupon'=>2000,'od_send_coupon'=>1000,'od_misu'=>21400));
        $db->query('update cart set cp_price=500 where ct_id=1');
        redtoy_bank_receipt_confirm('900001');
        verify_bank((int)bank_snapshot()[0][0]['od_receipt_price'] === 21400, "$engine points/coupons retained");
        bank_fixture(array('od_cart_price'=>20000,'od_misu'=>25000));
        $db->query('update cart set io_price=-900 where ct_id=1');
        redtoy_bank_receipt_confirm('900001');
        verify_bank((int)bank_snapshot()[0][0]['od_receipt_price'] === 25000, "$engine negative price option");
        bank_fixture(array('od_cart_price'=>7000,'od_send_cost2'=>2000,'od_misu'=>14000));
        $db->query('update cart set io_type=1,io_price=1000,ct_qty=2 where ct_id=1');
        redtoy_bank_receipt_confirm('900001');
        verify_bank((int)bank_snapshot()[0][0]['od_receipt_price'] === 14000, "$engine additional option and regional shipping");
        foreach (array(array('od_settle_case'=>'card'),array('od_settle_case'=>'hpp'),array('od_settle_case'=>'가상계좌'),array('od_status'=>'취소'),array('od_status'=>'배송'),array('od_cancel_price'=>1),array('od_refund_price'=>1)) as $bad) {
            bank_fixture($bad); $before = bank_snapshot();
            bank_reject(function () { redtoy_bank_receipt_confirm('900001'); }, 'bank_not_eligible');
            verify_bank(bank_snapshot() === $before, "$engine rejected order untouched");
        }
        foreach (array("update cart set ct_status='취소' where ct_id=2", "delete from cart", 'update orders set od_misu=1', 'update orders set od_cart_price=100', 'update orders set od_cart_coupon=1', 'update cart set ct_qty=0 where ct_id=1') as $bad) {
            bank_fixture(); $db->query($bad); $before = bank_snapshot();
            bank_reject(function () { redtoy_bank_receipt_confirm('900001'); }, 'bank_amount_mismatch');
            verify_bank(bank_snapshot() === $before, "$engine invalid cart/amount untouched");
        }
        bank_fixture(); $db->query("insert into g5_redtoy_cancel values ('synthetic','900001','review',now())");
        bank_reject(function () { redtoy_bank_receipt_confirm('900001'); }, 'reconciliation_required');
        bank_fixture();
        $peer = new mysqli('localhost', 'root', '', $name, 0, $socket);
        $peer->query("select GET_LOCK('redtoy_complete_order_900001',0)");
        bank_reject(function () { redtoy_bank_receipt_confirm('900001'); }, 'busy');
        $peer->query("select RELEASE_LOCK('redtoy_complete_order_900001')"); $peer->close();
        // Force the second write to fail, after cart states were changed. Both engines must restore.
        bank_fixture(); $before = bank_snapshot();
        $db->query("create trigger bank_failure before update on orders for each row begin if NEW.od_status='입금' then signal sqlstate '45000' set message_text='synthetic'; end if; end");
        bank_reject(function () { redtoy_bank_receipt_confirm('900001'); }, 'database_failure');
        verify_bank(bank_snapshot() === $before, "$engine restores cart/order on SQL failure");
        $db->query('drop trigger bank_failure');
        // Real controller: rights, CSRF, forged fields, notifications, and replay.
        bank_fixture(); $before = bank_snapshot();
        $GLOBALS['bank_alimtalk'] = $GLOBALS['bank_telegram'] = 0;
        $GLOBALS['bank_alimtalk_fail'] = $GLOBALS['bank_tg_fail'] = false;
        verify_bank(bank_controller(array(), false) === 'permission', "$engine unauthorized");
        verify_bank(bank_controller(array(), true, false) === 'csrf', "$engine csrf");
        verify_bank(strpos(bank_controller(array('receipt_action'=>'unknown')), 'alert:') === 0, "$engine invalid action blocked");
        verify_bank(bank_snapshot() === $before && $GLOBALS['bank_alimtalk'] === 0, "$engine security rejects no effects");
        $result = bank_controller(array('od_receipt_price'=>'1','od_misu'=>'0','od_send_cost'=>'999999','od_refund_price'=>'999','od_invoice'=>'forged','od_send_alimtalk2'=>'1','od_send_mail'=>'1','od_receipt_time'=>'2000-01-01 00:00:00'));
        verify_bank(strpos($result,'redirect:') === 0, "$engine controller success");
        $saved = bank_snapshot()[0][0];
        verify_bank((int)$saved['od_receipt_price'] === 25900 && (int)$saved['od_send_cost'] === 5000 && (int)$saved['od_refund_price'] === 0 && $saved['od_invoice'] === 'keep invoice' && $saved['od_receipt_time'] >= $start, "$engine forged form values ignored");
        verify_bank($GLOBALS['bank_alimtalk'] === 1 && $GLOBALS['bank_telegram'] === 1, "$engine post-save notifications once");
        verify_bank(strpos(bank_controller(),'alert:') === 0, "$engine replay stopped");
        verify_bank($GLOBALS['bank_alimtalk'] === 1 && $GLOBALS['bank_telegram'] === 1, "$engine replay no notification");
        bank_fixture(); $GLOBALS['bank_alimtalk_fail'] = true;
        verify_bank(strpos(bank_controller(),'alert:입금 확인은 저장됐지만') === 0, "$engine Alimtalk exception reported");
        verify_bank(bank_snapshot()[0][0]['od_status'] === '입금', "$engine Alimtalk exception preserves receipt");
        bank_fixture(); $GLOBALS['bank_alimtalk_fail'] = false; $GLOBALS['bank_tg_fail'] = true;
        verify_bank(strpos(bank_controller(),'redirect:') === 0, "$engine Telegram exception isolated");
        verify_bank(bank_snapshot()[0][0]['od_status'] === '입금', "$engine Telegram exception preserves receipt");
    }
    // Existing manual controller path still accepts explicitly edited receipt/shipping fields.
    bank_fixture(); $GLOBALS['bank_tg_fail'] = false; $GLOBALS['bank_statistics'] = 0;
    $GLOBALS['bank_legacy_includes'] = array();
    set_error_handler(function ($severity, $message) {
        if (strpos($message, 'already defined') !== false) return true;
        throw new RuntimeException($message);
    });
    try {
        $result = bank_controller(array('receipt_action'=>null,'od_receipt_price'=>'25900',
            'od_receipt_time'=>'2026-01-02 03:04:05','od_bank_account'=>'edited bank',
            'od_deposit_name'=>'synthetic payer','od_receipt_point'=>'0','od_refund_price'=>'0',
            'od_delivery_company'=>'','od_invoice'=>'','od_invoice_time'=>'',
            'od_send_cost'=>'5000','od_send_cost2'=>'0','od_tno'=>'','od_escrow'=>'0','od_send_mail'=>'0'));
    } finally { restore_error_handler(); }
    verify_bank(strpos($result,'redirect:') === 0, 'manual submit still succeeds: '.$result);
    $saved = bank_snapshot()[0][0];
    verify_bank($saved['od_receipt_time'] === '2026-01-02 03:04:05' && $saved['od_bank_account'] === 'edited bank', 'manual entered time/account retained');
    verify_bank($saved['od_status'] === '입금' && (int)$saved['od_misu'] === 0, 'manual status transition retained');
    verify_bank($GLOBALS['bank_statistics'] === 1 && count($GLOBALS['bank_legacy_includes']) === 3, 'manual existing downstream routes retained');
    // Render the actual inserted UI fragment, including the unchanged manual submit.
    $source = file_get_contents($root.'/adm/shop_admin/orderform.php');
    $start = strpos($source, "        <?php if ($"."od['od_settle_case'] === '무통장'");
    $end = strpos($source, '<input type="submit" value="결제/배송내역 수정"', $start);
    $end = strpos($source, '>', $end) + 1;
    $fragment = substr($source, $start, $end - $start);
    foreach (array(
        array('무통장','주문',25900,0,0,true), array('무통장','입금',0,0,0,false),
        array('card','주문',25900,0,0,false),array('hpp','주문',25900,0,0,false),
        array('무통장','취소',25900,0,0,false),array('무통장','주문',25900,1,0,false),
        array('무통장','주문',25900,0,1,false)
    ) as $case) {
        $od = array_combine(array('od_settle_case','od_status','od_misu','od_cancel_price','od_refund_price'),array_slice($case,0,5));
        ob_start(); eval('?>'.$fragment); $html = ob_get_clean();
        verify_bank((strpos($html,'입금 확인처리</button>') !== false) === $case[5], 'UI eligibility');
        verify_bank(strpos($html,'결제/배송내역 수정') !== false, 'manual button retained');
        if ($case[5]) {
            verify_bank(strpos($html,'입금 확인처리') < strpos($html,'결제/배송내역 수정'), 'one-click button to left');
            verify_bank(strpos($html,'form="redtoy_bank_receipt_form"') !== false, 'separate form preserves manual Enter submission');
        }
    }
    echo "Bank receipt: $checks checks passed (InnoDB + MyISAM; no real messages sent)\n";
} finally {
    $db->query('UNLOCK TABLES');
    $db->query("drop database `$name`"); $db->close();
    foreach (glob($dir.'/lib/*') as $file) unlink($file);
    rmdir($dir.'/lib'); foreach (glob($dir.'/*') as $file) unlink($file); rmdir($dir);
}
