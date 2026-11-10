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
    form.addEventListener('submit', function (event) { event.preventDefault(); });
    render();
  });
}());
