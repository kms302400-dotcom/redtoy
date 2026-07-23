<?php
$sub_menu = '500130';
include_once('./_common.php');
include_once('./redtoy_statistics.lib.php');

auth_check_menu($auth, $sub_menu, 'r');

function redtoy_statistics_parse_date($value)
{
    if (!is_string($value) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Seoul'));
    $errors = DateTime::getLastErrors();
    if (!$date
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $date->format('Y-m-d') !== $value) {
        return false;
    }

    return $date;
}

$today = new DateTime('today', new DateTimeZone('Asia/Seoul'));
$default_from = clone $today;
$default_from->modify('-13 days');

$fr_date = isset($_GET['fr_date']) ? trim($_GET['fr_date']) : $default_from->format('Y-m-d');
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : $today->format('Y-m-d');
$from = redtoy_statistics_parse_date($fr_date);
$to = redtoy_statistics_parse_date($to_date);

if (!$from || !$to) {
    alert('조회일자는 YYYY-MM-DD 형식으로 입력해 주십시오.');
}
if ($from > $to) {
    alert('시작일은 종료일보다 늦을 수 없습니다.');
}
if ($to > $today) {
    alert('종료일은 오늘 이후로 지정할 수 없습니다.');
}

$period_days = (int)$from->diff($to)->days + 1;
if ($period_days > 90) {
    alert('조회 기간은 최대 90일까지 지정할 수 있습니다.');
}

$statistics = redtoy_statistics_daily($fr_date, $to_date);
$rows = $statistics['rows'];
$totals = redtoy_statistics_empty_row('합계');
foreach ($rows as $row) {
    foreach ($totals as $key => $value) {
        if ($key !== 'date') {
            $totals[$key] += $row[$key];
        }
    }
}

$g5['title'] = '종합통계';
include_once(G5_ADMIN_PATH.'/admin.head.php');
include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');
?>

<div class="local_desc01 local_desc">
    <p>결제는 한국시간의 결제일시(od_receipt_time) 기준이며, 테스트·전체취소·완전환불 주문은 제외합니다. 결제금액은 입금액에서 환불액을 뺀 순액입니다.</p>
    <p>포인트만 결제한 정상 주문은 결제건수와 포인트 사용금액에는 포함되지만 실결제금액은 0원입니다.</p>
    <p>취소건수는 취소 이벤트 처리 건수이며, 취소금액은 환불액이 있으면 환불액, 없으면 취소상품액입니다. 과거 취소는 발생일을 복원하지 않고 결제일의 순결제금액에만 소급 반영됩니다.</p>
</div>

<?php if (!$statistics['cancel_event_table_ready']) { ?>
<div class="local_desc02 local_desc">
    <p><strong>취소 이벤트 테이블이 없습니다.</strong> 마이그레이션 적용 전에는 취소건수와 취소금액이 0으로 표시되며 새 취소 이벤트도 저장되지 않습니다.</p>
</div>
<?php } ?>

<form method="get" class="local_sch03 local_sch">
    <div>
        <strong>조회기간</strong>
        <label for="fr_date" class="sound_only">시작일</label>
        <input type="text" name="fr_date" value="<?php echo get_text($fr_date); ?>" id="fr_date" required class="required frm_input" size="10" maxlength="10">
        <span>~</span>
        <label for="to_date" class="sound_only">종료일</label>
        <input type="text" name="to_date" value="<?php echo get_text($to_date); ?>" id="to_date" required class="required frm_input" size="10" maxlength="10">
        <input type="submit" value="조회" class="btn_submit">
        <span class="frm_info">기본 14일, 최대 90일</span>
    </div>
</form>

<div class="tbl_head01 tbl_wrap redtoy-statistics-wrap">
    <table class="redtoy-statistics-table">
        <caption><?php echo get_text($fr_date); ?>부터 <?php echo get_text($to_date); ?>까지 종합통계</caption>
        <thead>
            <tr>
                <th scope="col" rowspan="2">일자</th>
                <th scope="col" rowspan="2">접속수</th>
                <th scope="col" rowspan="2">회원가입수</th>
                <th scope="col" rowspan="2">가입률</th>
                <th scope="colgroup" colspan="4">결제건수</th>
                <th scope="colgroup" colspan="4">결제금액</th>
                <th scope="col" rowspan="2">취소건수</th>
                <th scope="col" rowspan="2">취소금액</th>
                <th scope="col" rowspan="2">쿠폰 사용금액</th>
                <th scope="col" rowspan="2">포인트 사용금액</th>
                <th scope="col" rowspan="2">회원탈퇴수</th>
            </tr>
            <tr>
                <th scope="col">첫구매</th>
                <th scope="col">재구매</th>
                <th scope="col">비회원</th>
                <th scope="col">합계</th>
                <th scope="col">첫구매</th>
                <th scope="col">재구매</th>
                <th scope="col">비회원</th>
                <th scope="col">합계</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row) {
            $timestamp = strtotime($row['date']);
            $week_number = (int)date('w', $timestamp);
            $week_names = array('일', '월', '화', '수', '목', '금', '토');
            $date_class = $week_number === 0 ? ' stat-sunday' : ($week_number === 6 ? ' stat-saturday' : '');
            $join_rate = $row['visits'] > 0 ? ($row['joins'] / $row['visits']) * 100 : null;
        ?>
            <tr>
                <td class="stat-date<?php echo $date_class; ?>"><?php echo $row['date']; ?> (<?php echo $week_names[$week_number]; ?>)</td>
                <td><?php echo number_format($row['visits']); ?></td>
                <td><?php echo number_format($row['joins']); ?></td>
                <td><?php echo $join_rate === null ? '-' : number_format($join_rate, 1).'%'; ?></td>
                <td><?php echo number_format($row['first_count']); ?></td>
                <td><?php echo number_format($row['repeat_count']); ?></td>
                <td><?php echo number_format($row['guest_count']); ?></td>
                <td class="stat-total"><?php echo number_format($row['payment_count']); ?></td>
                <td><?php echo number_format($row['first_amount']); ?></td>
                <td><?php echo number_format($row['repeat_amount']); ?></td>
                <td><?php echo number_format($row['guest_amount']); ?></td>
                <td class="stat-total"><?php echo number_format($row['payment_amount']); ?></td>
                <td><?php echo number_format($row['cancel_count']); ?></td>
                <td><?php echo number_format($row['cancel_amount']); ?></td>
                <td><?php echo number_format($row['coupon_amount']); ?></td>
                <td><?php echo number_format($row['point_amount']); ?></td>
                <td><?php echo number_format($row['leaves']); ?></td>
            </tr>
        <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="stat-date">합계</td>
                <td><?php echo number_format($totals['visits']); ?></td>
                <td><?php echo number_format($totals['joins']); ?></td>
                <td><?php echo $totals['visits'] > 0 ? number_format(($totals['joins'] / $totals['visits']) * 100, 1).'%' : '-'; ?></td>
                <td><?php echo number_format($totals['first_count']); ?></td>
                <td><?php echo number_format($totals['repeat_count']); ?></td>
                <td><?php echo number_format($totals['guest_count']); ?></td>
                <td><?php echo number_format($totals['payment_count']); ?></td>
                <td><?php echo number_format($totals['first_amount']); ?></td>
                <td><?php echo number_format($totals['repeat_amount']); ?></td>
                <td><?php echo number_format($totals['guest_amount']); ?></td>
                <td><?php echo number_format($totals['payment_amount']); ?></td>
                <td><?php echo number_format($totals['cancel_count']); ?></td>
                <td><?php echo number_format($totals['cancel_amount']); ?></td>
                <td><?php echo number_format($totals['coupon_amount']); ?></td>
                <td><?php echo number_format($totals['point_amount']); ?></td>
                <td><?php echo number_format($totals['leaves']); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
$(function() {
    $('#fr_date, #to_date').datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        showButtonPanel: true,
        maxDate: 0
    });
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
