<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
function sh_banner($bn_gr_name) {
    global $g5, $is_mobile, $member;
    $info_group = sh_banner_get_info_group($bn_gr_name);
    if(!$info_group) return false;
    $bn_gr_id = $info_group['bn_gr_id'];
    // 사용기기에 따른 배너노출 확인
    if($is_mobile && !$info_group['bn_gr_mobile_use']) {
        return false;
    } else if(!$is_mobile && !$info_group['bn_gr_pc_use']) {
        return false;
    }
    // 레벨설정에 따른 배너노출 확인
    if($info_group['bn_gr_level_start'] > $member['mb_level']) {
        return false;
    } else if($info_group['bn_gr_level_end'] < $member['mb_level']) {
        return false;
    }

    // 노출 순서
    switch ($info_group['bn_order_opt']) {
        case 1:
            $order = ' bn_order desc, bn_id desc ';
            break;
        case 2:
            $order = ' bn_datetime desc, bn_id desc ';
            break;
        case 3:
            $order = ' bn_datetime asc, bn_id desc ';
            break;
        case 4:
            $order = ' rand() ';
            break;
        default:
            $order = ' bn_order desc, bn_id desc ';
    }
    $order = ' order by '.$order;

    // 갯수
    $info_group['bn_count_limit'] = trim($info_group['bn_count_limit']) + 0;
    if(!$info_group['bn_count_limit']) {
        $limit = '  ';
    } else {
        $limit = " limit 0, {$info_group['bn_count_limit']} ";
    }
    $list = sh_banner_get_list($bn_gr_id, $order, $limit);
    if(!$list) return;
    @$bn_gr_skin_set = json_decode($info_group['bn_gr_skin_set'], true);
    $sh_banner_skin_url = $g5['sh_banner_skin_url'].'/'.$info_group['bn_gr_skin'];
    $sh_banner_skin_path = $g5['sh_banner_skin_path'].'/'.$info_group['bn_gr_skin'];
    ob_start();
    include $sh_banner_skin_path.'/sh_banner.skin.php';
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

function sh_banner_get_list($bn_gr_id, $order, $limit) {
    global $g5, $member;
    $bn_gr_id = $bn_gr_id + 0;
    $where = array();
    $where[] = " bn_status = '1' ";
    $where[] = " bn_gr_id = '{$bn_gr_id}' ";
    $where[] = " bn_date_start <= '".G5_TIME_YMD."' ";
    $where[] = " bn_date_end >= '".G5_TIME_YMD."' ";
    $where = implode(' and ', $where);
    $list = array();
    $sql = " select * from {$g5['sh_banner_table']} where {$where} {$order} {$limit} ";
    $result = sql_query($sql);
    while($row=sql_fetch_array($result)) {
        $list[] = $row;
    }
    return $list;
}
function sh_banner_get_info_group($bn_gr_name) {
    global $g5;
    $sql = " select * from {$g5['sh_banner_group_table']} where bn_gr_name = '{$bn_gr_name}' ";
    $row = sql_fetch($sql);
    return $row;
}
?>