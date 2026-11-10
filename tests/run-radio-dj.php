<?php
require __DIR__ . '/stubs-wp.php';
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', '/tmp/wp/' );
$GLOBALS['__ot']=array(); $GLOBALS['__options']=array(); $GLOBALS['__postmeta']=array();
function tara_track_src($id){return 'https://ex/audio.mp3';}
function tara_track_cover($id,$size=''){return 'https://ex/image.jpg';}
function tara_track_title($id){return 'song';}
function tara_track_artist($id){return 'artist';}
require '/tmp/theme/inc/tara-radio.php';
$tests=array();
$tests['BPM validation'] = tara_radio_bpm(120.5)===120.5 && tara_radio_bpm('oops')===0 && tara_radio_bpm(40)===0 && tara_radio_bpm(240)===0;
$GLOBALS['__postmeta'][7]['_tara_radio_bpm']=120.5;
$tests['track metadata includes BPM'] = tara_radio_track(7)['bpm']===120.5;
$GLOBALS['__ot']['radio_station_file']='https://ex/one.mp3';
$GLOBALS['__ot']['radio_station_file_2']='https://ex/two.mp3';
$GLOBALS['__ot']['radio_station_file_3']='https://ex/one.mp3';
$GLOBALS['__ot']['radio_station_file_4']='';
$GLOBALS['__ot']['radio_station_file_5']='https://ex/five.mp3';
$st=tara_radio_settings()['station'];
$tests['old file remains compatible'] = $st['file']==='https://ex/one.mp3';
$tests['up to five files, blanks and duplicates removed'] = $st['files']===array('https://ex/one.mp3','https://ex/two.mp3','https://ex/five.mp3');
$GLOBALS['__caps']['edit_post']=true;
$_POST=array('tara_radio_bpm_nonce'=>'good','tara_radio_bpm'=>'128.5'); tara_radio_bpm_save(7);
$tests['authorized editor can save BPM'] = get_post_meta(7,'_tara_radio_bpm',true)===128.5;
$_POST=array('tara_radio_bpm_nonce'=>'bad','tara_radio_bpm'=>'100'); tara_radio_bpm_save(7);
$tests['invalid nonce cannot change BPM'] = get_post_meta(7,'_tara_radio_bpm',true)===128.5;
$GLOBALS['__caps']['edit_post']=false; $_POST=array('tara_radio_bpm_nonce'=>'good','tara_radio_bpm'=>'110'); tara_radio_bpm_save(7);
$tests['unauthorized editor cannot change BPM'] = get_post_meta(7,'_tara_radio_bpm',true)===128.5;
$GLOBALS['__caps']['edit_post']=true;
$_POST=array('tara_radio_bpm_nonce'=>'good','tara_radio_bpm'=>''); tara_radio_bpm_save(7);
$tests['blank deletes BPM'] = get_post_meta(7,'_tara_radio_bpm',true)==='';
$fail=0;foreach($tests as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name."\n";if(!$ok)$fail++;}
echo 'RESULT pass='.(count($tests)-$fail).' fail='.$fail."\n";
