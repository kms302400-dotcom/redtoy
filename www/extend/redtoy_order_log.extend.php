<?php
if (!defined('_GNUBOARD_')) exit;
require_once G5_LIB_PATH.'/redtoy_order_log.lib.php';
require_once G5_LIB_PATH.'/redtoy_checkout.lib.php';
add_replace('admin_menu','redtoy_order_log_menu',10,1);
function redtoy_order_log_menu($menus)
{
    if (!isset($menus['menu400'])) return $menus;
    foreach ($menus['menu400'] as $item) if ($item[0]==='400405') return $menus;
    foreach ($menus['menu400'] as $i=>$item) if ($item[0]==='400400') {
        array_splice($menus['menu400'],$i+1,0,array(array('400405','주문내역 로그',G5_ADMIN_URL.'/shop_admin/orderlog.php','redtoy_order_log',1)));
        break;
    }
    return $menus;
}
// Only known checkout entrypoints; IDs come from server sessions, never GET/POST.
$redtoy_log_script = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
$redtoy_log_paths = array('shop/danal_ready.php','shop/danal_ready_teledit.php','shop/orderformupdate.php','mobile/shop/orderformupdate.php');
foreach ($redtoy_log_paths as $redtoy_log_path) {
    if ($redtoy_log_script && $redtoy_log_script === realpath(G5_PATH.'/'.$redtoy_log_path)) {
        $GLOBALS['redtoy_order_log_context'] = array('id'=>redtoy_checkout_log_id(),'path'=>$redtoy_log_path);
        redtoy_order_log($GLOBALS['redtoy_order_log_context']['id'], 'submitted');
        add_event('alert','redtoy_order_log_alert',10,1);
        add_event('alert_close','redtoy_order_log_alert',10,1);
        register_shutdown_function('redtoy_order_log_shutdown');
        break;
    }
}
unset($redtoy_log_script,$redtoy_log_paths,$redtoy_log_path);
function redtoy_order_log_alert($message)
{
    $context=$GLOBALS['redtoy_order_log_context'] ?? array();
    $GLOBALS['redtoy_order_log_alerted'] = true;
    redtoy_order_log($context['id'] ?? '',redtoy_order_log_error_event($message));
}
function redtoy_order_log_shutdown()
{
    $context=$GLOBALS['redtoy_order_log_context'] ?? array();
    $id=$context['id'] ?? '';
    if (empty($GLOBALS['redtoy_order_log_alerted']) && ($context['path'] ?? '')==='shop/danal_ready.php' && isset($GLOBALS['RES_DATA']['RETURNCODE'])) {
        redtoy_order_log($id,(string)$GLOBALS['RES_DATA']['RETURNCODE']==='0000' ? 'payment_ready' : 'failed');
    }
    if (empty($GLOBALS['redtoy_order_log_alerted']) && ($context['path'] ?? '')==='shop/danal_ready_teledit.php' && isset($GLOBALS['Res']['Result'])) {
        redtoy_order_log($id,(string)$GLOBALS['Res']['Result']==='0' ? 'payment_ready' : 'failed');
    }
    $error=error_get_last();
    if ($error && in_array($error['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR),true)) redtoy_order_log($id,'failed');
}
