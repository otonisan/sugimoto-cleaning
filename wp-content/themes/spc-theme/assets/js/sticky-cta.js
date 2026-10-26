/*
 * スマホの固定CTA：FVや最終CTA（[data-sticky-hide]）が見えている間は隠す。
 * 少しスクロールするまでは出さない。IntersectionObserver が無い環境では常に出す。
 */
(function () {
	'use strict';

	var bar = document.querySelector('.spc-sticky-cta');
	if (!bar) {
		return;
	}

	if (!('IntersectionObserver' in window)) {
		bar.hidden = false;
		return;
	}

	var visible = new Set();
	var scrolled = false;

	function update() {
		bar.hidden = !(scrolled && visible.size === 0);
	}

	function checkScrolled() {
		// 画面に収まる短いページはスクロールできないので、そのまま「スクロール済み」とみなす。
		scrolled = window.scrollY > 0 || document.documentElement.scrollHeight <= window.innerHeight;
		update();
	}

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				visible.add(entry.target);
			} else {
				visible.delete(entry.target);
			}
		});
		update();
	});
	document.querySelectorAll('[data-sticky-hide]').forEach(function (el) {
		observer.observe(el);
	});

	window.addEventListener('scroll', checkScrolled, { passive: true });
	checkScrolled();
})();
