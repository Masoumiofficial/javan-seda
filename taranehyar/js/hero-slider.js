/**
 * ترانه یار ۳.۸.۲ — اسلایدر ویژهٔ صفحهٔ اصلی (ریل بومی، بدون وابستگی)
 * ==========================================================================
 * تاریخچهٔ کوتاه: این اسلایدر سه بار بازنویسی شد و هر بار یک جای دیگرِ آن
 * شکست (owl با عرض سه‌برابر، cross-fade با قاعدهٔ جا‌مانده، و در ۳.۸.۰/۱
 * محاسبهٔ `scrollLeft` در RTL). درسِ نسخهٔ فعلی:
 *
 *   **هیچ فرمولی برای RTL ننویس؛ از خودِ مرورگر بپرس.**
 *
 *   برای «کدام اسلاید الان در قاب است» به‌جای تقسیمِ `scrollLeft` بر عرض،
 *   فاصلهٔ افقیِ هر اسلاید از لبهٔ ریل را با `getBoundingClientRect()`
 *   می‌سنجیم و نزدیک‌ترین را انتخاب می‌کنیم. برای «برو به اسلاید i» هم
 *   همان فاصله را به `scrollLeft` فعلی اضافه می‌کنیم. این دو کار در **هر
 *   دو مدلِ RTL** (مدلِ منفیِ کروم/فایرفاکس و مدلِ مثبتِ قدیمی) یک نتیجه
 *   می‌دهند، پس دیگر پروب و حدس لازم نیست.
 *
 *   چرا نسخهٔ ۳.۸.۱ «نقطهٔ آخر گیر می‌کرد»؟ چون تشخیص مدل با نوشتن
 *   `scrollLeft = -1` انجام می‌شد و `scroll-snap: mandatory` بلافاصله آن را
 *   به صفر برمی‌گرداند ⇒ مدل اشتباه تشخیص داده می‌شد و شاخص همیشه آخرین
 *   اسلاید می‌شد. همان باگ فلش‌ها و چرخش خودکار را هم می‌خواباند.
 *
 * چه چیزهایی اضافه می‌کند (بدون JS هم همهٔ اسلایدها در DOM و قابل اسکرول‌اند):
 *   ۱) چرخش خودکار (توقف روی هاور/فوکوس/تبِ پنهان)
 *   ۲) نقطه‌های پایین کارت، هم‌گام با اسکرول
 *   ۳) فلش چپ/راست (فقط دسکتاپ) + کشیدن با ماوس
 *   ۴) پیمایش با کلیدهای جهت‌دار
 *
 * لینک‌ها: پیش از این `pointerdown` روی ریل، `setPointerCapture` صدا می‌زد
 * که هدفِ رویدادِ click را از لینکِ اسلاید به خودِ ریل تغییر می‌داد ⇒ هیچ
 * اسلایدی لینک نداشت. حالا capture فقط وقتی گرفته می‌شود که کاربر واقعاً
 * (بیش از ۶px) کشیده باشد؛ یک کلیکِ ساده دست‌نخورده به لینک می‌رسد.
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

		var interval = parseInt(root.getAttribute('data-interval'), 10);
		if (!interval || interval < 2500) {
			interval = 5200;
		}

		var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

		/* فاصلهٔ افقیِ اسلاید از لبهٔ شروع ریل (در RTL منفی است). */
		function offsetOf(i) {
			var slide = slides[i];
			if (!slide || typeof slide.getBoundingClientRect !== 'function') {
				return i * (root.clientWidth || 1);
			}
			return slide.getBoundingClientRect().left - root.getBoundingClientRect().left;
		}

		/* نزدیک‌ترین اسلاید به لبهٔ شروع (یعنی همان که در قاب است). */
		function index() {
			var best = 0;
			var bestDistance = Infinity;
			for (var i = 0; i < total; i++) {
				var distance = Math.abs(offsetOf(i));
				if (distance < bestDistance) {
					bestDistance = distance;
					best = i;
				}
			}
			return best;
		}

		function setScroll(left) {
			/* رفتار نرمِ CSS را لحظه‌ای خاموش می‌کنیم تا پرش با انیمیشنِ
			   طولانی انجام نشود (برای کلیک روی نقطه/فلش بی‌درنگ). */
			var previous = root.style.scrollBehavior;
			root.style.scrollBehavior = 'auto';
			root.scrollLeft = left;
			root.style.scrollBehavior = previous;
		}

		function scrollToIndex(i, smooth) {
			if (i < 0) {
				i = 0;
			}
			if (i > total - 1) {
				i = total - 1;
			}

			/* `scrollLeft + offset` در هر دو مدلِ RTL درست است: با تغییر
			   scrollLeft به اندازهٔ offset، خودِ اسلاید دقیقاً به لبه می‌رسد. */
			var left = root.scrollLeft + offsetOf(i);

			if (smooth && !reduce && typeof root.scrollTo === 'function') {
				root.scrollTo({ left: left, behavior: 'smooth' });
				return;
			}
			setScroll(left);
		}

		/* ---------------------------------------------------------- نقطه‌ها */
		var dots = null;
		var buttons = [];

		if (total > 1) {
			dots = document.createElement('div');
			dots.className = 'ty-hero-dots';
			/* این‌ها زبانه نیستند (پنلِ متناظر ندارند)؛ فقط دکمهٔ انتخاب اسلایدند. */
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
						/* فاصلهٔ بیش از یک اسلاید بی‌انیمیشن ⇒ پرشِ چندتایی
						   هرگز بین راه به snap نمی‌چسبد. */
						scrollToIndex(target, Math.abs(target - index()) < 2);
						restart();
					});
				})(i);
				dots.appendChild(dot);
				buttons.push(dot);
			}

			wrap.appendChild(dots);
		}

		/* -------------------------------------------------- فلش‌های چپ/راست */
		if (total > 1) {
			var nav = document.createElement('div');
			nav.className = 'ty-hero-nav';

			var chevronRight = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			var chevronLeft = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

			var prevBtn = document.createElement('button');
			prevBtn.type = 'button';
			prevBtn.className = 'ty-hero-arrow ty-hero-arrow--prev';
			prevBtn.setAttribute('aria-label', 'اسلاید قبلی');
			prevBtn.innerHTML = chevronRight;

			var nextBtn = document.createElement('button');
			nextBtn.type = 'button';
			nextBtn.className = 'ty-hero-arrow ty-hero-arrow--next';
			nextBtn.setAttribute('aria-label', 'اسلاید بعدی');
			nextBtn.innerHTML = chevronLeft;

			prevBtn.addEventListener('click', function () {
				var target = index() - 1;
				if (target < 0) { target = total - 1; }
				scrollToIndex(target, true);
				restart();
			});

			nextBtn.addEventListener('click', function () {
				var target = index() + 1;
				if (target > total - 1) { target = 0; }
				scrollToIndex(target, true);
				restart();
			});

			nav.appendChild(prevBtn);
			nav.appendChild(nextBtn);
			wrap.appendChild(nav);
		}

		/* --------------------------------------------------- هم‌گام‌سازی وضعیت */
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

				/* اسلایدهای پنهان از مسیر Tab بیرون می‌مانند (وگرنه با Tab
				   کاربر روی لینکِ اسلایدِ بیرون‌از‌قاب می‌رفت). */
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

		/* ------------------------------------------------ چرخش خودکار */
		var timer = null;
		var paused = false;

		function tick() {
			if (paused || document.hidden) {
				return;
			}
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
		document.addEventListener('visibilitychange', function () {
			paused = !!document.hidden;
		});

		/* --------------------------------------- کشیدن با ماوس (فقط دسکتاپ) */
		var drag = null;
		var suppressClick = false;

		function isUiTarget(node) {
			return !!(node && node.closest && node.closest('.ty-hero-dot, .ty-hero-arrow'));
		}

		root.addEventListener('pointerdown', function (event) {
			if (event.pointerType !== 'mouse' || event.button !== 0 || isUiTarget(event.target)) {
				return;
			}
			/* هیچ capture‌ای این‌جا گرفته نمی‌شود؛ وگرنه یک کلیکِ ساده هم
			   مقصدِ click را عوض می‌کند و لینکِ اسلاید کار نمی‌کند. */
			drag = {
				x: event.clientX,
				start: root.scrollLeft,
				moved: false,
				id: event.pointerId
			};
		});

		root.addEventListener('pointermove', function (event) {
			if (!drag || event.pointerId !== drag.id) {
				return;
			}
			var dx = event.clientX - drag.x;
			if (!drag.moved) {
				if (Math.abs(dx) < 6) {
					return;
				}
				drag.moved = true;
				paused = true; /* فقط وقتی واقعاً کشیدن شروع شد */
				root.classList.add('is-dragging');
				try {
					root.setPointerCapture(event.pointerId);
				} catch (error) {}
			}
			if (event.cancelable) {
				event.preventDefault();
			}
			setScroll(drag.start - dx);
		});

		function endDrag() {
			if (!drag) {
				return;
			}
			var moved = drag.moved;
			var id = drag.id;
			drag = null;
			root.classList.remove('is-dragging');
			try {
				root.releasePointerCapture(id);
			} catch (error) {}

			if (!moved) {
				return;
			}

			paused = false;

			/* کلیکِ ناخواسته بعد از کشیدن (باز شدن صفحهٔ آهنگ) مسدود می‌شود */
			suppressClick = true;
			window.setTimeout(function () { suppressClick = false; }, 120);

			scrollToIndex(index(), false);
			restart();
		}

		root.addEventListener('pointerup', endDrag);
		root.addEventListener('pointercancel', endDrag);

		root.addEventListener('click', function (event) {
			if (!suppressClick) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			suppressClick = false;
		}, true);

		/* ------------------------------------------------------- صفحه‌کلید */
		root.setAttribute('tabindex', '0');
		root.addEventListener('keydown', function (event) {
			if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
				return;
			}
			/* در RTL «چپ» یعنی اسلاید بعد. عرضِ ریل از دادهٔ واقعی می‌آید. */
			var step = event.key === 'ArrowRight' ? -1 : 1;
			event.preventDefault();
			scrollToIndex(index() + step, true);
			restart();
		});

		/* --------------------------------- تغییر اندازه: همان اسلاید بماند */
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
		lastIndex = -1;
		scrollToIndex(0, false);
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
