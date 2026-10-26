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
 * 未ログインでの /wp/v2/users（一覧・個別）を拒否する（ユーザー名の列挙対策）。
 * 他のREST経路とログイン済みの利用（管理画面・ブロックエディタ）には影響しない。
 *
 * @param mixed           $result  ここまでの結果。
 * @param WP_REST_Server  $server  サーバー。
 * @param WP_REST_Request $request リクエスト。
 * @return mixed
 */
function spc_require_login_for_rest_users( $result, $server, $request ) {
	if ( is_user_logged_in() || ! preg_match( '#^/wp/v2/users(/|$)#', $request->get_route() ) ) {
		return $result;
	}
	return new WP_Error(
		'rest_forbidden',
		__( 'ログインが必要です。', 'spc' ),
		array( 'status' => rest_authorization_required_code() )
	);
}
add_filter( 'rest_pre_dispatch', 'spc_require_login_for_rest_users', 10, 3 );

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
