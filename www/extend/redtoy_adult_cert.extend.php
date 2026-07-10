<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$is_member && get_session('ss_cert_adult') === '' && get_cookie('ss_cert_adult') === 'OK') {
    set_session('ss_cert_adult', 'OK');
}
