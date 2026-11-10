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
check_release_file('unknown category disallowed',false===tara_release_file_rule('a.mp3',100,'unknown'));
echo "RESULT pass=$pass fail=$fail\n";
