/*
 * PC（768px以上でホバーできる環境）では、CTAを押すとLINEへ飛ばさずQRモーダルを開く。
 * <dialog> が使えない環境では何もしない（リンクがそのまま動く）。
 */
(function () {
	'use strict';

	var dialog = document.getElementById('spc-qr-modal');
	if (!dialog || typeof dialog.showModal !== 'function' || !window.matchMedia) {
		return;
	}

	var pc = window.matchMedia('(min-width: 768px) and (hover: hover)');
	var opener = null;

	document.addEventListener('click', function (event) {
		var cta = event.target.closest && event.target.closest('[data-spc-cta]');
		if (!cta || !pc.matches || cta.getAttribute('data-cta-location') === 'qr_modal') {
			return;
		}
		event.preventDefault();
		opener = cta;
		if (!dialog.open) {
			dialog.showModal();
		}
	});

	dialog.addEventListener('click', function (event) {
		// 閉じるボタン
		if (event.target.closest && event.target.closest('[data-qr-close]')) {
			dialog.close();
			return;
		}
		// 背景クリック（dialog 自身が対象になるのは枠の外側を押したときだけ）
		if (event.target === dialog) {
			dialog.close();
		}
	});

	// Esc・閉じるボタン・背景クリックのいずれでも、元のボタンへフォーカスを戻す。
	dialog.addEventListener('close', function () {
		if (opener && typeof opener.focus === 'function') {
			opener.focus();
		}
		opener = null;
	});
})();
