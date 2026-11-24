(function () {
 'use strict';
 function init(box) {
  var ajax=box.getAttribute('data-ajax'), post=box.getAttribute('data-post'), nonce=box.getAttribute('data-nonce');
  function request(data) {
   return fetch(ajax,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:new URLSearchParams(data).toString()}).then(function(r){return r.json();});
  }
  function load(control, retry) {
   var button=control.querySelector('button'), status=control.querySelector('[role=status]');
   if (!button || button.disabled) return Promise.resolve(false);
   button.disabled=true; status.textContent='در حال بارگذاری…';
   var data={action:'tara_comments_more',post_id:post,nonce:nonce,parent:control.getAttribute('data-parent'),cursor:control.getAttribute('data-cursor')};
   return request(data).then(function(result){
    if (!result.success && result.data && result.data.code==='nonce' && retry) {
     return request({action:'tara_comments_nonce',post_id:post}).then(function(fresh){
      if(!fresh.success || !fresh.data || !fresh.data.nonce) throw new Error('nonce');
      nonce=fresh.data.nonce; button.disabled=false; return load(control,false);
     });
    }
    if (!result.success || !result.data || typeof result.data.html!=='string') throw new Error('load');
    var list=control.parentElement.querySelector(':scope > ol[data-tara-children]');
    if(!list) throw new Error('list');
    list.insertAdjacentHTML('beforeend',result.data.html);
    control.setAttribute('data-cursor',result.data.cursor || '');
    status.textContent=''; button.disabled=false;
    if(!result.data.more) { control.remove(); }
    if(!window.MutationObserver && !window.WebKitMutationObserver && !window.MozMutationObserver && window.addComment && typeof window.addComment.init==='function') window.addComment.init(list);
    return true;
   }).catch(function(){button.disabled=false;status.textContent='بارگذاری ناموفق بود؛ دوباره تلاش کنید.';return false;});
  }
  box.addEventListener('click',function(e){var button=e.target.closest('.tara-cmore-button'); if(!button || !box.contains(button)) return; load(button.parentElement,true);});
 }
 function boot(){document.querySelectorAll('.tara-cmore').forEach(init);}
 if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',boot); else boot();
}());
