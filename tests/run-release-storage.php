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
function test_release_storage($name,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$name."\n";if($ok)++$pass;else ++$fail;}
$data=array('full_name'=>'نمونه','artist_name'=>'هنرمند','mobile'=>'۰۹۱۲۳۴۵۶۷۸۹','email'=>'a@example.com','country'=>'ایران','title'=>'اثر','performer'=>'هنرمند','service'=>'song','file_url'=>'https://example.org/song.mp3','rights'=>'1','terms'=>'1');
$GLOBALS['__db_sum']=1;
$token=str_repeat('a',32);$legacy=array('id'=>'old','type'=>'plan','status'=>'paid');
update_user_meta(42,'tara_artist_requests',array($legacy));
$id=tara_release_save_request(42,$data,$token,1000);
test_release_storage('request added',!is_wp_error($id));
test_release_storage('same token retry returns same id',tara_release_save_request(42,$data,$token,1000)===$id);
test_release_storage('legacy request preserved',count(tara_hub_requests(42))===2 && tara_hub_requests(42)[1]===$legacy);
$result=tara_release_change_request(42,$id,function($row){return tara_release_transition($row,'review','admin',1001);});
test_release_storage('release transition stored',!is_wp_error($result) && tara_hub_requests(42)[0]['status']==='review');
test_release_storage('legacy record cannot be modified',is_wp_error(tara_release_change_request(42,'old',function($row){return array();})));
test_release_storage('owner scope enforced by target user',is_wp_error(tara_release_change_request(43,$id,function($row){return $row;})));
test_release_storage('callback cannot change id',is_wp_error(tara_release_change_request(42,$id,function($row){$row['id']='other';return $row;})));
test_release_storage('invalid token rejected',is_wp_error(tara_release_save_request(42,$data,'bad',1000)));
test_release_storage('admin changes require capability',is_wp_error(tara_release_admin_update(42,$id,array('status'=>'approved'),1002)));
$GLOBALS['__caps']['manage_options']=true;
$r=tara_release_admin_update(42,$id,array('status'=>'approved','note'=>'بررسی شد'),1002);
test_release_storage('admin review and note',!is_wp_error($r) && $r['status']==='approved' && $r['note']==='بررسی شد');
$r=tara_release_admin_update(42,$id,array('status'=>'paid'),1003);
test_release_storage('admin cannot mark paid',is_wp_error($r));
$r=tara_release_admin_update(42,$id,array('publication_url'=>'javascript:alert(1)'),1003);
test_release_storage('unsafe publication URL rejected',is_wp_error($r));
$r=tara_release_admin_update(42,$id,array('status'=>'awaiting_payment','final_toman'=>'1500000','extra_costs'=>'ندارد','refund_terms'=>'شرایط اختصاصی','quote_revision'=>str_repeat('b',32)),1003);
test_release_storage('admin supplies quote then changes status',!is_wp_error($r) && $r['status']==='awaiting_payment');
$GLOBALS['__db_sum']=0;
test_release_storage('lock timeout fails closed',is_wp_error(tara_release_save_request(44,$data,str_repeat('f',32),1000)) && empty(tara_hub_requests(44)));
echo "RESULT pass=$pass fail=$fail\n";
