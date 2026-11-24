/**
 * پنل «ترانه یار» — رفتار پیشخوان
 * فقط در صفحه‌های پنل بارگذاری می‌شود. هیچ وابستگی بیرونی ندارد.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-ty-copy]');
		if (!button) {
			return;
		}

		event.preventDefault();

		var target = document.querySelector(button.getAttribute('data-ty-copy'));
		if (!target) {
			return;
		}

		var text = typeof target.value === 'string' ? target.value : target.textContent;
		var original = button.innerHTML;
		var done = function (label) {
			button.innerHTML = '<span class="dashicons dashicons-yes"></span>' + label;
			window.setTimeout(function () {
				button.innerHTML = original;
			}, 2000);
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				done('کپی شد');
			}, function () {
				done('کپی نشد');
			});
			return;
		}

		/* مرورگرهای قدیمی‌تر */
		target.removeAttribute('readonly');
		target.select();
		try {
			document.execCommand('copy');
			done('کپی شد');
		} catch (error) {
			done('کپی نشد');
		}
		target.setAttribute('readonly', 'readonly');
	});
})();
