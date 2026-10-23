#!/usr/bin/env python3
"""Synthetic local HTTP integration; no production bootstrap, DB or external API."""
from pathlib import Path
from http.cookies import SimpleCookie
from email.utils import parsedate_to_datetime
import base64
import json
import os
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1] / 'www'
PHP = os.environ.get('REDTOY_TEST_PHP') or shutil.which('php')
CHECKS = 0


def check(ok, message):
    global CHECKS
    CHECKS += 1
    if not ok:
        raise AssertionError(message)


BOOTSTRAP = r'''<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Seoul');
define('_GNUBOARD_', true);
define('G5_SERVER_TIME', time());
define('G5_COOKIE_DOMAIN', '');
define('G5_URL', 'http://fixture.invalid');
define('G5_BBS_URL', G5_URL.'/bbs');
define('G5_ADMIN_URL', G5_URL.'/adm');
define('G5_DATA_PATH', __DIR__.'/data');
define('G5_LIB_PATH', __DIR__.'/lib');
define('G5_TABLE_PREFIX', 'test_');
$_SERVER['SERVER_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = 'redtoy-synthetic-http';
// The local HTTP client deliberately sends these Secure cookies only to loopback.
$_SERVER['HTTPS'] = 'on';
$root = getenv('REDTOY_AUTO_TEST_ROOT');
$source = file_get_contents($root.'/lib/common.lib.php');
function real_function($source, $name) {
    $tokens = token_get_all($source);
    for ($i=0; $i<count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j = $i+1;
        while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
        $body = ''; $depth = 0; $opened = false;
        for ($k=$i; $k<count($tokens); $k++) {
            $token = $tokens[$k];
            $body .= is_array($token) ? $token[1] : $token;
            if ($token === '{') { $opened = true; $depth++; }
            if ($token === '}' && --$depth === 0 && $opened) return $body;
        }
    }
    throw new RuntimeException('Missing real function '.$name);
}
foreach (['set_session','get_session','set_cookie','get_cookie','is_admin',
    'ss_mb_key','verify_mb_key','generate_mb_key','get_random_token_string',
    'get_token_encryption_key','update_auth_session_token','check_auth_session_token'] as $name) {
    eval(real_function($source, $name));
}
$admin_source = file_get_contents($root.'/adm/admin.lib.php');
foreach (['auth_check_menu','auth_check','get_admin_token','check_admin_token'] as $name) eval(real_function($admin_source, $name));
session_save_path(__DIR__.'/sessions');
session_start();
$config = ['cf_admin'=>'chief','cf_use_point'=>0,'cf_use_email_certify'=>1];
$g5 = ['member_table'=>'synthetic_members','auth_table'=>'synthetic_auth','session_cookie_samesite'=>'Lax'];
$group = $board = [];
$member_skin_path = __DIR__.'/empty-skin';
$url = isset($_POST['url']) ? $_POST['url'] : '';
$bo_table = '';
function run_replace($tag, $value, ...$args) { return $value; }
function run_event(...$args) {}
function check_url_host($url, ...$args) {
    if (parse_url($url, PHP_URL_HOST) !== 'fixture.invalid') throw new RuntimeException('Unexpected fixture redirect host');
}
function is_use_email_certify() { return true; }
function get_member($id) {
    if (!in_array($id, ['customer','chief','staff','blocked','withdrawn','unverified'], true)) return [];
    $hash = file_get_contents(__DIR__.(isset($_GET['changed_password']) ? '/new-hash' : '/hash'));
    return ['mb_id'=>$id,'mb_password'=>$hash,'mb_datetime'=>'2020-01-01 00:00:00',
        'mb_intercept_date'=>$id==='blocked'?'20000101':'',
        'mb_leave_date'=>$id==='withdrawn'?'20000101':'',
        'mb_email_certify'=>$id==='unverified'?'':'2020-01-01 00:00:00',
        'mb_ip'=>'127.0.0.1','mb_recommend'=>'synthetic-recommender',
        'mb_nick'=>'Synthetic','mb_email'=>'synthetic@example.invalid','mb_adult'=>0];
}
function login_password_check($mb, $plain, $hash) { return password_verify($plain, $hash); }
function respond($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function alert($message, ...$args) { respond(['denied'=>$message,'mail_calls'=>$GLOBALS['mail_calls']??0]); }
function alert_close($message) { alert($message); }
function confirm($message, ...$args) { alert($message); }
function goto_url($url) { respond(['redirect'=>$url,'session'=>(object)$_SESSION]); }
function sql_fetch($sql) {
    if (!preg_match("/where mb_id = '([^']*)'/", $sql, $m)) throw new RuntimeException('Unexpected SQL');
    return get_member($m[1]) ?: ['mb_password'=>''];
}
function sql_query($sql) {
    if (strpos($sql, 'synthetic_auth') === false) throw new RuntimeException('Unexpected database write');
    return new ArrayIterator(($GLOBALS['member']['mb_id']??'') === 'staff' && !isset($_GET['revoked'])
        ? [['au_menu'=>'400400','au_auth'=>'r']] : []);
}
function sql_fetch_array($rows) {
    if (!$rows->valid()) return false;
    $row = $rows->current(); $rows->next(); return $row;
}
function mailer(...$args) { $GLOBALS['mail_calls'] = ($GLOBALS['mail_calls']??0)+1; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/login') {
    include $root.'/bbs/login_check.php';
} elseif ($path === '/restore') {
    // Execute the actual restoration block. Valid restoration exits for the real reload response.
    $common = file_get_contents($root.'/common.php');
    $start = strpos($common, '    // 자동로그인 ---------------------------------------');
    $end = strpos($common, '    // 자동로그인 end ---------------------------------------', $start);
    if ($start === false || $end === false) throw new RuntimeException('Restore boundaries changed');
    eval(substr($common, $start, $end-$start));
    respond(['restored'=>false,'session'=>(object)$_SESSION]);
} elseif ($path === '/admin') {
    $member = get_member(get_session('ss_mb_id'));
    $member = $member ?: ['mb_id'=>''];
    $is_admin = is_admin($member['mb_id']);
    $start = strpos($admin_source, '// 접근 권한 검사');
    $end = strpos($admin_source, 'if (isset($auth) && is_array($auth))', $start);
    eval(substr($admin_source, $start, $end-$start));
    auth_check_menu($auth??[], '400400', $_GET['attr']??'r');
    if (($_GET['csrf']??'') === 'issue') respond(['token'=>get_admin_token()]);
    if (($_GET['csrf']??'') === 'check') check_admin_token();
    respond(['admin_allowed'=>true,'role'=>$is_admin]);
} elseif ($path === '/logout') {
    include $root.'/bbs/logout.php';
} elseif ($path === '/mark-adult') {
    $_SESSION['ss_cert_adult'] = 'OK'; respond(['marked'=>true]);
} elseif ($path === '/tamper-session') {
    $_SESSION['ss_mb_key'] = 'synthetic-invalid-key'; respond(['tampered'=>true]);
} elseif ($path === '/state') {
    $member = get_member(get_session('ss_mb_id'));
    respond(['session'=>(object)$_SESSION,'mb_adult'=>$member['mb_adult']??null,
        'auth_token_valid'=>$member ? check_auth_session_token($member['mb_datetime']) : false]);
} else {
    respond(['fixture'=>true]);
}
'''


class Browser:
    def __init__(self, address):
        self.address = address
        self.cookies = {}
        self.agent = 'RedtoySyntheticBrowser/1'
        self.headers = []

    def request(self, route, post=None):
        headers = {'User-Agent': self.agent}
        if self.cookies:
            headers['Cookie'] = '; '.join(k+'='+v['value'] for k, v in self.cookies.items())
        data = urllib.parse.urlencode(post, doseq=True).encode() if post is not None else None
        req = urllib.request.Request(self.address+route, data=data, headers=headers)
        with urllib.request.urlopen(req, timeout=5) as response:
            self.headers = response.headers.get_all('Set-Cookie', [])
            for line in self.headers:
                parsed = SimpleCookie()
                parsed.load(line)
                for k, v in parsed.items():
                    expires = parsedate_to_datetime(v['expires']).timestamp() if v['expires'] else None
                    if expires is not None and expires <= time.time():
                        self.cookies.pop(k, None)
                    else:
                        self.cookies[k] = {'value': v.value, 'expires': expires,
                                           'secure': bool(v['secure']), 'httponly': bool(v['httponly'])}
            body = response.read().decode()
        try:
            return json.loads(body)
        except json.JSONDecodeError:
            return {'html': body}

    def close_browser(self):
        self.cookies = {k: v for k, v in self.cookies.items()
                        if v['expires'] is not None and v['expires'] > time.time()}

    def login(self, who='customer', auto='1', password='synthetic-only-password'):
        data = {'mb_id': who, 'mb_password': password}
        if auto is not None:
            data['auto_login'] = auto
        return self.request('/login', data)


def cookie_name(name):
    import hashlib
    return hashlib.md5(name.encode()).hexdigest()


def run_tests(address):
    for who in ['customer', 'chief', 'staff']:
        browser = Browser(address)
        result = browser.login(who)
        check('redirect' in result, who+' direct login succeeds')
        check(result['session']['ss_cert_adult'] == 'OK', 'existing direct-login adult flow retained')
        for key in ['ck_mb_id', 'ck_auto']:
            cookie = browser.cookies[cookie_name(key)]
            check(abs(cookie['expires']-time.time()-86400*365) < 10, who+' 365-day expiry')
            check(cookie['secure'] and cookie['httponly'], who+' Secure/HttpOnly retained')
        browser.close_browser()
        check('PHPSESSID' not in browser.cookies, 'real browser-close simulation removes session cookie')
        restored = browser.request('/restore')
        check('window.location.reload()' in restored.get('html', ''), who+' actual restore branch succeeds')
        state = browser.request('/state')
        check(state['session']['ss_mb_id'] == who, who+' login restored')
        check(state['auth_token_valid'], who+' session auth token restored')
        check('ss_cert_adult' not in state['session'] and state['mb_adult'] == 0,
              who+' restoration does not fabricate adult certification')
        admin = browser.request('/admin')
        if who == 'customer':
            check('denied' in admin, 'customer cannot enter admin')
        else:
            check(admin.get('admin_allowed'), who+' actual admin client gate passes')
            if who == 'staff':
                check('denied' in browser.request('/admin?attr=w'), 'staff remains read-only')
                check('denied' in browser.request('/admin?revoked=1'), 'revoked staff rights denied')
        browser.request('/logout')
        check(cookie_name('ck_auto') not in browser.cookies and cookie_name('ck_mb_id') not in browser.cookies,
              who+' logout clears persistence cookies')
        browser.close_browser()
        check(not browser.request('/restore').get('restored', True), who+' logout prevents restoration')

    for choice in [None, '0', 'invalid']:
        browser = Browser(address)
        browser.login()
        result = browser.login(auto=choice)
        check('redirect' in result, 'unchecked/invalid selection preserves normal login')
        check(cookie_name('ck_auto') not in browser.cookies and cookie_name('ck_mb_id') not in browser.cookies,
              'unchecked/invalid selection clears previous persistence cookies')
        browser.close_browser()
        check(not browser.request('/restore').get('restored', True), 'unchecked does not restore')
    browser = Browser(address)
    check('redirect' in browser.login(auto='on'), 'legacy checkbox on value supported')
    browser.close_browser()
    check('window.location.reload()' in browser.request('/restore').get('html', ''), 'legacy value restores')

    for denied in ['blocked', 'withdrawn', 'unverified', 'missing']:
        browser = Browser(address)
        browser.login()
        browser.close_browser()
        # Same synthetic password hash lets the test exercise each account guard with a valid cookie key.
        browser.cookies[cookie_name('ck_mb_id')]['value'] = base64.b64encode(denied.encode()).decode()
        check(not browser.request('/restore').get('restored', True), denied+' restore denied')
        check(not browser.request('/state')['session'].get('ss_mb_id'), denied+' no identity set')
        check('denied' in Browser(address).login(denied), denied+' direct login remains denied')

    for change in ['password', 'agent', 'tamper']:
        browser = Browser(address)
        browser.login('chief')
        browser.close_browser()
        route = '/restore'
        if change == 'password':
            route += '?changed_password=1'
        elif change == 'agent':
            browser.agent += '-changed'
        else:
            browser.cookies[cookie_name('ck_auto')]['value'] = base64.b64encode(b'invalid').decode()
        check(not browser.request(route).get('restored', True), change+' invalidates remember cookie')
        check(not browser.request('/state')['session'].get('ss_mb_id'), change+' no identity set')

    browser = Browser(address)
    browser.login('chief')
    browser.close_browser()
    browser.request('/restore')
    browser.request('/tamper-session')
    result = browser.request('/admin')
    check('denied' in result and result.get('mail_calls') == 1, 'actual admin verification still rejects altered session')

    browser = Browser(address)
    check('denied' in browser.login(password='wrong-synthetic-password'), 'wrong password still denied')
    check(cookie_name('ck_auto') not in browser.cookies, 'failed login does not issue persistence cookie')
    browser = Browser(address)
    result = browser.request('/login', {'mb_id':'customer','mb_password':'synthetic-only-password','auto_login[]':'1'})
    check('redirect' in result and cookie_name('ck_auto') not in browser.cookies, 'array input does not enable persistence')
    browser = Browser(address)
    browser.login('chief')
    expires = browser.cookies[cookie_name('ck_auto')]['expires']
    browser.close_browser()
    browser.request('/mark-adult')
    guest_session = browser.cookies['PHPSESSID']['value']
    browser.request('/restore')
    check(browser.cookies['PHPSESSID']['value'] != guest_session, 'restore rotates guest session ID')
    state = browser.request('/state')
    check(state['session']['ss_cert_adult'] == 'OK', 'existing adult verification session preserved')
    check(browser.cookies[cookie_name('ck_auto')]['expires'] == expires, 'restoration does not extend the fixed 365-day expiry')
    csrf = browser.request('/admin?csrf=issue')['token']
    check('denied' in browser.request('/admin?csrf=check', {'token':'wrong'}), 'admin rejects invalid CSRF token')
    csrf = browser.request('/admin?csrf=issue')['token']
    check(browser.request('/admin?csrf=check', {'token':csrf}).get('admin_allowed'), 'admin valid CSRF token succeeds')
    check('denied' in browser.request('/admin?csrf=check', {'token':csrf}), 'admin rejects replayed CSRF token')
    for who in ['customer', 'chief', 'staff']:
        for auto in ['1', 'on', None]:
            for target, tracking in [('http://fixture.invalid/', False),
                                     ('http://fixture.invalid/shop/?from=cart', False),
                                     ('http://fixture.invalid/shop/?from=cart', True)]:
                browser = Browser(address)
                data = {'mb_id': who, 'mb_password': 'synthetic-only-password', 'url': target}
                if auto is not None:
                    data['auto_login'] = auto
                if tracking:
                    data['campaign'] = 'synthetic'
                result = browser.request('/login', data)
                expected = target + ('&amp;campaign=synthetic' if tracking else '')
                check(result.get('redirect') == expected, 'redirect excludes auto_login and retains existing URL/other parameters')
                check((cookie_name('ck_auto') in browser.cookies) == (auto is not None),
                      'redirect cleanup preserves opt-in/opt-out cookie behavior')
    # Parse the actual checkbox tags; no theme/bootstrap or operational settings are loaded.
    import re
    for name in ['theme/buzinga/skin/member/basic/login.skin.php',
                 'theme/buzinga/mobile/skin/member/basic/login.skin.php',
                 'skin/member/basic/login.skin.php', 'mobile/skin/member/basic/login.skin.php', '19m.php']:
        source = (ROOT/name).read_text()
        tags = re.findall(r'<input\b[^>]*\bname="auto_login"[^>]*>', source)
        check(len(tags) == 1 and 'checked="checked"' in tags[0] and 'value="1"' in tags[0],
              name+' checkbox is enabled by default')


def main():
    if not PHP:
        raise SystemExit('PHP executable is required')
    with tempfile.TemporaryDirectory(prefix='redtoy-auto-login-') as temp:
        folder = Path(temp)
        for sub in ['sessions', 'lib', 'data', 'empty-skin']:
            (folder/sub).mkdir()
        (folder/'_common.php').write_text('<?php\n')
        (folder/'lib/mailer.lib.php').write_text('<?php\n')
        for name, plain in [('hash', 'synthetic-only-password'), ('new-hash', 'changed-synthetic-password')]:
            result = subprocess.check_output([PHP, '-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', plain], text=True)
            (folder/name).write_text(result)
        (folder/'router.php').write_text(BOOTSTRAP)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        address = f'http://127.0.0.1:{port}'
        with (folder/'server.log').open('w+') as log:
            process = subprocess.Popen([PHP, '-d', 'short_open_tag=1', '-S', f'127.0.0.1:{port}',
                                        str(folder/'router.php')], cwd=folder,
                                       env={**os.environ, 'REDTOY_AUTO_TEST_ROOT': str(ROOT)},
                                       stdout=log, stderr=log)
            try:
                for _ in range(50):
                    try:
                        Browser(address).request('/ready')
                        break
                    except OSError:
                        time.sleep(.05)
                else:
                    raise RuntimeError('Local PHP fixture server did not start')
                run_tests(address)
                log.flush()
                log.seek(0)
                errors = log.read()
                for line in errors.splitlines():
                    if 'PHP Warning' in line or 'PHP Fatal' in line or 'PHP Deprecated' in line:
                        print(line)
                check('PHP Warning' not in errors and 'PHP Fatal' not in errors and 'PHP Deprecated' not in errors,
                      'no PHP warnings/fatal/deprecation in fixture')
                print(f'PASS: {CHECKS} auto-login assertions; real PHP HTTP cookies, sessions and admin gates; synthetic members only')
            finally:
                process.terminate()
                process.wait(timeout=5)


if __name__ == '__main__':
    main()
