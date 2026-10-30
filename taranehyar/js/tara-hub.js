(function () {
  'use strict';
  var CFG = window.TaraAccount || {};
  var API = CFG.api || '/wp-admin/admin-ajax.php';
  var NONCE = CFG.nonce || '';

  function post(data) {
    var body = new FormData();
    Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });
    body.set('nonce', data.action === 'tara_dm' ? (CFG.dmNonce || '') : NONCE);
    return fetch(API, { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (r) { return r.json(); });
  }
  function msg(form, text, ok) {
    var el = form && form.querySelector('[data-tara-hub-msg]');
    if (el) { el.textContent = text || ''; el.style.color = ok ? '#15803d' : '#dc2626'; }
  }

  /* --- پنل هنرمند: فرم‌ها --- */
  document.querySelectorAll('[data-tara-hub]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      if (form.dataset.busy === '1') { return; }
      form.dataset.busy = '1';
      var submit = form.querySelector('button[type=submit]');
      if (submit) { submit.disabled = true; }
      var kind = form.getAttribute('data-tara-hub');
      var fd = new FormData(form);
      var data = { action: 'tara_hub', 'do': kind };
      fd.forEach(function (v, k) { data[k] = v; });
      msg(form, 'در حال ثبت…', true);
      post(data).then(function (res) {
        if (res && res.success) {
          msg(form, (res.data && res.data.message) || 'ثبت شد.', true);
          if (res.data && res.data.checkout) { window.location.href = res.data.checkout; return; }
          if (kind !== 'buy') { setTimeout(function () { window.location.reload(); }, 900); }
        } else {
          msg(form, (res && res.data && res.data.message) || 'خطایی رخ داد.', false);
        }
      }).catch(function () { msg(form, 'خطای شبکه؛ دوباره تلاش کنید.', false); }).finally(function () {
        form.dataset.busy = '0';
        if (submit) { submit.disabled = false; }
      });
    });
  });

  /* --- دایرکت --- */
  var sheet = document.getElementById('ty-dm');
  if (sheet) {
    var bodyEl = sheet.querySelector('[data-ty-dm-body]');
    var formEl = sheet.querySelector('[data-ty-dm-form]');
    var artistEl = sheet.querySelector('[data-ty-dm-artist]');
    var fanEl = sheet.querySelector('[data-ty-dm-fan]');
    var titleEl = sheet.querySelector('[data-ty-dm-title]');
    var statusEl = sheet.querySelector('[data-ty-dm-status]');
    function status(text) { if (statusEl) { statusEl.textContent = text || ''; } }
    var current = { artist: 0, fan: 0 };
    var refreshTimer = null;

    function render(rows) {
      if (!rows || !rows.length) {
        bodyEl.innerHTML = '<p class="ty-dm__empty">هنوز پیامی رد و بدل نشده؛ اولین پیام را بنویسید.</p>';
        return;
      }
      bodyEl.innerHTML = rows.map(function (m) {
        return '<div class="ty-dm__msg' + (m.mine ? ' is-mine' : '') + '"><p>' +
          String(m.body || '').replace(/</g, '&lt;') + '</p><time>' +
          String(m.when || '').replace(/</g, '&lt;') + '</time></div>';
      }).join('');
      bodyEl.scrollTop = bodyEl.scrollHeight;
    }
    function load() {
      var selected = current;
      post({ action: 'tara_dm', 'do': 'thread', artist: selected.artist, fan: selected.fan })
        .then(function (res) {
          if (current !== selected || sheet.hidden) { return; }
          if (res && res.success) { status(''); render(res.data.messages); }
          else { status((res && res.data && res.data.message) || 'دریافت پیام‌ها ناموفق بود.'); }
        }).catch(function () { status('خطای شبکه؛ دوباره تلاش کنید.'); });
    }
    function open(artist, fan, label) {
      current = { artist: artist || 0, fan: fan || 0 };
      if (artistEl) { artistEl.value = current.artist; }
      if (fanEl) { fanEl.value = current.fan; }
      if (titleEl && label) { titleEl.textContent = label; }
      sheet.hidden = false;
      document.body.classList.add('ty-dm-open');
      status('');
      bodyEl.innerHTML = '<p class="ty-dm__empty">در حال بارگذاری…</p>';
      load();
      if (refreshTimer) { clearInterval(refreshTimer); }
      refreshTimer = setInterval(function () { if (!document.hidden) { load(); } }, 15000);
    }
    function close() {
      sheet.hidden = true;
      if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
      document.body.classList.remove('ty-dm-open');
    }
    document.querySelectorAll('[data-tara-dm]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        open(btn.getAttribute('data-tara-dm'), btn.getAttribute('data-tara-dm-fan') || 0, btn.textContent.trim());
      });
    });
    sheet.querySelectorAll('[data-ty-dm-close]').forEach(function (el) {
      el.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !sheet.hidden) { close(); } });
    if (formEl) {
      formEl.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var ta = formEl.querySelector('textarea');
        var text = (ta && ta.value || '').trim();
        if (!text) { return; }
        post({ action: 'tara_dm', 'do': 'send', artist: current.artist, fan: current.fan, body: text })
          .then(function (res) {
            if (res && res.success) { ta.value = ''; status(''); load(); }
            else { status((res && res.data && res.data.message) || 'ارسال پیام ناموفق بود.'); }
          }).catch(function () { status('خطای شبکه؛ دوباره تلاش کنید.'); });
      });
    }
  }

  /* --- فهرستِ آهنگ‌ها: اول ۴ ردیف، بعد «نمایش همه» (کم‌کردنِ طولِ صفحه) --- */
  document.querySelectorAll('.ty-artist .ty-tracklist, .music_box_player .play-list').forEach(function (list) {
    var items = list.querySelectorAll(':scope > li, :scope > .play-list-row');
    if (items.length <= 4 || list.closest('.ty-acct')) { return; }
    list.classList.add('ty-list--collapsed');
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ty-list-more';
    btn.textContent = 'نمایش همه (' + items.length + ')';
    btn.setAttribute('aria-expanded', 'false');
    list.insertAdjacentElement('afterend', btn);
    btn.addEventListener('click', function () {
      var open = list.classList.toggle('ty-list--collapsed');
      btn.textContent = open ? 'نمایش همه (' + items.length + ')' : 'نمایش کمتر';
      btn.setAttribute('aria-expanded', String(!open));
    });
  });
  // The radio repaints its queue while playing; observe changes without altering playback.
  document.querySelectorAll('[data-radio-queue]').forEach(function (list) {
    var btn = list.parentNode.querySelector('[data-tara-radio-more]');
    if (!btn) { return; }
    function update() { btn.hidden = list.children.length <= 4; }
    btn.addEventListener('click', function () {
      var collapsed = list.classList.toggle('ty-list--collapsed');
      btn.setAttribute('aria-expanded', String(!collapsed));
      btn.textContent = collapsed ? 'نمایش بیشتر' : 'نمایش کمتر';
    });
    if (window.MutationObserver) { new MutationObserver(update).observe(list, { childList: true }); }
    update();
  });
  // Collapsible footer sections on narrow screens; without JS they stay visible.
  document.querySelectorAll('.ab-footer__col--links, .ab-footer__col--cats').forEach(function (column, index) {
    var heading = column.querySelector('.ab-footer__title');
    if (!heading) { return; }
    var button = document.createElement('button');
    button.type = 'button'; button.className = 'ty-footer-toggle';
    button.textContent = heading.textContent;
    button.setAttribute('aria-expanded', 'false');
    var panel = document.createElement('div'); panel.id = 'ty-footer-panel-' + index;
    button.setAttribute('aria-controls', panel.id);
    while (heading.nextSibling) { panel.appendChild(heading.nextSibling); }
    heading.textContent = ''; heading.appendChild(button); column.appendChild(panel);
    function sync() { panel.hidden = window.matchMedia('(max-width: 767px)').matches && button.getAttribute('aria-expanded') === 'false'; }
    button.addEventListener('click', function () { button.setAttribute('aria-expanded', button.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'); sync(); });
    window.addEventListener('resize', sync); sync();
  });
})();
