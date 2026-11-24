<?php
require __DIR__ . '/stubs-wp.php';
class CM_DB { public $comments = 'wp_comments'; function prepare($sql,...$args) { foreach ($args as $v) { preg_match('/%[sd]/',$sql,$m); $sql=preg_replace('/%[sd]/',$m[0]==='%d'?(int)$v:"'".$v."'",$sql,1); } return $sql; } }
$GLOBALS['wpdb']=new CM_DB();
function remove_filter($tag,$fn,$priority=10) { $GLOBALS['__hooks'][$tag]=array_values(array_filter($GLOBALS['__hooks'][$tag]??array(),function($item)use($fn){return $item!==$fn;})); }
function get_comment($id) { foreach($GLOBALS['cm_rows'] as $row) { if ($row->comment_ID === (int)$id) return $row; } return null; }
function setup_postdata($post) {}
function comment_loop_cd($c,$args,$depth) { echo '<li id="comment-'.(int)$c->comment_ID.'" data-depth="'.(int)$depth.'"><article>'.esc_html($c->comment_content).'</article>'; }
function get_comments($args) {
 $GLOBALS['cm_last_args']=$args;
 $sql=apply_filters('comments_clauses',array('where'=>''));
 $date=null;$id=0;$operator='';
 if(preg_match("/comment_date_gmt ([<>]) '([^']+)'.*comment_ID [<>] ([0-9]+)/",$sql['where'],$m)) { $operator=$m[1];$date=$m[2];$id=(int)$m[3]; }
 $all=array_values(array_filter($GLOBALS['cm_rows'],function($c) use($args,$date,$id,$operator){
  if($c->comment_post_ID!=$args['post_id'] || (isset($args['parent']) && $c->comment_parent!=$args['parent']) || $c->comment_approved!=='1') return false;
  if($date!==null) { $cmp=strcmp($c->comment_date_gmt,$date); if(!$cmp)$cmp=$c->comment_ID<=>$id; return $operator==='>' ? $cmp>0 : $cmp<0; }
  return true;
 }));
 usort($all,function($a,$b)use($args){$v=strcmp($a->comment_date_gmt,$b->comment_date_gmt);if(!$v)$v=$a->comment_ID<=>$b->comment_ID;return $args['order']==='ASC'?$v:-$v;});
 return array_slice($all,0,$args['number']);
}
require '/tmp/theme/inc/tara-comments-more.php';
$pass=0;$fail=0;function ck($msg,$ok){global $pass,$fail;echo ($ok?'PASS ':'FAIL ').$msg."\n";if($ok)$pass++;else $fail++;}
$GLOBALS['__options']['default_comments_page']='oldest';$GLOBALS['__options']['comment_order']='asc';$GLOBALS['__options']['thread_comments']=1;$GLOBALS['__options']['thread_comments_depth']=5;
$GLOBALS['cm_rows']=array();
function cm_add($id,$parent=0,$status='1',$post=10) { $GLOBALS['cm_rows'][]=(object)array('comment_ID'=>$id,'comment_parent'=>$parent,'comment_post_ID'=>$post,'comment_date_gmt'=>'2026-01-01 00:00:00','comment_approved'=>$status,'comment_content'=>'safe <script>'); }
for($i=1;$i<=45;$i++) cm_add($i,$i>15?1:0);
cm_add(100,0,'0'); cm_add(101,0,'1',11);
ck('empty post has no rows', tara_cm_get_batch(999,0,'',5)===array());
ck('cursor validation',tara_cm_valid_cursor('2026-01-01 00:00:00|3') && !tara_cm_valid_cursor("x' OR 1=1") && !tara_cm_valid_cursor(array()));
$budget=30;ob_start();$r=tara_cm_render_batch(10,0,'',$budget,1,20);$html=ob_get_clean();
ck('bounded initial output',substr_count($html,'<article>')<=30 && $budget>=0);
ck('unapproved and different posts excluded',strpos($html,'comment-100"')===false && strpos($html,'comment-101"')===false);
ck('nested replies and continuation',strpos($html,'data-depth="2"')!==false && strpos($html,'مشاهده پاسخ‌های بیشتر')!==false);
ck('existing callback output escaped',strpos($html,'&lt;script&gt;')!==false);
$root1=tara_cm_get_batch(10,0,'',5);$cursor=tara_cm_cursor($root1[4]);cm_add(102); // insert after cursor; no duplicate even as data changes
$root2=tara_cm_get_batch(10,0,$cursor,5);
ck('cursor participates in query cache key', isset($GLOBALS['cm_last_args']['cache_domain']) && $GLOBALS['cm_last_args']['cache_domain'] === 'tara-comments-' . md5($cursor));
ck('keyset avoids repeat after insert',!array_intersect(array_column(array_slice($root1,0,5),'comment_ID'),array_column($root2,'comment_ID')));
$GLOBALS['__options']['default_comments_page']='newest';$GLOBALS['__options']['comment_order']='desc';$desc=tara_cm_get_batch(10,0,'',5);$next=tara_cm_get_batch(10,0,tara_cm_cursor($desc[4]),5);
ck('descending order and cursor', $desc[0]->comment_ID===102 && !array_intersect(array_column(array_slice($desc,0,5),'comment_ID'),array_column($next,'comment_ID')));

$GLOBALS['__options']['thread_comments']=0; $flat=tara_cm_get_batch(10,0,'',20);
ck('threading off includes replies in flat list', count($flat)>15);

$GLOBALS['__options']['thread_comments']=1; $GLOBALS['__options']['comment_order']='asc'; $budget=7; ob_start(); $newest=tara_cm_render_batch(10,0,'',$budget,1,5); $revhtml=ob_get_clean();
ck('newest-first batch remains descending and reserves siblings', strpos($revhtml,'comment-15"') < strpos($revhtml,'comment-12"') && substr_count($revhtml,'data-depth="1"')===5 && strpos($newest['cursor'],'|12')!==false);
foreach (array('newest'=>'DESC','oldest'=>'ASC') as $first=>$direction) {
 $GLOBALS['__options']['default_comments_page']=$first; $cursor=''; $allids=array(); $steps=0;
 do {
  $budget=30;ob_start();$batch=tara_cm_render_batch(10,0,$cursor,$budget,1,5);$out=ob_get_clean();
  preg_match_all('/id="comment-([0-9]+)" data-depth="1"/',$out,$found);
  $allids=array_merge($allids,array_map('intval',$found[1]));$cursor=$batch['cursor'];$steps++;
 } while($batch['more'] && $steps<10);
 $expected=array_merge(range(1,15),array(102)); if($direction==='DESC') $expected=array_reverse($expected);
 ck('all root batches appended in '.$direction.' order without duplicates', $allids===$expected && !$batch['more'] && $steps>1);
}
ck('password posts excluded',!tara_cm_is_post((object)array('post_type'=>'post','post_status'=>'publish','post_password'=>'secret')));
$GLOBALS['__posts'][10]=new WP_Post(10,'Song');
$_POST=array('post_id'=>'10','nonce'=>'bad','parent'=>'0','cursor'=>''); try { tara_cm_ajax(); } catch (RuntimeException $e) {}
ck('nonce rejected', !$GLOBALS['__json']['success'] && $GLOBALS['__json']['code']===403);
$_POST=array('post_id'=>'10','nonce'=>'good','parent'=>array('0'),'cursor'=>''); try { tara_cm_ajax(); } catch (RuntimeException $e) {}
ck('array parent rejected', !$GLOBALS['__json']['success'] && $GLOBALS['__json']['code']===400);
$_POST=array('post_id'=>'10','nonce'=>'good','parent'=>'101','cursor'=>''); try { tara_cm_ajax(); } catch (RuntimeException $e) {}
ck('parent of different post rejected', !$GLOBALS['__json']['success'] && $GLOBALS['__json']['code']===400);
$_POST=array('post_id'=>'10','nonce'=>'good','parent'=>'0','cursor'=>"bad' OR 1=1"); try { tara_cm_ajax(); } catch (RuntimeException $e) {}
ck('invalid cursor rejected', !$GLOBALS['__json']['success'] && $GLOBALS['__json']['code']===400);
$_POST=array('post_id'=>'10','nonce'=>'good','parent'=>'1','cursor'=>''); try { tara_cm_ajax(); } catch (RuntimeException $e) {}
ck('approved child batch works', $GLOBALS['__json']['success'] && strpos($GLOBALS['__json']['data']['html'],'data-depth="2"')!==false);
echo "RESULT pass=$pass fail=$fail\n";
