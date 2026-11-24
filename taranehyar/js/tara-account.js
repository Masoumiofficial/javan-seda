/**
 * ترانه یار — اسکریپتِ حسابِ کاربری (نسخهٔ ۳.۱۰.۰)
 * =========================================================================
 * بدونِ هیچ کتابخانهٔ بیرونی. کارها:
 *   ۱) مودالِ ورود: شماره ← کدِ ۶رقمی ← ورود (با تایمرِ ارسالِ دوباره)
 *   ۲) قلبِ «ذخیره» و دکمهٔ «دنبال‌کردن» — خوش‌بینانه، با برگشتِ خودکار
 *   ۳) برگهٔ «افزودن به پلی‌لیست» (ساخت، افزودن، تغییرِ نام، حذف، عمومی)
 *   ۴) ویرایشِ مشخصات، اتصال به ربات، خروج، پاک‌کردنِ تاریخچه
 *   ۵) ثبتِ تاریخچهٔ شنیدن از روی همان دکمه‌های پلیرِ قالب
 *
 * قاعده: هر خطایی به کاربر پیامِ فارسی می‌دهد؛ هیچ‌وقت دکمه در حالتِ
 * «در حال انجام» گیر نمی‌کند.
 */
(function () {
	'use strict';

	var CFG = window.TaraAccount || {};
	var API = CFG.api || '';
	var NONCE = CFG.nonce || '';

	if (!API) {
		return;
	}

	var doc = document;

	/* ---------------------------------------------------------------- ابزار */

	function fa(n) {
		var d = '۰۱۲۳۴۵۶۷۸۹';
		return String(n).replace(/\d/g, function (m) {
			return d.charAt(Number(m));
		});
	}

	function later(fn, ms) {
		return window.setTimeout(fn, ms);
	}

	function closest(el, sel) {
		return el && el.closest ? el.closest(sel) : null;
	}

	function post(params) {
		var body = new URLSearchParams();
		body.set('action', 'tara_account');
		body.set('nonce', NONCE);
		Object.keys(params || {}).forEach(function (k) {
			body.set(k, params[k] == null ? '' : String(params[k]));
		});

		return window
			.fetch(API, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			})
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				if (!json || typeof json.success === 'undefined') {
					throw new Error((CFG.labels && CFG.labels.error) || 'خطا');
				}
				return json;
			});
	}

	/* پیامِ شناور */
	var toastBox = null;
	function toast(text, kind) {
		if (!text) {
			return;
		}
		if (!toastBox) {
			toastBox = doc.createElement('div');
			toastBox.className = 'ty-acct-toasts';
			toastBox.setAttribute('role', 'status');
			toastBox.setAttribute('aria-live', 'polite');
			doc.body.appendChild(toastBox);
		}
		var item = doc.createElement('div');
		item.className = 'ty-acct-toast' + (kind ? ' ty-acct-toast--' + kind : '');
		item.textContent = text;
		toastBox.appendChild(item);
		later(function () {
			item.classList.add('is-out');
			later(function () {
				item.remove();
			}, 260);
		}, 2600);
	}

	function setBusy(btn, on) {
		if (!btn) {
			return;
		}
		btn.classList.toggle('is-busy', !!on);
		btn.disabled = !!on;
	}

	/* ------------------------------------------------------- کپی در کلیپ‌بورد */

	function copy(text) {
		var done = function () {
			toast((CFG.labels && CFG.labels.copied) || 'کپی شد.');
		};
		if (window.navigator.clipboard && window.navigator.clipboard.writeText) {
			window.navigator.clipboard.writeText(text).then(done).catch(function () {
				fallbackCopy(text, done);
			});
			return;
		}
		fallbackCopy(text, done);
	}

	function fallbackCopy(text, done) {
		var ta = doc.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', 'readonly');
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		doc.body.appendChild(ta);
		ta.select();
		try {
			doc.execCommand('copy');
		} catch (e) {}
		ta.remove();
		if (done) {
			done();
		}
	}

	/* ------------------------------------------------------------- مودال */

	var modal = null;
	var lastFocus = null;
	var state = { phone: '', wait: 0, timer: null };

	function q(sel, root) {
		return (root || doc).querySelector(sel);
	}

	function qa(sel, root) {
		return Array.prototype.slice.call((root || doc).querySelectorAll(sel));
	}

	function openModal() {
		if (!modal) {
			modal = q('#ty-acct-modal');
		}
		if (!modal) {
			return;
		}
		lastFocus = doc.activeElement;
		modal.hidden = false;
		doc.documentElement.classList.add('ty-acct-lock');
		later(function () {
			modal.classList.add('is-open');
			var first = q('[data-tara-form="phone"] input[name="phone"]', modal);
			if (first && !q('[data-tara-form="code"]:not([hidden])', modal)) {
				first.focus({ preventScroll: true });
			}
		}, 20);
	}

	function closeModal() {
		if (!modal) {
			return;
		}
		modal.classList.remove('is-open');
		doc.documentElement.classList.remove('ty-acct-lock');
		later(function () {
			modal.hidden = true;
			if (lastFocus && lastFocus.focus) {
				lastFocus.focus({ preventScroll: true });
			}
		}, 200);
	}

	function showStep(name) {
		if (!modal) {
			return;
		}
		qa('[data-tara-form]', modal).forEach(function (form) {
			form.hidden = form.getAttribute('data-tara-form') !== name;
		});
		var title = q('[data-tara-title-phone]', modal);
		var sub = q('[data-tara-sub-phone]', modal);
		if (title) {
			title.textContent =
				name === 'code'
					? 'کدِ ۶رقمی را وارد کنید'
					: name === 'password'
						? 'ورود با رمزِ عبور'
						: 'با شمارهٔ موبایل وارد شوید';
		}
		if (sub) {
			sub.hidden = name !== 'phone';
		}
		if (name === 'code') {
			var first = q('[data-tara-code-inputs] input', modal);
			if (first) {
				later(function () {
					first.focus({ preventScroll: true });
				}, 60);
			}
		}
		if (name === 'phone') {
			var phone = q('[data-tara-form="phone"] input[name="phone"]', modal);
			if (phone) {
				later(function () {
					phone.focus({ preventScroll: true });
				}, 40);
			}
		}
	}

	function msg(form, text, kind) {
		if (!form) {
			return;
		}
		var box = q('[data-tara-msg]', form);
		if (!box) {
			return;
		}
		box.textContent = text || '';
		box.className = 'ty-acct-form__msg' + (text ? (kind === 'ok' ? ' is-ok' : ' is-bad') : '');
	}

	function startCountdown(seconds) {
		state.wait = Number(seconds) || 0;
		var btn = modal ? q('[data-tara-resend]', modal) : null;
		var txt = modal ? q('[data-tara-resend-txt]', modal) : null;
		if (state.timer) {
			window.clearInterval(state.timer);
		}
		var tick = function () {
			if (state.wait <= 0) {
				window.clearInterval(state.timer);
				if (btn) {
					btn.hidden = false;
				}
				if (txt) {
					txt.textContent = '';
				}
				return;
			}
			if (btn) {
				btn.hidden = true;
			}
			if (txt) {
				txt.textContent =
					((CFG.labels && CFG.labels.resendIn) || 'ارسالِ دوباره تا %s ثانیه').replace('%s', fa(state.wait));
			}
			state.wait -= 1;
		};
		tick();
		state.timer = window.setInterval(tick, 1000);
	}

	function codeValue() {
		return qa('[data-tara-code-inputs] input', modal)
			.map(function (i) {
				return i.value.replace(/\D/g, '');
			})
			.join('');
	}

	function clearCode() {
		qa('[data-tara-code-inputs] input', modal).forEach(function (i) {
			i.value = '';
		});
	}

	function sendCode(phone) {
		var form = q('[data-tara-form="phone"]', modal);
		var btn = form ? q('.ty-acct-submit', form) : null;
		setBusy(btn, true);
		msg(form, (CFG.labels && CFG.labels.sending) || 'در حال ارسال…', 'ok');

		return post({ do: 'send', phone: phone })
			.then(function (json) {
				setBusy(btn, false);
				if (!json.success) {
					var data = json.data || {};
					msg(form, data.message || (CFG.labels && CFG.labels.error) || 'خطا');
					if (data.wait) {
						state.phone = phone;
						showStep('code');
						startCountdown(data.wait);
					}
					return false;
				}
				state.phone = phone;
				var d = json.data || {};
				var shown = q('[data-tara-phone-shown]', modal);
				if (shown) {
					shown.textContent = d.phone || phone;
				}
				var dev = q('[data-tara-devcode]', modal);
				if (dev) {
					if (d.dev && d.dev_code) {
						dev.hidden = false;
						dev.textContent = 'حالتِ آزمایشی: کدِ شما ' + d.dev_code + ' است (پیامکی ارسال نشد).';
					} else {
						dev.hidden = true;
						dev.textContent = '';
					}
				}
				clearCode();
				showStep('code');
				msg(form, '', '');
				startCountdown(d.resend || 60);
				toast((CFG.labels && CFG.labels.codeSent) || 'کد پیامک شد.', 'ok');
				return true;
			})
			.catch(function () {
				setBusy(btn, false);
				msg(form, (CFG.labels && CFG.labels.error) || 'خطا');
				return false;
			});
	}

	function verify() {
		var form = q('[data-tara-form="code"]', modal);
		var btn = form ? q('.ty-acct-submit', form) : null;
		var code = codeValue();
		if (code.length !== 6) {
			msg(form, 'کدِ ۶رقمی را کامل وارد کنید.');
			return;
		}
		setBusy(btn, true);
		msg(form, (CFG.labels && CFG.labels.verifying) || 'در حال بررسی…', 'ok');

		post({ do: 'verify', phone: state.phone, code: code })
			.then(function (json) {
				setBusy(btn, false);
				if (!json.success) {
					var data = json.data || {};
					msg(form, data.message || 'کد نادرست است.');
					clearCode();
					var first = q('[data-tara-code-inputs] input', modal);
					if (first) {
						first.focus();
					}
					return;
				}
				msg(form, 'خوش آمدید!', 'ok');
				doc.documentElement.classList.add('ty-acct-done');
				var target = (json.data && json.data.redirect) || (CFG.url && CFG.url.account) || '/';
				later(function () {
					window.location.href = target;
				}, 420);
			})
			.catch(function () {
				setBusy(btn, false);
				msg(form, (CFG.labels && CFG.labels.error) || 'خطا');
			});
	}

	/* کلیک‌های عمومی */
	doc.addEventListener('click', function (event) {
		var t = event.target;

		if (closest(t, '[data-tara-open="account"]')) {
			event.preventDefault();
			openModal();
			return;
		}
		if (closest(t, '[data-tara-close]')) {
			event.preventDefault();
			closeModal();
			var sheet = q('#ty-acct-sheet');
			if (sheet && !sheet.hidden) {
				sheet.hidden = true;
			}
			return;
		}

		var modeBtn = closest(t, '[data-tara-mode]');
		if (modeBtn && modal && modal.contains(modeBtn)) {
			event.preventDefault();
			var mode = modeBtn.getAttribute('data-tara-mode');
			showStep(mode);
			qa('[data-tara-mode]', modal).forEach(function (b) {
				b.hidden = b === modeBtn;
			});
			return;
		}

		if (closest(t, '[data-tara-back]') && modal) {
			event.preventDefault();
			showStep('phone');
			return;
		}

		var resend = closest(t, '[data-tara-resend]');
		if (resend) {
			event.preventDefault();
			sendCode(state.phone);
			return;
		}

		/* ---- قلبِ ذخیره ---- */
		var likeBtn = closest(t, '[data-tara-like]');
		if (likeBtn) {
			event.preventDefault();
			toggleLike(likeBtn);
			return;
		}

		/* ---- دنبال‌کردن ---- */
		var followBtn = closest(t, '[data-tara-follow]');
		if (followBtn) {
			event.preventDefault();
			toggleFollow(followBtn);
			return;
		}

		/* ---- برگهٔ پلی‌لیست ---- */
		var plOpen = closest(t, '[data-tara-pl-open]');
		if (plOpen) {
			event.preventDefault();
			openSheet(plOpen.getAttribute('data-tara-pl-open'));
			return;
		}

		var plAdd = closest(t, '[data-tara-pl-add]');
		if (plAdd) {
			event.preventDefault();
			addToPlaylist(plAdd.getAttribute('data-tara-pl-add'), plAdd);
			return;
		}

		var plToggle = closest(t, '[data-tara-pl-toggle]');
		if (plToggle) {
			event.preventDefault();
			togglePlaylistTrack(plToggle);
			return;
		}

		var plDelete = closest(t, '[data-tara-pl-delete]');
		if (plDelete) {
			event.preventDefault();
			if (!window.confirm((CFG.labels && CFG.labels.confirmDelete) || 'حذف شود؟')) {
				return;
			}
			playlistOp('delete', { key: plDelete.getAttribute('data-tara-pl-delete') });
			return;
		}

		var plRename = closest(t, '[data-tara-pl-rename]');
		if (plRename) {
			event.preventDefault();
			var name = window.prompt((CFG.labels && CFG.labels.newList) || 'نامِ تازه', '');
			if (name) {
				playlistOp('rename', { key: plRename.getAttribute('data-tara-pl-rename'), name: name });
			}
			return;
		}

		var plPublic = closest(t, '[data-tara-pl-public]');
		if (plPublic) {
			event.preventDefault();
			var on = plPublic.getAttribute('data-tara-on') === '1' ? '0' : '1';
			playlistOp('public', { key: plPublic.getAttribute('data-tara-pl-public'), on: on }, plPublic);
			return;
		}

		var share = closest(t, '[data-tara-pl-share]');
		if (share) {
			event.preventDefault();
			copy(share.getAttribute('data-tara-pl-share'));
			return;
		}

		/* ---- تاریخچه ---- */
		if (closest(t, '[data-tara-history-clear]')) {
			event.preventDefault();
			post({ do: 'history-clear' }).then(function (json) {
				if (json.success) {
					window.location.reload();
				}
			});
			return;
		}

		/* ---- پیام‌ها ---- */
		if (closest(t, '[data-tara-read-all]')) {
			event.preventDefault();
			post({ do: 'notifs-read' }).then(function (json) {
				if (json.success) {
					qa('.ty-acct-notif.is-new').forEach(function (n) {
						n.classList.remove('is-new');
					});
					toast('همه خوانده شد.', 'ok');
				}
			});
			return;
		}

		/* ---- تلگرام ---- */
		if (closest(t, '[data-tara-tg-code]')) {
			event.preventDefault();
			tgCode();
			return;
		}
		if (closest(t, '[data-tara-tg-copy]')) {
			event.preventDefault();
			var val = q('[data-tara-tg-value]');
			if (val) {
				copy(val.textContent.trim());
			}
			return;
		}
		if (closest(t, '[data-tara-tg-refresh]')) {
			event.preventDefault();
			window.location.reload();
			return;
		}

		if (closest(t, '[data-tara-tg-unlink]')) {
			event.preventDefault();
			post({ do: 'tg-unlink' }).then(function (json) {
				if (json.success) {
					toast('اتصال قطع شد.', 'ok');
					later(function () {
						window.location.reload();
					}, 500);
				}
			});
			return;
		}

		/* ---- خروج ---- */
		if (closest(t, '[data-tara-logout]')) {
			event.preventDefault();
			post({ do: 'logout' }).then(function (json) {
				var target = (json.data && json.data.redirect) || (CFG.url && CFG.url.home) || '/';
				window.location.href = target;
			});
			return;
		}
	});

	/* ---------- فرم‌ها ---------- */
	doc.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.matches) {
			return;
		}

		var kind = form.getAttribute('data-tara-form');
		if (kind === 'phone') {
			event.preventDefault();
			var phone = (form.querySelector('input[name="phone"]') || {}).value || '';
			phone = phone.replace(/[^\d]/g, '');
			if (phone.length < 10) {
				msg(form, 'شمارهٔ موبایل را کامل وارد کنید.');
				return;
			}
			sendCode(phone);
			return;
		}

		if (kind === 'code') {
			event.preventDefault();
			verify();
			return;
		}

		if (kind === 'password') {
			event.preventDefault();
			var btn = q('.ty-acct-submit', form);
			setBusy(btn, true);
			msg(form, '', '');
			post({
				do: 'password',
				login: (form.querySelector('input[name="login"]') || {}).value || '',
				password: (form.querySelector('input[name="password"]') || {}).value || ''
			})
				.then(function (json) {
					setBusy(btn, false);
					if (!json.success) {
						msg(form, (json.data && json.data.message) || 'ورود انجام نشد.');
						return;
					}
					window.location.href = (json.data && json.data.redirect) || '/';
				})
				.catch(function () {
					setBusy(btn, false);
					msg(form, (CFG.labels && CFG.labels.error) || 'خطا');
				});
			return;
		}

		if (kind === 'profile') {
			event.preventDefault();
			var save = q('button[type="submit"]', form);
			setBusy(save, true);
			post({
				do: 'profile',
				name: (form.querySelector('input[name="name"]') || {}).value || '',
				bio: (form.querySelector('textarea[name="bio"]') || {}).value || ''
			})
				.then(function (json) {
					if (!json.success) {
						setBusy(save, false);
						msg(form, (json.data && json.data.message) || 'ذخیره نشد.');
						return;
					}
					/* v3.11.0 — تصویر جدا و با فایل آپلود می‌شود؛ این فرم فقط نام و توضیح را می‌فرستد. */
					setBusy(save, false);
					msg(form, (CFG.labels && CFG.labels.saved) || 'ذخیره شد.', 'ok');
					toast((CFG.labels && CFG.labels.saved) || 'ذخیره شد.', 'ok');
					var nameNode = q('.ty-acct__name');
					if (nameNode && json.data && json.data.name) {
						nameNode.textContent = json.data.name;
					}
					var txtNode = q('.ty-acct-btn--desktop .ty-acct-btn__txt');
					if (txtNode && json.data && json.data.name) {
						txtNode.textContent = json.data.name;
					}
				})
				.catch(function () {
					setBusy(save, false);
					msg(form, (CFG.labels && CFG.labels.error) || 'خطا');
				});
			return;
		}

		if (form.hasAttribute('data-tara-pl-new')) {
			event.preventDefault();
			var input = form.querySelector('input[name="name"]');
			if (!input || !input.value.trim()) {
				if (input) {
					input.focus();
				}
				return;
			}
			var sheetOpen = form.closest('#ty-acct-sheet');
			playlistOp(
				'create',
				{ name: input.value.trim() },
				null,
				function (res) {
					var created = res && res.playlists && res.playlists[0];
					if (sheetOpen && created && created.k) {
						/* در برگه: بلافاصله همان آهنگ به پلی‌لیستِ تازه اضافه شود */
						addToPlaylist(created.k, null, sheetOpen.getAttribute('data-tara-post'));
					} else {
						window.location.reload();
					}
				}
			);
			return;
		}
	});

	/* ---------- ورودی‌های کد ---------- */
	doc.addEventListener('input', function (event) {
		var input = event.target;
		if (!input || !input.closest || !input.closest('[data-tara-code-inputs]')) {
			return;
		}
		input.value = input.value.replace(/\D/g, '').slice(0, 1);
		if (input.value) {
			var next = input.nextElementSibling;
			if (next) {
				next.focus();
				next.select && next.select();
			} else if (codeValue().length === 6) {
				verify();
			}
		}
	});

	doc.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			var sheet = q('#ty-acct-sheet');
			if (sheet && !sheet.hidden) {
				sheet.hidden = true;
				return;
			}
			if (modal && !modal.hidden) {
				closeModal();
			}
			return;
		}
		var input = event.target;
		if (!input || !input.closest || !input.closest('[data-tara-code-inputs]')) {
			return;
		}
		if (event.key === 'Backspace' && !input.value) {
			var prev = input.previousElementSibling;
			if (prev) {
				prev.focus();
				prev.value = '';
			}
		}
		if (event.key === 'ArrowLeft' && input.previousElementSibling) {
			input.previousElementSibling.focus();
		}
		if (event.key === 'ArrowRight' && input.nextElementSibling) {
			input.nextElementSibling.focus();
		}
	});

	/* پیستِ کدِ کامل */
	doc.addEventListener('paste', function (event) {
		var input = event.target;
		if (!input || !input.closest || !input.closest('[data-tara-code-inputs]')) {
			return;
		}
		var text = (event.clipboardData || window.clipboardData || {}).getData;
		var data = text ? String(text('text') || '').replace(/\D/g, '').slice(0, 6) : '';
		if (!data) {
			return;
		}
		event.preventDefault();
		var inputs = qa('[data-tara-code-inputs] input', modal);
		inputs.forEach(function (el, i) {
			el.value = data.charAt(i) || '';
		});
		if (data.length === 6) {
			verify();
		} else if (inputs[data.length]) {
			inputs[data.length].focus();
		}
	});

	/* ------------------------------------------------------ قلب و دنبال‌کردن */

	function toggleLike(btn) {
		var id = btn.getAttribute('data-tara-like');
		if (!id) {
			return;
		}
		if (!CFG.logged) {
			openModal();
			toast((CFG.labels && CFG.labels.needLogin) || 'وارد شوید.', 'bad');
			return;
		}
		var on = btn.getAttribute('aria-pressed') !== 'true';
		btn.classList.add('is-busy');
		post({ do: 'like', post: id, on: on ? '1' : '0' })
			.then(function (json) {
				btn.classList.remove('is-busy');
				if (!json.success) {
					if (json.data && json.data.login) {
						openModal();
					}
					toast((json.data && json.data.message) || 'انجام نشد.', 'bad');
					return;
				}
				applyLike(btn, !!json.data.liked);
				toast(json.data.liked ? 'به علاقه‌مندی‌ها اضافه شد.' : 'از علاقه‌مندی‌ها برداشته شد.', 'ok');
			})
			.catch(function () {
				btn.classList.remove('is-busy');
				toast((CFG.labels && CFG.labels.error) || 'خطا', 'bad');
			});
	}

	function applyLike(btn, liked) {
		btn.classList.toggle('is-on', liked);
		btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
		var txt = btn.querySelector('.ty-acct-like__txt');
		if (txt) {
			txt.textContent = liked ? 'ذخیره شد' : 'ذخیره';
		}
		/* همهٔ قلب‌های همین آهنگ هم‌گام شوند */
		var id = btn.getAttribute('data-tara-like');
		qa('[data-tara-like="' + id + '"]').forEach(function (other) {
			other.classList.toggle('is-on', liked);
			other.setAttribute('aria-pressed', liked ? 'true' : 'false');
		});
	}

	function toggleFollow(btn) {
		var id = btn.getAttribute('data-tara-follow');
		if (!id) {
			return;
		}
		if (!CFG.logged) {
			openModal();
			toast((CFG.labels && CFG.labels.needLogin) || 'وارد شوید.', 'bad');
			return;
		}
		var on = btn.getAttribute('aria-pressed') !== 'true';
		btn.classList.add('is-busy');
		post({ do: 'follow', term: id, on: on ? '1' : '0' })
			.then(function (json) {
				btn.classList.remove('is-busy');
				if (!json.success) {
					if (json.data && json.data.login) {
						openModal();
					}
					toast((json.data && json.data.message) || 'انجام نشد.', 'bad');
					return;
				}
				var following = !!json.data.following;
				btn.classList.toggle('is-on', following);
				btn.setAttribute('aria-pressed', following ? 'true' : 'false');
				var txt = btn.querySelector('.ty-acct-follow__txt');
				if (txt) {
					txt.textContent = following ? 'دنبال می‌کنید' : 'دنبال‌کردن';
				}
				var num = btn.querySelector('[data-tara-followers]');
				if (num) {
					num.textContent = json.data.count > 0 ? fa(json.data.count) : '';
				}
				toast(following ? 'خواننده دنبال شد.' : 'از دنبال‌شده‌ها برداشته شد.', 'ok');
			})
			.catch(function () {
				btn.classList.remove('is-busy');
				toast((CFG.labels && CFG.labels.error) || 'خطا', 'bad');
			});
	}

	/* ---------------------------------------------------- برگهٔ پلی‌لیست */

	var sheet = null;
	var sheetPost = '';

	function openSheet(postId) {
		sheet = q('#ty-acct-sheet');
		if (!sheet) {
			return;
		}
		if (!CFG.logged) {
			openModal();
			return;
		}
		sheetPost = String(postId || '');
		sheet.setAttribute('data-tara-post', sheetPost);
		sheet.hidden = false;
		later(function () {
			sheet.classList.add('is-open');
			var first = sheet.querySelector('[data-tara-pl-add]');
			if (first) {
				first.focus({ preventScroll: true });
			}
		}, 20);
	}

	function addToPlaylist(key, btn, forcedPost) {
		var postId = forcedPost || sheetPost || '';
		if (!key || !postId) {
			return;
		}
		playlistOp('toggle', { key: key, post: postId, on: '1' }, btn, function () {
			var s = q('#ty-acct-sheet');
			if (s) {
				s.classList.remove('is-open');
				later(function () {
					s.hidden = true;
				}, 160);
			}
		});
	}

	function togglePlaylistTrack(btn) {
		var key = btn.getAttribute('data-tara-pl-key');
		var postId = btn.getAttribute('data-tara-pl-toggle');
		var on = btn.getAttribute('data-tara-pl-on') === '1';
		playlistOp('toggle', { key: key, post: postId, on: on ? '1' : '0' }, btn, function () {
			var row = btn.closest('li');
			if (row) {
				row.classList.add('is-gone');
				later(function () {
					row.remove();
				}, 220);
			}
		});
	}

	function playlistOp(op, extra, btn, done) {
		if (!CFG.logged) {
			openModal();
			return;
		}
		var params = { do: 'playlist', op: op };
		Object.keys(extra || {}).forEach(function (k) {
			params[k] = extra[k];
		});
		setBusy(btn, true);
		post(params)
			.then(function (json) {
				setBusy(btn, false);
				if (!json.success) {
					toast((json.data && json.data.message) || 'انجام نشد.', 'bad');
					return;
				}
				if (btn && btn.tagName === 'BUTTON' && btn.classList.contains('ty-acct-mini')) {
					btn.classList.toggle('is-on');
				}
				if (op === 'toggle') {
					toast(json.data.added ? (CFG.labels && CFG.labels.added) || 'اضافه شد.' : (CFG.labels && CFG.labels.removed) || 'برداشته شد.', 'ok');
				}
				if (done) {
					done(json.data);
				} else if (op !== 'toggle') {
					window.location.reload();
				}
			})
			.catch(function () {
				setBusy(btn, false);
				toast((CFG.labels && CFG.labels.error) || 'خطا', 'bad');
			});
	}

	/* ------------------------------------------------------------- تلگرام */

	function tgCode() {
		var btn = q('[data-tara-tg-code]');
		setBusy(btn, true);
		post({ do: 'tg-code' })
			.then(function (json) {
				setBusy(btn, false);
				if (!json.success) {
					toast((json.data && json.data.message) || 'کد ساخته نشد.', 'bad');
					return;
				}
				var box = q('[data-tara-tg-box]');
				var val = q('[data-tara-tg-value]');
				var link = q('[data-tara-tg-link]');
				if (val) {
					val.textContent = json.data.code;
				}
				if (box) {
					box.hidden = false;
				}
				/* v3.11.0 — «رفتن به ربات» فقط بعد از ساختِ کد و به‌عنوانِ گامِ بعد
				   ظاهر می‌شود (پیش از این دو دکمهٔ هم‌وزن بود و کاربر سردرگم می‌شد).
				   لینک آگاهانه ساده است (`t.me/<bot>`) چون ربات کد را از متنِ چت
				   می‌خواند، نه از پارامترِ start. */
				if (link) {
					link.hidden = false;
				}
				copy(json.data.code);
			})
			.catch(function () {
				setBusy(btn, false);
				toast((CFG.labels && CFG.labels.error) || 'خطا', 'bad');
			});
	}

	/* -------------------------------------------------- تاریخچهٔ شنیدن */

	var lastHistory = 0;
	doc.addEventListener('click', function (event) {
		if (!CFG.logged || !CFG.features || !CFG.features.history) {
			return;
		}
		var trigger = closest(event.target, '[data-tara-track]');
		var id = trigger ? trigger.getAttribute('data-tara-track') : '';
		if (!id) {
			return;
		}
		var now = Date.now();
		if (now - lastHistory < 15000) {
			return;
		}
		lastHistory = now;
		post({ do: 'history', post: id }).catch(function () {});
	});

	/* ------------------------------------------------------- شروع */

	/* ------------------------------------------------------------------
	 * v3.11.0 — آپلودِ تصویرِ پروفایل
	 * فایل به همان نقطهٔ پایانیِ AJAX با FormData می‌رود و بعد از موفقیت،
	 * پیش‌نمایشِ پنل و تصویرِ کوچکِ هدر با هم عوض می‌شوند.
	 * ---------------------------------------------------------------- */
	function avatarMessage(text, kind) {
		var box = q('[data-tara-avatar-msg]');
		if (!box) {
			return;
		}
		box.textContent = text || '';
		box.className = 'ty-acct-form__msg' + (kind ? ' is-' + kind : '');
	}

	function paintAvatar(url) {
		if (!url) {
			return;
		}
		var main = q('[data-tara-avatar-img]');
		if (main) {
			main.src = url;
		}
		var preview = q('[data-tara-avatar-preview]');
		if (preview) {
			preview.src = url;
		}
		var heads = q('[data-tara-avatar-head]');
		if (heads) {
			heads.src = url;
		}
		var btnImg = q('.ty-acct-btn--desktop .ty-acct-btn__img, .ty-acct-btn--mobile .ty-acct-btn__img');
		if (btnImg) {
			btnImg.src = url;
		}
	}

	function uploadAvatar(file) {
		if (!file) {
			return;
		}
		if (!/^image\//.test(file.type || '')) {
			avatarMessage('فقط فایلِ تصویری پذیرفته می‌شود.', 'err');
			return;
		}
		if (file.size > 2 * 1024 * 1024) {
			avatarMessage('حجمِ تصویر باید کمتر از ۲ مگابایت باشد.', 'err');
			return;
		}

		var form = new FormData();
		form.append('action', 'tara_account');
		form.append('nonce', NONCE);
		form.append('do', 'avatar');
		form.append('avatar', file, file.name || 'avatar.jpg');

		avatarMessage((CFG.labels && CFG.labels.sending) || 'در حال ارسال…');

		window
			.fetch(API, { method: 'POST', credentials: 'same-origin', body: form })
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				if (!json || !json.success) {
					avatarMessage((json && json.data && json.data.message) || 'آپلود انجام نشد.', 'err');
					return;
				}
				paintAvatar(json.data && json.data.url);
				avatarMessage('تصویرِ پروفایل عوض شد.', 'ok');
				toast('تصویرِ پروفایل عوض شد.', 'ok');
			})
			.catch(function () {
				avatarMessage((CFG.labels && CFG.labels.error) || 'خطا', 'err');
			});
	}

	function boot() {
		modal = q('#ty-acct-modal');

		/* انتخابِ فایل ⇒ آپلودِ فوری */
		doc.addEventListener('change', function (event) {
			var input = event.target;
			if (input && input.matches && input.matches('[data-tara-avatar-file]')) {
				uploadAvatar(input.files && input.files[0]);
			}
		});

		/* حذفِ تصویر */
		doc.addEventListener('click', function (event) {
			var btn = closest(event.target, '[data-tara-avatar-remove]');
			if (!btn) {
				return;
			}
			event.preventDefault();
			btn.classList.add('is-busy');
			post({ do: 'avatar-remove' })
				.then(function (json) {
					btn.classList.remove('is-busy');
					if (json.success) {
						paintAvatar(json.data && json.data.url);
						avatarMessage('تصویر برداشته شد.', 'ok');
					}
				})
				.catch(function () {
					btn.classList.remove('is-busy');
				});
		});
		if (modal && (window.location.hash === '#login' || window.location.search.indexOf('login=1') !== -1)) {
			openModal();
		}
		/* اگر کاربر مهمان روی «ذخیره» بزند، بعد از ورود همان آهنگ ذخیره شود */
		try {
			var pending = window.localStorage.getItem('tara_pending_like');
			if (pending && CFG.logged) {
				window.localStorage.removeItem('tara_pending_like');
				post({ do: 'like', post: pending, on: '1' }).then(function (json) {
					if (json.success) {
						toast('آهنگِ قبلی ذخیره شد.', 'ok');
					}
				});
			}
		} catch (e) {}
	}

	if (doc.readyState === 'loading') {
		doc.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
