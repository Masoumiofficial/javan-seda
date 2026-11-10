<?php
require __DIR__ . '/stubs-wp.php';
require '/tmp/theme/inc/tara-release-core.php';
require '/tmp/theme/inc/tara-release-files.php';
require '/tmp/theme/inc/tara-release-ui.php';
$pass=0; $fail=0;
function ui_release_test($name,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$name."\n";if($ok)++$pass;else ++$fail;}
$GLOBALS['__ot']['release_enabled']='on';
$GLOBALS['__ot']['release_title']='<script>alert(1)</script>';
$GLOBALS['__ot']['release_condition_1_text']='قانون نمونه';
$GLOBALS['__ot']['release_condition_2_text']='نباید نمایش داده شود';
$GLOBALS['__ot']['release_condition_2_on']='off';
$GLOBALS['__ot']['release_price_song']='1234000';
$GLOBALS['__ot']['release_service_video']='off';
$html=tara_release_render_wizard();
ui_release_test('title escaped',false===strpos($html,'<script>alert(1)</script>'));
ui_release_test('condition enabled',false!==strpos($html,'قانون نمونه'));
ui_release_test('condition disabled',false===strpos($html,'نباید نمایش داده شود'));
ui_release_test('price from options',false!==strpos($html,'1,234,000'));
ui_release_test('service disabled',false===strpos($html,'value="video"'));
ui_release_test('five stages',5===substr_count($html,'data-release-step'));
ui_release_test('submit is available when enabled',false!==strpos($html,'data-release-submit>'));
ui_release_test('international explicitly review only',false!==strpos($html,'فقط بررسی'));
echo "RESULT pass=$pass fail=$fail\n";
