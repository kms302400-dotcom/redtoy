<?php
if (!defined('_GNUBOARD_')) exit;

function redtoy_purchase_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redtoy_purchase_on($value) { return is_string($value) && $value === '1'; }
function redtoy_purchase_table($key)
{
    global $g5;
    $name = $key === 'codes' ? G5_TABLE_PREFIX.'redtoy_purchase_item' : (isset($g5[$key]) ? $g5[$key] : '');
    if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name)) throw new RuntimeException('invalid_table');
    return '`'.$name.'`';
}
function redtoy_purchase_query($sql)
{
    global $g5;
    try { $result = mysqli_query($g5['connect_db'], $sql); }
    catch (Throwable $e) { throw new RuntimeException('purchase_database_error'); }
    if ($result === false) throw new RuntimeException('purchase_database_error');
    return $result;
}
function redtoy_purchase_escape($value) { global $g5; return mysqli_real_escape_string($g5['connect_db'], (string)$value); }
function redtoy_purchase_rows($sql) { return mysqli_fetch_all(redtoy_purchase_query($sql), MYSQLI_ASSOC); }
function redtoy_purchase_codes()
{
    return array_column(redtoy_purchase_rows('SELECT it_id FROM '.redtoy_purchase_table('codes').' ORDER BY it_id'), 'it_id');
}
function redtoy_purchase_revision($codes) { sort($codes, SORT_STRING); return hash('sha256', implode("\n", $codes)); }
function redtoy_purchase_parse($text)
{
    if (!is_string($text) || strlen($text) > 32768) throw new RuntimeException('invalid_codes');
    $parts = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    if ($parts === false) throw new RuntimeException('invalid_codes');
    $codes = array();
    foreach ($parts as $code) {
        if (!preg_match('/^[a-zA-Z0-9-]{1,20}$/D', $code)) throw new RuntimeException('invalid_codes');
        $codes[strtolower($code)] = $code;
    }
    if (count($codes) > 500) throw new RuntimeException('too_many_codes');
    return array_values($codes);
}
function redtoy_purchase_save($text, $revision, $actor)
{
    $codes = redtoy_purchase_parse($text);
    if (!is_string($revision) || !preg_match('/^[a-f0-9]{64}$/D', $revision)) throw new RuntimeException('stale_codes');
    $table = redtoy_purchase_table('codes');
    $schema = redtoy_purchase_rows("SHOW TABLE STATUS WHERE Name='".redtoy_purchase_escape(trim($table, '`'))."'");
    if (!$schema || strcasecmp($schema[0]['Engine'], 'InnoDB') !== 0) throw new RuntimeException('purchase_schema_required');
    $database = redtoy_purchase_rows('SELECT DATABASE() AS db');
    $lock_name = 'redtoy_purchase_'.substr(hash('sha256', $database[0]['db'].'|'.$table), 0, 32);
    $lock = redtoy_purchase_rows("SELECT GET_LOCK('$lock_name',0) AS ok");
    if (empty($lock[0]['ok'])) throw new RuntimeException('purchase_busy');
    $transaction = false;
    try {
        $old = redtoy_purchase_codes();
        if (!hash_equals(redtoy_purchase_revision($old), $revision)) throw new RuntimeException('stale_codes');
        $known = array();
        foreach ($old as $code) $known[strtolower($code)] = $code;
        if ($codes) {
            $quoted = array_map(function ($code) { return "'".redtoy_purchase_escape($code)."'"; }, $codes);
            $rows = redtoy_purchase_rows('SELECT it_id FROM '.redtoy_purchase_table('g5_shop_item_table').' WHERE it_id IN ('.implode(',', $quoted).')');
            foreach ($rows as $row) $known[strtolower($row['it_id'])] = $row['it_id'];
        }
        $unknown = array(); $canonical = array();
        foreach ($codes as $code) {
            if (!isset($known[strtolower($code)])) $unknown[] = $code;
            else $canonical[] = $known[strtolower($code)];
        }
        if ($unknown) return array('unknown'=>$unknown);
        redtoy_purchase_query('START TRANSACTION'); $transaction = true;
        redtoy_purchase_query('DELETE FROM '.$table);
        if ($canonical) {
            $actor = redtoy_purchase_escape($actor);
            $now = date('Y-m-d H:i:s');
            $values = array();
            foreach ($canonical as $code) $values[] = "('".redtoy_purchase_escape($code)."','$actor','$now')";
            redtoy_purchase_query("INSERT INTO $table (it_id,updated_by,updated_at) VALUES ".implode(',', $values));
        }
        redtoy_purchase_query('COMMIT'); $transaction = false;
        return array('saved'=>count($canonical));
    } finally {
        try { if ($transaction) redtoy_purchase_query('ROLLBACK'); }
        finally { redtoy_purchase_query("SELECT RELEASE_LOCK('$lock_name')"); }
    }
}
// Require a covering item->order index to avoid unindexed cart scans; never add schema here.
function redtoy_purchase_filter()
{
    $table = redtoy_purchase_table('codes');
    if (!redtoy_purchase_rows("SELECT it_id FROM $table LIMIT 1"))
        return array('from'=>' from '.redtoy_purchase_table('g5_shop_order_table'),'where'=>'1=0');
    $cart = redtoy_purchase_table('g5_shop_cart_table');
    $indexes = array();
    foreach (redtoy_purchase_rows('SHOW INDEX FROM '.$cart) as $row) {
        if ((isset($row['Ignored']) && $row['Ignored'] === 'YES') || (isset($row['Visible']) && $row['Visible'] === 'NO')) continue;
        $indexes[$row['Key_name']][(int)$row['Seq_in_index']] = $row;
    }
    $usable = false;
    foreach ($indexes as $columns) {
        ksort($columns);
        if ($columns[1]['Column_name'] !== 'it_id' || $columns[1]['Sub_part'] !== null) continue;
        foreach ($columns as $column) {
            if ($column['Column_name'] === 'od_id' && $column['Sub_part'] === null) $usable = true;
        }
    }
    if (!$usable) throw new RuntimeException('purchase_index_required');
    $order = redtoy_purchase_table('g5_shop_order_table');
    // Start with the deduplicated matching order IDs, then probe orders by primary key.
    // This avoids scanning every order when only a few purchased products match.
    return array('from'=>" from (SELECT DISTINCT pc.od_id AS redtoy_purchase_order_id FROM $table pi INNER JOIN $cart pc ON pc.it_id=pi.it_id) redtoy_purchase_orders STRAIGHT_JOIN $order ON $order.od_id=redtoy_purchase_orders.redtoy_purchase_order_id", 'where'=>'1=1');
}
function redtoy_purchase_select($sql)
{
    global $g5;
    // Bound only the opt-in filter queries on the production MariaDB engine.
    if (stripos(mysqli_get_server_info($g5['connect_db']), 'MariaDB') !== false)
        $sql = 'SET STATEMENT max_statement_time=1 FOR '.$sql;
    return redtoy_purchase_query($sql);
}
// Navigation state only. This helper never queries a database.
function redtoy_purchase_context($input)
{
    $state = array();
    $allowed = array('od_status','od_settle_case','od_misu','od_cancel_price','od_refund_price',
        'od_receipt_point','od_coupon','fr_date','to_date','sel_field','search','save_search',
        'od_escrow','sort1','sort2','purchase_only');
    foreach ($allowed as $key) {
        if (isset($input[$key]) && is_string($input[$key]) && strlen($input[$key]) <= 256
            && !preg_match('/[\x00-\x1f\x7f]/', $input[$key])) $state[$key] = $input[$key];
    }
    $state['purchase_only'] = isset($state['purchase_only']) && redtoy_purchase_on($state['purchase_only']) ? '1' : '0';
    return $state;
}
function redtoy_purchase_url($state) { return './orderlist.php?'.http_build_query(redtoy_purchase_context($state), '', '&', PHP_QUERY_RFC3986); }
function redtoy_purchase_hidden($state, $exclude = array())
{
    $html = '';
    foreach (redtoy_purchase_context($state) as $key=>$value)
        if (!in_array($key, $exclude, true)) $html .= '<input type="hidden" name="'.$key.'" value="'.redtoy_purchase_h($value).'">'."\n";
    return $html;
}
function redtoy_purchase_return($post)
{
    if (!isset($post['purchase_context']) || !is_string($post['purchase_context']) || strlen($post['purchase_context']) > 8192) return '';
    parse_str($post['purchase_context'], $state);
    $state = redtoy_purchase_context($state);
    return $state['purchase_only'] === '1' ? http_build_query($state, '', '&amp;', PHP_QUERY_RFC3986) : '';
}
// Keep the list's navigation context through detail-page edits without touching payment handlers.
function redtoy_purchase_detail_back($id, $input)
{
    if (!is_scalar($id) || !preg_match('/^[0-9]{1,32}$/D', (string)$id)) return '';
    $contexts = isset($_SESSION['ss_redtoy_purchase_back']) && is_array($_SESSION['ss_redtoy_purchase_back'])
        ? $_SESSION['ss_redtoy_purchase_back'] : array();
    if (isset($input['purchase_only'])) {
        unset($contexts[$id]);
        if (redtoy_purchase_on($input['purchase_only'])) {
            $state = redtoy_purchase_context($input);
            if (isset($input['page']) && is_string($input['page']) && ctype_digit($input['page']))
                $state['page'] = max(1, min(100000, (int)$input['page']));
            $contexts[$id] = './orderlist.php?'.http_build_query($state, '', '&', PHP_QUERY_RFC3986);
        }
        $_SESSION['ss_redtoy_purchase_back'] = array_slice($contexts, -10, null, true);
    }
    return isset($contexts[$id]) && is_string($contexts[$id]) ? $contexts[$id] : '';
}
