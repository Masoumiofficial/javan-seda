/**
 * ترانه یار — موتور پخش «رادیو» + نوار چسبان + کادر فهرست + ویدیو
 * ==========================================================================
 * چرا از نو نوشته شد؟
 *   ۱) نسخهٔ قبل برای هر کارت یک <audio> می‌ساخت و دکمه‌ها را تک‌تک bind می‌کرد؛
 *      اگر مارکاپ جابه‌جا می‌شد (کش صفحه، افزونهٔ بهینه‌ساز، تبِ پویا) دکمه‌ها
 *      مرده می‌ماندند و کاربر می‌گفت «پلیر کار نمی‌کند».
 *   ۲) فهرست پخش داخل خود کارت بود و صفحهٔ اصلی را بلند می‌کرد.
 *
 * معماری تازه:
 *   • یک موتور و یک <audio> برای تمام صفحه.
 *   • همهٔ کلیک‌ها با delegation روی document گرفته می‌شوند ⇒ هر المانی که
 *     بعداً به صفحه اضافه شود هم کار می‌کند.
 *   • کارت، نوار چسبان و کادر فهرست فقط «نمایش» هستند و از یک وضعیت مشترک
 *     (same state) تغذیه می‌شوند ⇒ همیشه با هم هماهنگ‌اند.
 *   • صف پخش از `[data-tara-queue]` هر فهرست خوانده می‌شود، پس هر بخش صفحه
 *     (آهنگ‌ها، آلبوم‌ها، آرشیو) صف خودش را دارد.
 *
 * بدون jQuery و بدون وابستگی.
 *
 * @package Taranehyar
 */
(function () {
	'use strict';

	var FA = '۰۱۲۳۴۵۶۷۸۹';

	function toFa(value) {
		return String(value).replace(/[0-9]/g, function (d) {
			return FA.charAt(Number(d));
		});
	}

	function fmt(seconds) {
		if (!isFinite(seconds) || seconds < 0) {
			return '۰۰:۰۰';
		}
		var m = Math.floor(seconds / 60);
		var s = Math.floor(seconds % 60);
		return toFa((m < 10 ? '0' : '') + m) + ':' + toFa((s < 10 ? '0' : '') + s);
	}

	function clamp(n, min, max) {
		return Math.min(max, Math.max(min, n));
	}

	function readJSON(node, attr) {
		if (!node) {
			return null;
		}
		var raw = node.getAttribute(attr);
		if (!raw) {
			return null;
		}
		try {
			return JSON.parse(raw);
		} catch (error) {
			return null;
		}
	}

	/* ==================================================================
	 * ۱) موتور پخش
	 * ================================================================== */
	var Engine = (function () {
		var audio = null;
		var queue = [];
		var index = 0;
		var owner = null;      /* کارتی که صف را شروع کرده */
		var listeners = [];
		var started = false;
		var lastVolume = 1;

		function emit(type, data) {
			for (var i = 0; i < listeners.length; i++) {
				try {
					listeners[i](type, data || {});
				} catch (error) {
					/* یک شنوندهٔ خراب نباید بقیه را بخواباند */
				}
			}
		}

		function ensure() {
			if (audio) {
				return audio;
			}

			audio = document.querySelector('audio[data-tara-audio]');

			if (!audio) {
				audio = document.createElement('audio');
				audio.setAttribute('data-tara-audio', '');
				audio.setAttribute('preload', 'none');
				audio.setAttribute('playsinline', '');
				audio.className = 'ty-player__audio';
				document.body.appendChild(audio);
			}

			audio.addEventListener('loadstart', function () {
				emit('loading');
			});
			audio.addEventListener('canplay', function () {
				emit('ready');
			});
			audio.addEventListener('playing', function () {
				started = true;
				emit('playing');
				syncMediaSession();
			});
			audio.addEventListener('pause', function () {
				emit('paused');
			});
			audio.addEventListener('timeupdate', function () {
				emit('time', { position: audio.currentTime || 0, duration: audio.duration || 0 });
			});
			audio.addEventListener('durationchange', function () {
				emit('time', { position: audio.currentTime || 0, duration: audio.duration || 0 });
			});
			audio.addEventListener('progress', function () {
				var buffered = 0;
				if (audio.buffered && audio.buffered.length && audio.duration) {
					buffered = (audio.buffered.end(audio.buffered.length - 1) / audio.duration) * 100;
				}
				emit('buffer', { percent: buffered });
			});
			audio.addEventListener('ended', function () {
				if (queue.length > 1) {
					next();
				} else {
					emit('paused');
				}
			});
			audio.addEventListener('error', function () {
				emit('error', { src: audio && audio.src ? audio.src : '' });
			});

			return audio;
		}

		function track() {
			return queue[index] || null;
		}

		function load(list, start, ownerNode, autoplay) {
			queue = (list || []).slice();
			if (!queue.length) {
				return;
			}

			index = clamp(Number(start) || 0, 0, queue.length - 1);
			owner = ownerNode || null;

			var el = ensure();
			var item = track();

			el.src = item.src;
			el.load();

			emit('load', { track: item, index: index, owner: owner, queue: queue });

			if (autoplay) {
				play();
			}
		}

		function play() {
			if (!queue.length) {
				return;
			}

			var el = ensure();
			if (!el.src) {
				load(queue, index, owner, false);
			}

			var promise = el.play();
			if (promise && typeof promise.catch === 'function') {
				promise.catch(function () {
					emit('blocked');
				});
			}
		}

		function pause() {
			if (audio) {
				audio.pause();
			}
		}

		function toggle() {
			if (!audio || audio.paused) {
				play();
			} else {
				pause();
			}
		}

		function next() {
			if (!queue.length) {
				return;
			}
			index = (index + 1) % queue.length;
			load(queue, index, owner, true);
		}

		function prev() {
			if (!queue.length) {
				return;
			}
			/* رفتار استاندارد: اگر بیش از ۳ ثانیه گذشته، برگرد به اول همین قطعه */
			if (audio && audio.currentTime > 3) {
				audio.currentTime = 0;
				return;
			}
			index = (index - 1 + queue.length) % queue.length;
			load(queue, index, owner, true);
		}

		function seekPercent(percent) {
			var el = ensure();
			if (!el.duration) {
				return;
			}
			el.currentTime = (clamp(percent, 0, 100) / 100) * el.duration;
		}

		function volume(value) {
			var el = ensure();
			el.volume = clamp(value, 0, 1);
			el.muted = el.volume === 0;
			if (el.volume > 0) {
				lastVolume = el.volume;
			}
			emit('volume', { volume: el.volume, muted: el.muted });
		}

		function mute() {
			var el = ensure();
			el.muted = !el.muted;
			emit('volume', { volume: el.volume, muted: el.muted });
		}

		function stop() {
			if (audio) {
				audio.pause();
				audio.removeAttribute('src');
				audio.load();
			}
			queue = [];
			index = 0;
			owner = null;
			emit('stop');
		}

		function syncMediaSession() {
			if (!('mediaSession' in navigator) || !window.MediaMetadata) {
				return;
			}
			var item = track();
			if (!item) {
				return;
			}
			try {
				navigator.mediaSession.metadata = new window.MediaMetadata({
					title: item.title || '',
					artist: item.artist || '',
					album: document.title,
					artwork: item.cover ? [{ src: item.cover, sizes: '256x256', type: 'image/jpeg' }] : []
				});
				if (navigator.mediaSession.setActionHandler) {
					navigator.mediaSession.setActionHandler('play', play);
					navigator.mediaSession.setActionHandler('pause', pause);
					navigator.mediaSession.setActionHandler('previoustrack', prev);
					navigator.mediaSession.setActionHandler('nexttrack', next);
				}
			} catch (error) {
				/* مرورگرهای قدیمی */
			}
		}

		return {
			subscribe: function (fn) {
				listeners.push(fn);
			},
			load: load,
			play: play,
			pause: pause,
			toggle: toggle,
			next: next,
			prev: prev,
			seekPercent: seekPercent,
			volume: volume,
			mute: mute,
			stop: stop,
			on: ensure,
			state: function () {
				return {
					queue: queue,
					index: index,
					owner: owner,
					track: track(),
					playing: !!(audio && !audio.paused && started),
					audio: audio
				};
			}
		};
	})();

	/* ==================================================================
	 * ۲) کارت پلیر
	 * ================================================================== */
	function PlayerCard(root) {
		var tracks = [];

		try {
			tracks = JSON.parse((root.querySelector('.ty-player__data') || {}).textContent || '[]');
		} catch (error) {
			tracks = [];
		}

		/* v3.7.4 — اگر JSON کارت خالی/خراب بود، به‌جای «مردنِ» کل کارت، صفِ
		   اولین فهرست صفحه ([data-tara-queue]) را برمی‌داریم تا دکمهٔ «فهرست
		   پخش» و پخشِ همان بخش همیشه کار کند. */
		if (!tracks || !tracks.length) {
			var holder = document.querySelector('[data-tara-queue]');
			tracks = readJSON(holder, 'data-tara-queue') || [];
			if (!tracks.length) {
				return;
			}
			root.__taraTracks = tracks;
		}

		var refs = {
			art: root.querySelector('[data-tara-art]'),
			title: root.querySelector('[data-tara-title]'),
			artist: root.querySelector('[data-tara-artist]'),
			link: root.querySelector('[data-tara-title-link]'),
			time: root.querySelector('[data-tara-time]'),
			fill: root.querySelector('[data-tara-fill]'),
			buffer: root.querySelector('[data-tara-buffer]'),
			seek: root.querySelector('[data-tara-seek]'),
			volume: root.querySelector('[data-tara-volume]'),
			count: root.querySelector('[data-tara-count]'),
			eyebrow: root.querySelector('.ty-player__eyebrow'),
			mute: root.querySelector('[data-tara-mute]'),
			error: root.querySelector('[data-tara-error]')
		};

		/* v3.7.8 — رنگ کاور روی پلیر */
		watchCoverTint(root, refs.art);

		var isOwner = function () {
			return Engine.state().owner === root;
		};

		/* v3.7.8 — «کاور/لینک آهنگ دیگر روی کارت»:
		   پیش از این اگر قطعهٔ تازه کاور نداشت، srcِ قبلی روی <img> می‌ماند و
		   تصویر آهنگ پیشین نمایش داده می‌شد. حالا کاورِ اصلی همین کارت
		   نگه داشته می‌شود و در نبود کاور، همان بازگردانده می‌شود؛ لینک هم
		   همیشه از قطعهٔ جاری گرفته می‌شود. جست‌وجو هم فقط داخل خودِ کارت
		   انجام می‌شود تا کارت‌های دیگر دست‌نخورده بمانند. */
		var baseCover = refs.art ? (refs.art.getAttribute('src') || '') : '';

		function paintTrack(item) {
			if (!item) {
				return;
			}
			if (refs.title) {
				refs.title.textContent = item.title || '';
			}
			if (refs.artist) {
				refs.artist.textContent = item.artist || '';
			}
			if (refs.art) {
				var cover = item.cover || baseCover;
				if (cover) {
					refs.art.setAttribute('src', cover);
					tintFromCover(root, refs.art);
				}
			}
			var links = root.querySelectorAll('[data-tara-title-link]');
			for (var i = 0; i < links.length; i++) {
				if (item.link) {
					links[i].setAttribute('href', item.link);
				}
			}
		}

		function paintProgress(position, duration) {
			var percent = duration > 0 ? (position / duration) * 100 : 0;
			if (refs.fill) {
				refs.fill.style.width = percent + '%';
			}
			if (refs.time) {
				refs.time.textContent = fmt(position) + ' / ' + fmt(duration);
			}
			if (refs.seek && duration > 0 && document.activeElement !== refs.seek) {
				refs.seek.value = String(percent);
			}
		}

		Engine.subscribe(function (type, data) {
			if (!isOwner()) {
				root.classList.remove('is-playing', 'is-loading');
				return;
			}

			if (type === 'load') {
				root.__taraIndex = typeof data.index === 'number' ? data.index : 0;
				paintTrack(data.track);
				if (refs.count) {
					var total = (Engine.state().queue || []).length;
					if (total > 0) {
						refs.count.textContent = toFa(total) + ' قطعه';
					}
				}
				root.classList.remove('is-error');
				if (refs.error) {
					refs.error.hidden = true;
				}
			}

			if (type === 'loading') {
				root.classList.add('is-loading');
			}

			if (type === 'ready' || type === 'playing') {
				root.classList.remove('is-loading');
			}

			root.classList.toggle('is-playing', type === 'playing' ? true : (type === 'paused' ? false : root.classList.contains('is-playing')));

			if (type === 'time') {
				paintProgress(data.position, data.duration);
			}

			if (type === 'buffer' && refs.buffer) {
				refs.buffer.style.width = (data.percent || 0) + '%';
			}

			if (type === 'volume') {
				root.classList.toggle('is-muted', !!data.muted);
				if (refs.mute) {
					refs.mute.setAttribute('aria-pressed', data.muted ? 'true' : 'false');
				}
				if (refs.volume) {
					refs.volume.value = String(data.muted ? 0 : data.volume);
				}
			}

			if (type === 'error') {
				root.classList.remove('is-loading', 'is-playing');
				root.classList.add('is-error');
				if (refs.error) {
					refs.error.hidden = false;
				}
			}
		});

		/* مقدار اولیه */
		paintTrack(tracks[0]);

		/* دکمه‌های همین کارت — با delegation، پس اگر مارکاپ دوباره ساخته شود هم کار می‌کند */
		root.addEventListener('click', function (event) {
			var button = event.target.closest ? event.target.closest('[data-tara-toggle], [data-tara-prev], [data-tara-next], [data-tara-mute]') : null;
			if (!button) {
				return;
			}

			event.preventDefault();

			/* v3.7.8 — از همان قطعه‌ای ادامه بده که روی کارت دیده می‌شود،
			   نه از قطعهٔ اول صف (باگ «کاور آهنگ دیگری بالا می‌آمد»). */
			var at = typeof root.__taraIndex === 'number' ? root.__taraIndex : 0;

			if (button.hasAttribute('data-tara-toggle')) {
				if (isOwner() && Engine.state().track) {
					Engine.toggle();
				} else {
					Engine.load(root.__taraTracks || tracks, at, root, true);
				}
				return;
			}

			if (!isOwner()) {
				Engine.load(root.__taraTracks || tracks, at, root, false);
			}

			if (button.hasAttribute('data-tara-prev')) {
				Engine.prev();
			} else if (button.hasAttribute('data-tara-next')) {
				Engine.next();
			} else if (button.hasAttribute('data-tara-mute')) {
				Engine.mute();
			}
		});

		if (refs.seek) {
			refs.seek.addEventListener('input', function () {
				if (isOwner()) {
					Engine.seekPercent(Number(refs.seek.value));
				}
			});
		}

		if (refs.volume) {
			refs.volume.addEventListener('input', function () {
				Engine.volume(Number(refs.volume.value));
			});
		}

		root.__taraTracks = tracks;
		root.__taraTitle = (root.querySelector('.ty-player__eyebrow') || {}).textContent || 'فهرست پخش';
	}

	/* ==================================================================
	 * ۳) نوار چسبان
	 * ================================================================== */
	function Dock(dock) {
		if (!dock) {
			return;
		}

		var refs = {
			title: dock.querySelector('[data-tara-dock-title]'),
			artist: dock.querySelector('[data-tara-dock-artist]'),
			art: dock.querySelector('[data-tara-dock-art]'),
			link: dock.querySelector('[data-tara-dock-link]'),
			fill: dock.querySelector('[data-tara-dock-fill]'),
			vol: dock.querySelector('[data-tara-dock-vol]'),
			mute: dock.querySelector('[data-tara-dock-mute]')
		};

		/* v3.7.7 — کنترل صدا روی همان نوار چسبان پایین صفحه */
		var lastVol = 1;

		function paintVolume(volume, muted) {
			var v = muted ? 0 : volume;
			if (typeof v !== 'number' || isNaN(v)) {
				v = 1;
			}
			if (refs.vol) {
				refs.vol.value = Math.round(v * 100);
				refs.vol.style.setProperty('--vol', Math.round(v * 100) + '%');
			}
			if (refs.mute) {
				refs.mute.classList.toggle('is-muted', v === 0);
				refs.mute.setAttribute('aria-pressed', v === 0 ? 'true' : 'false');
			}
		}

		if (refs.vol) {
			refs.vol.addEventListener('input', function () {
				var value = Number(refs.vol.value) / 100;
				if (value > 0) {
					lastVol = value;
				}
				Engine.volume(value);
			});
			refs.vol.style.setProperty('--vol', '100%');
		}

		if (refs.mute) {
			refs.mute.addEventListener('click', function (event) {
				event.stopPropagation();
				var state = Engine.state();
				if (state.audio && (state.audio.muted || state.audio.volume === 0)) {
					Engine.volume(lastVol || 0.8);
				} else {
					var current = state.audio ? state.audio.volume : lastVol;
					if (current > 0) {
						lastVol = current;
					}
					Engine.volume(0);
				}
			});
		}

		function show() {
			if (dock.hidden) {
				dock.hidden = false;
				window.requestAnimationFrame(function () {
					dock.classList.add('is-visible');
					document.body.classList.add('has-tara-dock');
				});
			}
		}

		function hide() {
			dock.classList.remove('is-visible');
			document.body.classList.remove('has-tara-dock');
			window.setTimeout(function () {
				if (!dock.classList.contains('is-visible')) {
					dock.hidden = true;
				}
			}, 260);
		}

		Engine.subscribe(function (type, data) {
			if (type === 'load' && data.track) {
				/* v3.7.5 — با یک قطعه، «فهرست پخش» بی‌معناست */
				var listBtn = dock.querySelector('[data-tara-dock-list]');
				if (listBtn) {
					listBtn.hidden = !(data.queue && data.queue.length > 1);
				}
				if (refs.title) {
					refs.title.textContent = data.track.title || '';
				}
				if (refs.artist) {
					refs.artist.textContent = data.track.artist || '';
				}
				if (refs.art && data.track.cover) {
					refs.art.setAttribute('src', data.track.cover);
				}
				if (refs.link) {
					refs.link.setAttribute('href', data.track.link || '#');
				}
				if (refs.fill) {
					refs.fill.style.width = '0%';
				}
			}

			if (type === 'playing') {
				dock.classList.add('is-playing');
				show();
			}

			if (type === 'paused') {
				dock.classList.remove('is-playing');
			}

			if (type === 'time' && refs.fill && data.duration > 0) {
				refs.fill.style.width = (data.position / data.duration) * 100 + '%';
			}

			if (type === 'volume') {
				paintVolume(data.volume, data.muted);
			}

			if (type === 'stop') {
				dock.classList.remove('is-playing');
				hide();
			}
		});

		dock.addEventListener('click', function (event) {
			if (event.target.closest && event.target.closest('[data-tara-dock-mute]')) {
				return; /* هندلر اختصاصی بالا */
			}
			var target = event.target.closest ? event.target.closest('[data-tara-dock-toggle], [data-tara-dock-prev], [data-tara-dock-next], [data-tara-dock-close], [data-tara-dock-list]') : null;
			if (!target) {
				return;
			}

			if (target.hasAttribute('data-tara-dock-toggle')) {
				Engine.toggle();
			} else if (target.hasAttribute('data-tara-dock-prev')) {
				Engine.prev();
			} else if (target.hasAttribute('data-tara-dock-next')) {
				Engine.next();
			} else if (target.hasAttribute('data-tara-dock-list')) {
				Modal.open();
			} else if (target.hasAttribute('data-tara-dock-close')) {
				Engine.stop();
			}
		});
	}

	/* ==================================================================
	 * ۴) کادر فهرست پخش
	 * ================================================================== */
	var Modal = (function () {
		var node = null;
		var listNode = null;
		var searchNode = null;
		var emptyNode = null;
		var titleNode = null;
		var countNode = null;
		var metaNode = null;
		var lastFocus = null;
		var queue = [];
		var owner = null;

		function ensureNode() {
			if (node) {
				return node;
			}
			node = document.querySelector('[data-tara-modal]');
			return node;
		}

		function rowsFor(term) {
			var state = Engine.state();
			var currentQueue = owner && owner.__taraTracks ? owner.__taraTracks : state.queue;
			var currentIndex = (owner === state.owner) ? state.index : -1;
			var out = [];
			var needle = String(term || '').trim().toLowerCase();

			for (var i = 0; i < currentQueue.length; i++) {
				var item = currentQueue[i] || {};
				var haystack = (item.title || '') + ' ' + (item.artist || '');
				if (needle && haystack.toLowerCase().indexOf(needle) === -1) {
					continue;
				}
				out.push({ index: i, item: item, current: i === currentIndex });
			}

			return { rows: out, total: currentQueue.length, currentIndex: currentIndex };
		}

		function render(term) {
			if (!listNode) {
				return;
			}

			var data = rowsFor(term);
			listNode.innerHTML = '';

			for (var i = 0; i < data.rows.length; i++) {
				var row = data.rows[i];
				var li = document.createElement('li');
				li.className = 'ty-modal__row' + (row.current ? ' is-current' : '');

				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'ty-modal__row-btn';
				btn.setAttribute('data-tara-modal-play', String(row.index));
				if (row.current) {
					btn.setAttribute('aria-current', 'true');
				}

				var num = document.createElement('span');
				num.className = 'ty-modal__row-num';
				num.innerHTML = row.current
					? '<span class="ty-icon-eq" aria-hidden="true"><i></i><i></i><i></i></span>'
					: toFa(row.index + 1);
				btn.appendChild(num);

				if (row.item.cover) {
					var cover = document.createElement('img');
					cover.className = 'ty-modal__row-cover';
					cover.src = row.item.cover;
					cover.alt = '';
					cover.loading = 'lazy';
					cover.width = 40;
					cover.height = 40;
					btn.appendChild(cover);
				} else {
					var blank = document.createElement('span');
					blank.className = 'ty-modal__row-cover ty-modal__row-cover--empty';
					blank.setAttribute('aria-hidden', 'true');
					blank.textContent = '♪';
					btn.appendChild(blank);
				}

				var body = document.createElement('span');
				body.className = 'ty-modal__row-body';
				var title = document.createElement('span');
				title.className = 'ty-modal__row-title';
				title.textContent = row.item.title || '';
				var artist = document.createElement('span');
				artist.className = 'ty-modal__row-artist';
				artist.textContent = row.item.artist || '';
				body.appendChild(title);
				body.appendChild(artist);
				btn.appendChild(body);

				var dur = document.createElement('span');
				dur.className = 'ty-modal__row-state';
				dur.textContent = row.current ? 'در حال پخش' : 'پخش';
				btn.appendChild(dur);

				li.appendChild(btn);
				listNode.appendChild(li);
			}

			if (emptyNode) {
				emptyNode.hidden = data.rows.length > 0;
			}

			if (countNode) {
				countNode.textContent = toFa(data.total) + ' قطعه';
			}

			if (metaNode) {
				metaNode.textContent = toFa(data.rows.length) + ' از ' + toFa(data.total) + ' قطعه';
			}

			var current = listNode.querySelector('.ty-modal__row.is-current');
			if (current) {
				window.setTimeout(function () {
					if (current.scrollIntoView) {
						current.scrollIntoView({ block: 'nearest' });
					}
				}, 40);
			}
		}

		function open(card) {
			if (!ensureNode()) {
				return;
			}

			owner = card || Engine.state().owner || null;
			queue = owner && owner.__taraTracks ? owner.__taraTracks : Engine.state().queue;

			/* پشتیبان: صفِ اولین فهرست صفحه (اگر کارتِ اصلی خالی بود) */
			if (!queue || !queue.length) {
				var holder = document.querySelector('[data-tara-queue]');
				queue = readJSON(holder, 'data-tara-queue') || [];
				if (holder && queue.length) {
					holder.__taraTitle = holder.getAttribute('data-tara-title') || 'فهرست پخش';
					owner = owner || holder;
				}
			}

			if (!queue || !queue.length) {
				/* هیچ صفی نیست ⇒ پیام روشن، نه سکوت */
				if (window.console && console.warn) {
					console.warn('[tara] فهرست پخش خالی است: نه JSON کارت و نه data-tara-queue.');
				}
				return;
			}

			lastFocus = document.activeElement;
			node.hidden = false;
			document.body.classList.add('has-tara-modal');
			window.requestAnimationFrame(function () {
				node.classList.add('is-open');
			});

			if (titleNode) {
				titleNode.textContent = (owner && owner.__taraTitle) ? owner.__taraTitle : 'فهرست پخش';
			}

			if (searchNode) {
				searchNode.value = '';
			}

			render('');

			var listButtons = document.querySelectorAll('[data-tara-list]');
			for (var i = 0; i < listButtons.length; i++) {
				listButtons[i].setAttribute('aria-expanded', 'true');
			}

			window.setTimeout(function () {
				if (searchNode) {
					searchNode.focus({ preventScroll: true });
				}
			}, 120);
		}

		function close() {
			if (!node || node.hidden) {
				return;
			}

			node.classList.remove('is-open');
			document.body.classList.remove('has-tara-modal');

			window.setTimeout(function () {
				if (!node.classList.contains('is-open')) {
					node.hidden = true;
				}
			}, 220);

			var listButtons = document.querySelectorAll('[data-tara-list]');
			for (var i = 0; i < listButtons.length; i++) {
				listButtons[i].setAttribute('aria-expanded', 'false');
			}

			if (lastFocus && lastFocus.focus) {
				lastFocus.focus({ preventScroll: true });
			}
		}

		function init() {
			if (!ensureNode()) {
				return;
			}

			listNode = node.querySelector('[data-tara-modal-list]');
			searchNode = node.querySelector('[data-tara-modal-search]');
			emptyNode = node.querySelector('[data-tara-modal-empty]');
			titleNode = node.querySelector('[data-tara-modal-title]');
			countNode = node.querySelector('[data-tara-modal-count]');
			metaNode = node.querySelector('[data-tara-modal-meta]');

			node.addEventListener('click', function (event) {
				var closer = event.target.closest ? event.target.closest('[data-tara-modal-close]') : null;
				if (closer) {
					close();
					return;
				}

				var playBtn = event.target.closest ? event.target.closest('[data-tara-modal-play]') : null;
				if (playBtn) {
					var index = Number(playBtn.getAttribute('data-tara-modal-play'));
					var card = owner || Engine.state().owner;
					var list = card && card.__taraTracks ? card.__taraTracks : queue;
					Engine.load(list, index, card, true);
					window.setTimeout(function () {
						render(searchNode ? searchNode.value : '');
					}, 80);
				}
			});

			if (searchNode) {
				searchNode.addEventListener('input', function () {
					render(searchNode.value);
				});
			}

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && node && !node.hidden) {
					close();
				}
			});

			/* با تغییر قطعه از هر جای دیگری، ردیف فعال کادر هم به‌روز شود */
			Engine.subscribe(function (type) {
				if (!node || node.hidden) {
					return;
				}
				if (type === 'load' || type === 'playing') {
					render(searchNode ? searchNode.value : '');
				}
			});
		}

		return { init: init, open: open, close: close, render: render };
	})();

	/* ==================================================================
	 * ۴.۴) رنگِ کاور ⇒ گرادیانتِ سبکِ پلیر (v3.7.8)
	 * ------------------------------------------------------------------
	 * رنگ غالبِ تصویر با یک کنواسِ ۲۴×۲۴ خوانده می‌شود (میانگینِ پیکسل‌های
	 * رنگی، با حذف سفید/سیاه/خاکستری) و به‌صورت دو لایهٔ شفاف روی پس‌زمینهٔ
	 * کارت می‌نشیند: لایهٔ اول پررنگ‌تر گوشهٔ بالا، لایهٔ دوم محو گوشهٔ پایین.
	 * هیچ درخواست اضافه‌ای نیست (همان تصویر کاور). اگر مرورگر اجازهٔ خواندن
	 * پیکسل نداد (تصویر از دامنهٔ دیگر)، بی‌صدا رد می‌شود و طرح پیش‌فرض
	 * می‌ماند. شدت‌ها کم‌اند تا متن همیشه خوانا بماند.
	 * ================================================================== */
	function tintFromCover(root, img) {
		if (!root || !img || !img.naturalWidth) {
			return;
		}
		try {
			var size = 24;
			var canvas = document.createElement('canvas');
			canvas.width = size;
			canvas.height = size;
			var ctx = canvas.getContext && canvas.getContext('2d');
			if (!ctx) {
				return;
			}
			ctx.drawImage(img, 0, 0, size, size);
			var data = ctx.getImageData(0, 0, size, size).data;
			var r = 0, g = 0, b = 0, n = 0;
			for (var i = 0; i < data.length; i += 4) {
				var pr = data[i], pg = data[i + 1], pb = data[i + 2], pa = data[i + 3];
				if (pa < 200) { continue; }
				var max = Math.max(pr, pg, pb);
				var min = Math.min(pr, pg, pb);
				var sat = max === 0 ? 0 : (max - min) / max;
				var lum = (pr * 0.299 + pg * 0.587 + pb * 0.114) / 255;
				if (sat < 0.18 || lum > 0.94 || lum < 0.06) { continue; }
				r += pr; g += pg; b += pb; n++;
			}
			if (!n) {
				return;
			}
			r = Math.round(r / n);
			g = Math.round(g / n);
			b = Math.round(b / n);
			root.style.setProperty('--ty-cover-1', 'rgba(' + r + ',' + g + ',' + b + ',0.16)');
			root.style.setProperty('--ty-cover-2', 'rgba(' + r + ',' + g + ',' + b + ',0.07)');
			root.classList.add('has-cover-tint');
		} catch (e) {
			/* تصویر cross-origin یا کنواس غیرفعال ⇒ همان طرح پیش‌فرض */
		}
	}

	function watchCoverTint(root, img) {
		if (!root || !img) {
			return;
		}
		if (img.complete && img.naturalWidth) {
			tintFromCover(root, img);
			return;
		}
		img.addEventListener('load', function () { tintFromCover(root, img); });
	}

	/* ==================================================================
	 * ۴.۵) پاپ‌آپ تصویر کاور (v3.7.8)
	 * ------------------------------------------------------------------
	 * هر عنصری با `data-tara-zoom` (امروز: دکمهٔ دوربین روی پلیر) نسخهٔ
	 * اصلی تصویر را در یک لایهٔ ساده نشان می‌دهد. هیچ کتابخانه‌ای اضافه
	 * نمی‌شود؛ تصویر تازه فقط هنگام باز شدن بار می‌گیرد (lazy).
	 * ================================================================== */
	var lbNode = null;
	var lbFocus = null;

	function lbKey(event) {
		if (event.key === 'Escape' || event.keyCode === 27) {
			event.stopPropagation();
			closeLightbox();
		}
	}

	function closeLightbox() {
		if (!lbNode) {
			return;
		}
		document.body.classList.remove('tara-lb-open');
		document.removeEventListener('keydown', lbKey, true);
		if (lbNode.parentNode) {
			lbNode.parentNode.removeChild(lbNode);
		}
		lbNode = null;
		if (lbFocus && typeof lbFocus.focus === 'function') {
			lbFocus.focus();
		}
	}

	function openLightbox(src) {
		if (!src) {
			return;
		}
		closeLightbox();
		lbFocus = document.activeElement;

		var box = document.createElement('div');
		box.className = 'ty-lightbox';
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-label', 'تصویر کاور');
		box.innerHTML = '<button type="button" class="ty-lightbox__close" aria-label="بستن">×</button>'
			+ '<img class="ty-lightbox__img" alt="" loading="lazy" decoding="async">'
			+ '<a class="ty-lightbox__open" target="_blank" rel="noopener">باز کردن اصل تصویر</a>';

		var img = box.querySelector('.ty-lightbox__img');
		img.src = src;
		box.querySelector('.ty-lightbox__open').setAttribute('href', src);

		box.addEventListener('click', function (event) {
			var t = event.target;
			if (t === box || (t.closest && t.closest('.ty-lightbox__close'))) {
				closeLightbox();
			}
		});

		document.body.appendChild(box);
		document.body.classList.add('tara-lb-open');
		document.addEventListener('keydown', lbKey, true);
		box.querySelector('.ty-lightbox__close').focus();
		lbNode = box;
	}

	function bindZoom() {
		document.addEventListener('click', function (event) {
			var btn = event.target && event.target.closest ? event.target.closest('[data-tara-zoom]') : null;
			if (!btn) {
				return;
			}
			event.preventDefault();
			openLightbox(btn.getAttribute('data-tara-zoom'));
		});
	}

	/* ==================================================================
	 * ۵) کلیک‌های عمومی: صف‌های صفحه، دکمهٔ فهرست، ردیف‌های آهنگ
	 * ================================================================== */
	function bindGlobalClicks() {
		document.addEventListener('click', function (event) {
			if (!event.target.closest) {
				return;
			}

			/* دکمهٔ «فهرست پخش» روی کارت یا نوار چسبان */
			var listBtn = event.target.closest('[data-tara-list]');
			if (listBtn) {
				event.preventDefault();
				var card = listBtn.closest('[data-tara-player]');
				Modal.open(card);
				return;
			}

			/* ردیف‌های آهنگ: صف از نزدیک‌ترین [data-tara-queue]، وگرنه اولین کارت */
			var playBtn = event.target.closest('[data-tara-play], [data-tara-track]');
			if (!playBtn || playBtn.closest('[data-tara-player]')) {
				return;
			}

			var index = playBtn.hasAttribute('data-tara-play') ? Number(playBtn.getAttribute('data-tara-play')) : -1;

			var holder = playBtn.closest('[data-tara-queue]');
			var queue = readJSON(holder, 'data-tara-queue');
			var owner = holder ? null : document.querySelector('[data-tara-player]');

			if (!queue || !queue.length) {
				queue = owner && owner.__taraTracks ? owner.__taraTracks : null;
			}

			if (index < 0 && playBtn.hasAttribute('data-tara-track')) {
				var want = String(playBtn.getAttribute('data-tara-track'));
				index = -1;
				for (var q = 0; queue && q < queue.length; q++) {
					if (String((queue[q] || {}).id) === want) {
						index = q;
						break;
					}
				}
			}

			/* v3.7.8 — اگر صف پیدا نشد یا شناسهٔ دکمه در آن نبود، خودِ دکمه
			   دادهٔ کافی برای پخش دارد (`data-tara-src`) ⇒ یک صف تک‌قطعه‌ای
			   ساخته می‌شود تا دکمه هرگز «بی‌اثر» نماند. این همان باگی است که
			   در صفحهٔ هنرمند، دکمهٔ پخش را در پلیر رادیو به صدا درنمی‌آورد. */
			if ((isNaN(index) || index < 0) && playBtn.hasAttribute('data-tara-src')) {
				event.preventDefault();
				var single = [{
					id: playBtn.getAttribute('data-tara-track') || '',
					title: playBtn.getAttribute('data-tara-title') || '',
					artist: playBtn.getAttribute('data-tara-artist') || '',
					src: playBtn.getAttribute('data-tara-src'),
					cover: playBtn.getAttribute('data-tara-cover') || '',
					link: playBtn.getAttribute('data-tara-link') || ''
				}];
				var singleCard = document.querySelector('[data-tara-player]');
				if (singleCard) {
					singleCard.__taraTracks = single;
				}
				Engine.load(single, 0, singleCard, true);
				return;
			}

			if (isNaN(index) || index < 0) {
				return;
			}

			if (!queue || !queue.length) {
				return;
			}

			event.preventDefault();

			var title = holder ? (holder.getAttribute('data-tara-title') || 'فهرست پخش') : '';

			if (holder) {
				holder.__taraTitle = title;
			}

			/* کارت روی صفحه «مالک» می‌شود تا کارت و نوار چسبان هم‌زمان همان
			   قطعه را نشان دهند؛ صفِ فهرست هم داخل کارت می‌نشیند. */
			var cardNode = document.querySelector('[data-tara-player]');
			if (cardNode && cardNode.__taraTracks) {
				cardNode.__taraTracks = queue;
				cardNode.__taraTitle = title || cardNode.__taraTitle;
				Engine.load(queue, index, cardNode, true);
				return;
			}

			Engine.load(queue, index, holder || owner, true);
		});
	}

	/* ==================================================================
	 * ۶) پلیر ویدیو
	 * ================================================================== */
	function Video(root) {
		var video = root.querySelector('[data-tara-video-el]');
		if (!video) {
			return;
		}

		var refs = {
			cover: root.querySelector('[data-tara-video-toggle]'),
			played: root.querySelector('[data-tara-video-played]'),
			loaded: root.querySelector('[data-tara-video-loaded]'),
			seek: root.querySelector('[data-tara-video-seek]'),
			time: root.querySelector('[data-tara-video-time]'),
			qualityLabel: root.querySelector('[data-tara-video-quality]'),
			pop: root.querySelector('[data-tara-video-pop]'),
			settings: root.querySelector('[data-tara-video-settings]')
		};

		function paint() {
			var duration = video.duration || 0;
			var percent = duration > 0 ? (video.currentTime / duration) * 100 : 0;

			if (refs.played) {
				refs.played.style.width = percent + '%';
			}
			if (refs.seek && duration > 0 && document.activeElement !== refs.seek) {
				refs.seek.value = String(percent);
			}
			if (refs.time) {
				refs.time.textContent = fmt(video.currentTime) + ' / ' + fmt(duration);
			}
			if (refs.loaded && video.buffered && video.buffered.length && duration) {
				refs.loaded.style.width = (video.buffered.end(video.buffered.length - 1) / duration) * 100 + '%';
			}
		}

		function hideCover() {
			if (refs.cover) {
				refs.cover.classList.add('is-hidden');
			}
		}

		function showCover() {
			if (refs.cover) {
				refs.cover.classList.remove('is-hidden');
			}
		}

		video.addEventListener('timeupdate', paint);
		video.addEventListener('durationchange', paint);
		video.addEventListener('progress', paint);
		video.addEventListener('play', function () {
			root.classList.add('is-playing');
			hideCover();
		});
		video.addEventListener('pause', function () {
			root.classList.remove('is-playing');
		});
		video.addEventListener('ended', function () {
			root.classList.remove('is-playing');
			showCover();
		});

		root.addEventListener('click', function (event) {
			var target = event.target.closest ? event.target.closest('[data-tara-video-toggle], [data-tara-video-play], [data-tara-video-mute], [data-tara-video-fs], [data-tara-video-settings], [data-tara-video-src], [data-tara-video-rate], [data-tara-video-pip]') : null;
			if (!target) {
				return;
			}

			if (target.hasAttribute('data-tara-video-toggle') || target.hasAttribute('data-tara-video-play')) {
				if (video.paused) {
					var promise = video.play();
					if (promise && promise.catch) {
						promise.catch(function () {});
					}
				} else {
					video.pause();
				}
			} else if (target.hasAttribute('data-tara-video-mute')) {
				video.muted = !video.muted;
				target.classList.toggle('is-muted', video.muted);
				target.setAttribute('aria-pressed', video.muted ? 'true' : 'false');
			} else if (target.hasAttribute('data-tara-video-fs')) {
				/* v3.7.7 — تمام‌صفحهٔ واقعی + چرخش خودکار روی موبایل.
				   * اگر مرورگر Fullscreen API دارد: خودِ کارت پلیر تمام‌صفحه
				     می‌شود و صفحه افقی قفل می‌شود (اندروید/دسکتاپ).
				   * iOS روی <video> فقط حالت بومی خودش را دارد؛ همان صدا
				     زده می‌شود که خودبه‌خود می‌چرخد.
				   * اگر هیچ‌کدام نبود (مرورگرهای قدیمی/وب‌ویو): حالت
				     «شبه‌تمام‌صفحه» با CSS؛ در حالت عمودی، تصویر ۹۰ درجه
				     می‌چرخد تا مثل پلیر افقی پرِ صفحه دیده شود. */
				var fsEl = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;

				function lockLandscape() {
					try {
						if (screen.orientation && screen.orientation.lock) {
							var p = screen.orientation.lock('landscape');
							if (p && p.catch) { p.catch(function () {}); }
						}
					} catch (e) {}
				}

				function unlockOrientation() {
					try {
						if (screen.orientation && screen.orientation.unlock) { screen.orientation.unlock(); }
					} catch (e) {}
				}

				function nativeVideo() {
					if (video.webkitEnterFullscreen) {
						video.webkitEnterFullscreen();
						root.classList.add('is-fullscreen');
					} else {
						pseudoFullscreen(!root.classList.contains('is-pseudofs'));
					}
				}

				/* شبه‌تمام‌صفحه: بدون Fullscreen API، با CSS کل صفحه را می‌گیریم. */
				function pseudoFullscreen(on) {
					root.classList.toggle('is-pseudofs', on);
					document.body.classList.toggle('tara-fs-open', on);
					var portrait = window.innerHeight > window.innerWidth;
					root.classList.toggle('is-rotated', on && portrait);
					if (on) { lockLandscape(); } else { unlockOrientation(); }
					try { video.play(); } catch (e) {}
				}

				if (root.classList.contains('is-pseudofs')) {
					pseudoFullscreen(false);
					return;
				}

				if (fsEl) {
					if (document.exitFullscreen) {
						document.exitFullscreen();
					} else if (document.webkitExitFullscreen) {
						document.webkitExitFullscreen();
					} else if (document.msExitFullscreen) {
						document.msExitFullscreen();
					}
					unlockOrientation();
					return;
				}

				/* iOS: تمام‌صفحهٔ عنصری روی همهٔ عناصر کار نمی‌کند؛ اگر فقط
				   ویدیو پشتیبانی می‌کند، مستقیم همان را می‌بریم. */
				if (!root.requestFullscreen && !root.webkitRequestFullscreen && !root.mozRequestFullScreen && !root.msRequestFullscreen) {
					nativeVideo();
					return;
				}

				var req = root.requestFullscreen || root.webkitRequestFullscreen || root.mozRequestFullScreen || root.msRequestFullscreen;
				var out;
				try {
					out = req.call(root, { navigationUI: 'hide' });
				} catch (err) {
					out = req.call(root);
				}

				if (out && out.then) {
					out.then(function () {
						root.classList.add('is-fullscreen');
						lockLandscape();
					}).catch(function () {
						nativeVideo();
					});
				} else {
					root.classList.add('is-fullscreen');
					lockLandscape();
				}
			} else if (target.hasAttribute('data-tara-video-settings')) {
				if (refs.pop) {
					refs.pop.hidden = !refs.pop.hidden;
					target.setAttribute('aria-expanded', refs.pop.hidden ? 'false' : 'true');
				}
			} else if (target.hasAttribute('data-tara-video-src')) {
				var wasPlaying = !video.paused;
				var at = video.currentTime || 0;
				var source = video.querySelector('source');
				var resume = function () {
					video.removeEventListener('loadedmetadata', resume);
					try {
						if (at > 0) {
							video.currentTime = at;
						}
					} catch (error) {}
					if (wasPlaying) {
						var p2 = video.play();
						if (p2 && p2.catch) {
							p2.catch(function () {});
						}
					}
				};
				if (source) {
					source.src = target.getAttribute('data-tara-video-src');
				} else {
					video.src = target.getAttribute('data-tara-video-src');
				}
				video.addEventListener('loadedmetadata', resume);
				video.load();
				if (refs.qualityLabel) {
					refs.qualityLabel.textContent = target.getAttribute('data-tara-video-q') + 'p';
				}
				var qualities = root.querySelectorAll('[data-tara-video-src]');
				for (var i = 0; i < qualities.length; i++) {
					qualities[i].classList.toggle('is-active', qualities[i] === target);
				}
			} else if (target.hasAttribute('data-tara-video-rate')) {
				video.playbackRate = Number(target.getAttribute('data-tara-video-rate'));
				var rates = root.querySelectorAll('[data-tara-video-rate]');
				for (var r = 0; r < rates.length; r++) {
					rates[r].classList.toggle('is-active', rates[r] === target);
				}
			} else if (target.hasAttribute('data-tara-video-pip')) {
				if (document.pictureInPictureElement) {
					document.exitPictureInPicture();
				} else if (video.requestPictureInPicture) {
					video.requestPictureInPicture();
				}
			}
		});

		if (refs.seek) {
			refs.seek.addEventListener('input', function () {
				if (video.duration) {
					video.currentTime = (Number(refs.seek.value) / 100) * video.duration;
				}
			});
		}

		/* کلیک بیرون منوی کیفیت آن را می‌بندد */
		document.addEventListener('click', function (event) {
			if (!refs.pop || refs.pop.hidden) {
				return;
			}
			if (!refs.pop.contains(event.target) && !(refs.settings && refs.settings.contains(event.target))) {
				refs.pop.hidden = true;
				if (refs.settings) {
					refs.settings.setAttribute('aria-expanded', 'false');
				}
			}
		});

		paint();
	}

	/* ==================================================================
	 * ۶.۵) پوستهٔ سایت: هدر چسبان + دکمهٔ «بالا»
	 * ================================================================== */
	function Chrome() {
		/* ---------------------------------------------------------------
		 * کلید شب/روز — v3.7.4
		 * چرا این‌جا؟ چون در بعضی نصب‌ها هندلر jQuery (scripts.js) اجرا
		 * نمی‌شد و دکمه «کار نمی‌کرد». این نسخه با capture صدا زده می‌شود
		 * تا هندلرِ قبلی دوباره اجرا نشود (وگرنه دو بار toggle = بی‌اثر).
		 * ------------------------------------------------------------ */
		var nightKey = 'tara-night';
		var setNight = function (on) {
			document.body.classList.toggle('night', on);
			var switches = document.querySelectorAll('.dark_light_mode, .toggle_modes');
			for (var n = 0; n < switches.length; n++) {
				if (switches[n].classList.contains('dark_light_mode')) {
					switches[n].classList.toggle('dark_active', on);
				} else {
					switches[n].classList.toggle('active', on);
				}
			}
			try {
				window.localStorage.setItem(nightKey, on ? '1' : '0');
			} catch (error) {}
			document.documentElement.setAttribute('data-tara-night', on ? '1' : '0');
		};

		try {
			var saved = window.localStorage.getItem(nightKey);
			if (saved === '1' || saved === '0') {
				setNight(saved === '1');
			}
		} catch (error) {}

		/* دکمهٔ «بالا» — v3.7.4
		   اگر هندلرِ jQuery (scripts.js) اجرا نشده باشد، دکمه بی‌اثر می‌ماند.
		   این‌جا با capture می‌گیریم و از رسیدن رویداد به هندلرِ قبلی
		   جلوگیری می‌کنیم تا اسکرول دو بار اجرا نشود. */
		document.addEventListener('click', function (event) {
			/* اگر اسکریپتِ درون‌خطیِ قالب (v3.7.5) همین کار را انجام می‌دهد،
			   این‌جا تکرار نمی‌کنیم تا کلید دو بار toggle نشود. */
			if (document.documentElement.getAttribute('data-tara-chrome') === '1') {
				return;
			}
			var top = event.target.closest ? event.target.closest('.top_page') : null;
			if (!top) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			try {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			} catch (error) {
				window.scrollTo(0, 0);
			}
		}, true);

		document.addEventListener('click', function (event) {
			/* اگر اسکریپتِ درون‌خطیِ قالب (v3.7.5) همین کار را انجام می‌دهد،
			   این‌جا تکرار نمی‌کنیم تا کلید دو بار toggle نشود. */
			if (document.documentElement.getAttribute('data-tara-chrome') === '1') {
				return;
			}
			var target = event.target.closest ? event.target.closest('.dark_light_mode, .toggle_modes') : null;
			if (!target) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			setNight(!document.body.classList.contains('night'));
		}, true);

		var header = document.getElementById('header');
		if (header) {
			var onChange = function () {
				header.classList.toggle('is-stuck', window.scrollY > 40);
			};
			onChange();
			window.addEventListener('scroll', onChange, { passive: true });
		}

		var top = document.querySelector('.top_page');
		if (top) {
			top.setAttribute('role', 'button');
			top.setAttribute('tabindex', '0');
			top.setAttribute('aria-label', 'بازگشت به بالای صفحه');
			var goTop = function (event) {
				if (event) {
					event.preventDefault();
				}
				window.scrollTo({ top: 0, behavior: 'smooth' });
			};
			top.addEventListener('click', goTop);
			top.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					goTop(event);
				}
			});
		}
	}

	/* ==================================================================
	 * ۷) راه‌اندازی
	 * ================================================================== */
	function boot() {
		Chrome();

		var cards = document.querySelectorAll('[data-tara-player]');
		for (var i = 0; i < cards.length; i++) {
			PlayerCard(cards[i]);
		}

		var dock = document.querySelector('[data-tara-dock]');
		if (dock) {
			Dock(dock);
		}

		var videos = document.querySelectorAll('[data-tara-video]');
		for (var v = 0; v < videos.length; v++) {
			Video(videos[v]);
		}

		Modal.init();
		bindGlobalClicks();
		Engine.on();
		bindZoom();

		/* نشانهٔ سلامت برای عیب‌یابی: اگر این صفت روی <html> نبود، یعنی همین فایل
		   اجرا نشده است (کش/CDN/خطای اسکریپت). */
		document.documentElement.setAttribute('data-tara-ready', '3.8.2');

		/* v3.7.7 — وقتی کاربر با دکمهٔ Esc یا ژست بومی از تمام‌صفحه بیرون
		   می‌آید، کلاس‌ها و قفل چرخش باید پاک شوند؛ وگرنه صفحه سیاه/
		   افقیِ گیرکرده می‌ماند. */
		var fsNodes = document.querySelectorAll('[data-tara-video]');
		function clearFs() {
			var on = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
			for (var i = 0; i < fsNodes.length; i++) {
				if (!on) {
					fsNodes[i].classList.remove('is-fullscreen', 'is-pseudofs', 'is-rotated');
				}
			}
			if (!on) {
				document.body.classList.remove('tara-fs-open');
				try {
					if (screen.orientation && screen.orientation.unlock) { screen.orientation.unlock(); }
				} catch (e) {}
			}
		}
		document.addEventListener('fullscreenchange', clearFs);
		document.addEventListener('webkitfullscreenchange', clearFs);
		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape' && e.keyCode !== 27) { return; }
			for (var i = 0; i < fsNodes.length; i++) {
				if (fsNodes[i].classList.contains('is-pseudofs')) {
					fsNodes[i].classList.remove('is-pseudofs', 'is-rotated');
					document.body.classList.remove('tara-fs-open');
				}
			}
		});
		window.addEventListener('orientationchange', function () {
			for (var i = 0; i < fsNodes.length; i++) {
				if (fsNodes[i].classList.contains('is-pseudofs')) {
					fsNodes[i].classList.toggle('is-rotated', window.innerHeight > window.innerWidth);
				}
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
