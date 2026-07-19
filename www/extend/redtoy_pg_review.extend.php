<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// PG사 검수 종료 후 false로 변경하면 모든 검수용 제한이 비활성화됩니다.
if (!defined('REDTOY_PG_REVIEW_ENABLED')) {
    define('REDTOY_PG_REVIEW_ENABLED', true);
}

if (!defined('REDTOY_PG_REVIEW_MEMBER_ID')) {
    define('REDTOY_PG_REVIEW_MEMBER_ID', 'test');
}

$GLOBALS['redtoy_pg_review_hidden_category_prefixes'] = array(
    '40', '30', '60', '90', '50', '70', '80'
);

$GLOBALS['redtoy_pg_review_company_info'] = array(
    'company_name'           => '(주)위드달리',
    'company_owner'          => '김무석',
    'business_number'        => '888-87-03766',
    'business_number_digits' => '8888703766',
    'company_address'        => '(14989) 경기도 시흥시 목감남서로 38, 401동 1301호',
    'address_region'         => '경기도',
    'address_locality'       => '시흥시',
    'street_address'         => '목감남서로 38, 401동 1301호',
    'online_sales_number'    => '2026-경기시흥-1426',
    'privacy_officer'        => '김무석',
    'email'                  => 'kms302400@gmail.com',
    'customer_service'       => '02-6101-9272',
    'fax'                    => '없음',
    'depositor'              => '(주)위드달리',
    'copyright'              => 'Copyright © 2024 redtoy. All Rights Reserved.'
);

function redtoy_pg_review_is_enabled()
{
    return defined('REDTOY_PG_REVIEW_ENABLED') && REDTOY_PG_REVIEW_ENABLED === true;
}

function redtoy_pg_review_is_test_member()
{
    global $member;

    return redtoy_pg_review_is_enabled()
        && isset($member['mb_id'])
        && $member['mb_id'] === REDTOY_PG_REVIEW_MEMBER_ID;
}

function redtoy_pg_review_get_company_info()
{
    if (!redtoy_pg_review_is_enabled()) {
        return array();
    }

    return isset($GLOBALS['redtoy_pg_review_company_info'])
        ? $GLOBALS['redtoy_pg_review_company_info']
        : array();
}

function redtoy_pg_review_is_hidden_category($ca_id)
{
    if (!redtoy_pg_review_is_test_member()) {
        return false;
    }

    $ca_id = (string) $ca_id;
    $prefixes = isset($GLOBALS['redtoy_pg_review_hidden_category_prefixes'])
        ? $GLOBALS['redtoy_pg_review_hidden_category_prefixes']
        : array();

    foreach ($prefixes as $prefix) {
        if ($ca_id !== '' && strpos($ca_id, (string) $prefix) === 0) {
            return true;
        }
    }

    return false;
}

function redtoy_pg_review_is_hidden_item($item)
{
    if (!redtoy_pg_review_is_test_member() || !is_array($item)) {
        return false;
    }

    foreach (array('ca_id', 'ca_id2', 'ca_id3') as $field) {
        if (isset($item[$field]) && redtoy_pg_review_is_hidden_category($item[$field])) {
            return true;
        }
    }

    return false;
}

function redtoy_pg_review_is_hidden_item_id($it_id)
{
    global $g5;

    if (!redtoy_pg_review_is_test_member() || !$it_id) {
        return false;
    }

    $it_id = sql_real_escape_string((string) $it_id);
    $item = sql_fetch(" select ca_id, ca_id2, ca_id3 from {$g5['g5_shop_item_table']} where it_id = '{$it_id}' ");

    return redtoy_pg_review_is_hidden_item($item);
}

function redtoy_pg_review_item_sql_condition($alias='')
{
    if (!redtoy_pg_review_is_test_member()) {
        return '';
    }

    $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $alias);
    $column_prefix = $alias ? $alias.'.' : '';
    $conditions = array();
    $prefixes = isset($GLOBALS['redtoy_pg_review_hidden_category_prefixes'])
        ? $GLOBALS['redtoy_pg_review_hidden_category_prefixes']
        : array();

    foreach (array('ca_id', 'ca_id2', 'ca_id3') as $field) {
        foreach ($prefixes as $prefix) {
            $prefix = sql_real_escape_string((string) $prefix);
            $column = "{$column_prefix}{$field}";
            $conditions[] = "({$column} is null or {$column} not like '{$prefix}%')";
        }
    }

    return $conditions ? '( '.implode(' and ', $conditions).' )' : '';
}

function redtoy_pg_review_filter_category_tree($categories)
{
    if (!redtoy_pg_review_is_test_member() || !is_array($categories)) {
        return $categories;
    }

    foreach ($categories as $key => $category) {
        if (!is_array($category)) {
            continue;
        }

        if (isset($category['text']['ca_id']) && redtoy_pg_review_is_hidden_category($category['text']['ca_id'])) {
            unset($categories[$key]);
            continue;
        }

        foreach ($category as $child_key => $child) {
            if ($child_key === 'text' || !is_array($child)) {
                continue;
            }

            $filtered = redtoy_pg_review_filter_category_tree(array($child_key => $child));
            if (empty($filtered)) {
                unset($categories[$key][$child_key]);
            } else {
                $categories[$key][$child_key] = $filtered[$child_key];
            }
        }
    }

    return $categories;
}

function redtoy_pg_review_block_access()
{
    global $g5;

    if (!redtoy_pg_review_is_test_member()) {
        return;
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $request_path = parse_url($request_uri, PHP_URL_PATH);
    $request_path = is_string($request_path) ? $request_path : '';
    $request_path = $request_path === '/' ? '/' : rtrim($request_path, '/');
    $request_parsing_excluded_endpoints = array(
        '/shop/cartupdate.php',
        '/mobile/shop/cartupdate.php',
        '/shop/ajax.cartupdate.php',
        '/theme/buzinga/shop/ajax.cartupdate.php',
        '/shop/danal_ready.php',
        '/mobile/shop/danal_ready.php',
        '/shop/danal_ready_teledit.php',
        '/mobile/shop/danal_ready_teledit.php'
    );
    if (in_array($script, $request_parsing_excluded_endpoints, true) || in_array($request_path, $request_parsing_excluded_endpoints, true)) {
        return;
    }

    $ca_id = isset($_REQUEST['ca_id']) ? preg_replace('/[^0-9a-z]/i', '', $_REQUEST['ca_id']) : '';
    if (!$ca_id && preg_match('#^/shop/list-([0-9a-z]+)$#i', $request_path, $matches)) {
        $ca_id = $matches[1];
    }
    $it_id = isset($_REQUEST['it_id']) ? get_search_string(trim($_REQUEST['it_id'])) : '';
    $type = isset($_REQUEST['type']) ? (string) $_REQUEST['type'] : '';
    $ev_id = isset($_REQUEST['ev_id']) ? (string) $_REQUEST['ev_id'] : '';
    $bo_table = isset($_REQUEST['bo_table']) ? preg_replace('/[^0-9a-z_]/i', '', $_REQUEST['bo_table']) : '';
    $top_category_pages = array(
        '/theme/buzinga/mobile/shop/topman.php'       => '40',
        '/theme/buzinga/mobile/shop/topwoman.php'     => '30',
        '/theme/buzinga/mobile/shop/topanal.php'      => '60',
        '/theme/buzinga/mobile/shop/topcouple.php'    => '90',
        '/theme/buzinga/mobile/shop/topbdsm.php'      => '50',
        '/theme/buzinga/mobile/shop/topunder.php'     => '70',
        '/theme/buzinga/mobile/shop/topwashtools.php' => '80'
    );
    $blocked = false;

    if (($script === '/shop/list.php' || $script === '/mobile/shop/list.php' || strpos($request_path, '/shop/list-') === 0)
        && redtoy_pg_review_is_hidden_category($ca_id)) {
        $blocked = true;
    } elseif (($script === '/shop/item.php' || $script === '/mobile/shop/item.php') && redtoy_pg_review_is_hidden_item_id($it_id)) {
        $blocked = true;
    } elseif ((in_array($script, array('/shop/listtype.php', '/mobile/shop/listtype.php'), true) && $type === '5') || $request_path === '/shop/type-5') {
        $blocked = true;
    } elseif ((in_array($script, array('/shop/event.php', '/mobile/shop/event.php'), true) || $request_path === '/shop/event.php') && $ev_id === '1711342008') {
        $blocked = true;
    } elseif (in_array($script, array('/shop/itemuselist.php', '/mobile/shop/itemuselist.php'), true) || $request_path === '/shop/itemuselist.php') {
        $blocked = true;
    } elseif (($script === '/bbs/board.php' && $bo_table === 'event') || $request_path === '/event') {
        $blocked = true;
    } elseif (isset($top_category_pages[$script]) && redtoy_pg_review_is_hidden_category($top_category_pages[$script])) {
        $blocked = true;
    }

    if ($blocked) {
        alert('검수 계정에서는 접근할 수 없습니다.', G5_URL.'/');
    }
}

redtoy_pg_review_block_access();
