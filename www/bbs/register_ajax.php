<?
    include_once('./_common.php');
    
    if (!isset($_SESSION["register_secure"])) exit;
    if ($_SESSION["register_secure"] + (60 * 60 * 4) < time()) exit;
    
    $userid = isset($_REQUEST['userid']) ? preg_replace('/[^0-9a-zA-Z]+$/', '', $_REQUEST['userid']) : "";
    if ($userid) {
        $sql = " SELECT count(mb_id) as cnt from {$g5['member_table']} WHERE mb_id = '" . $userid . "' ";
        $row = sql_fetch($sql);
        if ($row['cnt']) {
            echo "이미 사용중인 아이디 입니다.";
        }
    }
    exit;
?>