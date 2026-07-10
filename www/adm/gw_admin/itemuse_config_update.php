<?php
$sub_menu = "700100";
include_once('./_common.php');

if (!$type || !$content) {
	echo 'error';
}

$file = G5_ADMIN_PATH."/gw_admin/itemuse_".$type.".txt";
$dat = trim($_POST['content']);

@mkdir(G5_ADMIN_PATH."/gw_admin", G5_DIR_PERMISSION);
@chmod(G5_ADMIN_PATH."/gw_admin", G5_DIR_PERMISSION);

$f = fopen($file, "w");
fwrite($f, $dat);
fclose($f);

echo '저장되었습니다.';
?>