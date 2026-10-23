<?php
// The real phone adapter runs a local fake SClient with synthetic credentials. No PG/API.
define('_GNUBOARD_',true);
require dirname(__DIR__).'/www/lib/redtoy_danal_cancel_pg.lib.php';
$checks=0;
function verify_phone_transport($ok,$message) {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('FAIL: '.$message);
}
$fake=tempnam(sys_get_temp_dir(),'redtoy_fake_sclient_');
$names=['REDTOY_PG_CANCEL_ENABLED','REDTOY_DANAL_PHONE_ID','REDTOY_DANAL_PHONE_PASSWORD','REDTOY_DANAL_PHONE_BINARY'];
$previous=[];
foreach ($names as $name) $previous[$name]=getenv($name);
try {
    $script= <<<'FAKE'
<?php
$expected='ID=TEST000001;PWD=TESTPASS01;TID=222222222222222222;Command=BILL_CANCEL;OUTPUTOPTION=3';
if (count($argv)!==2 || $argv[1]!==$expected) {
    echo 'Result=9999&ErrMsg=synthetic_wrong_wire_format';
    exit(0);
}
echo "Result=0\nTID=222222222222222222\nDate=20260102120000\n";
FAKE;
    file_put_contents($fake,'#!'.PHP_BINARY."\n".$script);
    chmod($fake,0700);
    putenv('REDTOY_PG_CANCEL_ENABLED=1');
    putenv('REDTOY_DANAL_PHONE_ID=TEST000001');
    putenv('REDTOY_DANAL_PHONE_PASSWORD=TESTPASS01');
    putenv('REDTOY_DANAL_PHONE_BINARY='.$fake);
    $plan=['method'=>'phone','cash'=>10000,'base'=>['order'=>['od_tno'=>str_repeat('2',18)]]];
    $result=redtoy_cancel_pg($plan);
    verify_phone_transport($result['ok']===true,'real adapter sends legacy semicolon-delimited SClient argument');
    verify_phone_transport($result['tid']===str_repeat('2',18),'successful phone response matches original transaction');
    $spaced=" Result = 0 \r\n TID = 222222222222222222 \r\n Date = 20260102120000 \r\n";
    verify_phone_transport(redtoy_cancel_phone_result(redtoy_cancel_phone_response($spaced),$plan)['ok'],
        'vendor-style padded keys and values accepted without weakening transaction checks');
    verify_phone_transport(!redtoy_cancel_phone_result(redtoy_cancel_phone_response('Result=0&Result=0&TID=222222222222222222&Date=20260102120000'),$plan)['ok'],
        'duplicate result fields remain unverified');
    verify_phone_transport(redtoy_cancel_phone_result(['Result'=>'1234'],$plan)['code']==='phone_result_1234',
        'numeric vendor failure code retained without free-text data');
    $r=['Result'=>'0','TID'=>str_repeat('2',18),'Date'=>'20260102120000'];
    verify_phone_transport(redtoy_cancel_phone_result($r,$plan)['ok'],'valid response accepted');
    foreach (['Result'=>'9999','TID'=>str_repeat('3',18),'Date'=>'invalid'] as $key=>$value) {
        $bad=$r; $bad[$key]=$value;
        verify_phone_transport(!redtoy_cancel_phone_result($bad,$plan)['ok'],'invalid '.$key.' stays unverified');
    }
    putenv('REDTOY_PG_CANCEL_ENABLED=0');
    $blocked=false;
    try { redtoy_cancel_pg($plan); }
    catch (RuntimeException $e) { $blocked=$e->getMessage()==='pg_not_configured'; }
    verify_phone_transport($blocked,'disabled switch blocks adapter before execution');
    $plan['cash']=0; $blocked=false;
    try { redtoy_cancel_pg_ready($plan); }
    catch (RuntimeException $e) { $blocked=$e->getMessage()==='pg_not_configured'; }
    verify_phone_transport($blocked,'zero-cash cancellation also respects disabled switch');
    echo "PASS: $checks phone transport assertions; local fake SClient; no PG/API\n";
} finally {
    foreach ($previous as $name=>$value) putenv($value===false?$name:$name.'='.$value);
    if (is_file($fake)) unlink($fake);
}
