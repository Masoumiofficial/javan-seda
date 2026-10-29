/**
 * ترانه یار — موتور رادیو و دی‌جیِ زنده (نسخهٔ ۳.۹.۰)
 * ===========================================================================
 * چه می‌کند؟
 *   • دو المانِ صوتیِ هم‌زمان (دِک A/B) و کراس‌فیدِ برابر-توان بین آن‌ها.
 *   • «هم‌ترازیِ سرعت»: آهنگِ تازه با سرعتِ کمی کمتر وارد می‌شود و به ۱ می‌رسد
 *     (همان کاری که دی‌جی با پیچ می‌کند) — فقط وقتی طولِ میکس بالای ۲.۵ ثانیه باشد.
 *   • «باس‌سوپ» و فیلترِ گذر با Web Audio — **فقط برای فایل‌های همین دامنه**.
 *     برای فایلِ دامنهٔ دیگر گراف ساخته نمی‌شود، چون بدونِ هدرِ CORS صدای
 *     MediaElementSource کاملاً قطع می‌شود. در آن حالت همه‌چیز با ولومِ
 *     خودِ المان انجام می‌شود و موسیقی هرگز ساکت نمی‌شود.
 *   • معرفیِ صوتیِ ایستگاه (فایلِ آپلودی یا گفتارِ مرورگر) با کم‌کردنِ
 *     خودکارِ موسیقی (ducking).
 *   • صفِ خودتجدیدشونده با AJAX، «فهرست من» در localStorage، خروجیِ متنیِ جلسه.
 *
 * وابستگی: هیچ (بدون jQuery). اگر هر بخشی پشتیبانی نشود، پخشِ ساده سر جایش
 * می‌ماند.
 *
 * @package Taranehyar
 */
(function () {
	'use strict';

	var CFG = (window.TaraRadio && typeof window.TaraRadio === 'object') ? window.TaraRadio : {};
	var CONF = CFG.config || {};
	var L = CONF.labels || {};
	var API = CFG.api || '';
	var NONCE = CFG.nonce || '';
	var SITE = CONF.site || 'ترانه یار';
	var FA = '۰۱۲۳۴۵۶۷۸۹';

	var KEY_MINE = 'tara:radio:mine';
	var KEY_PREF = 'tara:radio:pref';

	var instances = [];
	var allAudio = [];

	/* =====================================================================
	 * ۰) ابزارهای کوچک
	 * ================================================================== */

	function now() {
		return (window.performance && window.performance.now) ? window.performance.now() : Date.now();
	}

	function faNum(value) {
		return String(value).replace(/\d/g, function (d) {
			return FA.charAt(Number(d));
		});
	}

	function clock(sec) {
		sec = Math.max(0, Math.floor(Number(sec) || 0));
		var m = Math.floor(sec / 60);
		var s = sec % 60;
		return faNum(m) + ':' + (s < 10 ? '0' : '') + s;
	}

	function clamp(v, min, max) {
		v = Number(v) || 0;
		return v < min ? min : (v > max ? max : v);
	}

	function smooth(t) {
		t = clamp(t, 0, 1);
		return t * t * (3 - 2 * t);
	}

	function each(list, fn, scope) {
		Array.prototype.forEach.call(list || [], fn, scope);
	}

	function parseJSON(text) {
		try {
			return JSON.parse(text);
		} catch (e) {
			return null;
		}
	}

	function readStore(key, fallback) {
		try {
			var raw = window.localStorage.getItem(key);
			if (!raw) {
				return fallback;
			}
			var val = parseJSON(raw);
			return val === null ? fallback : val;
		} catch (e) {
			return fallback;
		}
	}

	function writeStore(key, value) {
		try {
			window.localStorage.setItem(key, JSON.stringify(value));
			return true;
		} catch (e) {
			return false;
		}
	}

	function trackIds(list) {
		var out = [];
		each(list, function (t) {
			if (t && t.id) {
				out.push(Number(t.id));
			}
		});
		return out;
	}

	function uniqueTracks(list) {
		var seen = {};
		var out = [];
		each(list, function (t) {
			if (!t || !t.src || !t.id) {
				return;
			}
			var id = String(t.id);
			if (seen[id]) {
				return;
			}
			seen[id] = true;
			out.push(t);
		});
		return out;
	}

	function shuffle(list) {
		var i;
		for (i = list.length - 1; i > 0; i--) {
			var j = Math.floor(Math.random() * (i + 1));
			var tmp = list[i];
			list[i] = list[j];
			list[j] = tmp;
		}
		return list;
	}

	function normalize(track) {
		if (!track || !track.src) {
			return null;
		}
		return {
			id: Number(track.id) || 0,
			title: String(track.title || ''),
			artist: String(track.artist || ''),
			src: String(track.src || ''),
			cover: String(track.cover || ''),
			link: String(track.link || ''),
			duration: Number(track.duration) || 0
		};
	}

	function sameOrigin(url) {
		try {
			return new URL(url, window.location.href).origin === window.location.origin;
		} catch (e) {
			return false;
		}
	}

	/* =====================================================================
	 * ۱) «فهرست من» — بین همهٔ صفحه‌ها مشترک است
	 * ================================================================== */

	function mineList() {
		var list = readStore(KEY_MINE, []);
		if (!Array.isArray(list)) {
			list = [];
		}
		return list.map(normalize).filter(Boolean);
	}

	function mineSave(list) {
		writeStore(KEY_MINE, uniqueTracks(list).slice(-80));
	}

	function mineAdd(track) {
		var t = normalize(track);
		if (!t) {
			return { added: false, track: null };
		}
		var list = mineList();
		var exists = list.some(function (item) {
			return String(item.id) === String(t.id);
		});
		if (exists) {
			return { added: false, track: t };
		}
		list.push(t);
		mineSave(list);
		return { added: true, track: t };
	}

	function mineRemove(id) {
		mineSave(mineList().filter(function (item) {
			return String(item.id) !== String(id);
		}));
	}

	/* =====================================================================
	 * ۲) پیامِ شناور (برای دکمه‌های بیرونِ کارت هم کار می‌کند)
	 * ================================================================== */

	var globalToast = null;
	var globalToastTimer = 0;

	function toastGlobal(text) {
		if (!document.body) {
			return;
		}
		if (!globalToast) {
			globalToast = document.createElement('div');
			globalToast.className = 'ty-radio-global-toast';
			globalToast.setAttribute('role', 'status');
			globalToast.setAttribute('aria-live', 'polite');
			document.body.appendChild(globalToast);
		}
		globalToast.textContent = text;
		globalToast.classList.add('is-on');
		window.clearTimeout(globalToastTimer);
		globalToastTimer = window.setTimeout(function () {
			globalToast.classList.remove('is-on');
		}, 2600);
	}

	/* =====================================================================
	 * ۳) پخش‌کننده
	 * ================================================================== */

	function Radio(root) {
		this.root = root;
		this.refs = {};
		this.decks = [];
		this.active = 0;
		this.queue = [];
		this.cursor = -1;
		this.nowId = null;
		this.history = [];
		this.bad = {};
		this.state = 'idle';
		this.fade = null;
		this.duck = 1;
		this.duckTimer = 0;
		this.toastTimer = 0;
		this.mixed = 0;
		this.trackCount = 0;
		this.playedSeconds = 0;
		this.startedAt = 0;
		this.sessionStart = 0;
		this.softStart = -10000;
		this.raf = 0;
		this.rafPending = false;
		this.refilling = false;
		this.waitingNext = false;
		this.seeking = false;
		this.lastMeterPaint = 0;
		this.lastClockPaint = 0;
		this.lastSecond = -1;
		this.voice = null;
		this.graph = null;
		this.forcedPlain = false;
		this.limit = Math.round(clamp(CONF.limit || 20, 4, 40));
		this.xfade = Math.round(clamp(CONF.xfade === undefined ? 6 : CONF.xfade, 0, 16));
		this.power = 'warm';
		this.mode = root.getAttribute('data-radio-mode') || CONF.mode || 'mixed';
		this.tax = root.getAttribute('data-radio-tax') || '';
		this.term = Number(root.getAttribute('data-radio-term') || 0) || 0;
	}

	/* ---- راه‌اندازی ---- */

	Radio.prototype.init = function () {
		this.collectRefs();
		this.readPayload();
		this.readPrefs();
		this.buildDecks();
		this.bind();
		this.paintAll();
		instances.push(this);

		if (CONF.autostart) {
			var self = this;
			window.setTimeout(function () {
				if (self.state === 'idle' && self.queue.length) {
					self.play();
				}
			}, 1200);
		}

		return this;
	};

	Radio.prototype.collectRefs = function () {
		var r = this.root;
		var pick = function (sel) {
			return r.querySelector(sel);
		};
		this.refs = {
			audio: pick('[data-radio-audio]'),
			cover: pick('[data-radio-cover]'),
			title: pick('[data-radio-title]'),
			artist: pick('[data-radio-artist]'),
			link: pick('[data-radio-link]'),
			state: pick('[data-radio-state]'),
			startText: pick('[data-radio-starttxt]'),
			rail: pick('[data-radio-fill]'),
			buffer: pick('[data-radio-buffer]'),
			seek: pick('[data-radio-seek]'),
			time: pick('[data-radio-time]'),
			xfade: pick('[data-radio-xfade]'),
			xfadeVal: pick('[data-radio-xfade-val]'),
			session: pick('[data-radio-session]'),
			mixed: pick('[data-radio-mixed]'),
			queued: pick('[data-radio-queued]'),
			queue: pick('[data-radio-queue]'),
			queueMeta: pick('[data-radio-queue-meta]'),
			empty: pick('[data-radio-empty]'),
			toast: pick('[data-radio-toast]'),
			minePanel: pick('[data-radio-mine-panel]'),
			mineList: pick('[data-radio-mine-list]'),
			mineEmpty: pick('[data-radio-mine-empty]'),
			mineCount: r.querySelectorAll('[data-radio-mine-count]'),
			mineBtn: pick('[data-radio-mine]'),
			vu: r.querySelectorAll('[data-radio-vu] i')
		};
	};

	Radio.prototype.readPayload = function () {
		var node = this.root.querySelector('.ty-radio__data');
		var data = node ? parseJSON(node.textContent || '') : null;
		if (!data || !Array.isArray(data.tracks)) {
			data = { tracks: [] };
		}
		this.queue = uniqueTracks(data.tracks.map(normalize));
		this.cursor = -1;
		this.limit = Math.round(clamp(Number(data.limit) || this.limit, 4, 40));

		/* آهنگ‌های «فهرست من» اولِ صف می‌آیند تا رادیو با انتخابِ خودِ
		   کاربر شروع شود؛ بعد صفِ خودکار ادامه می‌یابد. */
		var mine = mineList();
		if (mine.length) {
			var mineIds = trackIds(mine);
			this.queue = uniqueTracks(mine.concat(this.queue.filter(function (t) {
				return mineIds.indexOf(Number(t.id)) === -1;
			})));
		}
	};

	Radio.prototype.readPrefs = function () {
		var pref = readStore(KEY_PREF, {});
		if (pref && typeof pref === 'object') {
			if (typeof pref.xfade === 'number') {
				this.xfade = Math.round(clamp(pref.xfade, 0, 16));
			}
			if (pref.power) {
				this.power = String(pref.power);
			}
		}
	};

	Radio.prototype.savePrefs = function () {
		writeStore(KEY_PREF, { xfade: this.xfade, power: this.power });
	};

	Radio.prototype.buildDecks = function () {
		var self = this;
		var host = this.refs.audio;
		if (!host) {
			return;
		}
		host.innerHTML = '';
		this.decks = [];

		[0, 1].forEach(function (i) {
			var el = new window.Audio();
			el.preload = 'auto';
			el.setAttribute('playsinline', '');
			el.className = 'ty-radio__audioel';
			el.volume = 0;

			el.addEventListener('ended', function () {
				if (i === self.active && self.state === 'playing') {
					self.advance('ended');
				}
			});

			el.addEventListener('error', function () {
				if (!el.getAttribute('src')) {
					return;
				}
				self.onDeckError(i);
			});

			el.addEventListener('playing', function () {
				if (i === self.active) {
					self.root.classList.remove('is-loading');
					self.root.classList.add('is-playing');
				}
			});

			host.appendChild(el);
			allAudio.push(el);
			self.decks.push(el);
		});
	};

	/* ---- صف ---- */

	Radio.prototype.isBad = function (track) {
		return !track || !track.src || this.bad[String(track.id)] === true;
	};

	Radio.prototype.nextTrack = function () {
		var i;
		for (i = this.cursor + 1; i < this.queue.length; i++) {
			if (!this.isBad(this.queue[i])) {
				this.cursor = i;
				return this.queue[i];
			}
		}
		return null;
	};

	Radio.prototype.pickFirst = function () {
		var i;
		for (i = 0; i < this.queue.length; i++) {
			if (!this.isBad(this.queue[i])) {
				this.cursor = i;
				return this.queue[i];
			}
		}
		return null;
	};

	Radio.prototype.ensureQueue = function () {
		var self = this;

		if (this.cursor + 1 < this.queue.length) {
			return Promise.resolve(true);
		}
		if (this.refilling) {
			return Promise.resolve(false);
		}
		if (!API || !window.fetch) {
			return Promise.resolve(false);
		}
		this.refilling = true;

		var exclude = trackIds(this.history).concat(trackIds(this.queue)).slice(-120);
		var url = API + (API.indexOf('?') === -1 ? '?' : '&') + 'action=tara_radio'
			+ '&nonce=' + encodeURIComponent(NONCE)
			+ '&mode=' + encodeURIComponent(this.mode)
			+ '&limit=' + encodeURIComponent(String(this.limit))
			+ '&exclude=' + encodeURIComponent(exclude.join(','))
			+ (this.tax ? '&tax=' + encodeURIComponent(this.tax) : '')
			+ (this.term ? '&term=' + encodeURIComponent(String(this.term)) : '');

		return window.fetch(url, { credentials: 'same-origin' })
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				self.refilling = false;
				if (!json || !json.success || !json.data || !Array.isArray(json.data.tracks)) {
					return false;
				}
				var fresh = uniqueTracks(json.data.tracks.map(normalize)).filter(function (t) {
					return !self.isBad(t);
				});
				if (!fresh.length) {
					return false;
				}
				self.queue = self.queue.concat(fresh);
				self.paintQueue();
				return true;
			})
			.catch(function () {
				self.refilling = false;
				return false;
			});
	};

	/* ---- پخش ---- */

	Radio.prototype.current = function () {
		return this.decks[this.active];
	};

	Radio.prototype.storeTrack = function (track) {
		if (!track) {
			return;
		}
		this.history.push(track);
		if (this.history.length > 60) {
			this.history = this.history.slice(-60);
		}
	};

	Radio.prototype.recycle = function () {
		var rest = this.queue.filter(function (t) {
			return t && t.src && !this.isBad(t);
		}, this);
		if (!rest.length) {
			return false;
		}
		this.queue = shuffle(rest.slice());
		this.cursor = -1;
		this.paintQueue();
		return true;
	};

	Radio.prototype.sessionSeconds = function () {
		var live = 0;
		if (this.state === 'playing' || this.state === 'fading') {
			live = this.sessionStart ? (now() - this.sessionStart) / 1000 : 0;
		}
		return this.playedSeconds + live;
	};

	Radio.prototype.play = function (track) {
		var self = this;
		var target = normalize(track || null);

		if (!this.decks.length) {
			return;
		}

		if (!target) {
			target = (this.cursor >= 0 ? this.queue[this.cursor] : null) || this.pickFirst();
		}

		if (!target || this.isBad(target)) {
			this.toast(L.empty || 'آهنگی برای پخش پیدا نشد.');
			this.ensureQueue().then(function (ok) {
				if (ok) {
					self.play();
				}
			});
			return;
		}

		/* همان آهنگ در حال پخش است ⇒ فقط ادامه/توقف */
		if (String(target.id) === String(this.nowId) && (this.state === 'playing' || this.state === 'fading')) {
			return;
		}
		if (String(target.id) === String(this.nowId) && this.state === 'paused') {
			this.resume();
			return;
		}

		var idx = this.queue.indexOf(target);
		if (idx === -1) {
			this.queue.splice(this.cursor + 1, 0, target);
			idx = this.cursor + 1;
		}
		this.cursor = idx;

		pauseOthers(this);
		if (CONF.fx && !this.graph && !this.forcedPlain && sameOrigin(target.src)) {
			this.buildGraph();
		}
		this.startDeck(target, 0);
	};

	Radio.prototype.startDeck = function (track, time) {
		var self = this;
		var el = this.decks[this.active];
		var other = this.decks[1 - this.active];

		track = normalize(track);
		if (!track || !el) {
			return;
		}

		if (other) {
			other.pause();
			other.volume = 0;
		}

		this.root.classList.add('is-loading');
		this.state = 'playing';
		this.root.classList.add('is-playing');
		this.nowId = track.id;
		this.setNow(track);
		this.paintQueue();

		el.pause();
		if (this.graph) {
			resetGraphDeck(this.graph.nodes[this.active]);
		}
		el.src = track.src;
		el.volume = 0;
		el.playbackRate = 1;

		var cue = Math.round(clamp(CONF.cue || 0, 0, 30));
		var startAt = Math.max(0, Number(time) || cue);

		if (startAt > 0) {
			var onMeta = function () {
				el.removeEventListener('loadedmetadata', onMeta);
				if (isFinite(el.duration) && el.duration > startAt + 5) {
					try {
						el.currentTime = startAt;
					} catch (e) {}
				}
			};
			el.addEventListener('loadedmetadata', onMeta);
		}

		var promise = el.play();
		this.softStart = now();
		this.duck = 1;
		this.trackCount++;
		this.mixed++;
		this.storeTrack(track);

		if (!this.sessionStart) {
			this.sessionStart = now();
		}

		if (promise && typeof promise.catch === 'function') {
			promise.catch(function () {
				self.root.classList.remove('is-loading');
				self.state = 'paused';
				self.root.classList.remove('is-playing');
				self.toast('برای پخش، یک‌بار روی دکمهٔ پخش بزنید.');
				self.paintState();
			});
		}

		this.paintState();
		this.paintMeters();
		this.updateMediaSession(track);
		this.loop();
		this.maybeAnnounce(track, true);
	};

	Radio.prototype.advance = function (reason, seconds) {
		var self = this;

		if (this.waitingNext) {
			return;
		}

		var next = this.nextTrack();
		if (!next) {
			this.waitingNext = true;
			this.ensureQueue().then(function (ok) {
				self.waitingNext = false;
				if (!ok) {
					self.recycle();
				}
				var after = self.nextTrack();
				if (after) {
					self.crossTo(after, reason === 'ended' ? 0 : seconds);
				} else if (reason === 'user') {
					self.toast('صف تمام شد؛ آهنگ تازه‌ای پیدا نشد.');
				}
			});
			return;
		}

		this.crossTo(next, reason === 'ended' ? 0 : seconds);
	};

	Radio.prototype.crossTo = function (track, seconds) {
		var self = this;
		var cur = this.current();
		var want = typeof seconds === 'number' ? seconds : this.xfade;
		var dur = want * 1000;

		if (!cur || !cur.src || this.state === 'idle') {
			this.startDeck(track, 0);
			return;
		}

		/* سقف: نه بیشتر از ۴۵٪ آهنگ و نه بیشتر از زمانِ باقی‌مانده */
		if (isFinite(cur.duration) && cur.duration > 0) {
			var remainMs = (cur.duration - cur.currentTime) * 1000;
			var cap = Math.min(cur.duration * 450, Math.max(0, remainMs));
			dur = Math.min(dur, cap);
		}

		dur = clamp(dur, 0, 16000);

		if (dur < 350) {
			cur.pause();
			cur.volume = 0;
			this.startDeck(track, 0);
			return;
		}

		var incoming = this.decks[1 - this.active];
		if (!incoming) {
			this.startDeck(track, 0);
			return;
		}

		incoming.pause();
		incoming.src = track.src;
		incoming.volume = 0;
		incoming.playbackRate = 1;
		if (this.graph) {
			resetGraphDeck(this.graph.nodes[1 - this.active]);
		}

		var p = incoming.play();
		if (p && typeof p.catch === 'function') {
			p.catch(function () {});
		}

		this.pending = normalize(track);
		this.state = 'fading';
		this.fade = { start: now(), dur: dur };
		this.root.classList.add('is-fading');
		this.animateGraph(dur);
		this.paintState();

		window.setTimeout(function () {
			if (self.fade) {
				self.finishFade();
			}
		}, dur + 160);

		this.loop();
	};

	Radio.prototype.finishFade = function () {
		if (!this.fade) {
			return;
		}

		var old = this.decks[this.active];
		var nextTrack = this.pending;

		if (old) {
			old.pause();
			old.volume = 0;
			old.playbackRate = 1;
		}

		this.active = 1 - this.active;
		var el = this.decks[this.active];
		if (el) {
			el.volume = this.duck;
			el.playbackRate = 1;
		}
		if (this.graph) {
			resetGraphDeck(this.graph.nodes[this.active]);
		}

		this.fade = null;
		this.pending = null;
		this.state = 'playing';
		this.root.classList.remove('is-fading');
		this.root.classList.remove('is-loading');

		if (nextTrack) {
			this.nowId = nextTrack.id;
			this.setNow(nextTrack);
			this.storeTrack(nextTrack);
			this.mixed++;
			this.trackCount++;
			this.updateMediaSession(nextTrack);
			this.maybeAnnounce(nextTrack, false);
		}

		this.paintQueue();
		this.paintMeters();
		this.paintState();
	};

	Radio.prototype.toggle = function () {
		if (this.state === 'playing' || this.state === 'fading') {
			this.pause();
			return;
		}
		if (this.state === 'paused' && this.current() && this.current().getAttribute('src')) {
			this.resume();
			return;
		}
		this.play();
	};

	Radio.prototype.pause = function () {
		if (this.state === 'playing' || this.state === 'fading') {
			this.playedSeconds = this.sessionSeconds();
			this.sessionStart = 0;
		}
		var el = this.current();
		if (el) {
			el.pause();
		}
		if (this.state !== 'idle') {
			this.state = 'paused';
		}
		this.fade = null;
		this.pending = null;
		this.root.classList.remove('is-playing');
		this.root.classList.remove('is-loading');
		this.root.classList.remove('is-fading');
		this.paintState();
		window.cancelAnimationFrame(this.raf);
	};

	Radio.prototype.resume = function () {
		var el = this.current();
		if (!el || !el.getAttribute('src')) {
			this.play();
			return;
		}
		if (this.state === 'playing') {
			return;
		}
		pauseOthers(this);
		this.state = 'playing';
		this.root.classList.add('is-playing');
		this.sessionStart = now();
		el.play().catch(function () {});
		this.paintState();
		this.loop();
	};

	Radio.prototype.stop = function () {
		this.state = 'idle';
		this.nowId = null;
		this.fade = null;
		var el = this.current();
		if (el) {
			el.pause();
			el.removeAttribute('src');
			try {
				el.load();
			} catch (e) {}
		}
		this.root.classList.remove('is-playing');
		this.root.classList.remove('is-loading');
		this.root.classList.remove('is-fading');
		this.paintState();
		window.cancelAnimationFrame(this.raf);
	};

	/* ---- گرافِ صدا (اختیاری، فقط همین دامنه) ---- */

	Radio.prototype.buildGraph = function () {
		var AC = window.AudioContext || window.webkitAudioContext;
		if (!AC || this.graph) {
			return;
		}

		var ctx;
		try {
			ctx = new AC();
		} catch (e) {
			return;
		}

		var master = ctx.createGain();
		master.gain.value = 1;

		var analyser = ctx.createAnalyser();
		analyser.fftSize = 128;
		analyser.smoothingTimeConstant = 0.72;

		master.connect(analyser);
		analyser.connect(ctx.destination);

		/* «اکوی گذر»: تأخیرِ کوتاه با فیدبکِ ملایم برای انتهای هر میکس */
		var delay = ctx.createDelay(1);
		delay.delayTime.value = 0.26;
		var feedback = ctx.createGain();
		feedback.gain.value = 0.32;
		delay.connect(feedback);
		feedback.connect(delay);
		var wet = ctx.createGain();
		wet.gain.value = 0.55;
		delay.connect(wet);
		wet.connect(master);

		var nodes = [];

		try {
			this.decks.forEach(function (el) {
				el.crossOrigin = 'anonymous';
				var src = ctx.createMediaElementSource(el);
				var shelf = ctx.createBiquadFilter();
				shelf.type = 'lowshelf';
				shelf.frequency.value = 180;
				shelf.gain.value = 0;

				var lp = ctx.createBiquadFilter();
				lp.type = 'lowpass';
				lp.frequency.value = 20000;
				lp.Q.value = 0.6;

				var send = ctx.createGain();
				send.gain.value = 0;

				src.connect(shelf);
				shelf.connect(lp);
				lp.connect(master);
				lp.connect(send);
				send.connect(delay);

				nodes.push({ src: src, shelf: shelf, lp: lp, send: send });
			});
		} catch (e) {
			try {
				ctx.close();
			} catch (e2) {}
			this.forcedPlain = true;
			return;
		}

		this.graph = {
			ctx: ctx,
			master: master,
			analyser: analyser,
			delay: delay,
			nodes: nodes,
			data: new Uint8Array(analyser.frequencyBinCount)
		};

		this.root.classList.add('has-graph');

		if (ctx.state === 'suspended' && ctx.resume) {
			ctx.resume().catch(function () {});
		}
	};

	Radio.prototype.destroyGraph = function () {
		var track = this.queue[this.cursor] || null;
		var at = 0;
		var el = this.current();
		if (el) {
			at = el.currentTime || 0;
		}
		var wasPlaying = this.state === 'playing' || this.state === 'fading';

		if (this.graph) {
			try {
				this.graph.ctx.close();
			} catch (e) {}
		}
		this.graph = null;
		this.forcedPlain = true;
		this.root.classList.remove('has-graph');

		var self = this;
		this.resetDecks();

		if (track && wasPlaying) {
			window.setTimeout(function () {
				self.startDeck(track, at);
			}, 80);
		}
	};

	Radio.prototype.resetDecks = function () {
		each(this.decks, function (el) {
			try {
				el.pause();
				el.removeAttribute('src');
				el.load();
			} catch (e) {}
			if (el.parentNode) {
				el.parentNode.removeChild(el);
			}
		});
		this.decks = [];
		this.active = 0;
		this.nowId = null;
		allAudio = allAudio.filter(function (el) {
			return this.decks.indexOf(el) !== -1;
		}, this);
		this.buildDecks();
	};

	Radio.prototype.animateGraph = function (durMs) {
		if (!this.graph) {
			return;
		}
		var t = this.graph.ctx.currentTime;
		var d = Math.max(0.2, durMs / 1000);
		var out = this.graph.nodes[this.active];
		var inc = this.graph.nodes[1 - this.active];

		try {
			out.lp.frequency.cancelScheduledValues(t);
			out.lp.frequency.setValueAtTime(Math.max(500, out.lp.frequency.value), t);
			out.lp.frequency.exponentialRampToValueAtTime(480, t + d);
			out.shelf.gain.cancelScheduledValues(t);
			out.shelf.gain.setValueAtTime(out.shelf.gain.value, t);
			out.shelf.gain.linearRampToValueAtTime(-7, t + d);
			out.send.gain.cancelScheduledValues(t);
			out.send.gain.setValueAtTime(0, t);
			out.send.gain.linearRampToValueAtTime(0.22, t + d * 0.75);

			inc.lp.frequency.cancelScheduledValues(t);
			inc.lp.frequency.setValueAtTime(900, t);
			inc.lp.frequency.exponentialRampToValueAtTime(20000, t + d);
			inc.shelf.gain.cancelScheduledValues(t);
			inc.shelf.gain.setValueAtTime(-9, t);
			inc.shelf.gain.linearRampToValueAtTime(0, t + d * 0.8);
			inc.send.gain.cancelScheduledValues(t);
			inc.send.gain.setValueAtTime(0, t);
		} catch (e) {}
	};

	function resetGraphDeck(node) {
		if (!node) {
			return;
		}
		try {
			var t = node.lp.context.currentTime;
			node.lp.frequency.cancelScheduledValues(t);
			node.lp.frequency.setValueAtTime(20000, t);
			node.shelf.gain.cancelScheduledValues(t);
			node.shelf.gain.setValueAtTime(0, t);
			node.send.gain.cancelScheduledValues(t);
			node.send.gain.setValueAtTime(0, t);
		} catch (e) {}
	}

	/* ---- حلقهٔ زنده ---- */

	Radio.prototype.loop = function () {
		var self = this;
		if (this.rafPending) {
			return;
		}
		this.rafPending = true;

		var frame = function () {
			self.rafPending = false;
			if (self.state !== 'playing' && self.state !== 'fading') {
				return;
			}
			self.tick();
			if (self.state === 'playing' || self.state === 'fading') {
				self.raf = window.requestAnimationFrame(frame);
			}
		};

		this.raf = window.requestAnimationFrame(frame);
	};

	Radio.prototype.tick = function () {
		var el = this.current();
		if (!el) {
			return;
		}

		this.applyGains();

		var stamp = now();
		if (stamp - this.lastClockPaint > 120) {
			this.lastClockPaint = stamp;
			this.paintTime(el);
		}
		if (stamp - this.lastMeterPaint > 500) {
			this.lastMeterPaint = stamp;
			this.paintMeters();
		}
		this.paintVu();

		if (this.state === 'playing' && !this.fade) {
			var dur = el.duration;
			if (isFinite(dur) && dur > 0 && this.xfade > 0) {
				var fade = Math.min(this.xfade, dur * 0.45);
				var remain = dur - el.currentTime;
				if (remain <= fade && remain > 0.2) {
					this.advance('timer', remain);
				}
			}
		}
	};

	Radio.prototype.applyGains = function () {
		var a = this.decks[this.active];
		var b = this.decks[1 - this.active];
		if (!a || !b) {
			return;
		}

		var duck = this.duck;
		var gainOut = 1;
		var gainIn = 0;

		if (this.fade) {
			var t = clamp((now() - this.fade.start) / this.fade.dur, 0, 1);
			var e = smooth(t);
			gainOut = Math.cos(e * Math.PI / 2);
			gainIn = Math.sin(e * Math.PI / 2);

			if (CONF.tempo && this.fade.dur > 2500) {
				b.playbackRate = clamp(0.962 + 0.038 * e, 0.9, 1);
			}

			if (t >= 1) {
				this.finishFade();
				return;
			}
		} else {
			var soft = (now() - this.softStart) / 420;
			if (soft < 1) {
				gainOut = smooth(soft);
			}
		}

		a.volume = clamp(gainOut * duck, 0, 1);
		if (b.getAttribute('src')) {
			b.volume = clamp(gainIn * duck, 0, 1);
		}
	};

	/* ---- خطاها ---- */

	Radio.prototype.onDeckError = function (index) {
		var track = this.queue[this.cursor];

		if (index === this.active && track) {
			this.bad[String(track.id)] = true;
			this.toast('این فایل پخش نشد؛ آهنگ بعدی می‌آید.');
		}

		if (this.graph && !this.forcedPlain) {
			this.destroyGraph();
			return;
		}

		if (index === this.active && this.state !== 'idle') {
			var self = this;
			window.setTimeout(function () {
				self.advance('error');
			}, 350);
		}
	};

	/* ---- معرفیِ صوتیِ ایستگاه ---- */

	Radio.prototype.maybeAnnounce = function (track, first) {
		var st = CONF.station || {};
		if (!st.on) {
			return;
		}
		var every = Math.max(1, Number(st.every) || 3);
		if (!first && this.trackCount % every !== 0) {
			return;
		}

		var text = st.text || '';
		var next = this.queue[this.cursor + 1] || null;
		if (!first && st.names && st.next && this.trackCount % (every * 2) !== 0) {
			text = st.next;
		}
		if (!text) {
			return;
		}

		var self = this;
		window.setTimeout(function () {
			self.speak(text, track, next);
		}, first ? 900 : 700);
	};

	Radio.prototype.fillText = function (text, track, next) {
		var title = (track && track.title) || (next && next.title) || '';
		var artist = (track && track.artist) || (next && next.artist) || '';
		return String(text || '')
			.replace(/\{site\}/g, SITE)
			.replace(/\{title\}/g, title)
			.replace(/\{artist\}/g, artist)
			.replace(/\{genre\}/g, this.termName || '');
	};

	Radio.prototype.speak = function (text, track, next) {
		var self = this;
		var st = CONF.station || {};
		var line = this.fillText(text, track, next);
		if (!line) {
			return;
		}

		if (st.file) {
			this.playVoiceFile(st.file, line);
			return;
		}

		if (!window.speechSynthesis || !window.SpeechSynthesisUtterance) {
			return;
		}

		var utter = new window.SpeechSynthesisUtterance(line);
		utter.lang = 'fa-IR';
		utter.rate = 0.98;
		utter.pitch = 1;

		try {
			var voices = window.speechSynthesis.getVoices() || [];
			var fa = voices.filter(function (v) {
				return v && String(v.lang || '').toLowerCase().indexOf('fa') === 0;
			});
			if (fa.length) {
				utter.voice = fa[0];
			}
		} catch (e) {}

		utter.onstart = function () {
			self.duckTo(CONF.duck > 0 ? CONF.duck : 0.35);
		};
		utter.onend = function () {
			self.unduck();
		};
		utter.onerror = function () {
			self.unduck();
		};

		try {
			window.speechSynthesis.cancel();
			window.speechSynthesis.speak(utter);
			this.duckTo(CONF.duck > 0 ? CONF.duck : 0.35);
			this.duckGuard(20000);
		} catch (e) {
			this.unduck();
		}
	};

	Radio.prototype.playVoiceFile = function (src, fallbackText) {
		var self = this;

		if (!this.voice) {
			this.voice = new window.Audio();
			this.voice.preload = 'auto';
			if (this.refs.audio) {
				this.refs.audio.appendChild(this.voice);
			}
			allAudio.push(this.voice);
		}

		var voice = this.voice;
		voice.onended = function () {
			self.unduck();
		};
		voice.onerror = function () {
			self.unduck();
			self.speak(fallbackText, null, null);
		};

		try {
			voice.src = src;
			voice.volume = 1;
			voice.currentTime = 0;
			var p = voice.play();
			if (p && typeof p.catch === 'function') {
				p.catch(function () {
					self.unduck();
				});
			}
			this.duckTo(CONF.duck > 0 ? CONF.duck : 0.35);
			this.duckGuard(20000);
		} catch (e) {
			this.unduck();
		}
	};

	Radio.prototype.duckTo = function (value) {
		this.duck = clamp(value, 0.02, 1);
		this.root.classList.add('is-talking');
	};

	Radio.prototype.unduck = function () {
		this.duck = 1;
		this.root.classList.remove('is-talking');
		window.clearTimeout(this.duckTimer);
	};

	Radio.prototype.duckGuard = function (ms) {
		var self = this;
		window.clearTimeout(this.duckTimer);
		this.duckTimer = window.setTimeout(function () {
			self.unduck();
		}, ms);
	};

	/* ---- نقاشی ---- */

	Radio.prototype.paintAll = function () {
		this.paintPrefs();
		this.paintQueue();
		this.paintMine();
		this.paintState();
		this.paintMeters();

		var first = this.queue[0];
		if (first) {
			this.setNow(first, true);
		}
	};

	Radio.prototype.paintPrefs = function () {
		var refs = this.refs;
		if (refs.xfade) {
			refs.xfade.value = String(this.xfade);
		}
		if (refs.xfadeVal) {
			refs.xfadeVal.textContent = faNum(this.xfade) + ' ثانیه';
		}
		each(this.root.querySelectorAll('[data-radio-power]'), function (btn) {
			var on = btn.getAttribute('data-radio-power') === this.power;
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		}, this);
	};

	Radio.prototype.paintState = function () {
		var refs = this.refs;
		var map = {
			idle: 'آمادهٔ پخش',
			playing: 'در حال پخش',
			paused: 'متوقف',
			fading: 'در حال میکس'
		};
		if (refs.state) {
			refs.state.textContent = map[this.state] || map.idle;
		}
		if (refs.startText) {
			refs.startText.textContent = (this.state === 'playing' || this.state === 'fading') ? 'توقف' : (L.play || 'پخش زنده');
		}
		this.root.classList.toggle('is-playing', this.state === 'playing' || this.state === 'fading');
	};

	Radio.prototype.paintMeters = function () {
		var refs = this.refs;
		if (refs.session) {
			refs.session.textContent = clock(this.sessionSeconds());
		}
		if (refs.mixed) {
			refs.mixed.textContent = faNum(this.mixed);
		}
		if (refs.queued) {
			refs.queued.textContent = faNum(Math.max(0, this.queue.length - this.cursor - 1));
		}
	};

	Radio.prototype.setNow = function (track, silent) {
		var refs = this.refs;
		var self = this;
		if (!track) {
			return;
		}
		if (refs.cover && track.cover) {
			refs.cover.src = track.cover;
		}
		if (refs.title) {
			refs.title.textContent = track.title || '';
		}
		if (refs.artist) {
			refs.artist.textContent = track.artist || '';
		}
		if (refs.link && track.link) {
			refs.link.href = track.link;
		}
		if (!silent) {
			this.root.classList.add('is-switching');
			window.setTimeout(function () {
				self.root.classList.remove('is-switching');
			}, 340);
		}
	};

	Radio.prototype.paintTime = function (el) {
		var refs = this.refs;
		var dur = isFinite(el.duration) ? el.duration : 0;
		var pos = el.currentTime || 0;
		var second = Math.floor(pos);

		if (refs.time) {
			refs.time.textContent = clock(pos) + ' / ' + clock(dur);
		}
		if (refs.rail) {
			refs.rail.style.width = dur ? ((pos / dur) * 100) + '%' : '0%';
		}
		if (refs.buffer && second !== this.lastSecond) {
			this.lastSecond = second;
			var buffered = 0;
			try {
				if (el.buffered && el.buffered.length && dur) {
					buffered = (el.buffered.end(el.buffered.length - 1) / dur) * 100;
				}
			} catch (e) {}
			refs.buffer.style.width = clamp(buffered, 0, 100) + '%';
		}
		if (refs.seek && !this.seeking) {
			refs.seek.value = String(dur ? Math.round((pos / dur) * 1000) : 0);
		}
	};

	Radio.prototype.paintVu = function () {
		var bars = this.refs.vu;
		if (!bars || !bars.length || !this.graph) {
			return;
		}
		var analyser = this.graph.analyser;
		var data = this.graph.data;
		analyser.getByteFrequencyData(data);

		each(bars, function (bar, i) {
			var idx = Math.floor((i / bars.length) * data.length * 0.72);
			var v = data[idx] || 0;
			bar.style.height = clamp(8 + (v / 255) * 92, 8, 100) + '%';
		});
	};

	Radio.prototype.rowNode = function (track, isCurrent, index) {
		var li = document.createElement('li');
		li.className = 'ty-radio__row' + (isCurrent ? ' is-current' : '');
		li.setAttribute('data-radio-id', String(track.id));

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'ty-radio__rowplay';
		btn.setAttribute('data-radio-play', String(track.id));
		btn.setAttribute('aria-label', 'پخش ' + track.title);
		btn.innerHTML = '<i class="icofont-play-alt-1" aria-hidden="true"></i>';

		var img = document.createElement('img');
		img.className = 'ty-radio__rowart';
		img.src = track.cover || '';
		img.width = 44;
		img.height = 44;
		img.alt = '';
		img.loading = 'lazy';

		var meta = document.createElement('span');
		meta.className = 'ty-radio__rowmeta';
		var strong = document.createElement('strong');
		strong.textContent = track.title;
		var em = document.createElement('em');
		em.textContent = track.artist;
		meta.appendChild(strong);
		meta.appendChild(em);

		var num = document.createElement('span');
		num.className = 'ty-radio__rownum';
		num.textContent = isCurrent ? 'پخش' : faNum(index);

		li.appendChild(btn);
		li.appendChild(img);
		li.appendChild(meta);
		li.appendChild(num);

		return li;
	};

	Radio.prototype.paintQueue = function () {
		var refs = this.refs;
		var list = refs.queue;
		if (!list) {
			return;
		}

		var upcoming = this.queue.slice(this.cursor + 1, this.cursor + 13);
		var current = this.cursor >= 0 ? this.queue[this.cursor] : null;

		list.innerHTML = '';
		if (current) {
			list.appendChild(this.rowNode(current, true, 0));
		}
		upcoming.forEach(function (track, i) {
			list.appendChild(this.rowNode(track, false, i + 1));
		}, this);

		if (refs.empty) {
			refs.empty.hidden = !!(current || upcoming.length);
		}
		if (refs.queueMeta) {
			refs.queueMeta.textContent = upcoming.length
				? faNum(upcoming.length) + ' آهنگ در نوبت'
				: (current ? 'این آخرین آهنگِ صف است' : 'صف خالی است');
		}
		this.paintMeters();
	};

	Radio.prototype.paintMine = function () {
		var refs = this.refs;
		var list = mineList();
		each(refs.mineCount, function (node) {
			node.textContent = faNum(list.length);
		});
		if (refs.mineList) {
			refs.mineList.innerHTML = '';
			list.forEach(function (track) {
				var li = document.createElement('li');
				var span = document.createElement('span');
				span.textContent = track.title + (track.artist ? ' — ' + track.artist : '');
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.setAttribute('data-radio-mine-remove', String(track.id));
				btn.setAttribute('aria-label', 'حذف ' + track.title + ' از فهرست من');
				btn.innerHTML = '&times;';
				li.appendChild(span);
				li.appendChild(btn);
				refs.mineList.appendChild(li);
			});
		}
		if (refs.mineEmpty) {
			refs.mineEmpty.hidden = list.length > 0;
		}
	};

	Radio.prototype.toast = function (text) {
		var node = this.refs.toast;
		if (!node) {
			return;
		}
		node.textContent = text;
		node.hidden = false;
		window.clearTimeout(this.toastTimer);
		this.toastTimer = window.setTimeout(function () {
			node.hidden = true;
		}, 2800);
	};

	Radio.prototype.updateMediaSession = function (track) {
		if (!('mediaSession' in navigator) || !track) {
			return;
		}
		try {
			if (window.MediaMetadata) {
				navigator.mediaSession.metadata = new window.MediaMetadata({
					title: track.title,
					artist: track.artist || SITE,
					album: 'رادیو ' + SITE,
					artwork: track.cover ? [{ src: track.cover, sizes: '320x320', type: 'image/jpeg' }] : []
				});
			}
			var self = this;
			var handlers = {
				play: function () { self.resume(); },
				pause: function () { self.pause(); },
				previoustrack: function () { self.prev(); },
				nexttrack: function () { self.advance('user'); },
				stop: function () { self.stop(); }
			};
			Object.keys(handlers).forEach(function (key) {
				try {
					navigator.mediaSession.setActionHandler(key, handlers[key]);
				} catch (e) {}
			});
		} catch (e) {}
	};

	/* ---- کنترل‌ها ---- */

	Radio.prototype.prev = function () {
		if (!this.history.length) {
			return;
		}
		var back = this.history.length > 1 ? this.history[this.history.length - 2] : this.history[this.history.length - 1];
		var idx = -1;

		this.queue.forEach(function (t, i) {
			if (String(t.id) === String(back.id)) {
				idx = i;
			}
		});

		if (idx === -1) {
			this.queue.splice(this.cursor + 1, 0, back);
			idx = this.cursor + 1;
		}

		this.cursor = idx;
		this.crossTo(back, 0.7);
	};

	Radio.prototype.seek = function (ratio) {
		var el = this.current();
		if (!el || !isFinite(el.duration)) {
			return;
		}
		el.currentTime = clamp(ratio, 0, 1) * el.duration;
	};

	Radio.prototype.setXfade = function (value) {
		this.xfade = Math.round(clamp(value, 0, 16));
		this.power = this.xfade >= 11 ? 'club' : (this.xfade <= 3 ? 'calm' : 'warm');
		this.savePrefs();
		this.paintPrefs();
	};

	Radio.prototype.setPower = function (power) {
		this.power = power;
		this.xfade = power === 'calm' ? 3 : (power === 'club' ? 12 : 6);
		this.savePrefs();
		this.paintPrefs();
		this.toast('شدت میکس: ' + (power === 'calm' ? 'آرام' : (power === 'club' ? 'باشگاهی' : 'دی‌جی')));
	};

	Radio.prototype.setMode = function (mode) {
		var self = this;
		this.mode = mode;
		each(this.root.querySelectorAll('[data-radio-source]'), function (btn) {
			var on = btn.getAttribute('data-radio-source') === mode;
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});

		this.queue = [];
		this.cursor = -1;
		this.paintQueue();

		this.ensureQueue().then(function (ok) {
			if (!ok) {
				self.toast('منبع تازه‌ای پیدا نشد.');
				return;
			}
			var track = self.nextTrack();
			if (track) {
				self.play(track);
			}
		});
	};

	Radio.prototype.exportSession = function () {
		var lines = ['رادیو ' + SITE, 'فهرست جلسهٔ میکس', ''];
		var list = this.history.length ? this.history : this.queue;
		list.forEach(function (track, i) {
			lines.push((i + 1) + '. ' + track.title + (track.artist ? ' — ' + track.artist : '') + (track.link ? '\n   ' + track.link : ''));
		});
		lines.push('', 'جلسه: ' + clock(this.sessionSeconds()));

		var text = lines.join('\n');
		var url = '';
		try {
			if (window.Blob && window.URL && URL.createObjectURL) {
				url = URL.createObjectURL(new Blob([text], { type: 'text/plain;charset=utf-8' }));
			}
		} catch (e) {
			url = '';
		}
		if (!url) {
			/* مرورگرهای خیلی قدیمی یا محیطِ محدود: همان متن به‌صورت data URI */
			url = 'data:text/plain;charset=utf-8,' + encodeURIComponent(text);
		}

		var a = document.createElement('a');
		a.href = url;
		a.download = 'taranehyar-radio.txt';
		a.rel = 'noopener';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		if (0 === url.indexOf('blob:')) {
			window.setTimeout(function () {
				try {
					URL.revokeObjectURL(url);
				} catch (e) {}
			}, 1500);
		}
	};

	Radio.prototype.addToQueue = function (track) {
		var t = normalize(track);
		if (!t) {
			return false;
		}
		var exists = this.queue.some(function (item) {
			return String(item.id) === String(t.id);
		});
		if (exists) {
			return false;
		}
		this.queue.splice(this.cursor + 1, 0, t);
		this.paintQueue();
		return true;
	};

	/* ---- رویدادها ---- */

	Radio.prototype.bind = function () {
		var self = this;
		var root = this.root;

		root.addEventListener('click', function (event) {
			var target = event.target;
			var closest = function (sel) {
				return target && target.closest ? target.closest(sel) : null;
			};
			var btn;

			if (closest('[data-radio-toggle]')) {
				event.preventDefault();
				self.toggle();
				return;
			}
			if (closest('[data-radio-next]')) {
				event.preventDefault();
				self.advance('user');
				return;
			}
			if (closest('[data-radio-prev]')) {
				event.preventDefault();
				self.prev();
				return;
			}
			if (closest('[data-radio-shuffle]')) {
				event.preventDefault();
				self.mode = 'random';
				self.queue = [];
				self.cursor = -1;
				self.paintQueue();
				self.ensureQueue().then(function (ok) {
					if (ok) {
						self.play();
					}
					self.toast(ok ? 'صف با ترتیبِ تصادفی تازه شد.' : 'الان نشد؛ دوباره بزنید.');
				});
				return;
			}
			if ((btn = closest('[data-radio-play]'))) {
				var id = btn.getAttribute('data-radio-play');
				var track = null;
				self.queue.forEach(function (t) {
					if (String(t.id) === String(id)) {
						track = t;
					}
				});
				if (track) {
					self.play(track);
				}
				return;
			}
			if ((btn = closest('[data-radio-power]'))) {
				self.setPower(btn.getAttribute('data-radio-power'));
				return;
			}
			if ((btn = closest('[data-radio-source]'))) {
				self.setMode(btn.getAttribute('data-radio-source'));
				return;
			}
			if (closest('[data-radio-export]')) {
				event.preventDefault();
				self.exportSession();
				return;
			}
			if (closest('[data-radio-mine-clear]')) {
				mineSave([]);
				self.paintMine();
				self.toast('فهرست من پاک شد.');
				return;
			}
			if ((btn = closest('[data-radio-mine-remove]'))) {
				mineRemove(btn.getAttribute('data-radio-mine-remove'));
				self.paintMine();
				return;
			}
			if (closest('[data-radio-mine]')) {
				var panel = self.refs.minePanel;
				if (panel) {
					var willOpen = panel.hidden;
					panel.hidden = !willOpen;
					if (self.refs.mineBtn) {
						self.refs.mineBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
					}
					self.paintMine();
				}
				return;
			}
		});

		if (this.refs.seek) {
			this.refs.seek.addEventListener('input', function () {
				self.seeking = true;
				self.seek(Number(this.value) / 1000);
			});
			this.refs.seek.addEventListener('change', function () {
				self.seeking = false;
			});
			this.refs.seek.addEventListener('pointerup', function () {
				self.seeking = false;
			});
		}

		if (this.refs.xfade) {
			this.refs.xfade.addEventListener('input', function () {
				self.setXfade(this.value);
			});
		}

		root.addEventListener('keydown', function (event) {
			/* روی کنترل‌های خودشان دست نگذارید: Space روی یک دکمه باید همان
			   دکمه را بزند، نه اینکه پخش را دو بار عوض کند. */
			var target = event.target;
			var busy = target && (
				target.isContentEditable ||
				/^(input|textarea|select|button|a|summary)$/i.test( target.tagName || '' ) ||
				( target.closest && target.closest( '[role="button"], [contenteditable="true"]' ) )
			);
			if ( busy ) {
				return;
			}
			if (event.key === ' ' || event.key === 'Spacebar') {
				event.preventDefault();
				self.toggle();
			} else if (event.key === 'ArrowLeft') {
				self.advance('user');
			} else if (event.key === 'ArrowRight') {
				self.prev();
			}
		});
	};

	/* ---- توقفِ بقیه ---- */

	function pauseOthers(me) {
		instances.forEach(function (other) {
			if (other !== me) {
				other.pause();
			}
		});

		each(document.querySelectorAll('audio'), function (el) {
			if (allAudio.indexOf(el) !== -1) {
				return;
			}
			try {
				el.pause();
			} catch (e) {}
		});
	}

	/* =====================================================================
	 * ۴) راه‌اندازیِ صفحه
	 * ================================================================== */

	function boot() {
		each(document.querySelectorAll('[data-tara-radio]'), function (root) {
			if (root.getAttribute('data-radio-ready') === '1') {
				return;
			}
			root.setAttribute('data-radio-ready', '1');
			try {
				new Radio(root).init();
			} catch (e) {}
		});
	}

	/* اگر صدای دیگری در صفحه پخش شود (پلیرِ پست یا ویدیو)، رادیو کنار
	   می‌رود تا دو صدا روی هم نیفتند. */
	if (document.addEventListener) {
		document.addEventListener('play', function (event) {
			var el = event.target;
			if (!el || el.tagName !== 'AUDIO' || allAudio.indexOf(el) !== -1) {
				return;
			}
			instances.forEach(function (radio) {
				if (radio.state === 'playing' || radio.state === 'fading') {
					radio.pause();
				}
			});
		}, true);
	}

	/* دکمهٔ «افزودن به رادیو» در هر جای صفحه */
	document.addEventListener('click', function (event) {
		var btn = event.target && event.target.closest ? event.target.closest('[data-tara-radio-add]') : null;
		if (!btn) {
			return;
		}
		event.preventDefault();

		var data = parseJSON(btn.getAttribute('data-radio-track') || '');
		if (!data) {
			return;
		}

		var result = mineAdd(data);
		btn.classList.add('is-added');
		var label = btn.querySelector('.ty-radio-add__txt');
		if (label) {
			label.textContent = 'در فهرست من';
		}

		instances.forEach(function (radio) {
			if (result.added) {
				radio.addToQueue(result.track);
			}
			radio.paintMine();
		});

		toastGlobal(result.added ? (L.added || 'به رادیو اضافه شد.') : (L.inMine || 'از قبل در فهرست شما بود.'));
	});

	/* ---- دکمه‌های سربرگِ آرشیو (پخشِ میکسِ همین دسته / کپیِ نشانی) ---- */
	document.addEventListener('click', function (event) {
		var target = event.target;

		var playBtn = target && target.closest ? target.closest('[data-ty-taxplay]') : null;
		if (playBtn) {
			event.preventDefault();
			var radio = instances[0] || (document.querySelector('[data-tara-radio]') ? boot() || instances[0] : null);
			var root = document.querySelector('[data-tara-radio]');
			if (!radio || !root) {
				toastGlobal('رادیو در این صفحه خاموش است.');
				return;
			}
			try {
				root.scrollIntoView({ behavior: 'smooth', block: 'center' });
			} catch (e) {}
			radio.play();
			playBtn.classList.add('is-active');
			window.setTimeout(function () {
				playBtn.classList.remove('is-active');
			}, 1500);
			return;
		}

		var copyBtn = target && target.closest ? target.closest('[data-ty-taxcopy]') : null;
		if (copyBtn) {
			event.preventDefault();
			var url = copyBtn.getAttribute('data-url') || window.location.href;
			var done = function () {
				toastGlobal('نشانی کپی شد.');
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(done, function () {
					toastGlobal('کپی نشد؛ نشانی را از نوار مرورگر بردارید.');
				});
			} else {
				toastGlobal(url);
			}
		}
	});

	function start() {
		boot();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}

	window.TaraRadioEngine = { instances: instances, boot: boot };
})();
