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
 * v3.8.0 — ریل از LTR به RTL رفت. پیش از این اسلاید اول در **چپ** می‌نشست و
 * چرخش خودکار محتوا را به چپ می‌برد؛ در سایتی که همه‌چیزش راست‌چین است، این
 * «حرکتِ چپ‌چین» دیده می‌شد. حالا اسلاید اول از راست شروع می‌شود و اسلاید بعد
 * از سمت چپ وارد می‌شود. محاسبه‌های اسکرول هم همان‌جا برای RTL بازنویسی شد
 * (دو مدلِ اسکرول در مرورگرها داریم؛ هر دو پوشش داده می‌شود).
 *
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

		var wrap = root.parentNode && root.parentNode.nodeType === 1 ? root.parentNode : root;

		/* v3.8.0 — جهت ریل از CSS می‌آید (rtl). ولی مقدارِ scrollLeft در RTL
		   بین مرورگرها یکی نیست:
		     • مدل مدرن (کروم/فایرفاکس/سافاری تازه): در راست ۰ است و با رفتن
		       به چپ منفی می‌شود.
		     • مدل قدیمی: در چپ ۰ است و در راست به بیشترین مقدار می‌رسد.
		   یک بار با نوشتن ‎-1‎ تشخیص می‌دهیم، بعد فرمولِ درست را می‌چینیم. */
		var rtl = false;
		try {
			rtl = window.getComputedStyle(root).direction === 'rtl';
		} catch (error) {
			rtl = false;
		}

		var rtlNegative = false;
		if (rtl) {
			var probe = root.scrollLeft;
			try {
				root.scrollLeft = -1;
				rtlNegative = root.scrollLeft < 0;
				root.scrollLeft = probe;
			} catch (error) {
				rtlNegative = false;
			}
		}

		var interval = parseInt(root.getAttribute('data-interval'), 10);
		if (!interval || interval < 2500) {
			interval = 5200;
		}

		var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

		function width() {
			return root.clientWidth || 1;
		}

		function index() {
			var w = width();
			var raw;

			if (!rtl) {
				raw = Math.round(root.scrollLeft / w);
			} else if (rtlNegative) {
				raw = Math.round(Math.abs(root.scrollLeft) / w);
			} else {
				raw = (total - 1) - Math.round(root.scrollLeft / w);
			}

			if (raw < 0) {
				raw = 0;
			}
			if (raw > total - 1) {
				raw = total - 1;
			}
			return raw;
		}

		function scrollToIndex(i, smooth) {
			var w = width();
			var left;

			if (!rtl) {
				left = i * w;
			} else if (rtlNegative) {
				left = -i * w;
			} else {
				left = (total - 1 - i) * w;
			}

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
			/* v3.8.0 — قبلاً role="tablist"/"tab" بود بدون هیچ tabpanel؛ برای
			   صفحه‌خوان یعنی «زبانه‌هایی که پنل ندارند». این‌ها فقط دکمه‌های
			   انتخاب اسلایدند، پس گروه ساده‌اند و وضعیت با aria-current می‌آید. */
			dots.setAttribute('role', 'group');
			dots.setAttribute('aria-label', 'انتخاب اسلاید');

			for (var i = 0; i < total; i++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'ty-hero-dot';
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

				/* اسلایدهای پنهان از مسیر Tab بیرون می‌مانند (وگرنه با کلید
				   Tab کاربر روی لینکِ اسلایدِ بیرون‌از‌قاب می‌رفت). */
				if ('inert' in slides[n]) {
					slides[n].inert = !on;
				}

				if (buttons[n]) {
					buttons[n].classList[on ? 'add' : 'remove']('is-active');
					if (on) {
						buttons[n].setAttribute('aria-current', 'true');
					} else {
						buttons[n].removeAttribute('aria-current');
					}
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
				/* در RTL، «چپ» یعنی اسلاید بعد؛ در LTR برعکس. */
				var step = event.key === 'ArrowRight' ? 1 : -1;
				if (rtl) {
					step = -step;
				}
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
