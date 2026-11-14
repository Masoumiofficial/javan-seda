/* Keep SearchWP's body-level results inside the visible viewport without
   overriding its vertical positioning, visibility, focus or keyboard behavior. */
(function () {
    'use strict';
    var selector = '.searchwp-live-search-results';
    var pending = false;
    function adjust() {
        pending = false;
        var boxes = document.querySelectorAll(selector);
        var viewport = document.documentElement.clientWidth;
        if (!viewport) { return; }
        for (var i = 0; i < boxes.length; i++) {
            var box = boxes[i];
            if (!box.getClientRects().length) { continue; }
            var rect = box.getBoundingClientRect();
            var delta = rect.left < 8 ? 8 - rect.left : (rect.right > viewport - 8 ? viewport - 8 - rect.right : 0);
            if (Math.abs(delta) < 0.5) { continue; }
            var left = parseFloat(window.getComputedStyle(box).left);
            if (isFinite(left)) { box.style.left = (left + delta) + 'px'; }
        }
    }
    function schedule() {
        if (!pending) { pending = true; window.requestAnimationFrame(adjust); }
    }
    function boot() {
        var observer = new MutationObserver(function (changes) {
            for (var i = 0; i < changes.length; i++) {
                var change = changes[i];
                if (change.type === 'childList' || (change.target.matches && change.target.matches(selector))) { schedule(); return; }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
        window.addEventListener('resize', schedule);
        schedule();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
}());
