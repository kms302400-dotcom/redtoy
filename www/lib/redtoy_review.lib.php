<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_review_admin()
{
    global $is_admin, $member, $g5;
    if (empty($member['mb_id'])) return false;
    if ($is_admin === 'super') return true;
    $id = sql_real_escape_string($member['mb_id']);
    $row = sql_fetch("select au_auth from {$g5['auth_table']} where mb_id='$id' and au_menu='400650'", false);
    return !empty($row['au_auth']) && strpos($row['au_auth'], 'w') !== false;
}

function redtoy_review_values($allowed, $input, $now)
{
    if (!$allowed) return null;
    $name = isset($input['review_nickname']) && is_string($input['review_nickname']) ? trim(stripslashes($input['review_nickname'])) : '';
    $date = isset($input['review_date']) && is_string($input['review_date']) ? $input['review_date'] : '';
    if ($name === '' || preg_match('/[<>\x00-\x1f\x7f]/u', $name) || !preg_match('/^.{1,50}$/usD', $name)) {
        throw new InvalidArgumentException('닉네임을 HTML 없이 1~50자로 입력해 주세요.');
    }
    if (!preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $date)
        || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))
        || $date > substr($now, 0, 10)) {
        throw new InvalidArgumentException('오늘 또는 과거의 올바른 등록일을 선택해 주세요.');
    }
    return array('name' => $name, 'time' => $date.substr($now, 10));
}

function redtoy_review_token()
{
    $token = get_session('redtoy_review_token');
    if (!$token) {
        $token = bin2hex(random_bytes(32));
        set_session('redtoy_review_token', $token);
    }
    return $token;
}

function redtoy_review_check_token()
{
    $token = isset($_POST['redtoy_review_token']) ? $_POST['redtoy_review_token'] : '';
    $saved = get_session('redtoy_review_token');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || !$saved || !hash_equals($saved, $token)) {
        alert('잘못된 요청입니다. 리뷰 작성 화면을 새로 열어 주세요.');
        exit;
    }
}

function redtoy_review_fields($review = array(), $admin_screen = false)
{
    echo '<input type="hidden" name="redtoy_review_token" value="'.redtoy_review_token().'">';
    if (!redtoy_review_admin()) return;
    $provided = !empty($review['is_provided']);
    if (!empty($review['is_id']) && !$provided) return;
    $name = $provided ? $review['is_name'] : '';
    $date = $provided ? substr($review['is_time'], 0, 10) : substr(G5_TIME_YMDHIS, 0, 10);
    echo '<fieldset class="redtoy_review_fields" style="padding:12px;border:1px solid #ddd;margin:10px 0">';
    echo '<legend>상품 제공 리뷰 대리 등록</legend>';
    echo '<input type="hidden" name="review_provided" value="1">';
    echo '<label>고객 닉네임 <input class="frm_input" type="text" name="review_nickname" maxlength="50" required value="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"></label> ';
    echo '<label>리뷰 등록일 <input class="frm_input" type="date" name="review_date" required max="'.substr(G5_TIME_YMDHIS, 0, 10).'" value="'.$date.'"></label>';
    echo '<p>고객이 전달한 후기를 관리자가 대신 등록했습니다</p>';
    if ($admin_screen && $provided) echo '<p>실제 등록 관리자: '.htmlspecialchars($review['is_registered_by'], ENT_QUOTES, 'UTF-8').' / '.htmlspecialchars($review['is_registered_at'], ENT_QUOTES, 'UTF-8').'</p>';
    echo '</fieldset>';
}

function redtoy_review_notice($row)
{
    return empty($row['is_provided']) ? '' : '<p class="redtoy_review_notice"><strong>상품 제공 리뷰</strong><br>고객이 전달한 후기를 관리자가 대신 등록했습니다</p>';
}
