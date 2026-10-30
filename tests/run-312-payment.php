<?php
/** WooCommerce adapter contract tests using a test double, not a live gateway. */
require __DIR__ . '/run-391-fixtures.php';
require '/tmp/theme/inc/tara-artisthub.php';
$GLOBALS['currency'] = 'IRT';
$GLOBALS['orders'] = array();
function get_woocommerce_currency() { return $GLOBALS['currency']; }
class WC_Order_Item_Fee {
    public $amount;
    function set_name($v) {} function set_tax_status($v) {} function set_amount($v) { $this->amount = $v; } function set_total($v) { $this->amount = $v; }
}
class FakeOrder {
    public $id = 123, $meta = array(), $customer, $total = 0, $currency, $paid = false, $status = 'pending';
    function __construct($customer) { $this->customer=$customer; $this->currency=get_woocommerce_currency(); }
    function add_item($item) { $this->total=$item->amount; }
    function update_meta_data($key,$v) { $this->meta[$key]=$v; }
    function get_meta($key) { return isset($this->meta[$key]) ? $this->meta[$key] : ''; }
    function calculate_totals($tax) {} function save() { $GLOBALS['orders'][$this->id]=$this; }
    function get_id() { return $this->id; } function is_paid() { return $this->paid; }
    function has_status($s) { return $s === $this->status; } function get_customer_id() { return $this->customer; } function get_total() { return $this->total; } function get_currency() { return $this->currency; }
}
function wc_create_order($args) { return new FakeOrder($args['customer_id']); }
function wc_get_order($id) { return isset($GLOBALS['orders'][$id]) ? $GLOBALS['orders'][$id] : false; }
$GLOBALS['__usermeta'][11]['tara_artist_requests'] = array(array('id'=>'req1','type'=>'plan','status'=>'pending','order_id'=>123));
t('woocommerce toman configured', tara_hub_wc_ready() && 100 === tara_hub_wc_amount(100));
$order = tara_hub_wc_order(11,'req1','vip',100);
t('order created for correct customer and total', !is_wp_error($order) && 11 === $order->get_customer_id() && 100 === $order->get_total());
tara_hub_wc_paid(123);
t('unpaid order cannot mark request paid', 'pending' === tara_hub_requests(11)[0]['status']);
$order->paid = true; $order->total = 1; tara_hub_wc_paid(123);
t('modified order total cannot mark request paid', 'pending' === tara_hub_requests(11)[0]['status']);
$order->total = 100; $order->customer = 12; tara_hub_wc_paid(123);
t('different customer cannot mark request paid', 'pending' === tara_hub_requests(11)[0]['status']);
$order->customer = 11; tara_hub_wc_paid(123);
t('paid matching order marks request paid', 'paid' === tara_hub_requests(11)[0]['status']);
$order->status = 'refunded'; tara_hub_wc_refunded(123);
t('refunded order updates request', 'refunded' === tara_hub_requests(11)[0]['status']);
$GLOBALS['currency'] = 'IRR';
t('rial conversion', 1000 === tara_hub_wc_amount(100));
$GLOBALS['currency'] = 'USD';
t('unknown currency is refused', !tara_hub_wc_ready() && null === tara_hub_wc_amount(100));
echo "\nRESULT pass=$PASS fail=$FAIL\n";
