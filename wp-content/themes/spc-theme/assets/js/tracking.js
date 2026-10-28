/*
 * 計測：spcTrack()・landing_view・line_click・UTM の引き継ぎ。
 * GTM が読み込めなくても（ブロックされても）例外を出さない。
 */
(function () {
	'use strict';

	var UTM_KEY = 'spc_utm';

	// dataLayer にイベントを積む。
	function spcTrack(event, params) {
		try {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push(Object.assign({ event: event }, params));
		} catch (e) {}
	}
	window.spcTrack = spcTrack;

	// 最初に着地したURLの utm_* を保存する（保存済みなら上書きしない）。
	function storeUtm() {
		try {
			if (window.sessionStorage.getItem(UTM_KEY)) {
				return;
			}
			var found = new URLSearchParams(window.location.search);
			var utm = new URLSearchParams();
			found.forEach(function (value, key) {
				if (key.indexOf('utm_') === 0) {
					utm.append(key, value);
				}
			});
			if (utm.toString()) {
				window.sessionStorage.setItem(UTM_KEY, utm.toString());
			}
		} catch (e) {}
	}

	// CTAのリンクに、保存した utm_* を付ける（既にあるキーは足さない）。
	function applyUtm() {
		var saved = '';
		try {
			saved = window.sessionStorage.getItem(UTM_KEY) || '';
		} catch (e) {}
		if (!saved) {
			return;
		}
		var utm = new URLSearchParams(saved);
		document.querySelectorAll('[data-spc-cta]').forEach(function (link) {
			try {
				var url = new URL(link.getAttribute('href'), window.location.href);
				if (url.protocol !== 'http:' && url.protocol !== 'https:') {
					return;
				}
				utm.forEach(function (value, key) {
					if (!url.searchParams.has(key)) {
						url.searchParams.set(key, value);
					}
				});
				link.setAttribute('href', url.toString());
			} catch (e) {}
		});
	}

	// ページの種類（<body data-page-type>。無ければ other）。
	function pageType() {
		return (document.body && document.body.getAttribute('data-page-type')) || 'other';
	}

	// CTAのクリック（preventDefault はしない）。
	document.addEventListener('click', function (event) {
		try {
			var cta = event.target.closest && event.target.closest('[data-spc-cta]');
			if (cta) {
				spcTrack('line_click', {
					cta_location: cta.getAttribute('data-cta-location') || '',
					page_type: pageType()
				});
			}
		} catch (e) {}
	});

	storeUtm();
	applyUtm();

	if (pageType() === 'lp') {
		spcTrack('landing_view', { page_type: 'lp' });
	}
})();
