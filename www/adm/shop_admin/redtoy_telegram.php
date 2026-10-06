<?php
$sub_menu = '400990';
include_once './_common.php';
require_once G5_LIB_PATH.'/redtoy_telegram.lib.php';
if ($is_admin !== 'super') { alert('최고관리자만 접근할 수 있습니다.'); exit; }
header('Cache-Control: no-store');
$settings = redtoy_tg_config();
if (!$settings) { alert('텔레그램 DB 마이그레이션을 먼저 적용해 주세요.'); exit; }
$notice = '';
$table = G5_TABLE_PREFIX.'redtoy_telegram_queue';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_token();
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save') {
        $chat = isset($_POST['chat_id']) && is_string($_POST['chat_id']) ? trim($_POST['chat_id']) : '';
        $token = isset($_POST['bot_token']) && is_string($_POST['bot_token']) ? trim($_POST['bot_token']) : '';
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? 1 : 0;
        if ($chat !== '' && !preg_match('/^(?:-?[0-9]{1,20}|@[a-zA-Z][a-zA-Z0-9_]{4,63})$/D', $chat)) alert('올바른 Chat ID를 입력해 주세요.');
        $cipher = $settings['token_cipher'];
        if ($token !== '') {
            if (!preg_match('/^[0-9]{5,20}:[a-zA-Z0-9_-]{20,100}$/D', $token)) alert('봇 토큰 형식을 확인해 주세요.');
            try { $cipher = redtoy_tg_encrypt($token); }
            catch (Throwable $e) { alert('서버의 REDTOY_TELEGRAM_KEY 설정을 확인해 주세요.'); }
        }
        if ($enabled) {
            if (!$chat || !$cipher) alert('봇 토큰과 Chat ID를 먼저 설정해 주세요.');
            try { redtoy_tg_decrypt($cipher); }
            catch (Throwable $e) { alert('암호화 키와 저장 토큰을 확인해 주세요.'); }
        }
        $columns = array("enabled=$enabled", "chat_id='".sql_real_escape_string($chat)."'", "token_cipher='".sql_real_escape_string($cipher)."'", 'updated_at=NOW()');
        foreach (redtoy_tg_events() as $event=>$label) $columns[] = "$event=".(isset($_POST[$event]) && $_POST[$event] === '1' ? 1 : 0);
        if (!redtoy_tg_query('update '.G5_TABLE_PREFIX.'redtoy_telegram_config set '.implode(',', $columns).' where id=1')) alert('설정 저장에 실패했습니다.');
        $notice = '설정을 저장했습니다. 토큰은 다시 표시하지 않습니다.';
    } elseif ($action === 'test') {
        // Explicit human confirmation; enqueue only. The web request never calls Telegram.
        if (empty($_POST['confirm_send']) || $_POST['confirm_send'] !== '1') alert('실제 채널 전송 확인을 선택해 주세요.');
        $nonce = isset($_POST['test_nonce']) && is_string($_POST['test_nonce']) ? $_POST['test_nonce'] : '';
        if (!get_session('redtoy_tg_test_nonce') || !hash_equals(get_session('redtoy_tg_test_nonce'), $nonce)) alert('테스트 화면을 다시 열어 주세요.');
        if (!redtoy_tg_enqueue('test', $nonce, '[레드토이] 관리자 요청 테스트 메시지', true)) alert('큐 등록 실패: 사용 설정과 토큰, Chat ID를 확인해 주세요.');
        set_session('redtoy_tg_test_nonce', '');
        $notice = '테스트 메시지를 큐에 등록했습니다. CLI 작업자가 실제 채널로 전송합니다.';
    } elseif ($action === 'retry') {
        $id = isset($_POST['queue_id']) && is_scalar($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
        $row = redtoy_tg_row("select status from $table where id=$id");
        if (!$row || !in_array($row['status'], array('failed','uncertain'), true)) alert('재시도 가능한 실패 항목이 아닙니다.');
        if ($row['status'] === 'uncertain' && (!isset($_POST['confirm_duplicate']) || $_POST['confirm_duplicate'] !== '1')) alert('전송 여부 불명 항목은 채널 확인 후 중복 가능성에 동의해야 합니다.');
        $actor = sql_real_escape_string($member['mb_id']);
        if (!redtoy_tg_query("update $table set status='pending',attempts=0,available_at=NOW(),updated_at=NOW(),retry_by='$actor' where id=$id and status in ('failed','uncertain')")) alert('재시도 등록에 실패했습니다.');
        $notice = '재시도 큐에 등록했습니다.';
    } else { alert('잘못된 요청입니다.'); }
    // POST/redirect/GET: refresh cannot repeat saves or test messages.
    set_session('redtoy_tg_notice', $notice);
    goto_url('./redtoy_telegram.php');
}
$notice = get_session('redtoy_tg_notice');
set_session('redtoy_tg_notice', '');
$nonce = bin2hex(random_bytes(16));
set_session('redtoy_tg_test_nonce', $nonce);
$token = get_admin_token();
$g5['title'] = '텔레그램 운영 알림';
include_once G5_ADMIN_PATH.'/admin.head.php';
function redtoy_tg_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<div class="local_desc01 local_desc">
    <p>최고관리자 전용입니다. 저장 후 CLI 작업자(cron)를 실행해야 전송됩니다. 웹 요청에서는 전송하지 않습니다.</p>
    <p>봇 토큰은 암호화하여 저장하며 조회 화면에 반환하지 않습니다. 토큰 입력란이 비어 있으면 기존 값을 유지합니다.</p>
    <p>실패는 최대 5회 자동 재시도합니다. 전송 여부 불명(uncertain)은 채널 확인 후 수동으로 재시도하세요.</p>
    <p>대기 메시지는 등록 당시 Chat ID로 전송됩니다. 알림 또는 해당 이벤트를 끄면 대기 전송도 멈춥니다.</p>
    <?php if ($notice) { ?><p role="status"><?php echo redtoy_tg_h($notice); ?></p><?php } ?>
</div>
<form method="post" autocomplete="off">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    <input type="hidden" name="action" value="save">
    <div class="tbl_frm01 tbl_wrap"><table><tbody>
    <tr><th>알림 사용</th><td><label><input type="checkbox" name="enabled" value="1" <?php echo $settings['enabled'] ? 'checked' : ''; ?>> 사용</label></td></tr>
    <tr><th><label for="bot_token">봇 토큰</label></th><td><input id="bot_token" name="bot_token" type="password" class="frm_input" autocomplete="new-password" value=""> <?php echo $settings['token_cipher'] ? '등록됨' : '미등록'; ?></td></tr>
    <tr><th><label for="chat_id">수신 Chat ID</label></th><td><input id="chat_id" name="chat_id" class="frm_input" value="<?php echo redtoy_tg_h($settings['chat_id']); ?>"></td></tr>
    <tr><th>이벤트</th><td><?php foreach (redtoy_tg_events() as $event=>$label) { ?><label style="margin-right:15px"><input type="checkbox" name="<?php echo $event; ?>" value="1" <?php echo $settings[$event] ? 'checked' : ''; ?>> <?php echo $label; ?></label><?php } ?></td></tr>
    </tbody></table></div>
    <button class="btn_submit btn" type="submit">설정 저장</button>
</form>
<form method="post" style="margin:20px 0">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    <input type="hidden" name="action" value="test">
    <input type="hidden" name="test_nonce" value="<?php echo $nonce; ?>">
    <label><input type="checkbox" name="confirm_send" value="1" required> 저장된 실제 채널로 테스트 메시지가 전송됨을 확인했습니다.</label>
    <button class="btn btn_02" type="submit">테스트 메시지 전송 예약</button>
</form>
<h2>최근 전송 결과 · 실패 내역</h2>
<p>pending: 대기 / sending: 전송 중 / sent: 성공 / failed: 실패 / uncertain: 전송 여부 불명</p>
<div class="tbl_head01 tbl_wrap"><table><thead><tr><th>ID</th><th>이벤트 / 대상</th><th>상태 / 시도</th><th>등록 / 변경 시각</th><th>HTTP / 오류 / 메시지 ID</th><th>재시도</th></tr></thead><tbody>
<?php
$page = isset($_GET['page']) && is_scalar($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page-1)*50;
$rows = redtoy_tg_query("select id,event_type,object_id,status,attempts,created_at,updated_at,http_code,error_code,message_id from $table order by id desc limit $offset,50");
while ($rows && ($row = sql_fetch_array($rows))) { ?>
<tr><td><?php echo (int)$row['id']; ?></td><td><?php echo redtoy_tg_h($row['event_type'].' / '.$row['object_id']); ?></td><td><?php echo redtoy_tg_h($row['status'].' / '.$row['attempts']); ?></td><td><?php echo redtoy_tg_h($row['created_at'].' / '.$row['updated_at']); ?></td><td><?php echo redtoy_tg_h($row['http_code'].' / '.$row['error_code'].' / '.$row['message_id']); ?></td><td>
<?php if (in_array($row['status'], array('failed','uncertain'), true)) { ?>
<form method="post"><input type="hidden" name="token" value="<?php echo $token; ?>"><input type="hidden" name="action" value="retry"><input type="hidden" name="queue_id" value="<?php echo (int)$row['id']; ?>">
<?php if ($row['status'] === 'uncertain') { ?><label><input type="checkbox" name="confirm_duplicate" value="1" required> 채널 확인 완료 · 중복 가능성 동의</label><?php } ?>
<button type="submit" class="btn btn_02">재시도</button></form><?php } ?></td></tr>
<?php } ?></tbody></table></div>
<p><?php if ($page>1) { ?><a href="?page=<?php echo $page-1; ?>">이전</a><?php } ?> <a href="?page=<?php echo $page+1; ?>">다음</a></p>
<?php include_once G5_ADMIN_PATH.'/admin.tail.php'; ?>
