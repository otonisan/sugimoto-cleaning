<?php
/**
 * テーマ機能・メニュー位置・画像サイズ。
 */

defined( 'ABSPATH' ) || exit;

/**
 * テーマ機能を登録する。
 */
function spc_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus(
		array(
			'primary' => __( 'ヘッダーメニュー', 'spc' ),
			'footer'  => __( 'フッターメニュー', 'spc' ),
		)
	);

	// DESIGN.md §5 の比率：FV 16:10、職人 4:5、メニュー 3:2。
	add_image_size( 'spc-hero', 1600, 1000, true );
	add_image_size( 'spc-portrait', 800, 1000, true );
	add_image_size( 'spc-menu', 900, 600, true );
}
add_action( 'after_setup_theme', 'spc_theme_setup' );

/**
 * <title> のサイト名をブランド名にする。
 *
 * @param array<string,string> $parts タイトルの部品。
 * @return array<string,string>
 */
function spc_document_title_parts( array $parts ): array {
	$parts['site'] = spc_brand_name();
	if ( isset( $parts['title'] ) && is_front_page() ) {
		$parts['title'] = spc_brand_name();
		unset( $parts['site'] );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'spc_document_title_parts' );

/**
 * アーカイブ見出しの「アーカイブ:」などの接頭辞を外す。
 */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
