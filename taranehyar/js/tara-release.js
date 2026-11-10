(function () {
  'use strict';
  document.querySelectorAll('[data-release-wizard]').forEach(function (form) {
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-release-step]'));
    var next = form.querySelector('[data-release-next]');
    var prev = form.querySelector('[data-release-prev]');
    var submit = form.querySelector('[data-release-submit]');
    var progress = form.querySelector('[data-release-progress]');
    var summary = form.querySelector('[data-release-summary]');
    var service = form.querySelector('[name=service]');
    var current = 0;
    if (!steps.length || !next || !prev || !submit || !progress || !summary || !service) { return; }
    function render() {
      steps.forEach(function (step, index) { step.hidden = index !== current; });
      prev.hidden = current === 0;
      next.hidden = current === steps.length - 1;
      submit.hidden = current !== steps.length - 1;
      progress.textContent = 'مرحلهٔ ' + (current + 1) + ' از ' + steps.length;
      var selected = service.options[service.selectedIndex];
      summary.textContent = selected && selected.value ? 'خدمت: ' + selected.textContent + ' — تعرفهٔ پایه: ' + Number(selected.dataset.price || 0).toLocaleString('fa-IR') + ' تومان' : '';
    }
    prev.addEventListener('click', function () { if (current > 0) { current -= 1; render(); } });
    next.addEventListener('click', function () {
      var fields = steps[current].querySelectorAll('input,select,textarea');
      for (var i = 0; i < fields.length; i += 1) {
        if (!fields[i].checkValidity()) { fields[i].reportValidity(); return; }
      }
      if (current < steps.length - 1) { current += 1; render(); }
    });
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (form.dataset.busy === '1') { return; }
      var data = new FormData(form), cfg = window.TaraAccount || {}, message = form.querySelector('[data-release-message]');
      data.set('action', 'tara_release_submit'); data.set('nonce', cfg.nonce || '');
      form.dataset.busy = '1'; submit.disabled = true; if (message) { message.textContent = 'در حال ثبت…'; }
      fetch(cfg.api || '/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body: data }).then(function (r) { return r.json(); }).then(function (result) {
        if (message) { message.textContent = result && result.data && result.data.message || 'خطایی رخ داد.'; }
        if (result && result.success) { setTimeout(function () { window.location.reload(); }, 900); }
      }).catch(function () { if (message) { message.textContent = 'خطای شبکه؛ با همین فرم دوباره تلاش کنید.'; } }).finally(function () { form.dataset.busy = '0'; submit.disabled = false; });
    });
    render();
  });
  document.querySelectorAll('[data-release-checkout]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault(); if (form.dataset.busy === '1' || !form.reportValidity()) { return; }
      var data = new FormData(form), cfg = window.TaraAccount || {}, msg = form.querySelector('[data-release-message]'), button = form.querySelector('button');
      data.set('action', 'tara_release_checkout'); data.set('nonce', cfg.nonce || ''); form.dataset.busy = '1'; button.disabled = true;
      if (msg) { msg.textContent = 'در حال آماده‌سازی پرداخت…'; }
      fetch(cfg.api || '/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body: data }).then(function (r) { return r.json(); }).then(function (r) {
        if (r && r.success && r.data && r.data.checkout) { window.location.href = r.data.checkout; }
        else if (msg) { msg.textContent = r && r.data && r.data.message || 'خطا در آماده‌سازی پرداخت.'; }
      }).catch(function () { if (msg) { msg.textContent = 'خطای شبکه؛ دوباره تلاش کنید.'; } }).finally(function () { form.dataset.busy = '0'; button.disabled = false; });
    });
  });
}());
