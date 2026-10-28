/**
 * ترانه یار ۳.۷.۹ — اسلایدر ویژهٔ صفحهٔ اصلی (ریل بومی، بدون وابستگی)
 * ==========================================================================
 * چرا این‌قدر ساده است؟ چون دو بار در نسخه‌های قبل «لایه‌ای» نوشته شد و هر
 * بار یک جای دیگر آن را شکست:
 *   • ۳.۷.۰–۳.۷.۷ با owl: ناحیهٔ سفیدِ سه‌برابرِ عرض و اسلایدهای نامرئی.
 *   • ۳.۷.۸ با cross-fade: یک قاعدهٔ CSS جا‌مانده از دوران owl همهٔ اسلایدها
 *     جز اولی را `display:none` می‌کرد ⇒ «فقط یکی می‌آید بالا».
 *
 * معماری کنونی:
 *   اسلایدها در یک ریل `overflow-x:auto` کنار هم نشسته‌اند و `scroll-snap`
 *   آن‌ها را می‌چسباند. یعنی **بدون این فایل هم** هر پنج اسلاید با انگشت یا
 *   تراک‌پد دیده می‌شوند. این فایل فقط سه چیز اضافه می‌کند:
 *     ۱) چرخش خودکار (با توقف روی هاور/فوکوس/تبِ پنهان)
 *     ۲) نشانگرهای پایین کارت، هم‌گام با اسکرول
 *     ۳) کلیدهای چپ/راست و نمایش اسلاید فعلی برای صفحه‌خوان
 *
 * هیچ‌جا `opacity` یا `display` دست نمی‌زنیم؛ پس هیچ اسلایدی نمی‌تواند
 * «محو» شود.
 *
 * @package Taranehyar
 */
(function () {
	'use strict';

	function initSlider(root) {
		var slides = [].slice.call(root.querySelectorAll('[data-ty-slide]'));
		var total = slides.length;
		if (!total) {
			return;
		}

		/* روی ریل، جهت ltr است (توسط CSS)؛ پس scrollLeft همیشه مثبت است. */
		var wrap = root.parentNode && root.parentNode.nodeType === 1 ? root.parentNode : root;

		var interval = parseInt(root.getAttribute('data-interval'), 10);
		if (!interval || interval < 2500) {
			interval = 5200;
		}

		var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

		function width() {
			return root.clientWidth || 1;
		}

		function index() {
			var raw = Math.round(root.scrollLeft / width());
			if (raw < 0) {
				raw = 0;
			}
			if (raw > total - 1) {
				raw = total - 1;
			}
			return raw;
		}

		function scrollToIndex(i, smooth) {
			var left = i * width();
			if (smooth && typeof root.scrollTo === 'function') {
				root.scrollTo({ left: left, behavior: 'smooth' });
			} else {
				root.scrollLeft = left;
			}
		}

		/* نشانگرها ------------------------------------------------------- */
		var dots = null;
		var buttons = [];

		if (total > 1) {
			dots = document.createElement('div');
			dots.className = 'ty-hero-dots';
			dots.setAttribute('role', 'tablist');
			dots.setAttribute('aria-label', 'انتخاب اسلاید');

			for (var i = 0; i < total; i++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'ty-hero-dot';
				dot.setAttribute('role', 'tab');
				dot.setAttribute('aria-label', 'اسلاید ' + (i + 1) + ' از ' + total);
				/* بستن حلقه با closure — بدون آن همه به آخرین عدد اشاره می‌کردند */
				(function (target) {
					dot.addEventListener('click', function () {
						scrollToIndex(target, true);
						restart();
					});
				})(i);
				dots.appendChild(dot);
				buttons.push(dot);
			}

			wrap.appendChild(dots);
		}

		/* هم‌گام‌سازی وضعیت --------------------------------------------- */
		var lastIndex = -1;

		function sync() {
			var current = index();
			if (current === lastIndex) {
				return;
			}
			lastIndex = current;

			for (var n = 0; n < total; n++) {
				var on = n === current;
				slides[n].classList[on ? 'add' : 'remove']('is-active');
				slides[n].setAttribute('aria-hidden', on ? 'false' : 'true');

				if (buttons[n]) {
					buttons[n].classList[on ? 'add' : 'remove']('is-active');
					buttons[n].setAttribute('aria-selected', on ? 'true' : 'false');
					buttons[n].setAttribute('tabindex', on ? '0' : '-1');
				}
			}
		}

		/* اسکرول با rAF جمع می‌شود تا روی گوشیِ ضعیف هم هزینه نداشته باشد */
		var queued = false;

		root.addEventListener('scroll', function () {
			if (queued) {
				return;
			}
			queued = true;
			window.requestAnimationFrame(function () {
				queued = false;
				sync();
			});
		}, { passive: true });

		/* چرخش خودکار ---------------------------------------------------- */
		var timer = null;
		var paused = false;

		function tick() {
			if (paused || document.hidden) {
				return;
			}
			/* اگر کاربر خودش اسکرول کرده باشد، از اسلاید فعلی ادامه می‌دهیم */
			var next = index() + 1;
			if (next >= total) {
				next = 0;
			}
			scrollToIndex(next, !reduce);
		}

		function start() {
			if (reduce || timer || total < 2) {
				return;
			}
			timer = window.setInterval(tick, interval);
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		function restart() {
			stop();
			start();
		}

		root.addEventListener('mouseenter', function () { paused = true; });
		root.addEventListener('mouseleave', function () { paused = false; });
		root.addEventListener('focusin', function () { paused = true; });
		root.addEventListener('focusout', function () { paused = false; });
		root.addEventListener('pointerdown', function () { paused = true; });
		document.addEventListener('visibilitychange', function () {
			paused = !!document.hidden;
		});

		/* وقتی کاربر با انگشت اسکرول می‌کند، تایمر از نو شروع شود */
		root.addEventListener('touchend', function () {
			paused = false;
			restart();
		}, { passive: true });

		/* کلیدهای صفحه‌کلید --------------------------------------------- */
		root.setAttribute('tabindex', '0');
		root.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
				var step = event.key === 'ArrowRight' ? 1 : -1;
				event.preventDefault();
				scrollToIndex(index() + step, true);
				restart();
			}
		});

		/* تغییر اندازهٔ صفحه: بدون انیمیشن، همان اسلاید بماند ------------- */
		var resizeTimer = null;
		window.addEventListener('resize', function () {
			if (resizeTimer) {
				window.clearTimeout(resizeTimer);
			}
			resizeTimer = window.setTimeout(function () {
				scrollToIndex(index(), false);
			}, 150);
		});

		root.classList.add('is-ready');
		sync();
		start();
	}

	function boot() {
		var nodes = document.querySelectorAll('[data-ty-hero]');
		for (var i = 0; i < nodes.length; i++) {
			try {
				initSlider(nodes[i]);
			} catch (error) {
				/* یک اسلایدر خراب نباید بقیهٔ صفحه را متوقف کند؛ و چون همهٔ
				   اسلایدها در DOM و قابل اسکرول‌اند، خرابیِ JS فقط یعنی
				   «بدون چرخش خودکار» — نه «بدون اسلاید». */
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
