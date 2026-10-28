/**
 * ترانه یار ۳.۷.۸ — اسلایدر ویژهٔ صفحهٔ اصلی (بدون وابستگی)
 * ==========================================================================
 * چرا از نو نوشته شد؟
 *   نسخهٔ قبل با owl.carousel راه می‌افتاد. owl برای هر اسلاید یک `.owl-item`
 *   با عرض ۳۰۰٪ می‌سازد و اگر یک شرط CSS یا ترتیب بارگذاری اسکریپت به هم
 *   می‌خورد، ناحیه‌ای سفید به اندازهٔ سه اسلاید می‌ماند و هیچ تصویری دیده
 *   نمی‌شد — همان چیزی که کاربر گزارش کرد («کلا ریخته به هم»).
 *
 * معماری تازه:
 *   • اسلایدها همان مارکاپ قبلی‌اند؛ فقط کلاس `is-active` جابه‌جا می‌شود.
 *   • فقط یک اسلاید در جریان صفحه است ⇒ ارتفاع کارت همیشه درست است.
 *   • نشانگرها (dots) این‌جا ساخته می‌شوند تا مارکاپ صفحه سبک بماند.
 *   • پخش خودکار با هاور/فوکوس/تبِ پنهان متوقف می‌شود؛ با `prefers-reduced
 *     -motion` هم خودکار خاموش است. کشیدن انگشت هم اسلاید را عوض می‌کند.
 *   • اگر همین فایل بارگذاری نشود، CSS اسلاید اول را نشان می‌دهد (بدون JS).
 *
 * @package Taranehyar
 */
(function () {
	'use strict';

	function initSlider(root) {
		var slides = root.querySelectorAll('[data-ty-slide]');
		if (slides.length < 2) {
			return;
		}

		var total = slides.length;
		var current = 0;
		var interval = parseInt(root.getAttribute('data-interval'), 10);
		if (!interval || interval < 2000) {
			interval = 5200;
		}

		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		/* نشانگرها -------------------------------------------------------- */
		var dots = document.createElement('div');
		dots.className = 'ty-hero-dots';
		dots.setAttribute('role', 'tablist');
		dots.setAttribute('aria-label', 'انتخاب اسلاید');

		var buttons = [];
		for (var i = 0; i < total; i++) {
			var dot = document.createElement('button');
			dot.type = 'button';
			dot.className = 'ty-hero-dot' + (i === 0 ? ' is-active' : '');
			dot.setAttribute('role', 'tab');
			dot.setAttribute('aria-label', 'اسلاید ' + (i + 1) + ' از ' + total);
			dot.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
			(function (index) {
				dot.addEventListener('click', function () {
					go(index, true);
				});
			})(i);
			dots.appendChild(dot);
			buttons.push(dot);
		}

		if (root.parentNode) {
			root.parentNode.appendChild(dots);
		}

		/* تعویض اسلاید ---------------------------------------------------- */
		function go(next, byUser) {
			next = ((next % total) + total) % total;
			if (next === current) {
				return;
			}
			slides[current].classList.remove('is-active');
			slides[current].setAttribute('aria-hidden', 'true');
			buttons[current].classList.remove('is-active');
			buttons[current].setAttribute('aria-selected', 'false');

			current = next;

			slides[current].classList.add('is-active');
			slides[current].setAttribute('aria-hidden', 'false');
			buttons[current].classList.add('is-active');
			buttons[current].setAttribute('aria-selected', 'true');

			if (byUser) {
				restart();
			}
		}

		function nextSlide() {
			go(current + 1, false);
		}

		/* پخش خودکار ------------------------------------------------------ */
		var timer = null;
		var paused = false;

		function start() {
			if (reduce || timer) {
				return;
			}
			timer = window.setInterval(function () {
				if (!paused && !document.hidden) {
					nextSlide();
				}
			}, interval);
		}

		function restart() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
			start();
		}

		root.addEventListener('mouseenter', function () { paused = true; });
		root.addEventListener('mouseleave', function () { paused = false; });
		root.addEventListener('focusin', function () { paused = true; });
		root.addEventListener('focusout', function () { paused = false; });

		/* کشیدن انگشت ---------------------------------------------------- */
		var startX = 0;
		var startY = 0;
		var tracking = false;

		root.addEventListener('touchstart', function (event) {
			if (!event.touches || event.touches.length !== 1) {
				return;
			}
			tracking = true;
			startX = event.touches[0].clientX;
			startY = event.touches[0].clientY;
		}, { passive: true });

		root.addEventListener('touchend', function (event) {
			if (!tracking) {
				return;
			}
			tracking = false;
			var touch = event.changedTouches && event.changedTouches[0];
			if (!touch) {
				return;
			}
			var dx = touch.clientX - startX;
			var dy = touch.clientY - startY;
			if (Math.abs(dx) < 42 || Math.abs(dx) < Math.abs(dy)) {
				return;
			}
			/* در RTL، کشیدن به چپ = اسلاید بعدی */
			go(current + (dx < 0 ? 1 : -1), true);
		});

		/* آماده — برای CSS که اسلاید اول را در نبود JS نگه می‌دارد */
		root.classList.add('is-ready');
		start();
	}

	function boot() {
		var nodes = document.querySelectorAll('[data-ty-hero]');
		for (var i = 0; i < nodes.length; i++) {
			initSlider(nodes[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
