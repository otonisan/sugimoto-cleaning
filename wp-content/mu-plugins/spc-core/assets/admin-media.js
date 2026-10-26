/**
 * 汎用の画像選択（メディアライブラリ）。
 * 構造: [data-spc-media] 内に
 *   [data-spc-media-input]   添付IDを入れる hidden input
 *   [data-spc-media-preview] プレビュー表示領域
 *   [data-spc-media-select]  選択ボタン（data-title / data-button でダイアログ文言）
 *   [data-spc-media-remove]  削除ボタン
 */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '[data-spc-media-select]', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $wrap = $btn.closest( '[data-spc-media]' );
		var frame = wp.media( {
			title: $btn.data( 'title' ) || '画像を選択',
			button: { text: $btn.data( 'button' ) || 'この画像を使う' },
			library: { type: 'image' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$wrap.find( '[data-spc-media-input]' ).val( att.id );
			var $img = $( '<img>', { src: src, alt: att.alt || '', css: { maxWidth: '240px', height: 'auto' } } );
			$wrap.find( '[data-spc-media-preview]' ).empty().append( $img );
			$wrap.find( '[data-spc-media-remove]' ).prop( 'hidden', false );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '[data-spc-media-remove]', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '[data-spc-media]' );
		$wrap.find( '[data-spc-media-input]' ).val( 0 );
		$wrap.find( '[data-spc-media-preview]' ).empty();
		$( this ).prop( 'hidden', true );
	} );
}( jQuery ) );
