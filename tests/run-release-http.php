<?php
define('TARA_RELEASE_INTEGRATED',true);
require __DIR__ . '/stubs-wp.php';
function is_email($s){return filter_var($s,FILTER_VALIDATE_EMAIL);}
function wp_parse_url($url){return parse_url($url);}
require '/tmp/theme/inc/tara-artisthub.php';
require '/tmp/theme/inc/tara-release-core.php';
require '/tmp/theme/inc/tara-release-storage.php';
require '/tmp/theme/inc/tara-release-actions.php';
$pass=0;$fail=0;
function http_release_test($name,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$name."\n";if($ok)++$pass;else ++$fail;}
function call_release_submit(){try{tara_release_ajax_submit();}catch(RuntimeException $e){} return $GLOBALS['__json'];}
$GLOBALS['__db_sum']=1;$GLOBALS['__ot']['release_enabled']='on';$GLOBALS['__current']=23;
$_FILES=array();$_POST=array('submission_token'=>str_repeat('c',32),'full_name'=>'نام','artist_name'=>'هنرمند','mobile'=>'09123456789','email'=>'a@example.com','country'=>'ایران','title'=>'آهنگ','performer'=>'خواننده','service'=>'song','file_url'=>'https://example.com/file.mp3','rights'=>'1','terms'=>'1','price'=>'1','user_id'=>'999');
$r=call_release_submit();http_release_test('valid submission',!empty($r['success']));
$rows=tara_hub_requests(23);http_release_test('server controls owner and amount',count($rows)===1 && $rows[0]['price']===1500000 && empty(tara_hub_requests(999)));
$r=call_release_submit();http_release_test('retry has one row',!empty($r['success']) && count(tara_hub_requests(23))===1);
$_POST['submission_token']=str_repeat('d',32);unset($_POST['terms']);$r=call_release_submit();http_release_test('consent required',empty($r['success']) && count(tara_hub_requests(23))===1);
$GLOBALS['__ot']['release_enabled']='off';$_POST['terms']='1';$r=call_release_submit();http_release_test('disabled blocks new requests',empty($r['success']));
$GLOBALS['__ot']['release_enabled']='on';$GLOBALS['__current']=0;$r=call_release_submit();http_release_test('anonymous rejected',empty($r['success']));
echo "RESULT pass=$pass fail=$fail\n";
