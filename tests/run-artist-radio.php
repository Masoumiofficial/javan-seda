<?php
require __DIR__ . '/stubs-wp.php';
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', '/tmp/wp/' );
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) define( 'MINUTE_IN_SECONDS', 60 );
$GLOBALS['__trans'] = array();
$GLOBALS['__terms'][31] = new WP_Term(31,'A','artist','a',4);
$GLOBALS['__terms'][32] = new WP_Term(32,'B','artist','b',1);
$GLOBALS['__terms'][21] = new WP_Term(21,'pop','tara_genre','pop',1);
$GLOBALS['__taxonomies']['tara_genre'] = (object) array('name'=>'tara_genre');
$GLOBALS['__postmeta'] = array(
  1 => array('musics_type'=>'mp3','views'=>5),
  2 => array('musics_type'=>'song','views'=>30),
  3 => array('musics_type'=>'video','views'=>99),
  4 => array('musics_type'=>'آهنگ','views'=>10),
  5 => array('musics_type'=>'audio','views'=>500),
  6 => array('musics_type'=>'news','views'=>1000),
  7 => array('musics_type'=>'ahang','views'=>20),
);
$GLOBALS['artists'] = array(1=>31,2=>31,3=>31,4=>31,5=>32,6=>31,7=>31);
function tara_track_src($id) { return $id === 4 ? '' : 'https://ex/'.$id.'.mp3'; }
function tara_track_cover($id,$size='') { return 'https://ex/image.jpg'; }
function tara_track_title($id) { return 'song '.$id; }
function tara_track_artist($id) { return 'artist'; }
function get_posts($args=array()) {
 $GLOBALS['last_query']=$args;
 $ids=array(7,6,5,4,3,2,1);
 if (!empty($args['tax_query'])) { $term=$args['tax_query'][0]['terms']; $ids=array_values(array_filter($ids,function($id) use($term){return $GLOBALS['artists'][$id]===$term;})); }
 foreach (($args['meta_query'] ?? array()) as $clause) { if (($clause['key'] ?? '')==='musics_type') $ids=array_values(array_filter($ids,function($id) use($clause){return in_array($GLOBALS['__postmeta'][$id]['musics_type'],$clause['value'],true);})); }
 if (!empty($args['post__not_in'])) $ids=array_values(array_diff($ids,$args['post__not_in']));
 if (($args['orderby'] ?? '')==='meta_value_num') usort($ids,function($a,$b){return $GLOBALS['__postmeta'][$b]['views']-$GLOBALS['__postmeta'][$a]['views'];});
 if (($args['orderby'] ?? '')==='rand') $ids=array_reverse($ids);
 return array_slice($ids,0,$args['posts_per_page']);
}
function sanitize_html_class($s,$f='') { return preg_replace('/[^A-Za-z0-9_-]/','',(string)$s); }
require '/tmp/theme/inc/tara-radio.php';
require '/tmp/theme/inc/tara-radio-ui.php';
function checkit($name,$ok) { echo ($ok?'PASS ':'FAIL ').$name."\n"; if (!$ok) $GLOBALS['failures']++; }
$GLOBALS['failures']=0;
$args=array('taxonomy'=>'artist','term_id'=>31,'limit'=>20);
checkit('new: artist, type and audio file isolation',tara_radio_ids($args+array('mode'=>'new'))===array(7,2,1));
checkit('top sorted, without interleave',tara_radio_ids($args+array('mode'=>'views'))===array(2,7,1));
checkit('random scoped',tara_radio_ids($args+array('mode'=>'random'))===array(1,2,7));
checkit('no children included',$GLOBALS['last_query']['tax_query'][0]['include_children']===false);
checkit('unknown artist returns empty',tara_radio_ids(array('taxonomy'=>'artist','term_id'=>999,'mode'=>'new'))===array());
checkit('mismatched term returns empty',tara_radio_ids(array('taxonomy'=>'artist','term_id'=>21,'mode'=>'new'))===array());
checkit('incomplete scope returns empty',tara_radio_ids(array('taxonomy'=>'artist','term_id'=>0,'mode'=>'new'))===array());
checkit('empty when excluded',tara_radio_ids($args+array('mode'=>'new','exclude'=>array(1,2,7)))===array());
foreach (array(array('tax'=>'artist','term'=>31,'mode'=>'views'),array('tax'=>'artist','term'=>999),array('tax'=>'bad','term'=>31),array('tax'=>'artist','term'=>21),array('tax'=>'artist'),array('tax'=>'!!!')) as $i=>$input) {
 $_GET=$input; try { tara_radio_ajax(); } catch (RuntimeException $e) {}
 checkit('ajax scope '.$i, $i===0 ? ($GLOBALS['__json']['success'] && array_column($GLOBALS['__json']['data']['tracks'],'id')===array(2,7,1)) : !$GLOBALS['__json']['success']);
}
$html=tara_radio_box(array('context'=>'artist','taxonomy'=>'artist','term_id'=>31,'mode'=>'new','sources'=>true));
checkit('artist DJ renders three sources',strpos($html,'data-radio-source="new"')!==false && strpos($html,'data-radio-source="views"')!==false && strpos($html,'data-radio-source="random"')!==false && strpos($html,'data-radio-source="mixed"')===false && strpos($html,'data-radio-source="hot"')===false);
checkit('artist DJ refuses missing scope',tara_radio_box(array('context'=>'artist','taxonomy'=>'','term_id'=>0))==='');
checkit('artist DJ refuses mismatched scope',tara_radio_box(array('context'=>'artist','taxonomy'=>'artist','term_id'=>21))==='');
checkit('artist DJ hides global personal list control',strpos($html,'data-radio-mine aria-expanded')===false);
$GLOBALS['__options']['option_tree']['radio_on']='off';
checkit('master switch disables DJ',tara_radio_box(array('context'=>'artist','taxonomy'=>'artist','term_id'=>31))==='');
$GLOBALS['__options']['option_tree']['radio_on']='on';
checkit('DJ and old player default independently on',tara_radio_option('artist')==='on' && tara_radio_option('artist_player')==='on');
echo 'RESULT pass='.(20-$GLOBALS['failures']).' fail='.$GLOBALS['failures']."\n";
