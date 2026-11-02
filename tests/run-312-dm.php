<?php
/** Message persistence and read-state test with a minimal wpdb double. */
require __DIR__ . '/run-391-fixtures.php';
require '/tmp/theme/inc/tara-trust.php';
require '/tmp/theme/inc/tara-dm.php';
if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
class FakeDMDB {
 public $prefix = 'wp_', $insert_id = 0, $rows = array();
 function esc_like($v) { return $v; }
 function prepare($sql, ...$args) { return array($sql, $args); }
 function get_var($query) { return 'wp_tara_dm'; }
 function insert($table, $row, $formats) { $row['id'] = ++$this->insert_id; $this->rows[] = $row; return 1; }
 function get_results($query, $mode) {
  $a=$query[1]; $rows=array_filter($this->rows, function($r) use ($a) { return $r['artist_id']===$a[0] && $r['fan_id']===$a[1]; });
  return array_slice(array_reverse(array_values($rows)),0,$a[2]);
 }
 function query($query) { $a=$query[1]; foreach($this->rows as &$r) { if($r['artist_id']===$a[0] && $r['fan_id']===$a[1] && $r['sender_id']!==$a[2]) { $r['seen']=1; } } }
}
$GLOBALS['wpdb'] = new FakeDMDB();
update_option('tara_dm_version','1');
update_term_meta(41,'tara_artist_user',11);
update_user_meta(11,'tara_artist_term',41);
tara_artist_verify(41,true);
$GLOBALS['__current']=12;
t('verified artist has direct message control', false !== strpos(tara_dm_button(41), 'data-tara-dm="41"'));
$GLOBALS['__current']=11;
t('artist sees inbox link instead of self messaging', false !== strpos(tara_dm_button(41), 'دایرکت‌های من'));
$GLOBALS['__current']=12;
$id=tara_dm_send(41,12,'سلام',99);
t('fan message stored', 1 === $id && 12 === $GLOBALS['wpdb']->rows[0]['fan_id']);
$GLOBALS['__current']=11;
$rows=tara_dm_thread(41,12);
t('artist reads fan message', 1 === count($rows) && 'سلام' === $rows[0]['body']);
t('read message marked seen', 1 === $GLOBALS['wpdb']->rows[0]['seen']);
$id=tara_dm_send(41,11,'پاسخ',12);
t('artist reply stored', 2 === $id && 11 === $GLOBALS['wpdb']->rows[1]['sender_id']);
$GLOBALS['__current']=12;
$rows=tara_dm_thread(41,12);
t('fan reads reply', 2 === count($rows) && 'پاسخ' === $rows[1]['body']);
t('fan cannot choose a different fan id', 12 === $GLOBALS['wpdb']->rows[0]['fan_id']);
$GLOBALS['__trans']=array();
tara_artist_verify(41,false);
t('unlinked or unverified artist shows disabled explanation', false !== strpos(tara_dm_button(41), 'disabled'));
t('unverified artist rejects new fan message', is_wp_error(tara_dm_send(41,12,'test')));
echo "\nRESULT pass=$PASS fail=$FAIL\n";
