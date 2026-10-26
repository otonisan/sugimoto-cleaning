<?php
/**
 * セキュリティ設定：不要な入口を閉じ、サイト情報の漏えいを減らす。
 */

defined( 'ABSPATH' ) || exit;

// XML-RPC は使わないので無効化し、Pingback の案内（ヘッダ・RSD リンク）も出さない。
add_filter( 'xmlrpc_enabled', '__return_false' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * X-Pingback ヘッダを外す。
 *
 * @param array<string,string> $headers 送信するヘッダ。
 * @return array<string,string>
 */
function spc_remove_pingback_header( array $headers ): array {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'spc_remove_pingback_header' );

// WordPress のバージョンを出す <meta name="generator"> を出力しない。
add_filter( 'the_generator', '__return_empty_string' );

/**
 * 未ログインでの /wp/v2/users*（一覧・個別・me）を拒否する（ユーザー名の列挙対策）。
 * ルート照合は大文字小文字を区別しないため、URLではなくエンドポイント自体の
 * permission_callback を包んで確実に止める。ログイン済みの利用は元の判定のまま。
 *
 * @param array<string,mixed> $endpoints 登録済みのエンドポイント。
 * @return array<string,mixed>
 */
function spc_require_login_for_rest_users( array $endpoints ): array {
	foreach ( $endpoints as $route => $handlers ) {
		if ( 0 !== strpos( strtolower( (string) $route ), '/wp/v2/users' ) || ! is_array( $handlers ) ) {
			continue;
		}
		foreach ( $handlers as $i => $handler ) {
			if ( ! is_array( $handler ) || ! isset( $handler['callback'] ) ) {
				continue;
			}
			$original = $handler['permission_callback'] ?? null;
			$endpoints[ $route ][ $i ]['permission_callback'] = static function ( $request ) use ( $original ) {
				if ( ! is_user_logged_in() ) {
					return new WP_Error(
						'rest_forbidden',
						__( 'ログインが必要です。', 'spc' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				return is_callable( $original ) ? call_user_func( $original, $request ) : true;
			};
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'spc_require_login_for_rest_users' );

// ユーザー一覧のサイトマップ（/wp-sitemap-users-1.xml）は投稿者のスラッグを公開するため出さない。
add_filter(
	'wp_sitemaps_add_provider',
	static function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

/**
 * oEmbed の応答から投稿者名・投稿者URLを外す（ユーザー名の列挙対策）。
 *
 * @param array<string,mixed> $data oEmbed の応答データ。
 * @return array<string,mixed>
 */
function spc_oembed_hide_author( $data ) {
	if ( is_array( $data ) ) {
		unset( $data['author_name'], $data['author_url'] );
	}
	return $data;
}
add_filter( 'oembed_response_data', 'spc_oembed_hide_author' );

/**
 * 投稿者アーカイブと ?author=N をトップへ 301 で戻す（ユーザー名の列挙対策）。
 * redirect_canonical（優先度10）より先に動かす。
 */
function spc_redirect_author_archives(): void {
	if ( is_admin() ) {
		return;
	}
	if ( is_author() || '' !== (string) get_query_var( 'author' ) || '' !== (string) get_query_var( 'author_name' ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'spc_redirect_author_archives', 1 );
