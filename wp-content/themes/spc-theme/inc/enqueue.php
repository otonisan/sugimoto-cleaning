<?php
/**
 * フォント・CSSの読み込み。バージョンはファイルの更新時刻。
 */

defined( 'ABSPATH' ) || exit;

// DESIGN.md §3：書体2つ・必要なウェイトだけ。
const SPC_FONTS_URL = 'https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@500;700&family=Zen+Kaku+Gothic+New:wght@400;500;700&display=swap';

/**
 * テーマ内ファイルのバージョン（更新時刻）。
 */
function spc_asset_version( string $relative_path ): string {
	$path = get_theme_file_path( $relative_path );
	return file_exists( $path ) ? (string) filemtime( $path ) : (string) wp_get_theme()->get( 'Version' );
}

/**
 * フロントのスタイルを読み込む。
 */
function spc_enqueue_assets(): void {
	// ver を付けると family= が重複して壊れるため null（Google Fonts 側でキャッシュ管理）。
	wp_enqueue_style( 'spc-fonts', SPC_FONTS_URL, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

	$deps = array( 'spc-fonts' );
	foreach ( array( 'tokens', 'base', 'components' ) as $name ) {
		$rel = 'assets/css/' . $name . '.css';
		wp_enqueue_style( 'spc-' . $name, get_theme_file_uri( $rel ), $deps, spc_asset_version( $rel ) );
		$deps = array( 'spc-' . $name );
	}
	wp_enqueue_style( 'spc-style', get_stylesheet_uri(), $deps, spc_asset_version( 'style.css' ) );

	// LP の部品はトップだけで読み込む。
	if ( is_front_page() ) {
		wp_enqueue_style( 'spc-lp', get_theme_file_uri( 'assets/css/lp.css' ), array( 'spc-style' ), spc_asset_version( 'assets/css/lp.css' ) );
		// 施工事例の施工前／施工後の切り替え（JSが無くても両方の写真が見える）。
		wp_enqueue_script(
			'spc-before-after',
			get_theme_file_uri( 'assets/js/before-after.js' ),
			array(),
			spc_asset_version( 'assets/js/before-after.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'spc_enqueue_assets' );

/**
 * Google Fonts への preconnect（gstatic は crossorigin 付き）。
 *
 * @param array<int,string|array<string,string>> $urls          URL一覧。
 * @param string                                 $relation_type 種類。
 * @return array<int,string|array<string,string>>
 */
function spc_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'spc_resource_hints', 10, 2 );
