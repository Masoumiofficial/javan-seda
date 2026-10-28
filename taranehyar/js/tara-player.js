/**
 * ترانه یار — پلیر «رادیو» (بدون وابستگی)
 * ---------------------------------------------------------------------------
 * یک نمونه در هر صفحه. سه چیز را مدیریت می‌کند:
 *   ۱) کارت بزرگ پلیر (پخش، توقف، بعدی/قبلی، جابه‌جایی، صدا، فهرست پخش)
 *   ۲) نوار چسبانِ پایین صفحه که با اسکرول ظاهر می‌شود و همان صدا را کنترل می‌کند
 *   ۳) دکمه‌های «پخش» بیرون از کارت (ردیف‌های آهنگ) که با data-tara-play کار می‌کنند
 *
 * چرا بدون jQuery: قالب در صفحات مختلف jQuery را دیر یا اصلاً بار نمی‌کند و
 * پلیر نباید به آن وابسته باشد. هیچ کد دیگری را دست نمی‌زند.
 *
 * @package Taranehyar
 */
(function () {
	'use strict';

	var ICON_PLAY = 'ty-icon-play';
	var ICON_PAUSE = 'ty-icon-pause';

	function formatTime(seconds) {
		if (!isFinite(seconds) || seconds < 0) {
			return '۰۰:۰۰';
		}
		var m = Math.floor(seconds / 60);
		var s = Math.floor(seconds % 60);
		return toFa((m < 10 ? '0' : '') + m) + ':' + toFa((s < 10 ? '0' : '') + s);
	}

	function toFa(value) {
		return String(value).replace(/[0-9]/g, function (d) {
			return '۰۱۲۳۴۵۶۷۸۹'.charAt(Number(d));
		});
	}

	function initPlayer(root) {
		var dataEl = root.querySelector('.ty-player__data');
		if (!dataEl) {
			return;
		}

		var tracks = [];
		try {
			tracks = JSON.parse(dataEl.textContent || '[]');
		} catch (error) {
			return;
		}
		if (!tracks.length) {
			return;
		}

		var audio = root.querySelector('[data-tara-audio]');
		var toggle = root.querySelector('[data-tara-toggle]');
		var prev = root.querySelector('[data-tara-prev]');
		var next = root.querySelector('[data-tara-next]');
		var seek = root.querySelector('[data-tara-seek]');
		var fill = root.querySelector('[data-tara-fill]');
		var buffer = root.querySelector('[data-tara-buffer]');
		var time = root.querySelector('[data-tara-time]');
		var volume = root.querySelector('[data-tara-volume]');
		var mute = root.querySelector('[data-tara-mute]');
		var art = root.querySelector('[data-tara-art]');
		var titleEl = root.querySelector('[data-tara-title]');
		var artistEl = root.querySelector('[data-tara-artist]');
		var titleLink = root.querySelector('[data-tara-title-link]');
		var items = root.querySelectorAll('[data-tara-item]');

		var dock = document.querySelector('[data-tara-dock]');
		var dockToggle = dock ? dock.querySelector('[data-tara-dock-toggle]') : null;
		var dockPrev = dock ? dock.querySelector('[data-tara-dock-prev]') : null;
		var dockNext = dock ? dock.querySelector('[data-tara-dock-next]') : null;
		var dockFill = dock ? dock.querySelector('[data-tara-dock-fill]') : null;
		var dockTitle = dock ? dock.querySelector('[data-tara-dock-title]') : null;
		var dockArtist = dock ? dock.querySelector('[data-tara-dock-artist]') : null;
		var dockArt = dock ? dock.querySelector('[data-tara-dock-art]') : null;
		var dockLink = dock ? dock.querySelector('[data-tara-dock-link]') : null;

		var current = 0;
		var started = false;

		function track() {
			return tracks[current] || tracks[0];
		}

		function paint() {
			var t = track();
			if (titleEl) {
				titleEl.textContent = t.title;
			}
			if (artistEl) {
				artistEl.textContent = t.artist;
			}
			if (titleLink && t.link) {
				titleLink.setAttribute('href', t.link);
			}
			if (art && t.cover) {
				art.setAttribute('src', t.cover);
			}
			if (dockTitle) {
				dockTitle.textContent = t.title;
			}
			if (dockArtist) {
				dockArtist.textContent = t.artist;
			}
			if (dockArt && t.cover) {
				dockArt.setAttribute('src', t.cover);
			}
			if (dockLink && t.link) {
				dockLink.setAttribute('href', t.link);
			}

			for (var i = 0; i < items.length; i++) {
				var index = Number(items[i].getAttribute('data-tara-item'));
				items[i].classList.toggle('is-active', index === current);
			}

			if (document.title && started) {
				document.querySelectorAll('.ty-track.is-playing').forEach(function (row) {
					row.classList.remove('is-playing');
				});
			}
		}

		function load(index, autoplay) {
			current = (index + tracks.length) % tracks.length;
			var t = track();
			audio.src = t.src;
			paint();

			if (autoplay) {
				play();
			}
		}

		function play() {
			root.classList.add('is-loading');
			var promise = audio.play();
			started = true;
			if (promise && typeof promise.catch === 'function') {
				promise.catch(function () {
					root.classList.remove('is-loading', 'is-playing');
				});
			}
		}

		function pause() {
			audio.pause();
		}

		function setPlaying(state) {
			root.classList.toggle('is-playing', state);
			if (dock) {
				dock.classList.toggle('is-playing', state);
			}
			var label = state ? 'توقف' : 'پخش';
			if (toggle) {
				toggle.setAttribute('aria-label', label);
			}
			if (dockToggle) {
				dockToggle.setAttribute('aria-label', label);
			}
		}

		function updateTime() {
			var duration = audio.duration || 0;
			var position = audio.currentTime || 0;
			var percent = duration > 0 ? (position / duration) * 100 : 0;

			if (fill) {
				fill.style.width = percent + '%';
			}
			if (dockFill) {
				dockFill.style.width = percent + '%';
			}
			if (seek && duration > 0) {
				seek.value = String(percent);
				seek.setAttribute('aria-valuetext', Math.floor(position) + ' ثانیه از ' + Math.floor(duration));
			}
			if (time) {
				time.textContent = formatTime(position) + ' / ' + formatTime(duration);
			}
		}

		/* ---------- کنترل‌ها ---------- */

		if (toggle) {
			toggle.addEventListener('click', function () {
				if (audio.paused) {
					if (!audio.src) {
						load(current, true);
					} else {
						play();
					}
				} else {
					pause();
				}
			});
		}

		if (prev) {
			prev.addEventListener('click', function () {
				load(current - 1, !audio.paused || started);
			});
		}

		if (next) {
			next.addEventListener('click', function () {
				load(current + 1, !audio.paused || started);
			});
		}

		if (seek) {
			seek.addEventListener('input', function () {
				var duration = audio.duration || 0;
				if (duration > 0) {
					audio.currentTime = (Number(seek.value) / 100) * duration;
				}
			});
		}

		if (volume) {
			volume.addEventListener('input', function () {
				audio.volume = Number(volume.value);
				audio.muted = audio.volume === 0;
			});
		}

		if (mute) {
			mute.addEventListener('click', function () {
				audio.muted = !audio.muted;
				mute.classList.toggle('is-muted', audio.muted);
			});
		}

		for (var i = 0; i < items.length; i++) {
			items[i].addEventListener('click', function (event) {
				var button = event.target.closest('[data-tara-play]');
				if (!button) {
					return;
				}
				var index = Number(button.getAttribute('data-tara-play'));
				if (index === current && audio.src) {
					if (audio.paused) {
						play();
					} else {
						pause();
					}
					return;
				}
				load(index, true);
			});
		}

		/* دکمه‌های پخشِ بیرون از کارت */
		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-tara-play]');
			if (!button || root.contains(button)) {
				return;
			}
			var index = Number(button.getAttribute('data-tara-play'));
			if (isNaN(index)) {
				return;
			}
			if (index === current && audio.src && !audio.paused) {
				pause();
				return;
			}
			load(index, true);
		});

		/* نوار چسبان */
		if (dock) {
			if (dockToggle) {
				dockToggle.addEventListener('click', function () {
					if (audio.paused) {
						if (!audio.src) {
							load(current, true);
						} else {
							play();
						}
					} else {
						pause();
					}
				});
			}
			if (dockPrev) {
				dockPrev.addEventListener('click', function () {
					load(current - 1, true);
				});
			}
			if (dockNext) {
				dockNext.addEventListener('click', function () {
					load(current + 1, true);
				});
			}
		}

		/* ---------- رویدادهای صدا ---------- */

		audio.addEventListener('loadstart', function () {
			root.classList.add('is-loading');
		});
		audio.addEventListener('canplay', function () {
			root.classList.remove('is-loading');
		});
		audio.addEventListener('playing', function () {
			root.classList.remove('is-loading');
			setPlaying(true);
			if (dock) {
				dock.hidden = false;
				window.requestAnimationFrame(function () {
					dock.classList.add('is-visible');
					document.body.classList.add('has-tara-dock');
				});
			}
		});
		audio.addEventListener('pause', function () {
			setPlaying(false);
		});
		audio.addEventListener('timeupdate', updateTime);
		audio.addEventListener('durationchange', updateTime);
		audio.addEventListener('progress', function () {
			if (buffer && audio.buffered.length && audio.duration) {
				buffer.style.width = (audio.buffered.end(audio.buffered.length - 1) / audio.duration) * 100 + '%';
			}
		});
		audio.addEventListener('ended', function () {
			if (tracks.length > 1) {
				load(current + 1, true);
			} else {
				setPlaying(false);
			}
		});
		audio.addEventListener('error', function () {
			root.classList.remove('is-loading');
			root.classList.add('is-error');
		});

		/* رسانه در نوار سیستم (موبایل) */
		if ('mediaSession' in navigator && window.MediaMetadata) {
			var syncMediaSession = function () {
				var t = track();
				navigator.mediaSession.metadata = new window.MediaMetadata({
					title: t.title,
					artist: t.artist,
					album: document.title,
					artwork: t.cover ? [{ src: t.cover, sizes: '190x190', type: 'image/jpeg' }] : []
				});
			};
			audio.addEventListener('playing', syncMediaSession);
			if (navigator.mediaSession.setActionHandler) {
				navigator.mediaSession.setActionHandler('play', play);
				navigator.mediaSession.setActionHandler('pause', pause);
				navigator.mediaSession.setActionHandler('previoustrack', function () {
					load(current - 1, true);
				});
				navigator.mediaSession.setActionHandler('nexttrack', function () {
					load(current + 1, true);
				});
			}
		}

		paint();
	}

	function boot() {
		var players = document.querySelectorAll('[data-tara-player]');
		for (var i = 0; i < players.length; i++) {
			initPlayer(players[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
