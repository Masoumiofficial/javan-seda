/**
 * ترانه یار ۳.۵.۲ — «نمایش بیشتر / کمتر» برای توضیح کامل دسته‌بندی
 * ---------------------------------------------------------------------------
 * اصل کار: متن از ابتدا کامل در HTML هست (برای گوگل و کاربرِ بدون JS).
 * اگر JS باشد و متن بلندتر از حدِ دید باشد، جمع می‌شود و دکمه نمایش داده
 * می‌شود؛ اگر کوتاه بود، هیچ دکمه‌ای ساخته نمی‌شود.
 *
 * دسترس‌پذیری: دکمه، aria-expanded و aria-controls دارد؛ با کلید/Enter/Space
 * کار می‌کند و متنِ دکمه با وضعیت هم‌خوان است.
 */
(function () {
	'use strict';

	function setup(root) {
		var body = root.querySelector('[data-ab-desc-body]');
		var btn = root.querySelector('[data-ab-desc-toggle]');
		if (!body || !btn) {
			return;
		}

		var collapsed = true;
		body.classList.add('is-clamped');

		/* اجازه بده مرورگر ارتفاعِ کامل را حساب کند */
		window.requestAnimationFrame(function () {
			var full = body.scrollHeight;
			if (full <= body.clientHeight + 1) {
				body.classList.remove('is-clamped');
				btn.hidden = true;
				return;
			}
			btn.hidden = false;
			btn.textContent = 'نمایش بیشتر';
			btn.setAttribute('aria-expanded', 'false');
			root.classList.add('is-collapsed');
		});

		function render() {
			if (collapsed) {
				body.classList.add('is-clamped');
				root.classList.add('is-collapsed');
				btn.textContent = 'نمایش بیشتر';
				btn.setAttribute('aria-expanded', 'false');
			} else {
				body.classList.remove('is-clamped');
				root.classList.remove('is-collapsed');
				btn.textContent = 'نمایش کمتر';
				btn.setAttribute('aria-expanded', 'true');
			}
		}

		/* لینک‌های زیر محدودهٔ جمع‌شده با کیبورد قابل دسترس بمانند. */
		body.addEventListener('focusin', function () {
			if (collapsed && root.classList.contains('is-collapsed')) {
				collapsed = false;
				render();
			}
		});

		btn.addEventListener('click', function () {
			collapsed = !collapsed;
			render();
		});
	}

	function boot() {
		var nodes = document.querySelectorAll('[data-ab-desc]');
		Array.prototype.forEach.call(nodes, setup);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
