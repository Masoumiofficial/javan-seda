<?php
require __DIR__ . '/stubs-wp.php';
require '/tmp/theme/inc/tara-release-live.php';
$pass=0;$fail=0;
function test_live_release($n,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$n."\n";if($ok)++$pass;else ++$fail;}
test_live_release('logged-in submission hook registered',in_array('tara_release_ajax_submit',$GLOBALS['__hooks']['wp_ajax_tara_release_submit']));
test_live_release('no public submission hook',empty($GLOBALS['__hooks']['wp_ajax_nopriv_tara_release_submit']));
test_live_release('payment verification hook registered',in_array('tara_release_order_needs_payment',$GLOBALS['__hooks']['woocommerce_order_needs_payment']));
test_live_release('download uses authenticated action',!empty($GLOBALS['__hooks']['admin_post_tara_release_download']) && empty($GLOBALS['__hooks']['admin_post_nopriv_tara_release_download']));
$GLOBALS['__page_template']='page-account.php';$robots=tara_release_account_robots(array('index'=>true));test_live_release('account is noindex',!empty($robots['noindex']) && !isset($robots['index']));
$GLOBALS['__page_template']='page-other.php';$robots=tara_release_account_robots(array('index'=>true));test_live_release('other pages unchanged',empty($robots['noindex']) && !empty($robots['index']));
$GLOBALS['__is_page']='works';test_live_release('works title',tara_release_public_title('old')==='همکاری و انتشار آثار موسیقی | آهنگ بان');
$GLOBALS['__ot']['release_seo_description']='توضیح اختصاصی';test_live_release('custom SEO description',tara_release_public_description()==='توضیح اختصاصی');
$GLOBALS['__is_page']='other';test_live_release('other title unchanged',tara_release_public_title('old')==='old');
echo "RESULT pass=$pass fail=$fail\n";
