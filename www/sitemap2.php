<?php

include "./_common.php";

header("Content-Type: application/xml; charset=UTF-8");
ob_start();
echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'.PHP_EOL;
echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" 
xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd 
http://www.google.com/schemas/sitemap-image/1.1 
http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" 
xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

date_default_timezone_set('Asia/Seoul');
$site_lastmod = date('c');

// 메인 페이지
echo '<url>'.PHP_EOL;
echo '<loc>'.G5_URL.'</loc>'.PHP_EOL;
echo '<lastmod>'.$site_lastmod.'</lastmod>'.PHP_EOL;
echo '<changefreq>daily</changefreq>'.PHP_EOL;
echo '<priority>1.0</priority>'.PHP_EOL;
echo '</url>'.PHP_EOL;

// 카테고리 정보
$table = $g5['g5_shop_category_table'];
$sql = "SELECT 1 FROM information_schema.tables WHERE TABLE_NAME = '$table'";
$result = sql_fetch($sql);

if (!empty($result)) {
    $sql = "SELECT ca_id FROM {$table}";
    $items = sql_query($sql);

    while ($item = sql_fetch_array($items)) {
        echo '<url>'.PHP_EOL;
        echo '<loc>'.G5_SHOP_URL.'/list.php?ca_id='.$item['ca_id'].'</loc>'.PHP_EOL;
        echo '<lastmod>'.$site_lastmod.'</lastmod>'.PHP_EOL;
        echo '<changefreq>weekly</changefreq>'.PHP_EOL;
        echo '<priority>0.8</priority>'.PHP_EOL;
        echo '</url>'.PHP_EOL;
    }
}

function remove_invalid_xml_chars($str) {
    return preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $str);
}

function safe_cdata($str) {
    $clean = remove_invalid_xml_chars($str);
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $clean) . ']]>';
}

// 상품 정보
$table = $g5['g5_shop_item_table'];
$sql = "SELECT 1 FROM information_schema.tables WHERE TABLE_NAME = '$table'";
$result = sql_fetch($sql);

if (!empty($result)) {
    $sql = "SELECT it_id, it_update_time, it_name, it_img1 FROM {$table}";
    $items = sql_query($sql);

    while ($item = sql_fetch_array($items)) {
        $it_lastmod = (!empty($item['it_update_time']) && $item['it_update_time'] !== '0000-00-00 00:00:00')
            ? date("Y-m-d\TH:i:sP", strtotime($item['it_update_time']))
            : date("Y-m-d\TH:i:sP");

        echo '<url>'.PHP_EOL;
        echo '<loc>' . htmlspecialchars(G5_SHOP_URL . '/item.php?it_id=' . $item['it_id'], ENT_XML1, 'UTF-8') . '</loc>' . PHP_EOL;
        echo '<lastmod>' . $it_lastmod . '</lastmod>' . PHP_EOL;
        echo '<changefreq>weekly</changefreq>' . PHP_EOL;
        echo '<priority>0.7</priority>' . PHP_EOL;

        if (!empty($item['it_img1'])) {
            $image_url = G5_URL . '/data/item/' . $item['it_img1'];
            $item_name = !empty($item['it_name']) ? $item['it_name'] : '상품';

            $item_name_cdata = safe_cdata($item_name);

            echo '<image:image>' . PHP_EOL;
            echo '<image:loc>' . htmlspecialchars($image_url, ENT_XML1, 'UTF-8') . '</image:loc>' . PHP_EOL;
            echo '<image:title>' . $item_name_cdata . '</image:title>' . PHP_EOL;
            echo '</image:image>' . PHP_EOL;
        }

        echo '</url>' . PHP_EOL;
    }
}

echo '</urlset>'.PHP_EOL;
$xml = ob_get_clean();
echo $xml;
exit;
?>