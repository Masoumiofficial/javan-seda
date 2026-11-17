<?php
define('TARA_RELEASE_INTEGRATED',true);
require __DIR__ . '/stubs-wp.php';
function is_email($s){return filter_var($s,FILTER_VALIDATE_EMAIL);}
function wp_parse_url($url){return parse_url($url);}
require '/tmp/theme/inc/tara-artisthub.php';
require '/tmp/theme/inc/tara-release-core.php';
require '/tmp/theme/inc/tara-release-storage.php';
require '/tmp/theme/inc/tara-release-sms.php';
require '/tmp/theme/inc/tara-release-actions.php';
class WC_Order_Item_Fee { public $amount=0; function set_name($x){} function set_tax_status($x){} function set_amount($x){$this->amount=$x;} function set_total($x){$this->amount=$x;} }
class TestReleaseOrder {
 public $id;public $customer;public $currency='IRT';public $amount=0;public $meta=array();public $paid=false;public $status='pending';
 function __construct($id,$customer){$this->id=$id;$this->customer=$customer;}
 function get_id(){return $this->id;} function get_customer_id(){return $this->customer;} function get_currency(){return $this->currency;} function get_total(){return $this->amount;} function get_meta($key,$single=true){return $this->meta[$key]??'';}
 function update_meta_data($key,$value){$this->meta[$key]=$value;} function add_item($item){$this->amount=$item->amount;} function calculate_totals($recalc=true){} function save(){} function get_checkout_payment_url(){return 'https://example.com/pay/'.$this->id;} function needs_payment(){return $this->status==='pending';} function is_paid(){return $this->paid;} function get_date_paid(){return $this->paid ? '2026-01-01' : null;} function has_status($s){return in_array($this->status,(array)$s,true);} function update_status($s,$note=''){$this->status=$s;}
}
function get_woocommerce_currency(){return 'IRT';}
function wc_create_order($args){$id=count($GLOBALS['__test_orders']??array())+1;$o=new TestReleaseOrder($id,$args['customer_id']);$GLOBALS['__test_orders'][$id]=$o;return $o;}
function wc_get_order($id){return $GLOBALS['__test_orders'][$id]??null;}
$pass=0;$fail=0;
function test_release_storage($name,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$name."\n";if($ok)++$pass;else ++$fail;}
$data=array('full_name'=>'نمونه','artist_name'=>'هنرمند','mobile'=>'۰۹۱۲۳۴۵۶۷۸۹','email'=>'a@example.com','country'=>'ایران','title'=>'اثر','performer'=>'هنرمند','service'=>'song','file_url'=>'https://example.org/song.mp3','rights'=>'1','terms'=>'1');
$GLOBALS['__ot']['release_enabled']='on';
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
$r=tara_release_admin_update(42,$id,array('status'=>'awaiting_payment'),1002);
test_release_storage('payment cannot be ready without a quote',is_wp_error($r));
$r=tara_release_admin_update(42,$id,array('status'=>'paid'),1003);
test_release_storage('admin cannot mark paid',is_wp_error($r));
$r=tara_release_admin_update(42,$id,array('publication_url'=>'javascript:alert(1)'),1003);
test_release_storage('unsafe publication URL rejected',is_wp_error($r));
$r=tara_release_admin_update(42,$id,array('status'=>'awaiting_payment','final_toman'=>'1500000','extra_costs'=>'ندارد','refund_terms'=>'شرایط اختصاصی','quote_revision'=>str_repeat('b',32)),1003);
test_release_storage('admin supplies quote then changes status',!is_wp_error($r) && $r['status']==='awaiting_payment' && $r['sms_events']['quote_ready']['status']==='pending');
$GLOBALS['__ot']['release_payment_on']='on';
$hash=tara_release_quote_hash($r);
test_release_storage('stale checkout quote rejected',is_wp_error(tara_release_prepare_checkout(42,$id,str_repeat('0',64))));
$url=tara_release_prepare_checkout(42,$id,$hash);
test_release_storage('checkout creates order',!is_wp_error($url) && $url==='https://example.com/pay/1');
test_release_storage('repeated checkout reuses order',tara_release_prepare_checkout(42,$id,$hash)===$url && count($GLOBALS['__test_orders'])===1);
test_release_storage('another user cannot pay request',is_wp_error(tara_release_prepare_checkout(43,$id,$hash)));
$o=wc_get_order(1);test_release_storage('order-pay accepted when quote matches',tara_release_order_needs_payment(true,$o));
$o->amount=1;test_release_storage('order-pay rejects changed amount',!tara_release_order_needs_payment(true,$o));test_release_storage('altered order amount blocked',is_wp_error(tara_release_prepare_checkout(42,$id,$hash)));$o->amount=1500000;
$GLOBALS['__ot']['release_payment_on']='off';test_release_storage('order-pay disabled by manager',!tara_release_order_needs_payment(true,$o));$GLOBALS['__ot']['release_payment_on']='on';
tara_release_wc_paid(1);test_release_storage('unpaid callback cannot mark paid',tara_hub_requests(42)[0]['status']==='awaiting_payment');
$o->paid=true;$o->status='processing';tara_release_wc_paid(1);test_release_storage('verified callback marks paid',tara_hub_requests(42)[0]['status']==='paid');
test_release_storage('paid SMS queued only after verified callback',tara_hub_requests(42)[0]['sms_events']['paid']['status']==='pending');
tara_release_wc_paid(1);test_release_storage('duplicate callback leaves single paid event',count(array_filter(array_keys(tara_hub_requests(42)[0]['sms_events']),function($k){return 'paid'===$k;}))===1);
$r=tara_release_admin_update(42,$id,array('status'=>'preparing'),1005);
$r=tara_release_admin_update(42,$id,array('status'=>'published','publication_url'=>'https://example.com/song'),1006);
test_release_storage('published SMS queued with saved public URL',!is_wp_error($r) && $r['sms_events']['published']['url']==='https://example.com/song');
$o->status='refunded';tara_release_wc_refunded(1);test_release_storage('full verified refund recorded',tara_hub_requests(42)[0]['status']==='refunded');
$GLOBALS['__db_sum']=0;
test_release_storage('lock timeout fails closed',is_wp_error(tara_release_save_request(44,$data,str_repeat('f',32),1000)) && empty(tara_hub_requests(44)));
$GLOBALS['__db_sum']=1;
$v2=array('wizard_version'=>'2','plan'=>'std','service'=>'song','title'=>'اثر دوم','title_en'=>'Second','performer'=>'هنرمند دوم','performer_en'=>'Second Artist','rights'=>'1','terms'=>'1');
$id2=tara_release_save_request(45,$v2,'c'.str_repeat('d',31),2000,array('audio128'=>array('token'=>str_repeat('a',48),'mime'=>'audio/mpeg','size'=>100,'extension'=>'mp3')));
$r=tara_release_admin_update(45,$id2,array('send_quote'=>1,'final_toman'=>'2000000','extra_costs'=>'ندارد','refund_terms'=>'پیش از شروع کار بازپرداخت کامل','quote_revision'=>str_repeat('e',32)),2001);
test_release_storage('send quote advances safely to payment',!is_wp_error($r) && $r['status']==='awaiting_payment' && 2000000===$r['final_toman']);
test_release_storage('quote ready SMS queued',isset($r['sms_events']['quote_ready']['status']) && 'pending'===$r['sms_events']['quote_ready']['status']);
test_release_storage('old request remains intact',count(tara_hub_requests(42))===2);
$GLOBALS['__db_sum']=1; $GLOBALS['__ot']['release_payment_on']='on';
$v3=array('wizard_version'=>'2','service'=>'song','title'=>'فاکتور فوری','title_en'=>'Instant','performer'=>'هنرمند','performer_en'=>'Artist','rights'=>'1','terms'=>'1');
$id3=tara_release_save_request(46,$v3,'f'.str_repeat('a',31),3000,array('audio128'=>array('token'=>str_repeat('b',48),'mime'=>'audio/mpeg','size'=>100,'extension'=>'mp3')));
$before=count($GLOBALS['__test_orders']??array());
$url=tara_release_auto_checkout(46,$id3);
$row3=null; foreach(tara_hub_requests(46) as $candidate){ if($candidate['id']===$id3){$row3=$candidate;break;} }
test_release_storage('fixed price invoice is created on submit',!is_wp_error($url) && $url==='https://example.com/pay/'.($before+1) && 'awaiting_payment'===$row3['status']);
test_release_storage('invoice stores exact catalog price',1500000===$row3['final_toman'] && !empty($row3['order_id']) && 'ندارد'!==$row3['extra_costs']);
test_release_storage('auto invoice is accepted at the displayed price',tara_release_quote_ready($row3));
$order3=wc_get_order($row3['order_id']);
test_release_storage('order needs payment only for the matching fixed price',tara_release_order_needs_payment(true,$order3));
test_release_storage('submitting twice reuses the same invoice',tara_release_auto_checkout(46,$id3)===$url && count($GLOBALS['__test_orders'])===$before+1);
test_release_storage('payment reminder queued with checkout link','pending'===$row3['sms_events']['payment_ready']['status'] && $row3['sms_events']['payment_ready']['url']===$url);
$v4=array_merge($v3,array('title'=>'بین‌الملل','title_en'=>'Intl'));
$id4=tara_release_save_request(47,$v3,'a'.str_repeat('b',31),3001,array('audio128'=>array('token'=>str_repeat('c',48),'mime'=>'audio/mpeg','size'=>100,'extension'=>'mp3')));
$r47=tara_release_change_request(47,$id4,function($row){$row['service']='international';return $row;});
test_release_storage('international never gets an automatic invoice',is_wp_error(tara_release_auto_checkout(47,$id4)));
$r=tara_release_admin_update(46,$id3,array('status'=>'cancelled'),4000);
test_release_storage('admin can cancel an unpaid invoice',!is_wp_error($r) && 'cancelled'===$r['status'] && 'cancelled'===wc_get_order($row3['order_id'])->status);
test_release_storage('cancelled invoice still cannot be marked paid',is_wp_error(tara_release_admin_update(46,$id3,array('status'=>'paid'),4001)));
echo "RESULT pass=$pass fail=$fail\n";
