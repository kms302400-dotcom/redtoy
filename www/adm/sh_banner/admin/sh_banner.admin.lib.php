<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

function sh_banner_get_group_list() {
    global $g5;
    $sql = " select * from `{$g5['sh_banner_group_table']}` order by bn_gr_order desc ";
    $result = sql_query($sql);
    $list = array();
    while($row = sql_fetch_array($result)) {
        $list[$row['bn_gr_id']] = $row;
    }
    return $list;
}
// 스킨디렉토리를 SELECT 형식으로 얻음
function sh_banner_get_skin_select($id, $name, $selected='', $event='')
{
    global $g5;
    $skins = array();
    $dirname = $g5['sh_banner_skin_path'].'/';
    if(!is_dir($dirname)) return;

    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if($file == '.'||$file == '..') continue;
        if (is_dir($dirname.$file)) $skins[] = $file;
    }
    closedir($handle);
    sort($skins);
    $str = "<select id=\"$id\" name=\"$name\" $event class=\"required\">\n";
    for ($i=0; $i<count($skins); $i++) {
        if ($i == 0) $str .= "<option value=\"\">선택</option>";
        $text = $skins[$i];
        $str .= option_selected($skins[$i], $selected, $text);
    }
    $str .= "</select>";
    return $str;
}

?>