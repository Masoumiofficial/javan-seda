/**
 * ترانه یار — رفتار صفحهٔ «تنظیمات قالب» (۳.۷.۰)
 * ---------------------------------------------------------------------------
 * فقط سه چیز اضافه می‌کند و هیچ چیزی از OptionTree را جایگزین نمی‌کند:
 *   ۱) کادر «جست‌وجوی تنظیم» در نوار ذخیره: ردیف‌های فیلد را فیلتر می‌کند
 *      (روی همهٔ تب‌ها، نه فقط تب باز).
 *   ۲) بازکردن مستقیم یک تب با `?...&section=ab_ui` (از کارت‌های پنل).
 *   ۳) حفظ اسکرول هنگام ذخیره: اگر فرم با POST برگردد، همان تب باز می‌ماند.
 *
 * @package Taranehyar
 */
(function ($) {
	'use strict';

	var settings = window.taraSettings || {};

	function panels() {
		return $('.wrap.settings-wrap .ui-tabs-panel');
	}

	function rows() {
		return $('.wrap.settings-wrap .format-settings');
	}

	/* ---------------------------------------------------------------
	 * ۱) جست‌وجو
	 * ------------------------------------------------------------ */
	function buildFinder() {
		var $bar = $('#option-tree-sub-header');
		if (!$bar.length || $bar.find('.tara-find').length) {
			return;
		}

		var $wrap = $('<div class="tara-find" role="search"></div>');
		var $input = $('<input type="search" autocomplete="off">')
			.attr('placeholder', settings.i18n ? settings.i18n.find : 'جست‌وجوی تنظیم…')
			.attr('aria-label', settings.i18n ? settings.i18n.find : 'جست‌وجوی تنظیم…');
		var $empty = $('<p class="tara-find-empty" hidden>' + (settings.i18n ? settings.i18n.empty : '') + '</p>');

		$wrap.append($input);
		$bar.append($wrap).append($empty);

		var originalDisplay = null;

		function restore() {
			if (!originalDisplay) {
				return;
			}
			panels().each(function (index) {
				this.style.display = originalDisplay[index] || '';
			});
			originalDisplay = null;
			rows().removeClass('is-tara-hidden');
		}

		$input.on('input', function () {
			var term = String($input.val() || '').trim().toLowerCase();

			if (!term) {
				$empty.attr('hidden', 'hidden');
				restore();
				return;
			}

			if (!originalDisplay) {
				originalDisplay = [];
				panels().each(function (index) {
					originalDisplay[index] = this.style.display || '';
					this.style.display = 'block';
				});
			}

			var visible = 0;

			panels().each(function () {
				var $panel = $(this);
				var panelHits = 0;

				$panel.find('.format-settings').each(function () {
					var $row = $(this);
					var text = $row.text().toLowerCase();
					var id = String($row.attr('id') || '').toLowerCase();
					var hit = text.indexOf(term) !== -1 || id.indexOf(term) !== -1;

					$row.toggleClass('is-tara-hidden', !hit);
					if (hit) {
						panelHits++;
					}
				});

				if (panelHits === 0) {
					this.style.display = 'none';
				} else {
					visible += panelHits;
				}
			});

			$empty.attr('hidden', visible === 0 ? null : 'hidden');
		});
	}

	/* ---------------------------------------------------------------
	 * ۲) بازکردن تب با ?section=
	 * ------------------------------------------------------------ */
	function openSection(section) {
		if (!section) {
			return;
		}

		var $link = $('.ui-tabs .ui-tabs-nav a[href="#section_' + section + '"]');
		if (!$link.length) {
			return;
		}

		$link.trigger('click');

		var $panel = $('#section_' + section);
		if ($panel.length) {
			window.setTimeout(function () {
				var top = $panel.offset().top - 110;
				window.scrollTo({ top: top > 0 ? top : 0, behavior: 'smooth' });
			}, 80);
		}
	}

	/* ---------------------------------------------------------------
	 * ۳) یادآوری تب هنگام ذخیره
	 * ------------------------------------------------------------ */
	function rememberTab() {
		$('.ui-tabs .ui-tabs-nav a').on('click', function () {
			var href = String($(this).attr('href') || '');
			var match = href.match(/#section_(.+)$/);
			if (match) {
				if (window.history && window.history.replaceState) {
					var url = new URL(window.location.href);
					url.searchParams.set('section', match[1]);
					window.history.replaceState({}, '', url.toString());
				}
				window.sessionStorage.setItem('tara-settings-section', match[1]);
			}
		});
	}

	$(function () {
		buildFinder();
		rememberTab();

		var section = settings.section || window.sessionStorage.getItem('tara-settings-section') || '';
		openSection(section);
	});
})(jQuery);
