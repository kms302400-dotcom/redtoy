<?php
$sub_menu = '400400';
include_once './_common.php';
auth_check_menu($auth, $sub_menu, 'w');
require_once G5_LIB_PATH.'/redtoy_purchase.lib.php';
header('Cache-Control: no-store');
$back_query = redtoy_purchase_return($_REQUEST);
$back_url = $back_query !== '' ? './orderlist.php?'.html_entity_decode($back_query, ENT_QUOTES, 'UTF-8') : './orderlist.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') check_admin_token();
$saved = false;
$notice = ''; $error = ''; $unknown = array(); $codes = array(); $text = '';
try {
    $codes = redtoy_purchase_codes();
    $text = implode(' ', $codes);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $text = isset($_POST['codes']) ? $_POST['codes'] : '';
        $result = redtoy_purchase_save($text, isset($_POST['revision']) ? $_POST['revision'] : '', $member['mb_id']);
        if (isset($result['unknown'])) { $unknown = $result['unknown']; $error = '조회되지 않는 상품코드가 있습니다. 수정 후 다시 저장해 주세요.'; }
        else { $saved = true; }
    } elseif (isset($_GET['saved']) && $_GET['saved'] === '1') $notice = '사입제품 목록을 저장했습니다.';
} catch (Throwable $e) {
    $messages = array('invalid_codes'=>'상품코드는 영문·숫자·하이픈 20자 이내로, 공백으로 구분해 입력해 주세요.',
        'too_many_codes'=>'한 번에 최대 500개까지 등록할 수 있습니다.', 'stale_codes'=>'다른 관리자가 목록을 변경했습니다. 화면을 새로고침한 뒤 다시 저장해 주세요.',
        'purchase_busy'=>'다른 관리자가 저장 중입니다. 잠시 후 다시 시도해 주세요.');
    $error = isset($messages[$e->getMessage()]) ? $messages[$e->getMessage()] : '설정을 읽거나 저장하지 못했습니다. DB 적용 상태를 확인해 주세요.';
}
if ($saved) { goto_url('./redtoy_purchase.php?saved=1'.($back_query !== '' ? '&purchase_context='.rawurlencode(html_entity_decode($back_query, ENT_QUOTES, 'UTF-8')) : '')); exit; }
$g5['title'] = '사입제품관리';
include_once G5_ADMIN_PATH.'/admin.head.php';
?>
<p>사입한 상품코드를 공백으로 구분해 입력하세요. 줄바꿈도 사용할 수 있으며 중복 코드는 한 번만 저장합니다.</p>
<?php if ($notice) { ?><p role="status"><?php echo redtoy_purchase_h($notice); ?></p><?php } ?>
<?php if ($error) { ?><p role="alert" style="color:#b42318"><?php echo redtoy_purchase_h($error); ?></p><?php } ?>
<?php if ($unknown) { ?><p>확인할 코드: <?php echo redtoy_purchase_h(implode(' ', $unknown)); ?></p><?php } ?>
<form method="post" action="./redtoy_purchase.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="purchase_context" value="<?php echo redtoy_purchase_h(html_entity_decode($back_query, ENT_QUOTES, 'UTF-8')); ?>">
    <input type="hidden" name="revision" value="<?php echo redtoy_purchase_revision($codes); ?>">
    <label for="purchase_codes">상품코드 목록 (최대 500개)</label>
    <textarea name="codes" id="purchase_codes" rows="10" maxlength="32768" style="display:block;width:100%;max-width:1000px;margin:12px 0"><?php echo redtoy_purchase_h(is_string($text) ? $text : ''); ?></textarea>
    <p>모두 비우고 저장하면 등록 목록이 해제됩니다. 상품이나 기존 주문은 변경되지 않습니다.</p>
    <button type="submit" class="btn_submit btn">저장</button>
    <a class="btn btn_02" href="<?php echo redtoy_purchase_h($back_url); ?>">주문내역</a>
</form>
<?php include_once G5_ADMIN_PATH.'/admin.tail.php'; ?>
