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
			/* v3.8.1 — این‌جا یک تله بود: CSS ریل `scroll-behavior: smooth` دارد،
			   پس نوشتن ‎scrollLeft = -1‎ فقط یک انیمیشن شروع می‌کرد و خواندنِ
			   فوریِ همان مقدار، هنوز ۰ برمی‌گشت ⇒ مدل اشتباه تشخیص داده می‌شد،
			   فلش‌ها و نقطه‌ها به جایِ درست نمی‌رفتند و چرخش خودکار هم گیر
			   می‌کرد. حالا موقعِ آزمون، رفتار اسکرول موقتاً «auto» می‌شود. */
			var probe = root.scrollLeft;
			var prevBehavior = root.style.scrollBehavior;
			try {
				root.style.scrollBehavior = 'auto';
				root.scrollLeft = -1;
				rtlNegative = root.scrollLeft < 0;
				root.scrollLeft = probe;
			} catch (error) {
				rtlNegative = false;
			} finally {
				root.style.scrollBehavior = prevBehavior;
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

		function positionFor(i) {
			var w = width();

			if (!rtl) {
				return i * w;
			}
			return rtlNegative ? -i * w : (total - 1 - i) * w;
		}

		function scrollToIndex(i, smooth) {
			if (i < 0) {
				i = 0;
			}
			if (i > total - 1) {
				i = total - 1;
			}

			var left = positionFor(i);

			if (smooth && !reduce && typeof root.scrollTo === 'function') {
				root.scrollTo({ left: left, behavior: 'smooth' });
				return;
			}

			/* بدون انیمیشن: رفتار نرمِ CSS را لحظه‌ای خاموش می‌کنیم تا پرش
			   با یک انیمیشنِ طولانی انجام نشود. */
			var prev = root.style.scrollBehavior;
			root.style.scrollBehavior = 'auto';
			root.scrollLeft = left;
			root.style.scrollBehavior = prev;
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
						/* v3.8.1 — پرشِ چند اسلایدی با انیمیشنِ نرم می‌توانست
						   بین راه به snap بچسبد و «کار نکردنِ نقطه‌ها» شود؛
						   برای فاصلهٔ بیش از یک اسلاید، بی‌انیمیشن می‌رویم. */
						scrollToIndex(target, Math.abs(target - index()) < 2);
						restart();
					});
				})(i);
				dots.appendChild(dot);
				buttons.push(dot);
			}

			wrap.appendChild(dots);
		}

		/* فلش‌های چپ/راست — v3.8.1
		   ------------------------------------------------------------------
		   کاربر گفت اسلایدر روی دسکتاپ «فلش» ندارد و فقط با کشیدنِ ماوس
		   عوض می‌شود. حالا دو دکمهٔ شیشه‌ای در دو طرف کارت هست (فقط ≥۷۸۳px؛
		   روی گوشی، سوایپ و نقطه‌ها کافی‌اند). جهت‌شان هم با RTL هم‌خوان است:
		   دکمهٔ راست = اسلاید قبلی، دکمهٔ چپ = اسلاید بعدی. */
		var prevBtn = null;
		var nextBtn = null;

		if (total > 1) {
			var nav = document.createElement('div');
			nav.className = 'ty-hero-nav';

			var chevronRight = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			var chevronLeft = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

			prevBtn = document.createElement('button');
			prevBtn.type = 'button';
			prevBtn.className = 'ty-hero-arrow ty-hero-arrow--prev';
			prevBtn.setAttribute('aria-label', 'اسلاید قبلی');
			prevBtn.innerHTML = chevronRight;

			nextBtn = document.createElement('button');
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

		/* کشیدن با ماوس — v3.8.1
		   ------------------------------------------------------------------
		   تا امروز روی دسکتاپ فقط با شیفت+چرخ یا ترک‌پد می‌شد اسلاید را عوض
		   کرد و کاربر می‌گفت «حرمت نمی‌کند». حالا می‌شود کارت را گرفت و کشید؛
		   در حین کشیدن `scroll-snap` خاموش می‌شود (کلاس is-dragging) و بعد از
		   رها کردن، نزدیک‌ترین اسلاید می‌چسبد. کلیکِ ناخواستهٔ بعد از کشیدن هم
		   مسدود می‌شود تا صفحهٔ آهنگ باز نشود. */
		var drag = null;
		var suppressClick = false;

		function isUiTarget(node) {
			return !!(node && node.closest && node.closest('.ty-hero-dot, .ty-hero-arrow'));
		}

		root.addEventListener('pointerdown', function (event) {
			if (event.pointerType !== 'mouse' || event.button !== 0 || isUiTarget(event.target)) {
				return;
			}
			drag = { x: event.clientX, start: root.scrollLeft, moved: false, id: event.pointerId };
			root.classList.add('is-dragging');
			try { root.setPointerCapture(event.pointerId); } catch (error) {}
		});

		root.addEventListener('pointermove', function (event) {
			if (!drag || event.pointerId !== drag.id) {
				return;
			}
			var dx = event.clientX - drag.x;
			if (!drag.moved && Math.abs(dx) > 6) {
				drag.moved = true;
			}
			if (!drag.moved) {
				return;
			}
			event.preventDefault();
			root.scrollLeft = drag.start - dx;
		});

		function endDrag() {
			if (!drag) {
				return;
			}
			var moved = drag.moved;
			var id = drag.id;
			drag = null;
			root.classList.remove('is-dragging');
			try { root.releasePointerCapture(id); } catch (error) {}
			if (!moved) {
				return;
			}
			suppressClick = true;
			window.setTimeout(function () { suppressClick = false; }, 80);
			scrollToIndex(index(), false);
			restart();
		}

		root.addEventListener('pointerup', endDrag);
		root.addEventListener('pointercancel', endDrag);
		root.addEventListener('lostpointercapture', endDrag);

		/* بعد از کشیدن، کلیکِ روی لینکِ اسلاید نباید اجرا شود */
		root.addEventListener('click', function (event) {
			if (!suppressClick) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			suppressClick = false;
		}, true);

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
		/* همیشه از اسلاید اول شروع کن (بعضی مرورگرها در RTL از انتها باز می‌کنند) */
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
