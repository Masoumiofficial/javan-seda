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
$GLOBALS['__ot']['release_service_song']='off';
t_release('disabled service cannot be requested',!isset(tara_release_catalog()['song']));
unset($GLOBALS['__ot']['release_service_song']);
$GLOBALS['__ot']['release_price_song']='-50';
t_release('invalid configured price',tara_release_catalog()['song']['amount']===1500000);
$data=array('full_name'=>'نمونه','artist_name'=>'هنرمند','mobile'=>'۰۹۱۲۳۴۵۶۷۸۹','email'=>'a@example.com','country'=>'ایران','title'=>'اثر','performer'=>'هنرمند','service'=>'song','file_url'=>'https://example.org/song.mp3','rights'=>'1','terms'=>'1');
$r=tara_release_validate($data);t_release('valid and Persian phone',!is_wp_error($r) && $r['mobile']==='09123456789');
foreach(array(array('rights'=>'0'),array('terms'=>'0'),array('email'=>'bad'),array('file_url'=>''),array('service'=>'invalid'),array('release_date'=>'2026-02-30'),array('file_url'=>'javascript:alert(1)'),array('file_url'=>'https://user:pass@example.com/private'),array('title'=>array('x'))) as $i=>$bad){t_release('invalid request '.$i,is_wp_error(tara_release_validate(array_merge($data,$bad))));}
$row=array('id'=>'r1','type'=>'release','status'=>'awaiting_payment','service'=>'song','final_toman'=>1500000,'extra_costs'=>'ندارد','refund_terms'=>'تا پیش از شروع کار بازپرداخت می‌شود','quote_revision'=>'1');
t_release('no payment without consent',!tara_release_quote_ready($row));$row['accepted_quote_hash']=tara_release_quote_hash($row);
t_release('matching accepted quote',tara_release_quote_ready($row));
$GLOBALS['__ot']['release_service_song']='off';t_release('existing accepted quote survives disabled service',tara_release_quote_ready($row));unset($GLOBALS['__ot']['release_service_song']);
$row['final_toman']=1500001;t_release('changed quote requires new consent',!tara_release_quote_ready($row));
$row['service']='international';$row['accepted_quote_hash']=tara_release_quote_hash($row);t_release('international checks required',!tara_release_quote_ready($row));
foreach(array('rights_checked','geographic_checked','distribution_legal_confirmed','identity_checked','settlement_confirmed') as $key)$row[$key]=true;
t_release('explicit international checks',tara_release_quote_ready($row));$row['settlement_confirmed']='1';t_release('checks must be boolean',!tara_release_quote_ready($row));
t_release('admin cannot assert payment',!tara_release_transition_allowed('awaiting_payment','paid','admin'));
t_release('verified gateway payment transition',tara_release_transition_allowed('awaiting_payment','paid','verified_gateway'));
t_release('cannot bypass payment',!tara_release_transition_allowed('approved','published'));
t_release('cancelled terminal',!tara_release_transition_allowed('cancelled','approved'));
$token=str_repeat('a',32); $req=tara_release_build_request($data,$token,1000);
t_release('build request',!is_wp_error($req) && $req['status']==='new' && $req['price']===1500000);
t_release('idempotent lookup',tara_release_find_submission(array(array('id'=>'old','type'=>'plan'),$req),$token)===$req);
t_release('malformed idempotency token rejected',is_wp_error(tara_release_build_request($data,'bad',1000)));
$req=tara_release_transition($req,'review','admin',1001); $req=tara_release_transition($req,'approved','admin',1002);
t_release('quote cannot omit refund terms',is_wp_error(tara_release_set_quote($req,'1000','none','',str_repeat('b',32))));
$req=tara_release_set_quote($req,'1000','بدون هزینه','شرایط بازپرداخت',str_repeat('b',32));
$req=tara_release_transition($req,'awaiting_payment','admin',1003);
t_release('history tracks transitions',count($req['history'])===4);
t_release('stale quote cannot be accepted',is_wp_error(tara_release_accept_quote($req,str_repeat('0',64),1004)));
$req=tara_release_accept_quote($req,tara_release_quote_hash($req),1004);
t_release('fresh quote acceptance ready',tara_release_quote_ready($req));
$req=tara_release_set_quote($req,'1200','بدون هزینه','شرایط بازپرداخت',str_repeat('c',32));
t_release('quote edit removes consent',!tara_release_quote_ready($req) && !isset($req['accepted_at']));
$req['order_id']=123;
t_release('quote cannot be edited with order',is_wp_error(tara_release_set_quote($req,'1300','بدون هزینه','شرایط بازپرداخت',str_repeat('d',32))));
class ReleaseTestOrder {
 public $paid=true; public $total=12000; public $currency='IRR'; public $id=123; public $customer=42; public $meta=array(); public $bad=false;
 function get_id(){return $this->id;} function get_customer_id(){return $this->customer;} function get_meta($key,$single=true){return $this->meta[$key]??'';} function get_currency(){return $this->currency;} function get_total(){return $this->total;} function is_paid(){return $this->paid;} function has_status($s){return $this->bad;}
}
$req['accepted_quote_hash']=tara_release_quote_hash($req); $order=new ReleaseTestOrder();
$order->meta=array('_tara_release_request'=>$req['id'],'_tara_release_user'=>42,'_tara_release_quote'=>$req['accepted_quote_hash']);
t_release('server verified IRR order',tara_release_order_matches_paid($req,$order,42));
$order->paid=false;t_release('unpaid order cannot transition',!tara_release_order_matches_paid($req,$order,42));$order->paid=true;
$order->total=11999;t_release('wrong total rejected',!tara_release_order_matches_paid($req,$order,42));$order->total=12000;
t_release('wrong owner rejected',!tara_release_order_matches_paid($req,$order,43));
$order->currency='USD';t_release('unsupported currency rejected',!tara_release_order_matches_paid($req,$order,42));$order->currency='IRR';
$order->meta['_tara_release_quote']=str_repeat('0',64);t_release('different quote rejected',!tara_release_order_matches_paid($req,$order,42));
echo "RESULT pass=$pass fail=$fail\n";
