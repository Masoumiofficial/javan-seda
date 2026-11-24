<?php
require __DIR__ . '/stubs-wp.php';
if (!defined('ABSPATH')) define('ABSPATH','/tmp/wp/');
$GLOBALS['__options'] = array('option_tree'=>array());
$GLOBALS['__postmeta'] = array();
$GLOBALS['__posts'] = array();
$GLOBALS['__terms'][31] = new WP_Term(31,'آرام','tara_mood');
foreach(array(1,2,3,4,5,6,7) as $id) $GLOBALS['__posts'][$id]=new WP_Post($id,'track '.$id);
$GLOBALS['__posts'][2]->post_status='draft';
$GLOBALS['__posts'][3]->post_type='page';
$GLOBALS['__posts'][7]->post_password='secret';
$GLOBALS['__postmeta'][7]['musics_type']='mp3';
$GLOBALS['__postmeta'][1]['musics_type']='mp3';
$GLOBALS['__postmeta'][2]['musics_type']='mp3';
$GLOBALS['__postmeta'][3]['musics_type']='mp3';
$GLOBALS['__postmeta'][4]['musics_type']='video';
$GLOBALS['__postmeta'][5]['musics_type']='song';
$GLOBALS['__postmeta'][6]['musics_type']='آهنگ';
function tara_track_src($id){return $id===5 ? '' : 'https://ex/'.$id.'.mp3';}
function tara_track_cover($id,$size=''){return 'https://ex/cover.jpg';}
function tara_track_title($id){return 'track '.$id;}
function tara_track_artist($id){return 'artist';}
function sanitize_html_class($s,$f=''){return preg_replace('/[^A-Za-z0-9_-]/','',(string)$s);}
require '/tmp/theme/inc/tara-radio.php';
require '/tmp/theme/inc/tara-radio-ui.php';
require '/tmp/theme/inc/tara-playlists.php';
$fail=0;
function checkit($name,$ok){global $fail;echo ($ok?'PASS ':'FAIL ').$name."\n";if(!$ok)$fail++;}
checkit('audio published IDs only, ordered and unique',tara_playlist_audio_ids(array(6,1,1,2,3,4,5,7,99))===array(6,1));
$html=tara_playlist_radio(array(6,1,2,4),'list');
checkit('fixed radio produced',strpos($html,'data-radio-context="playlist"')!==false);
checkit('no global sources or mine',strpos($html,'data-radio-source=')===false && strpos($html,'data-radio-mine aria-expanded')===false);
checkit('empty does not fall back',tara_playlist_radio(array(2,4),'empty')==='');
$GLOBALS['__options']['option_tree']['radio_playlist_personal']='off';
checkit('personal DJ switch is independent',tara_playlist_radio(array(1),'mine','personal')==='' && tara_playlist_radio(array(1),'curated')!=='');
$GLOBALS['__options']['option_tree']['radio_playlist_curated']='off';
checkit('curated DJ switch',tara_playlist_radio(array(1),'curated')==='');
$_POST=array('tara_playlist_nonce'=>array('bad'),'tara_playlist_mood'=>array(31));
tara_playlist_meta_save(50);
checkit('array nonce safely rejected',empty($GLOBALS['__postmeta'][50]));
$_POST=array('tara_playlist_nonce'=>'valid','tara_playlist_mood'=>array(31));
tara_playlist_meta_save(50);
checkit('array mood safely ignored',empty($GLOBALS['__postmeta'][50]));
echo "RESULT pass=".(8-$fail)." fail=$fail\n";
if($fail) exit(1);
