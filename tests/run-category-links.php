<?php
/* Category description save/render regression test using the WordPress stubs. */
require __DIR__ . '/stubs-wp.php';
require '/tmp/theme/inc/ab-artist-fields.php';
require '/tmp/theme/inc/ab-cat-desc.php';
require '/tmp/theme/inc/ab-terms-admin.php';
$GLOBALS['__termmeta']=array(); $GLOBALS['__terms']=array(); $GLOBALS['__trans']=array();
$GLOBALS['__caps']=array('manage_categories'=>true);
$term = new WP_Term(8, 'موسیقی', 'category', 'music', 1);
$GLOBALS['__terms'][8]=$term;
$_POST=array('tara_term_nonce'=>'good','tara_cat_form'=>'1','ab_desc_short'=>'','ab_desc_full'=>'<p>متن <a href="https://example.org/a?x=1&amp;y=2" target="_blank" rel="nofollow">پیوند خارجی</a> و <a href="/music/">داخلی</a>.</p><script>bad()</script>');
tara_save_term_fields(8,8,'category');
$html=tara_category_desc_full($term); $saved=get_term_meta(8,'ab_desc_full',true);
$tests=array(
 'anchor href survives save and output'=>strpos($saved,'href="https://example.org/a?x=1&amp;y=2"')!==false && strpos($html,'href="https://example.org/a?x=1&amp;y=2"')!==false,
 'relative links survive'=>strpos($html,'href="/music/"')!==false,
 'target and rel survive'=>strpos($html,'target="_blank"')!==false && strpos($html,'rel="nofollow"')!==false,
 'script tag is removed by stub sanitizer'=>strpos($html,'<script')===false,
 'category heading uses requested form'=>strpos($html, 'درباره موسیقی')!==false && strpos($html, 'دربارهٔ')===false,
 'all text remains in output'=>strpos($html,'پیوند خارجی')!==false && strpos($html,'داخلی')!==false,
 'toggle is progressive enhancement'=>strpos($html,'hidden>نمایش بیشتر')!==false && strpos($html,'aria-expanded="true"')!==false,
);
$fail=0; foreach($tests as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name."\n"; if(!$ok) $fail++; }
echo 'RESULT pass='.(count($tests)-$fail).' fail='.$fail."\n";
