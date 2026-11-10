<?php
require __DIR__ . '/stubs-wp.php';
function tara_acc_opt($key,$default=''){return $GLOBALS['sms_config'][$key]??$default;}
function tara_acc_phone($s){return preg_match('/^09[0-9]{9}$/',$s)?$s:'';}
function tara_acc_digits($s){return preg_replace('/[^0-9]/','',$s);}
function tara_acc_user_phone($id){return $GLOBALS['sms_phone']??'';}
function tara_acc_http($url,$body,$headers=array()){$GLOBALS['sms_requests'][]=array($url,$body);return $GLOBALS['sms_response'];}
function tara_acc_sms_webhook($phone,$code,$text){$GLOBALS['sms_requests'][]=array('webhook',array('phone'=>$phone,'text'=>$text,'code'=>$code));return true;}
function tara_release_change_request($user,$id,$callback){$r=$GLOBALS['sms_row'];$r=$callback($r);if(!is_wp_error($r))$GLOBALS['sms_row']=$r;return $r;}
function wp_next_scheduled($hook,$args){return !empty($GLOBALS['sms_scheduled']);}
function wp_schedule_single_event($time,$hook,$args){$GLOBALS['sms_scheduled'][]=array($hook,$args);return true;}
require '/tmp/theme/inc/tara-release-sms.php';
$pass=0;$fail=0;
function sms_test($n,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$n."\n";if($ok)++$pass;else ++$fail;}
$GLOBALS['sms_phone']='09123456789';$GLOBALS['sms_config']=array('sms'=>'melipayamak','sms_user'=>'user','sms_pass'=>'password','sms_from'=>'1000','sms_pattern'=>'123');$GLOBALS['sms_response']=array('ok'=>true,'body'=>'{"RetStatus":1,"Value":12345}');$GLOBALS['sms_requests']=array();
$row=array('status'=>'paid','publication_url'=>'https://example.com/work');$row=tara_release_sms_queue($row,'paid');$row=tara_release_sms_queue($row,'paid');sms_test('event queue idempotent',count($row['sms_events'])===1);
$GLOBALS['sms_row']=$row;tara_release_sms_process(42,'release_a','paid');sms_test('paid event accepted',$GLOBALS['sms_row']['sms_events']['paid']['status']==='accepted');
sms_test('transaction uses text endpoint, not OTP pattern',strpos($GLOBALS['sms_requests'][0][0],'/SendSMS/SendSMS')!==false && !isset($GLOBALS['sms_requests'][0][1]['bodyId']));
tara_release_sms_process(42,'release_a','paid');sms_test('duplicate paid callback cannot resend',count($GLOBALS['sms_requests'])===1);
$GLOBALS['sms_row']['status']='published';$GLOBALS['sms_row']=tara_release_sms_queue($GLOBALS['sms_row'],'published');$GLOBALS['__ot']['release_sms_published_text']='منتشر شد';tara_release_sms_process(42,'release_a','published');sms_test('publication sends link even if template omits it',strpos($GLOBALS['sms_requests'][1][1]['text'],'https://example.com/work')!==false);
$GLOBALS['sms_row']=tara_release_sms_queue(array('status'=>'paid'),'paid');$GLOBALS['sms_phone']='';tara_release_sms_process(42,'release_a','paid');sms_test('no verified number fails closed',count($GLOBALS['sms_requests'])===2 && $GLOBALS['sms_row']['sms_events']['paid']['status']==='failed');
$GLOBALS['sms_phone']='09123456789';$GLOBALS['sms_config']['sms']='dev';sms_test('development mode is not actual delivery',!tara_release_sms_send_text($GLOBALS['sms_phone'],'سلام'));
$GLOBALS['sms_config']['sms']='melipayamak';$GLOBALS['sms_response']=array('ok'=>true,'body'=>'not-json');sms_test('unrecognized provider response not accepted',!tara_release_sms_send_text($GLOBALS['sms_phone'],'سلام'));
$GLOBALS['sms_config']['sms']='webhook';$GLOBALS['sms_row']=tara_release_sms_queue(array('status'=>'published','publication_url'=>'https://example.com/old'),'published');$GLOBALS['sms_row']['publication_url']='https://example.com/new';$count=count($GLOBALS['sms_requests']);tara_release_sms_process(42,'release_a','published');sms_test('changed publication link fails closed',count($GLOBALS['sms_requests'])===$count && $GLOBALS['sms_row']['sms_events']['published']['status']==='failed');
$GLOBALS['sms_row']=tara_release_sms_queue(array('status'=>'paid'),'paid');$GLOBALS['__ot']['release_sms_paid_on']='off';tara_release_sms_process(42,'release_a','paid');sms_test('manager can disable SMS',count($GLOBALS['sms_requests'])===$count && $GLOBALS['sms_row']['sms_events']['paid']['status']==='failed');unset($GLOBALS['__ot']['release_sms_paid_on']);
$GLOBALS['sms_scheduled']=array();sms_test('scheduling event',tara_release_sms_schedule(42,'release_a','paid'));tara_release_sms_schedule(42,'release_a','paid');sms_test('scheduling duplicate avoided',count($GLOBALS['sms_scheduled'])===1);
echo "RESULT pass=$pass fail=$fail\n";
