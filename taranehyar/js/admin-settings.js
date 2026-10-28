/**
 * ترانه یار — رفتار صفحهٔ «تنظیمات قالب» (۳.۷.۲)
 * ---------------------------------------------------------------------------
 * هیچ رفتاری از OptionTree عوض نمی‌شود؛ این فایل فقط «خوانایی» اضافه می‌کند:
 *   ۱) آیکون و سرصفحه برای هر بخش
 *   ۲) کادر جست‌وجو: با تایپ، فیلدهای همهٔ بخش‌ها فیلتر می‌شوند
 *   ۳) بازکردن مستقیم یک بخش با `?section=about` یا `#section_about`
 *   ۴) دکمهٔ «ذخیرهٔ تغییرات» سربرگ، همان دکمهٔ اصلی فرم را می‌زند
 *   ۵) یادآوری بخش فعال هنگام ذخیره/رفرش
 *
 * بدون jQuery و بدون وابستگی؛ اگر هیچ‌کدام از المان‌ها نبود، بی‌صدا برمی‌گردد.
 *
 * @package Taranehyar
 */
(function () {
	'use strict';

	var cfg = window.taraSettings || {};
	var i18n = cfg.i18n || {};
	var sections = cfg.sections || {};

	var root = null;      /* .ui-tabs */
	var nav = null;       /* .ui-tabs-nav */
	var search = null;    /* input جست‌وجو */
	var counter = null;   /* شمارندهٔ نتیجه */

	function el(tag, className, html) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (html !== undefined) {
			node.innerHTML = html;
		}
		return node;
	}

	/* ---------------------------------------------------------------
	 * ۱) آیکون و سرصفحهٔ بخش‌ها
	 * ------------------------------------------------------------ */
	function sectionData(id) {
		return sections[id] || { title: id, icon: 'dashicons-admin-generic', hint: '' };
	}

	function decorateNav() {
		if (!nav) {
			return;
		}

		var links = nav.querySelectorAll('a[href^="#section_"]');
		for (var i = 0; i < links.length; i++) {
			var link = links[i];
			var id = String(link.getAttribute('href') || '').replace('#section_', '');
			var data = sectionData(id);

			if (!link.querySelector('.dashicons')) {
				link.insertBefore(el('span', 'dashicons ' + data.icon), link.firstChild);
			}
			link.setAttribute('aria-label', data.title);
		}
	}

	function decoratePanels() {
		if (!root) {
			return;
		}

		var panels = root.querySelectorAll('.ui-tabs-panel');
		for (var i = 0; i < panels.length; i++) {
			var panel = panels[i];
			var id = String(panel.id || '').replace('section_', '');
			var data = sectionData(id);

			if (!id || panel.querySelector('.tara-section-head')) {
				continue;
			}

			var head = el('div', 'tara-section-head');
			head.appendChild(el('span', 'dashicons ' + data.icon + ' tara-section-head__icon'));

			var text = el('div');
			text.appendChild(el('h2', '', data.title));
			if (data.hint) {
				text.appendChild(el('p', '', data.hint));
			}
			head.appendChild(text);

			var inside = panel.querySelector('.inside');
			panel.insertBefore(head, inside || panel.firstChild);

			/* پیام «چیزی پیدا نشد» برای هر بخش */
			panel.appendChild(el('p', 'tara-empty', i18n.none || 'چیزی پیدا نشد.'));
		}
	}

	/* ---------------------------------------------------------------
	 * ۱-۲) زیربخش‌های باز/بستنی (v3.7.7)
	 * ------------------------------------------------------------
	 * هر «تیتر میانی» (ردیفی با کلاس ab-h) به یک <details> تبدیل می‌شود و
	 * ردیف‌های بعدی تا تیتر بعدی داخل آن می‌نشینند. پیش‌فرض همه باز است —
	 * کاربر چیزی را «گم» نمی‌کند؛ فقط اگر خواست می‌تواند یک بلوک را جمع
	 * کند. بلوک‌های تک‌گزینه‌ای اصلاً قابل بستن نمی‌شوند تا کلیکِ بی‌فایده
	 * نداشته باشیم.
	 * ------------------------------------------------------------ */
	/* OptionTree کلاس هر ردیف را با پسوند «-wrap» چاپ می‌کند
	   (`class` در تنظیمات → `ab-h-wrap`)، پس همان شکل را می‌سنجیم. */
	function roleOf(row) {
		var cls = ' ' + String(row.className || '') + ' ';
		if (cls.indexOf(' ab-h-wrap ') !== -1) { return 'head'; }
		if (cls.indexOf(' ab-sub-wrap ') !== -1) { return 'sub'; }
		if (cls.indexOf(' ab-guide-wrap ') !== -1) { return 'guide'; }
		return '';
	}

	function groupPanel(panel) {
		if (!panel || panel.getAttribute('data-tara-grouped')) {
			return;
		}
		panel.setAttribute('data-tara-grouped', '1');

		var inside = panel.querySelector('.inside');
		if (!inside) {
			return;
		}

		var rows = [];
		for (var i = 0; i < inside.children.length; i++) {
			if (inside.children[i].classList.contains('format-settings')) {
				rows.push(inside.children[i]);
			}
		}

		var current = null;
		var buffer = [];

		function flush() {
			if (!current) {
				buffer = [];
				return;
			}

			/* بلوک تک‌گزینه‌ای قابل بستن نیست (کلیکِ بی‌فایده) */
			if (buffer.length > 1) {
				var box = document.createElement('details');
				box.className = 'tara-fold';
				box.open = true;

				var sum = document.createElement('summary');
				sum.className = 'tara-fold__sum';
				sum.setAttribute('aria-label', 'باز یا بستن این بخش');

				var head = current.node;
				head.parentNode.insertBefore(box, head);
				sum.appendChild(head);
				box.appendChild(sum);

				for (var b = 0; b < buffer.length; b++) {
					box.appendChild(buffer[b]);
				}
			}

			buffer = [];
		}

		for (var r = 0; r < rows.length; r++) {
			var row = rows[r];
			var role = roleOf(row);

			if (role === 'head') {
				flush();
				row.classList.add('tara-head');
				current = { node: row };
			} else if (current) {
				buffer.push(row);
			}
		}

		flush();
	}

	function groupAll() {
		if (!root || typeof document.createElement('details').open === 'undefined') {
			return;
		}
		var panels = root.querySelectorAll('.ui-tabs-panel');
		for (var i = 0; i < panels.length; i++) {
			try {
				groupPanel(panels[i]);
			} catch (e) {
				/* هیچ‌وقت صفحهٔ تنظیمات را به‌خاطر یک بلوک خراب نکن */
			}
		}
	}

	/* ---------------------------------------------------------------
	 * ۲) جست‌وجو
	 * ------------------------------------------------------------ */
	function rowsOf(panel) {
		return panel.querySelectorAll('.format-settings');
	}

	function clearSearch() {
		if (!root) {
			return;
		}
		root.classList.remove('is-tara-searching');

		var rows = root.querySelectorAll('.format-settings.is-tara-hidden');
		for (var i = 0; i < rows.length; i++) {
			rows[i].classList.remove('is-tara-hidden');
		}

		var empties = root.querySelectorAll('.tara-empty.is-on');
		for (var j = 0; j < empties.length; j++) {
			empties[j].classList.remove('is-on');
		}

		var counts = nav ? nav.querySelectorAll('.tara-nav-count') : [];
		for (var k = 0; k < counts.length; k++) {
			counts[k].parentNode.removeChild(counts[k]);
		}

		var dimmed = nav ? nav.querySelectorAll('li.is-tara-dim') : [];
		for (var m = 0; m < dimmed.length; m++) {
			dimmed[m].classList.remove('is-tara-dim');
		}

		if (counter) {
			counter.textContent = '';
			counter.style.display = 'none';
		}
	}

	function runSearch(term) {
		if (!root) {
			return;
		}

		term = String(term || '').trim().toLowerCase();

		if (!term) {
			clearSearch();
			return;
		}

		root.classList.add('is-tara-searching');

		var total = 0;
		var panels = root.querySelectorAll('.ui-tabs-panel');

		for (var i = 0; i < panels.length; i++) {
			var panel = panels[i];
			var id = String(panel.id || '').replace('section_', '');
			var hits = 0;
			var rows = rowsOf(panel);

			for (var r = 0; r < rows.length; r++) {
				var row = rows[r];
				var text = (row.textContent || '').toLowerCase();
				var key = String(row.id || '').toLowerCase() + ' ' + (row.className || '').toLowerCase();
				var hit = text.indexOf(term) !== -1 || key.indexOf(term) !== -1;

				row.classList.toggle('is-tara-hidden', !hit);
				if (hit) {
					hits++;
				}
			}

			total += hits;

			var empty = panel.querySelector('.tara-empty');
			if (empty) {
				empty.classList.toggle('is-on', hits === 0);
			}

			/* شمارنده روی فهرست کنار */
			if (nav) {
				var link = nav.querySelector('a[href="#section_' + id + '"]');
				var li = link ? link.parentNode : null;
				if (li) {
					var badge = li.querySelector('.tara-nav-count');
					if (!badge) {
						badge = el('span', 'tara-nav-count');
						link.appendChild(badge);
					}
					badge.textContent = hits ? String(hits) : '۰';
					li.classList.toggle('is-tara-dim', hits === 0);
				}
			}
		}

		if (counter) {
			counter.textContent = (i18n.found ? i18n.found : 'نتیجه') + ': ' + total;
			counter.style.display = total ? '' : 'none';
		}
	}

	function buildSearch() {
		var bar = document.getElementById('option-tree-sub-header');
		if (!bar || bar.querySelector('.tara-find')) {
			return;
		}

		var wrap = el('div', 'tara-find');
		var icon = el('span', 'dashicons dashicons-search');
		search = el('input');
		search.type = 'search';
		search.autocomplete = 'off';
		search.placeholder = i18n.find || 'جست‌وجوی تنظیم…';
		search.setAttribute('aria-label', search.placeholder);

		counter = el('span', 'tara-find__count');
		counter.style.display = 'none';

		wrap.appendChild(icon);
		wrap.appendChild(search);
		wrap.appendChild(counter);

		bar.insertBefore(wrap, bar.firstChild);

		var timer = null;
		search.addEventListener('input', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				runSearch(search.value);
			}, 120);
		});

		search.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				search.value = '';
				runSearch('');
			}
		});

		/* کلیک روی فهرست، جست‌وجو را پاک می‌کند تا تب‌ها عادی کار کنند */
		if (nav) {
			nav.addEventListener('click', function () {
				if (search && search.value) {
					search.value = '';
					runSearch('');
				}
			});
		}
	}

	/* ---------------------------------------------------------------
	 * ۳) بازکردن بخش و یادآوری آن
	 * ------------------------------------------------------------ */
	function openSection(id) {
		if (!id || !nav) {
			return;
		}

		var link = nav.querySelector('a[href="#section_' + id + '"]');
		if (!link) {
			return;
		}

		link.click();

		window.setTimeout(function () {
			var panel = document.getElementById('section_' + id);
			if (panel && panel.getBoundingClientRect().top < 0) {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		}, 120);
	}

	function rememberSection() {
		if (!nav) {
			return;
		}

		nav.addEventListener('click', function (event) {
			var link = event.target.closest ? event.target.closest('a[href^="#section_"]') : null;
			if (!link) {
				return;
			}

			var id = String(link.getAttribute('href')).replace('#section_', '');
			try {
				window.sessionStorage.setItem('tara-settings-section', id);
			} catch (e) { /* حالت خصوصی مرورگر */ }

			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.set('section', id);
				window.history.replaceState({}, '', url.toString());
			}
		});
	}

	function wantedSection() {
		var hash = String(window.location.hash || '');
		if (hash.indexOf('#section_') === 0) {
			return hash.replace('#section_', '');
		}

		if (cfg.section) {
			return cfg.section;
		}

		try {
			return window.sessionStorage.getItem('tara-settings-section') || '';
		} catch (e) {
			return '';
		}
	}

	/* ---------------------------------------------------------------
	 * ۴) دکمهٔ ذخیرهٔ سربرگ
	 * ------------------------------------------------------------ */
	function wireSave() {
		var buttons = document.querySelectorAll('[data-tara-save]');
		for (var i = 0; i < buttons.length; i++) {
			buttons[i].addEventListener('click', function () {
				var real = document.querySelector('#option-tree-sub-header button.button-primary, #option-tree-sub-header .option-tree-ui-button.button-primary');
				if (real) {
					real.click();
					return;
				}
				var form = document.getElementById('option-tree-settings-api');
				if (form) {
					form.submit();
				}
			});
		}
	}

	/* ---------------------------------------------------------------
	 * راه‌اندازی
	 * ------------------------------------------------------------ */
	function boot() {
		/* اگر کلاس body به هر دلیلی نرسیده باشد، همین‌جا اضافه می‌شود تا CSS
		   صفحه حتماً اعمال شود. (ریشهٔ Option Tree در صفحه کافی است.) */
		if (document.getElementById('option-tree-settings-api')) {
			document.body.classList.add('tara-settings-screen');
		}

		root = document.querySelector('.wrap.settings-wrap .ui-tabs');
		nav = root ? root.querySelector('.ui-tabs-nav') : null;

		if (root) {
			decorateNav();
			decoratePanels();
			groupAll();
			rememberSection();
			buildSearch();
			openSection(wantedSection());
		}

		wireSave();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			window.setTimeout(boot, 80);
		});
	} else {
		window.setTimeout(boot, 80);
	}
})();
