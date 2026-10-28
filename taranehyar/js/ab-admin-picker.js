/**
 * جوان‌صدا ۳.۵.۲ — کنترل‌های پیشخوان
 * ---------------------------------------------------------------------------
 * ۱) انتخاب تصویر از کتابخانهٔ رسانهٔ خودِ وردپرس (هیچ آپلودرِ موازی‌ای ساخته نشده).
 * ۲) جست‌وجوی زندهٔ هنرمند/نوشته با AJAX (بدون بارگذاریِ کاملِ فهرست).
 *
 * وابستگی: window.JavanSedaPicker {ajax, nonce, empty, error, min}
 */
(function () {
	'use strict';

	var CFG = window.JavanSedaPicker || {};
	var NONCE = CFG.nonce || '';
	var AJAX = CFG.ajax || '';
	var TEXT = {
		empty: CFG.empty || 'نتیجه‌ای پیدا نشد.',
		error: CFG.error || 'خطا در جست‌وجو.',
		min: CFG.min || 'دست‌کم ۲ نویسه بنویسید…'
	};

	function esc(text) {
		var d = document.createElement('div');
		d.textContent = text == null ? '' : String(text);
		return d.innerHTML;
	}

	/* ------------------------------------------------------------------ */
	/* ۱) کتابخانهٔ رسانه                                                  */
	/* ------------------------------------------------------------------ */

	function initMedia() {
		var fields = document.querySelectorAll('[data-ab-image]');
		Array.prototype.forEach.call(fields, function (field) {
			if (field.getAttribute('data-ab-media-ready')) {
				return;
			}
			field.setAttribute('data-ab-media-ready', '1');

			var urlInput = field.querySelector('[data-ab-image-url]');
			var idInput = field.querySelector('[data-ab-image-id]');
			var preview = field.querySelector('[data-ab-image-preview]');
			var empty = field.querySelector('[data-ab-image-empty]');
			var pick = field.querySelector('[data-ab-media]');
			var clear = field.querySelector('[data-ab-media-clear]');
			var frame = null;

			function paint(url) {
				var has = !!url;
				if (preview) {
					var img = preview.querySelector('img');
					if (has && img) {
						img.src = url;
					}
					preview.hidden = !has;
				}
				if (empty) {
					empty.hidden = has;
				}
				if (clear) {
					clear.hidden = !has;
				}
				if (pick) {
					pick.textContent = has ? 'تغییر تصویر' : 'انتخاب از کتابخانه';
				}
			}

			if (pick && window.wp && window.wp.media) {
				pick.addEventListener('click', function (event) {
					event.preventDefault();
					if (!frame) {
						frame = window.wp.media({
							title: 'انتخاب تصویر هنرمند',
							button: { text: 'استفاده از این تصویر' },
							library: { type: 'image' },
							multiple: false
						});
						frame.on('select', function () {
							var att = frame.state().get('selection').first().toJSON();
							if (urlInput) {
								urlInput.value = att.url || '';
							}
							if (idInput) {
								idInput.value = att.id || '';
							}
							paint(att.url || '');
						});
					}
					frame.open();
				});
			}

			if (clear) {
				clear.addEventListener('click', function (event) {
					event.preventDefault();
					if (urlInput) {
						urlInput.value = '';
					}
					if (idInput) {
						idInput.value = '';
					}
					paint('');
				});
			}
		});
	}

	/* ------------------------------------------------------------------ */
	/* ۲) جست‌وجوی هنرمند / نوشته                                          */
	/* ------------------------------------------------------------------ */

	function request(type, query, done, fail) {
		var url = AJAX + (AJAX.indexOf('?') === -1 ? '?' : '&') +
			'action=javan_seda_picker&type=' + encodeURIComponent(type) +
			'&q=' + encodeURIComponent(query) +
			'&nonce=' + encodeURIComponent(NONCE);

		var xhr = new XMLHttpRequest();
		xhr.open('GET', url, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.onreadystatechange = function () {
			if (xhr.readyState !== 4) {
				return;
			}
			if (xhr.status < 200 || xhr.status >= 300) {
				fail();
				return;
			}
			var data;
			try {
				data = JSON.parse(xhr.responseText);
			} catch (e) {
				fail();
				return;
			}
			if (!data || !data.success) {
				fail();
				return;
			}
			done((data.data && data.data.items) || []);
		};
		xhr.send();
	}

	function renderResults(box, items, onPick) {
		box.innerHTML = '';
		if (!items.length) {
			var p = document.createElement('p');
			p.className = 'ab-picker__none';
			p.textContent = TEXT.empty;
			box.appendChild(p);
			box.hidden = false;
			return;
		}
		items.forEach(function (item) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'ab-picker__item';
			btn.setAttribute('data-id', item.id);

			var img = document.createElement('span');
			img.className = 'ab-picker__thumb';
			if (item.image) {
				var el = document.createElement('img');
				el.src = item.image;
				el.alt = '';
				img.appendChild(el);
			}
			btn.appendChild(img);

			var meta = document.createElement('span');
			meta.className = 'ab-picker__meta';
			meta.innerHTML = '<strong>' + esc(item.title) + '</strong>' +
				(item.sub ? '<small>' + esc(item.sub) + '</small>' : '') +
				(item.status && item.status !== 'publish' ? '<em class="ab-picker__status">' + esc(item.status) + '</em>' : '');
			btn.appendChild(meta);

			btn.addEventListener('click', function () {
				onPick(item);
			});
			box.appendChild(btn);
		});
		box.hidden = false;
	}

	function initPickers() {
		var pickers = document.querySelectorAll('[data-ab-picker]');
		Array.prototype.forEach.call(pickers, function (root) {
			if (root.getAttribute('data-ab-picker-ready')) {
				return;
			}
			root.setAttribute('data-ab-picker-ready', '1');

			var input = root.querySelector('[data-ab-picker-input]');
			var box = root.querySelector('[data-ab-picker-results]');
			var value = root.querySelector('[data-ab-picker-value]');
			var selected = root.querySelector('[data-ab-picker-selected]');
			var title = root.querySelector('[data-ab-picker-title]');
			var clear = root.querySelector('[data-ab-picker-clear]');
			var chips = root.querySelector('[data-ab-picker-chips]');
			var emptyNote = root.querySelector('[data-ab-picker-empty]');
			var type = root.getAttribute('data-type') || 'artist';
			var mode = root.getAttribute('data-mode') || 'single';
			var timer = null;

			if (!input || !box) {
				return;
			}

			function pickedIds() {
				if (!chips) {
					return [];
				}
				return Array.prototype.map.call(
					chips.querySelectorAll('input[name="ab_artist_ids[]"]'),
					function (el) { return String(el.value); }
				);
			}

			function toggleEmpty() {
				if (!emptyNote || !chips) {
					return;
				}
				emptyNote.hidden = chips.children.length > 0;
			}

			function addChip(item) {
				if (!chips || pickedIds().indexOf(String(item.id)) !== -1) {
					return;
				}
				var li = document.createElement('li');
				li.className = 'ab-chip';
				li.setAttribute('data-term-id', item.id);

				var thumb = document.createElement('span');
				thumb.className = 'ab-chip__img';
				if (item.image) {
					var img = document.createElement('img');
					img.src = item.image;
					img.alt = '';
					img.width = 28;
					img.height = 28;
					thumb.appendChild(img);
				}
				li.appendChild(thumb);

				var name = document.createElement('span');
				name.className = 'ab-chip__name';
				name.textContent = item.title;
				li.appendChild(name);

				var hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = 'ab_artist_ids[]';
				hidden.value = item.id;
				li.appendChild(hidden);

				var rm = document.createElement('button');
				rm.type = 'button';
				rm.className = 'ab-chip__remove';
				rm.setAttribute('data-ab-picker-remove', '');
				rm.setAttribute('aria-label', 'حذف');
				rm.textContent = '×';
				rm.addEventListener('click', function () {
					li.parentNode.removeChild(li);
					toggleEmpty();
				});
				li.appendChild(rm);

				chips.appendChild(li);
				toggleEmpty();
			}

			/* حذف چیپ‌های موجود (رندرِ سرور) */
			Array.prototype.forEach.call(root.querySelectorAll('[data-ab-picker-remove]'), function (btn) {
				btn.addEventListener('click', function () {
					var li = btn.closest('.ab-chip');
					if (li && li.parentNode) {
						li.parentNode.removeChild(li);
						toggleEmpty();
					}
				});
			});
			toggleEmpty();

			if (clear) {
				clear.addEventListener('click', function () {
					if (value) {
						value.value = '';
					}
					if (selected) {
						selected.hidden = true;
					}
					if (title) {
						title.textContent = '';
					}
					input.value = '';
					input.focus();
				});
			}

			input.addEventListener('input', function () {
				var q = input.value.trim();
				window.clearTimeout(timer);
				if (q.length < 2) {
					box.hidden = true;
					box.innerHTML = '';
					if (q.length === 1) {
						var p = document.createElement('p');
						p.className = 'ab-picker__none';
						p.textContent = TEXT.min;
						box.appendChild(p);
						box.hidden = false;
					}
					return;
				}
				timer = window.setTimeout(function () {
					request(type, q, function (items) {
						renderResults(box, items, function (item) {
							if (mode === 'multi') {
								addChip(item);
								input.value = '';
								box.hidden = true;
								box.innerHTML = '';
								input.focus();
								return;
							}
							if (value) {
								value.value = item.id;
							}
							if (title) {
								title.textContent = item.title;
							}
							if (selected) {
								selected.hidden = false;
							}
							input.value = '';
							box.hidden = true;
							box.innerHTML = '';
						});
					}, function () {
						box.innerHTML = '<p class="ab-picker__none">' + esc(TEXT.error) + '</p>';
						box.hidden = false;
					});
				}, 260);
			});
		});
	}

	function boot() {
		initMedia();
		initPickers();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
