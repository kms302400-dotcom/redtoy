<?php

function get_image_resource_from_file ($path_file){

    if (!is_file($path_file)) {

        $GLOBALS['errormsg'] = $path_file . '¿∫ ∆ƒ¿œ¿Ã æ∆¥’¥œ¥Ÿ.';

        return Array();
    }

    $size = @getimagesize($path_file);
    if (empty($size[2])) {

        $GLOBALS['errormsg'] = $path_file . '¿∫ ¿ÃπÃ¡ˆ ∆ƒ¿œ¿Ã æ∆¥’¥œ¥Ÿ.';

        return Array();
    }

    if ($size[2] != 1 && $size[2] != 2 && $size[2] != 3) {

        $GLOBALS['errormsg'] = $path_file . '¿∫ gif ≥™ jpg, png ∆ƒ¿œ¿Ã æ∆¥’¥œ¥Ÿ.';

        return Array();
    }

    switch($size[2]){

        case 1 : //gif

            $im = @imagecreatefromgif($path_file);
            break;

        case 2 : //jpg

            $im = @imagecreatefromjpeg($path_file);
            break;

        case 3 : //png

            $im = @imagecreatefrompng($path_file);
            break;
    }

    if ($im === false) {

        $GLOBALS['errormsg'] = $path_file . ' ø°º≠ ¿ÃπÃ¡ˆ ∏Æº“Ω∫∏¶ ∞°¡Æø¿¥¬ ∞Õø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.';

        return Array();
    }
    else {

        $return = $size;
        $return[0] = $im;
        $return[1] = $size[0];
        $return[2] = $size[1];
        $return[3] = $size[2];
        $return[4] = $size[3];

        return $return;
    }
}


function save_image_from_resource ($im, $path_save_file, $quality=70, $save_force=0){

    $path_save_dir = dirname($path_save_file);
    if (!is_dir($path_save_dir)) {

        $GLOBALS['errormsg'] = $path_save_dir . '¿∫ µ∑∫≈‰∏Æ∞° æ∆¥’¥œ¥Ÿ.';

        return false;
    }

    if (!is_writable($path_save_dir)){

        $GLOBALS['errormsg'] = $path_save_dir . 'ø° ¿ÃπÃ¡ˆ∏¶ ¿˙¿Â«“ ±««—¿Ã æ¯Ω¿¥œ¥Ÿ.';

        return false;
    }

    if (is_dir($path_save_file)) {

        $GLOBALS['errormsg'] = $path_save_file . '¿∫ ¿ÃπÃ ∞∞¿∫ ¿Ã∏ß¿« µ∑∫≈‰∏Æ∞° ¡∏¿Á«’¥œ¥Ÿ.';

        return false;
    }

    if (is_file($path_save_file)){

        if ($save_force == 1) {

            return true;
        }
        else if ($save_force == 2){

            $result_unlink = @unlink($path_save_file);
            if ($result_unlink === false) {

                $GLOBALS['errormsg'] = '±‚¡∏ø° ¡∏¿Á«œ¥¯ ' . $path_save_file . '¿« ªË¡¶ø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.';

                return false;
            }
        }
        else {

            $GLOBALS['errormsg'] = $path_save_file . '¿∫ ¿ÃπÃ ∞∞¿∫ ¿Ã∏ß¿« ∆ƒ¿œ¿Ã ¡∏¿Á«’¥œ¥Ÿ.';

            return false;
        }
    }

    $extension = strtolower(substr($path_save_file, strrpos($path_save_file, '.') + 1));

    switch($extension){

        case 'gif' :

            $result_save = @imagegif($im, $path_save_file);
            break;

        case 'jpg' :

        case 'jpeg' :

            $result_save = @imagejpeg($im, $path_save_file, $quality);
            break;

        default :

            $result_save = @imagepng($im, $path_save_file);
    }

    if ($result_save === false) {

        $GLOBALS['errormsg'] = $path_save_file . '¿« ¿˙¿Âø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.';

        return false;
    }
    else {

        return true;
    }
}

function get_size_by_rule($src_w, $src_h, $dst_size, $rule='width'){

    //¡§ºˆ«¸¿Ã æ∆¥œ∂Û∏È ¡§ºˆ«¸¿∏∑Œ ∞≠¡¶ «¸∫Ø»Ø
    if (!is_int($src_w)) settype($src_w, 'int');
    if (!is_int($src_h)) settype($src_h, 'int');
    if (!is_int($dst_size)) settype($dst_size, 'int');

    if ($src_w < 1 || $src_h < 1){

        $GLOBALS['errormsg'] = "ø¯∫ª¿« ≥ ∫ÒøÕ ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($src_w, $src_h)";

        return false;
    }

    if ($dst_size < 1){

        $GLOBALS['errormsg'] = "∏ÆªÁ¿Ã¡Óµ… ªÁ¿Ã¡Ó∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($dst_size)";

        return false;
    }

    if ($rule != 'height') {

        return ceil($dst_size / $src_w * $src_h);
    }
    else {

        return ceil($dst_size / $src_h * $src_w);
    }
}

function get_bigsize_by_rule($dst_w, $dst_h, $src_size, $rule='width'){

    if (!is_int($dst_w)) settype($dst_w, 'int');
    if (!is_int($dst_h)) settype($dst_h, 'int');
    if (!is_int($src_size)) settype($src_size, 'int');

    if ($dst_w < 1 || $dst_h < 1){

        $GLOBALS['errormsg'] = "ΩÊ≥◊¿œ¿« ≥ ∫ÒøÕ ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($dst_w, $dst_h)";

        return false;
    }

    if ($src_size < 1){

        $GLOBALS['errormsg'] = "ø¯∫ª¿« ªÁ¿Ã¡Ó∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($src_size)";

        return false;
    }

    if ($rule != 'height') {

        return ceil($src_size / $dst_w * $dst_h);
    }
    else {

        return ceil($src_size / $dst_h * $dst_w);
    }
}

function get_image_resize($src, $src_w, $src_h, $dst_w, $dst_h=0){

    if (empty($src))    {

        $GLOBALS['errormsg'] = 'ø¯∫ª ∏Æº“Ω∫∞° æ¯Ω¿¥œ¥Ÿ.';

        return false;
    }

    //¡§ºˆ«¸¿Ã æ∆¥œ∂Û∏È ¡§ºˆ«¸¿∏∑Œ ∞≠¡¶ «¸∫Ø»Ø
    if (!is_int($src_w)) settype($src_w, 'int');
    if (!is_int($src_h)) settype($src_h, 'int');
    if (!is_int($dst_w)) settype($dst_w, 'int');
    if (!is_int($dst_h)) settype($dst_h, 'int');

    if ($src_w < 1 || $src_h < 1){

        $GLOBALS['errormsg'] = "ø¯∫ª¿« ≥ ∫ÒøÕ ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($src_w, $src_h)";

        return false;
    }

    if (empty($dst_w) && empty($dst_h)) {

        $GLOBALS['errormsg'] = 'ΩÊ≥◊¿œ¿« ≥ ∫ÒøÕ ≥Ù¿Ã¥¬ µ—¡ﬂø° «œ≥™¥¬ π›µÌ¿Ã ¿÷æÓæﬂ «’¥œ¥Ÿ.';

        return false;
    }

    if (!empty($dst_w) && $dst_w < 1){

        $GLOBALS['errormsg'] = "ΩÊ≥◊¿œ¿« ≥ ∫Ò∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($dst_w)";

        return false;
    }

    if (!empty($dst_h) && $dst_h < 1){

        $GLOBALS['errormsg'] = "ΩÊ≥◊¿œ¿« ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($dst_h)";

        return false;
    }


    if (empty($dst_w) || empty($dst_h)) {

        if (empty($dst_h)) $dst_h = get_size_by_rule($src_w, $src_h, $dst_w, 'width');
        else $dst_w = get_size_by_rule($src_w, $src_h, $dst_h, 'height');
    }


    $dst = @imagecreatetruecolor ($dst_w , $dst_h);
    if ($dst === false) {

        $GLOBALS['errormsg'] = "$dst_w , $dst_h ≈©±‚¿« ΩÊ≥◊¿œ ∏Æº“Ω∫∏¶ ª˝º∫«œ¡ˆ ∏¯«ﬂΩ¿¥œ¥Ÿ.";

        return false;
    }


    $result_resize = imagecopyresampled ($dst , $src , 0 , 0 , 0 , 0 , $dst_w , $dst_h , $src_w , $src_h );
    if ($result_resize === false) {

        $GLOBALS['errormsg'] = "$dst_w , $dst_h ≈©±‚∑Œ ∏ÆªÁ¿Ã¡Óø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.";

        return false;
    }

    return $dst;
}


function get_image_cropresize($src, $src_w, $src_h, $dst_w, $dst_h=0, $pos_width=2, $pos_height=2){

    if (empty($src))    {

        $GLOBALS['errormsg'] = 'ø¯∫ª ∏Æº“Ω∫∞° æ¯Ω¿¥œ¥Ÿ.';

        return false;
    }

    if (!is_int($src_w)) settype($src_w, 'int');
    if (!is_int($src_h)) settype($src_h, 'int');
    if (!is_int($dst_w)) settype($dst_w, 'int');
    if (!is_int($dst_h)) settype($dst_h, 'int');

    if ($src_w < 1 || $src_h < 1){

        $GLOBALS['errormsg'] = "ø¯∫ª¿« ≥ ∫ÒøÕ ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($src_w, $src_h)";

        return false;
    }

    if (empty($dst_w) && empty($dst_h)) {

        $GLOBALS['errormsg'] = 'ΩÊ≥◊¿œ¿« ≥ ∫ÒøÕ ≥Ù¿Ã¥¬ µ—¡ﬂø° «œ≥™¥¬ π›µÌ¿Ã ¿÷æÓæﬂ «’¥œ¥Ÿ.';

        return false;
    }

    if (!empty($dst_w) && $dst_w < 1){

        $GLOBALS['errormsg'] = "ΩÊ≥◊¿œ¿« ≥ ∫Ò∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($dst_w)";

        return false;
    }

    if (!empty($dst_h) && $dst_h < 1){

        $GLOBALS['errormsg'] = "ΩÊ≥◊¿œ¿« ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($dst_h)";

        return false;
    }

    if (empty($dst_w) || empty($dst_h)) {

        if (empty($dst_h)) $dst_h = get_size_by_rule($src_w, $src_h, $dst_w, 'width');
        else $dst_w = get_size_by_rule($src_w, $src_h, $dst_h, 'height');
    }

    $dst = @imagecreatetruecolor ($dst_w , $dst_h);
    if ($dst === false) {

        $GLOBALS['errormsg'] = "$dst_w , $dst_h ≈©±‚¿« ΩÊ≥◊¿œ ∏Æº“Ω∫∏¶ ª˝º∫«œ¡ˆ ∏¯«ﬂΩ¿¥œ¥Ÿ.";

        return false;
    }

    $s_w = $dst_w;
    $s_h = get_size_by_rule($src_w, $src_h, $s_w, 'width');


    $src_x = 0;
    $src_y = 0;
    $src_nw = $src_w;
    $src_nh = $src_h;


    if ($dst_h != $s_h) {

        if ($dst_h < $s_h) {

            $src_nh = get_bigsize_by_rule($dst_w, $dst_h, $src_w, 'width');

            $src_x = 0;

            if ($pos_height == 1) $src_y = 0;
            else if ($pos_height == 2) $src_y = ceil(($src_h - $src_nh) / 2);
            else $src_y = $src_h - $src_nh;
        }
        else {

            $src_nw = get_bigsize_by_rule($dst_w, $dst_h, $src_h, 'height');

            if ($pos_width == 1) $src_x = 0;
            else if ($pos_width == 2) $src_x = ceil(($src_w - $src_nw) / 2);
            else $src_x = $src_w - $src_nw;

            $src_y = 0;
        }
    }

    $result_resize = imagecopyresampled ($dst , $src , 0 , 0 , $src_x , $src_y , $dst_w , $dst_h , $src_nw , $src_nh );
    if ($result_resize === false) {

        $GLOBALS['errormsg'] = "$dst_w , $dst_h ≈©±‚∑Œ ≈©∑” π◊ ∏ÆªÁ¿Ã¡Óø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.";

        return false;
    }

    return $dst;
}

function proc_watermark($src, $src_w, $src_h, $path_mark_file, $pos, $sharpness, $padding=0){

    if (empty($src))    {

        $GLOBALS['errormsg'] = 'ø¯∫ª ∏Æº“Ω∫∞° æ¯Ω¿¥œ¥Ÿ.';

        return false;
    }

    if (!is_int($src_w)) settype($src_w, 'int');
    if (!is_int($src_h)) settype($src_h, 'int');
    if (!is_int($sharpness)) settype($sharpness, 'int');
    if (!is_int($padding)) settype($padding, 'int');


    if ($src_w < 1 || $src_h < 1){

        $GLOBALS['errormsg'] = "ø¯∫ª¿« ≥ ∫ÒøÕ ≥Ù¿Ã∞° 0∫∏¥Ÿ ≈´ ¡§ºˆ∞° æ∆¥’¥œ¥Ÿ. ($src_w, $src_h)";

        return false;
    }



    if (empty($path_mark_file)) {

        $GLOBALS['errormsg'] = 'øˆ≈Õ∏∂≈© ¿ÃπÃ¡ˆ∞Ê∑Œ∞™¿Ã æ¯Ω¿¥œ¥Ÿ.';

        return false;
    }

    list($mark, $mark_w, $mark_h) = get_image_resource_from_file ($path_mark_file);

    if (empty($mark)) return false;



    if ($src_w < $mark_w + (2 * $padding)) {

        return true;
    }

    if ($src_h < $mark_h + (2 * $padding)) {

        return true;
    }



    if ($sharpness < 0 || $sharpness > 100) $sharpness = 30;

    if ($padding < 0 || $padding > $mark_w || $padding > $mark_h) $padding = 10;



    if ($pos == 10) {

        $w_max = $src_w - $padding;
        $h_max = $src_h - $padding;

        $x_max = ceil($w_max / ($mark_w + $padding));

        $y_max = ceil($h_max / ($mark_h + $padding));

        for($x = 0; $x < $x_max; $x++){

            for($y = 0; $y < $y_max; $y++){

                $src_x = $x * ($mark_w + $padding) + $padding;
                $src_y = $y * ($mark_h + $padding) + $padding;

                $copy_w = $mark_w;
                $copy_h = $mark_h;

                if ($src_x + $mark_w > $w_max) $copy_w = $w_max - $src_x;
                if ($src_y + $mark_h > $h_max) $copy_h = $h_max - $src_y;

                if ($sharpness != 100) {

                    $result_watermark = imagecopymerge($src, $mark, $src_x, $src_y, 0, 0, $copy_w, $copy_h, $sharpness);
                }
                else {

                    $result_watermark = imagecopyresampled ($src , $mark , $src_x, $src_y, 0 , 0 , $copy_w, $copy_h , $copy_w, $copy_h);
                }

                if ($result_watermark === false) {

                    @imagedestroy($mark);

                    $GLOBALS['errormsg'] = "øˆ≈Õ∏∂≈© √≥∏Æø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.";

                    return false;
                }
            }
        }
    }
    else {

        $copy_w = $mark_w;
        $copy_h = $mark_h;

        switch($pos){

            case 1 :

                $src_x = 0 + $padding;
                $src_y = 0 + $padding;

                break;

            case 2 :

                $src_x = $src_w - $mark_w - $padding;
                $src_y = 0 + $padding;

                break;

            case 3 :

                $src_x = 0 + $padding;
                $src_y = $src_h - $mark_h - $padding;

                break;

            case 4 :

                $src_x = $src_w - $mark_w - $padding;
                $src_y = $src_h - $mark_h - $padding;

                break;

            case 5 :

                $src_x = ceil(($src_w - $mark_w) / 2);
                $src_y = ceil(($src_h - $mark_h) / 2);

                break;

            default :

                $src_x = 0 + $padding;
                $src_y = 0 + $padding;

        }

        if ($sharpness != 100) {

            $result_watermark = imagecopymerge($src, $mark, $src_x, $src_y, 0, 0, $copy_w, $copy_h, $sharpness);
        }
        else {

            $result_watermark = imagecopyresampled ($src , $mark , $src_x, $src_y, 0 , 0 , $copy_w, $copy_h , $copy_w, $copy_h);
        }

        @imagedestroy($mark);

        if ($result_watermark === false) {

            $GLOBALS['errormsg'] = "øˆ≈Õ∏∂≈© √≥∏Æø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.";

            return false;
        }
    }

    return true;
}


function get_remote_image($url, $referer='') {

    $url_info = parse_url($url);

    $url_info['scheme'] = strtolower($url_info['scheme']);
    if ($url_info['scheme'] != 'http' && $url_info['scheme'] != 'https') {

        $GLOBALS['errormsg'] = "¡§ªÛ¿˚¿Œ ¡÷º“∞° æ∆¥’¥œ¥Ÿ.";

        return false;
    }

    if (empty($url_info['port'])) {

        if ($url_info['scheme'] == 'http') $url_info['port'] = '80';
        else $url_info['port'] = '443';
    }

    if (empty($url_info['path'])) $url_info['path'] = '/';
    if (!empty($url_info['query'])) $url_info['query'] = '?' . $url_info['query'];

    if (empty($referer)) {

        $referer = $url_info['scheme'] . '://' . $url_info['host'];
        if ($url_info['port'] != 80 && $url_info['port'] != 443) $referer .= ':' . $url_info['port'];
        $referer .= '/';
    }

    if ($url_info['scheme'] == 'http') $fp = fsockopen($url_info['host'], $url_info['port'], $errno, $errstr, 30);
    else $fp = fsockopen('ssl://' . $url_info['host'], $url_info['port'], $errno, $errstr, 30);

    if ($fp) {

        $put = "GET " . $url_info['path'] . $url_info['query'] . " HTTP/1.1\r\n";
        $put .= "Host: " . $url_info['host'] . "\r\n";
        $put .= "User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:10.0.2) Gecko/20100101 Firefox/10.0.2\r\n";
        $put .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
        $put .= "Accept-Language: ko-kr,ko;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
        $put .= "Referer: " . $referer . "\r\n";
        $put .= "Connection: Close\r\n\r\n";

        fwrite($fp, $put);

        $header = $status = $return = '';
        while (!feof($fp)) {

            $header_ = fgets($fp, 128);
            $header .= $header_;

            if ($header_ == "\r\n") break;

            if (empty($status)) {

                preg_match("`^HTTP/[^\s]*\s+([0-9]+)\s`", $header_, $status);
                $status = $status[1];
            }
            else {

                if ($status == 302) {

                    preg_match("`^(Location:|URI:)\s+(.*)`", rtrim($header_), $redirect);
                    $redirect = $redirect[2];

                    if (!empty($redirect)) {

                        if(!preg_match("|\:\/\/|", $redirect)) {

                            $redirect_ = $url_info['scheme'] . '://' . $url_info['host'];
                            if ($url_info['port'] != 80 && $url_info['port'] != 443) $redirect_ .= ':' . $url_info['port'];

                            if(!preg_match("`^/`", $redirect))
                                $redirect_ .= '/' . $redirect;
                            else
                                $redirect_ .= $redirect;

                            $redirect = $redirect_;
                        }

                        break;
                    }
                }
                else if ($status != 200){

                    break;
                }
            }
        }

        if ($status == 302 && !empty($redirect)) {

            fclose($fp);
            return get_remote_image($redirect, $referer);
        }
        else if ($status != 200) {

            fclose($fp);

            $GLOBALS['errormsg'] = "¿¿¥‰¿Ã 200 ¿Ã æ∆¥’¥œ¥Ÿ. " . $status;

            return false;
        }

        while (!feof($fp)) {

            $return .= fgets($fp, 128);
        }
        fclose($fp);
    }
    else  {

        $GLOBALS['errormsg'] = "º“ƒœ ¡¢º”ø° Ω«∆–«œø¥Ω¿¥œ¥Ÿ.";

        return false;
    }

    return $return;
}

function create_thumbnail($path_src_file_or_url, $path_save_file, $save_w, $save_h=0, $options=Array()){


    $save_quality = 100;
    $save_force = 2;

    $crop_use = 0;
    $crop_pos_width = 2;
    $crop_pos_height = 1;

    $watermark_path_file = '';
    $watermark_pos = 4;
    $watermark_sharpness = 30;
    $watermark_padding = 10;

    if (!empty($options)) @extract($options);

    $path_src_file_or_url = trim($path_src_file_or_url);
    if (preg_match("`^http`i", $path_src_file_or_url)) {

        $remote_image_text = get_remote_image($path_src_file_or_url);
        if (empty($remote_image_text)){

            return false;
        }

        $path_temp_dir = sys_get_temp_dir();
        $path_src_file = tempnam($path_temp_dir, "RI");

        $fp = @fopen($path_src_file, "w");
        @fwrite($fp, $remote_image_text);
        @fclose($fp);

        list($src, $src_w, $src_h) = get_image_resource_from_file ($path_src_file);

        @unlink($path_src_file);
    }
    else {

        $path_src_file = $path_src_file_or_url;

        list($src, $src_w, $src_h) = get_image_resource_from_file ($path_src_file);
    }

    if (empty($src)) return false;

    if ($crop_use == 1) {

        $dst = get_image_cropresize($src, $src_w, $src_h, $save_w, $save_h, $crop_pos_width, $crop_pos_height);
    }
    else {

        $dst = get_image_resize($src, $src_w, $src_h, $save_w, $save_h);
    }

    @imagedestroy($src);
    if (empty($dst)) return false;

    $save_w = imagesx($dst);
    $save_h = imagesy($dst);

    if (!empty($watermark_path_file) && is_file($watermark_path_file)) {

        $result_watermark = proc_watermark($dst, $save_w, $save_h, $watermark_path_file, $watermark_pos, $watermark_sharpness, $watermark_padding);

        if (empty($result_watermark)) return false;
    }

    $result_save = save_image_from_resource ($dst, $path_save_file, $save_quality, $save_force);

    @imagedestroy($dst);

    return $result_save;
}

?>