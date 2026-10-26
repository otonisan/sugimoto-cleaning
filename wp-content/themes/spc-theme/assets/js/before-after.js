/*
 * 施工事例の施工前／施工後の切り替え（DESIGN.md §7）。
 * 自動では切り替えない。ボタンを押したときだけ、表示する写真を入れ替える。
 * 切り替えの動き（不透明度 200ms）は lp.css。動きを減らす設定では CSS 側で即時にする。
 * このファイルが動かないときは、2枚の写真が縦に並んで両方見える。
 */
(function () {
	'use strict';

	function setup(root) {
		var toggle = root.querySelector('[data-spc-ba-toggle]');
		var buttons = root.querySelectorAll('[data-spc-ba-show]');
		var panes = root.querySelectorAll('[data-spc-ba-pane]');
		if (!toggle || buttons.length < 2 || panes.length < 2) {
			return;
		}

		function show(name) {
			var i;
			for (i = 0; i < buttons.length; i++) {
				buttons[i].setAttribute('aria-pressed', buttons[i].getAttribute('data-spc-ba-show') === name ? 'true' : 'false');
			}
			for (i = 0; i < panes.length; i++) {
				panes[i].classList.toggle('is-current', panes[i].getAttribute('data-spc-ba-pane') === name);
			}
		}

		for (var i = 0; i < buttons.length; i++) {
			buttons[i].addEventListener('click', function (event) {
				show(event.currentTarget.getAttribute('data-spc-ba-show'));
			});
		}

		show('before');
		toggle.hidden = false;
		root.classList.add('is-switchable');
	}

	var roots = document.querySelectorAll('[data-spc-ba]');
	for (var i = 0; i < roots.length; i++) {
		setup(roots[i]);
	}
})();
