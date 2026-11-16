(function () {
  'use strict';
  document.querySelectorAll('[data-release-wizard]').forEach(function (form) {
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-release-step]'));
    var next = form.querySelector('[data-release-next]');
    var prev = form.querySelector('[data-release-prev]');
    var submit = form.querySelector('[data-release-submit]');
    var progress = form.querySelector('[data-release-progress]');
    var summary = form.querySelector('[data-release-summary]');
    var progressBar = form.querySelector('[data-release-progress-bar]');
    var plan = form.querySelector('[name=plan]');
    var service = form.querySelector('[name=service]');
    var current = 0;
    if (!steps.length || !next || !prev || !submit || !progress || !summary || !service) { return; }
    var maxAudio = Number(form.dataset.maxAudio || 31457280), maxCover = Number(form.dataset.maxCover || 5242880);
    function render() {
      steps.forEach(function (step, index) { step.hidden = index !== current; });
      prev.hidden = current === 0;
      next.hidden = current === steps.length - 1;
      submit.hidden = current !== steps.length - 1;
      var percent = Math.round(((current + 1) / steps.length) * 100);
      progress.textContent = 'مرحله ' + (current + 1) + ' از ' + steps.length + ' · ' + percent.toLocaleString('fa-IR') + '٪';
      if (progressBar) { progressBar.style.width = percent + '%'; }
      var selected = service.options[service.selectedIndex], chosenPlan = plan ? plan.options[plan.selectedIndex] : null;
      var title = form.querySelector('[name=title]'), performer = form.querySelector('[name=performer]');
      summary.textContent = selected && selected.value ? 'نوع اثر: ' + selected.textContent + (chosenPlan && chosenPlan.value ? ' — پلن: ' + chosenPlan.textContent : '') + (title && title.value ? ' — اثر: ' + title.value : '') + (performer && performer.value ? ' — هنرمند: ' + performer.value : '') + ' — تعرفه پایه: ' + Number(selected.dataset.price || 0).toLocaleString('fa-IR') + ' تومان' : '';
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
      function fail(text) { if (message) { message.textContent = text; } }
      var audio = ['audio128', 'audio320'].map(function (name) { return form.querySelector('[name=' + name + ']'); }).filter(function (input) { return input && input.files && input.files[0]; });
      if (!audio.length) { fail('حداقل یک فایل MP3 با کیفیت ۱۲۸ یا ۳۲۰ انتخاب کنید.'); return; }
      for (var a = 0; a < audio.length; a += 1) {
        var file = audio[a].files[0];
        if (file.size > maxAudio) { fail('حجم فایل صوتی بیش از ۳۰ مگابایت است.'); return; }
        if (!/\.mp3$/i.test(file.name)) { fail('فقط فایل MP3 مجاز است.'); return; }
      }
      var cover = form.querySelector('[name=cover_file]');
      if (cover && cover.files && cover.files[0]) {
        if (cover.files[0].size > maxCover) { fail('حجم کاور بیش از ۵ مگابایت است.'); return; }
        if (!/\.(jpe?g|png)$/i.test(cover.files[0].name)) { fail('کاور باید JPG یا PNG باشد.'); return; }
      }
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
