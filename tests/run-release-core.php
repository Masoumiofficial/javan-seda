<?php
require __DIR__ . '/stubs-wp.php';
function is_email( $s ) { return filter_var( $s, FILTER_VALIDATE_EMAIL ); }
function wp_parse_url( $url ) { return parse_url( $url ); }
require '/tmp/theme/inc/tara-release-core.php';
$pass=0; $fail=0;
function t_release($name,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$name."\n";if($ok)++$pass;else ++$fail;}
$catalog=tara_release_catalog();
t_release('five exact base prices',array_column(array_slice($catalog,0,5),'amount')===array(1500000,2500000,4500000,1800000,1000000));
$GLOBALS['__ot']['release_price_song']='1200000';
t_release('configured price',tara_release_catalog()['song']['amount']===1200000);
$GLOBALS['__ot']['release_price_song']='-50';
t_release('invalid configured price',tara_release_catalog()['song']['amount']===1500000);
$data=array('full_name'=>'نمونه','artist_name'=>'هنرمند','mobile'=>'۰۹۱۲۳۴۵۶۷۸۹','email'=>'a@example.com','country'=>'ایران','title'=>'اثر','performer'=>'هنرمند','service'=>'song','file_url'=>'https://example.org/song.mp3','rights'=>'1','terms'=>'1');
$r=tara_release_validate($data);t_release('valid and Persian phone',!is_wp_error($r) && $r['mobile']==='09123456789');
foreach(array(array('rights'=>'0'),array('terms'=>'0'),array('email'=>'bad'),array('file_url'=>''),array('service'=>'invalid'),array('release_date'=>'2026-02-30'),array('file_url'=>'javascript:alert(1)'),array('file_url'=>'https://user:pass@example.com/private'),array('title'=>array('x'))) as $i=>$bad){t_release('invalid request '.$i,is_wp_error(tara_release_validate(array_merge($data,$bad))));}
$row=array('id'=>'r1','type'=>'release','status'=>'awaiting_payment','service'=>'song','final_toman'=>1500000,'extra_costs'=>'ندارد','refund_terms'=>'تا پیش از شروع کار بازپرداخت می‌شود','quote_revision'=>'1');
t_release('no payment without consent',!tara_release_quote_ready($row));$row['accepted_quote_hash']=tara_release_quote_hash($row);
t_release('matching accepted quote',tara_release_quote_ready($row));$row['final_toman']=1500001;t_release('changed quote requires new consent',!tara_release_quote_ready($row));
$row['service']='international';$row['accepted_quote_hash']=tara_release_quote_hash($row);t_release('international checks required',!tara_release_quote_ready($row));
foreach(array('rights_checked','geographic_checked','distribution_legal_confirmed','identity_checked','settlement_confirmed') as $key)$row[$key]=true;
t_release('explicit international checks',tara_release_quote_ready($row));$row['settlement_confirmed']='1';t_release('checks must be boolean',!tara_release_quote_ready($row));
t_release('admin cannot assert payment',!tara_release_transition_allowed('awaiting_payment','paid','admin'));
t_release('verified gateway payment transition',tara_release_transition_allowed('awaiting_payment','paid','verified_gateway'));
t_release('cannot bypass payment',!tara_release_transition_allowed('approved','published'));
t_release('cancelled terminal',!tara_release_transition_allowed('cancelled','approved'));
echo "RESULT pass=$pass fail=$fail\n";
