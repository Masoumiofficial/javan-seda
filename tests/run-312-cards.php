<?php
/** Artist cards use verified status of the matched term, never the display name alone. */
require __DIR__ . '/run-391-fixtures.php';
require '/tmp/theme/inc/tara-trust.php';
require '/tmp/theme/inc/ab-artist-cards.php';
$item = array('title' => 'خوانندهٔ الف', 'siing_u' => 'https://example.org/artist/singer-a/', 'siing_img' => 'https://example.org/a.jpg');
$plain = tara_artist_card($item);
t('unverified card has no badge', false === strpos($plain, 'ab-artist-card__badge') && false === strpos($plain, 'ty-verified'));
tara_artist_verify(41, true);
$verified = tara_artist_card($item);
t('verified card has profile and name badges', 2 === substr_count($verified, 'class="ty-verified ty-verified--sm"'));
t('portrait badge inside media before name', strpos($verified, 'ab-artist-card__media') < strpos($verified, 'ab-artist-card__badge') && strpos($verified, 'ab-artist-card__badge') < strpos($verified, 'ab-artist-card__name--verified'));
t('name and badge share a single layout container', false !== strpos($verified, 'ab-artist-card__name-text') && strpos($verified, 'ab-artist-card__name-text') < strrpos($verified, 'ty-verified--sm'));
t('decorative portrait badge is hidden from screen readers', false !== strpos($verified, 'ab-artist-card__badge" aria-hidden="true"'));
$GLOBALS['__options']['option_tree']['artist_verified_on'] = 'off';
t('global badge setting applies to both', false === strpos(tara_artist_card($item), 'ty-verified'));
$GLOBALS['__options']['option_tree']['artist_verified_on'] = 'on';
$unknown = tara_artist_card(array('title' => 'غریبه', 'siing_u' => 'https://example.org/artist/missing/', 'siing_img' => 'https://example.org/a.jpg'));
t('unmatched term never receives a badge', false === strpos($unknown, 'ty-verified'));
echo "\nRESULT pass=$PASS fail=$FAIL\n";
