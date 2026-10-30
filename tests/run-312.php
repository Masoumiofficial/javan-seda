<?php
/** Focused tests for artist identity, followers, quotes and messaging access. */
require __DIR__ . '/run-391-fixtures.php';
require '/tmp/theme/inc/tara-trust.php';
require '/tmp/theme/inc/tara-dm.php';
require '/tmp/theme/inc/tara-artisthub.php';

update_term_meta(41, 'tara_artist_user', 11);
update_user_meta(11, 'tara_artist_term', 41);
t('linked artist has reciprocal identity', 41 === tara_artist_term_of_user(11));
update_user_meta(12, 'tara_artist_term', 41);
t('unrelated user cannot claim artist badge', 0 === tara_artist_term_of_user(12));
tara_artist_verify(41, true);
t('verified artist has badge', '' !== tara_artist_verified_badge(41));
t('linked verified user has badge', '' !== tara_user_verified_badge(11));
t('unrelated user has no badge', '' === tara_user_verified_badge(12));
$GLOBALS['__options']['option_tree']['artist_verified_on'] = 'off';
t('hidden badge preserves verification', tara_artist_is_verified(41) && '' === tara_artist_verified_badge(41));
$GLOBALS['__options']['option_tree']['artist_verified_on'] = 'on';
update_term_meta(41, 'tara_followers', 100);
$r = tara_artist_followers_set(41, 90, 'test');
t('previous follower peak is preserved', 100 === tara_artist_follower_peak(41));
t('drop raises warning', $r['drop'] && isset(tara_follower_warnings()[41]));
t('follower history records both values', 90 === tara_artist_follower_history(41)[0]['n'] && 100 === tara_artist_follower_history(41)[0]['p']);
tara_artist_followers_set(41, 110, 'test');
t('new peak clears warning', 110 === tara_artist_follower_peak(41) && !isset(tara_follower_warnings()[41]));
$q = tara_hub_promo_quote(2500);
t('promotion discount counts whole thousands', 2 === $q['units'] && 20000 === $q['discount'] && 270000 === $q['final']);
$q = tara_hub_promo_quote(1000000);
t('promotion discount capped at price', 0 === $q['final'] && $q['discount'] === $q['price']);
t('fan can contact verified artist', tara_dm_can_message(41, 12));
t('artist cannot message self', !tara_dm_can_message(41, 11));
tara_artist_verify(41, false);
t('fan cannot message unverified artist', !tara_dm_can_message(41, 12));
$GLOBALS['__attachments'][701] = true;
$GLOBALS['__postmeta'][701]['album320'] = '701';
t('album attachment ID is normalized', tara_music_value('album320', 701) === wp_get_attachment_url(701));
$GLOBALS['__is_ssl'] = true;
t('download preserves external HTTP origin', 'http://files.example/song.mp3' === tara_media_url('http://files.example/song.mp3', true));
t('player still upgrades HTTP scheme', 'https://files.example/song.mp3' === tara_media_url('http://files.example/song.mp3'));
t('JSON file metadata is resolved', 'https://files.example/a.mp3' === tara_media_url('{"url":"https://files.example/a.mp3"}', true));
if (!defined('MB_IN_BYTES')) { define('MB_IN_BYTES', 1048576); }
function ajax_track_test($uid, $files) {
    $GLOBALS['__current'] = $uid;
    $_POST = array('do'=>'submit_track', 'title'=>'عنوان', 'artist_fa'=>'هنرمند');
    $_FILES = $files;
    try { tara_hub_ajax(); } catch (RuntimeException $e) {}
    return $GLOBALS['__json'];
}
$before = count($GLOBALS['__posts']);
$r = ajax_track_test(12, array());
t('unlinked user cannot submit track', !$r['success']);
tara_artist_verify(41, true);
$r = ajax_track_test(11, array());
t('audio required for track', !$r['success']);
$r = ajax_track_test(11, array('audio128'=>array('name'=>'fake.mp3','tmp_name'=>'/tmp/not-upload','size'=>100,'error'=>0)));
t('forged upload is rejected', !$r['success'] && count($GLOBALS['__posts']) === $before);
file_put_contents('/tmp/fake-mp3', 'not an MP3');
t('fake mp3 header is rejected', !tara_hub_mp3_header_valid('/tmp/fake-mp3'));
file_put_contents('/tmp/fake-mp3', "ID3\x04\x00\x00\x00");
t('id3 audio header is recognized', tara_hub_mp3_header_valid('/tmp/fake-mp3'));
file_put_contents('/tmp/fake-mp3', "\xff\xfb\x90\x00");
t('mpeg audio frame is recognized', tara_hub_mp3_header_valid('/tmp/fake-mp3'));
unlink('/tmp/fake-mp3');
echo "\nRESULT pass=$PASS fail=$FAIL\n";
