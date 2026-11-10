<?php
require __DIR__ . '/stubs-wp.php';
require '/tmp/theme/inc/tara-release-files.php';
$pass=0; $fail=0;
function check_release_file($name,$ok){global $pass,$fail; echo ($ok?'PASS ':'FAIL ').$name."\n"; if($ok)++$pass;else ++$fail;}
check_release_file('missing private directory fails closed',is_wp_error(tara_release_private_dir()));
check_release_file('directory root itself',tara_release_path_within('/srv/site','/srv/site'));
check_release_file('directory child',tara_release_path_within('/srv/site/uploads','/srv/site/'));
check_release_file('directory prefix is not child',!tara_release_path_within('/srv/site-other','/srv/site'));
check_release_file('empty root rejected',tara_release_path_within('/anything','/'));
check_release_file('valid cover metadata',is_array(tara_release_file_rule('cover.JPG',100,'cover')));
check_release_file('executable disallowed',false===tara_release_file_rule('cover.jpg.php',100,'cover'));
check_release_file('empty file disallowed',false===tara_release_file_rule('cover.png',0,'cover'));
check_release_file('oversized disallowed',false===tara_release_file_rule('cover.png',10485761,'cover'));
$GLOBALS['__ot']['release_max_cover_mb']='1';
check_release_file('manager can lower maximum',false===tara_release_file_rule('cover.png',1048577,'cover'));
$GLOBALS['__ot']['release_max_cover_mb']='9999';
check_release_file('manager cannot exceed safe maximum',false===tara_release_file_rule('cover.png',10485761,'cover'));
check_release_file('unknown category disallowed',false===tara_release_file_rule('a.mp3',100,'unknown'));
$multi=array('work_files'=>array('name'=>array('one.mp3','two.wav'),'tmp_name'=>array('/tmp/a','/tmp/b'),'error'=>array(0,0),'size'=>array(12,20)));
$r=tara_release_file_inputs($multi);check_release_file('album files split into independent slots',isset($r['work'],$r['work_2']) && count($r)===2);
check_release_file('malformed multi array rejected',is_wp_error(tara_release_file_inputs(array('work_files'=>array('name'=>array('x'),'tmp_name'=>array(),'error'=>array(),'size'=>array())))));
check_release_file('ten track limit',is_wp_error(tara_release_file_inputs(array('work_files'=>array('name'=>array_fill(0,11,'x'),'tmp_name'=>array_fill(0,11,'x'),'error'=>array_fill(0,11,0),'size'=>array_fill(0,11,1))))));
$GLOBALS['__ot']['release_max_tracks']='1';check_release_file('manager can limit track count',is_wp_error(tara_release_file_inputs($multi)));unset($GLOBALS['__ot']['release_max_tracks']);
check_release_file('valid work 10 slot',tara_release_file_key_valid('work_10'));
check_release_file('reject traversal slot',!tara_release_file_key_valid('../work_1'));
echo "RESULT pass=$pass fail=$fail\n";
