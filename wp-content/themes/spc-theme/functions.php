<?php
defined( 'ABSPATH' ) || exit;

// スタイルシートを読み込む。
add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_style( 'spc-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );
