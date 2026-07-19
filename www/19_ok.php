<?php
include_once("./_common.php");
header('Content-Type: text/html; charset=UTF-8');
//set_session('ss_cert_adult',   "OK");

if (!function_exists('redtoy_validate_adult_return_url')) {
    function redtoy_validate_adult_return_url($url)
    {
        if (!is_string($url)) {
            return '/';
        }

        $url = trim($url);
        $decoded_url = rawurldecode($url);
        if ($url === ''
            || preg_match('#^https?://#i', $url)
            || substr($url, 0, 1) !== '/'
            || substr($url, 0, 2) === '//'
            || substr($decoded_url, 0, 2) === '//'
            || strpos($url, '\\') !== false
            || strpos($decoded_url, '\\') !== false
            || preg_match('/[\x00-\x1F\x7F]/', $decoded_url)) {
            return '/';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return '/';
        }

        $normalized_segments = array();
        foreach (explode('/', rawurldecode($path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($normalized_segments);
                continue;
            }
            $normalized_segments[] = $segment;
        }

        $normalized_path = '/'.implode('/', $normalized_segments);
        if (preg_match('#^/(?:19_ok|19m)\.php(?:/|$)#i', $normalized_path)) {
            return '/';
        }

        return $url;
    }
}

$cert_url = get_session('ss_cert_url');
set_session('ss_cert_url', '');
$cert_url = redtoy_validate_adult_return_url($cert_url);

goto_url($cert_url);
