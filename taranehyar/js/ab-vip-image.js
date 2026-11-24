/**
 * ab-vip-image.js — انتخاب‌گرِ بنرِ «اسلایدر ویژه» در صفحهٔ ویرایش نوشته (v3.8.3)
 * ===========================================================================
 * فقط با API خودِ وردپرس (wp.media) کار می‌کند؛ هیچ کتابخانهٔ بیرونی ندارد.
 * اگر چارچوب رسانه بار نشده باشد، دکمه بی‌اثر می‌ماند و صفحه سالم است.
 */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var box = document.querySelector('[data-tara-vip]');
		if (!box || !window.wp || !window.wp.media) {
			return;
		}

		var input = box.querySelector('[data-tara-vip-input]');
		var img = box.querySelector('[data-tara-vip-img]');
		var empty = box.querySelector('[data-tara-vip-empty]');
		var pick = box.querySelector('[data-tara-vip-pick]');
		var clear = box.querySelector('[data-tara-vip-clear]');
		var labels = window.TaraVipImage || {};
		var frame = null;

		function paint(url, has) {
			if (!img) {
				return;
			}
			if (has && url) {
				img.src = url;
				img.hidden = false;
				if (empty) {
					empty.hidden = true;
				}
				if (clear) {
					clear.hidden = false;
				}
			} else {
				img.removeAttribute('src');
				img.hidden = true;
				if (empty) {
					empty.hidden = false;
				}
				if (clear) {
					clear.hidden = true;
				}
			}
		}

		if (pick) {
			pick.addEventListener('click', function (event) {
				event.preventDefault();

				if (!frame) {
					frame = window.wp.media({
						title: labels.title || 'بنر اسلایدر ویژه',
						button: { text: labels.button || 'استفاده از این تصویر' },
						library: { type: 'image' },
						multiple: false
					});

					frame.on('select', function () {
						var att = frame.state().get('selection').first().toJSON();
						if (!att || !att.id) {
							return;
						}
						if (input) {
							input.value = String(att.id);
						}
						var url = (att.sizes && att.sizes.medium && att.sizes.medium.url) || att.url;
						paint(url, true);
					});
				}

				frame.open();
			});
		}

		if (clear) {
			clear.addEventListener('click', function (event) {
				event.preventDefault();
				if (input) {
					input.value = '';
				}
				paint('', false);
			});
		}
	});
})();
