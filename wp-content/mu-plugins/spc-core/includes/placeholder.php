<?php
/**
 * 仮データのガード：本番では仮データを公開・表示しない。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 本番環境か（フィルタ spc_is_production で上書き可）。
 */
function spc_is_production(): bool {
	return (bool) apply_filters( 'spc_is_production', 'production' === wp_get_environment_type() );
}

/**
 * 投稿に「仮データ」のチェックが付いているか。
 *
 * @param int|WP_Post $post 投稿ID または投稿。
 */
function spc_is_placeholder( $post ): bool {
	$post_id = $post instanceof WP_Post ? $post->ID : absint( $post );
	return $post_id > 0 && '1' === (string) get_post_meta( $post_id, '_spc_is_placeholder', true );
}

/**
 * 仮データ（'1'）以外、またはメタなしだけを通す meta_query 句。
 *
 * @return array<int|string,mixed>
 */
function spc_placeholder_meta_guard(): array {
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_spc_is_placeholder',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_spc_is_placeholder',
			'value'   => '1',
			'compare' => '!=',
		),
	);
}

/**
 * 既存の meta_query に仮データ除外の句を AND で足す。
 *
 * @param array<string,mixed> $args クエリ引数。
 * @return array<string,mixed>
 */
function spc_add_placeholder_guard( array $args ): array {
	$guard = spc_placeholder_meta_guard();
	if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array( 'relation' => 'AND', $args['meta_query'], $guard );
	} else {
		$args['meta_query'] = $guard;
	}
	return $args;
}

/**
 * 本番ではフロントのメインクエリ（事例・固定ページ・検索）から公開済みの仮データを除く（仮の固定ページは404）。
 * 管理画面は対象外。
 */
function spc_exclude_placeholder_from_main_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! spc_is_production() ) {
		return;
	}
	$types     = (array) $query->get( 'post_type' );
	$is_case   = in_array( 'spc_case', $types, true ) || $query->is_post_type_archive( 'spc_case' );
	$is_page   = in_array( 'page', $types, true ) || $query->is_page();
	$is_search = $query->is_search() && '' === (string) $query->get( 'post_type' );
	if ( ! $is_case && ! $is_page && ! $is_search ) {
		return;
	}
	$query->set( 'meta_query', spc_add_placeholder_guard( array( 'meta_query' => $query->get( 'meta_query' ) ) )['meta_query'] );
}
add_action( 'pre_get_posts', 'spc_exclude_placeholder_from_main_query' );

/**
 * 本番ではサイトマップ（事例・固定ページ）から仮データを除く。
 *
 * @param array<string,mixed> $args      クエリ引数。
 * @param string              $post_type 投稿タイプ。
 * @return array<string,mixed>
 */
function spc_exclude_placeholder_from_sitemap( array $args, $post_type ): array {
	if ( spc_is_production() && in_array( $post_type, array( 'spc_case', 'page' ), true ) ) {
		$args = spc_add_placeholder_guard( $args );
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'spc_exclude_placeholder_from_sitemap', 10, 2 );

/**
 * フロント表示用のクエリ引数。本番では仮データを除外し、並び順の既定値を入れる。
 *
 * @param array<string,mixed> $args 呼び出し側の指定（既定値より優先）。
 * @return array<string,mixed>
 */
function spc_query_args( string $post_type, array $args = array() ): array {
	$defaults = 'spc_case' === $post_type
		? array(
			'orderby' => 'date',
			'order'   => 'DESC',
		)
		: array(
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		);
	$args     = wp_parse_args( $args, $defaults + array( 'post_status' => 'publish' ) );
	$args['post_type'] = $post_type;

	if ( spc_is_production() ) {
		$args = spc_add_placeholder_guard( $args );
	}

	return $args;
}

/**
 * 設定画面（ブランド設定）のURL。
 */
function spc_settings_page_url(): string {
	return admin_url( 'options-general.php?page=spc-settings' );
}

/**
 * サイトに必要な固定ページ（パス => ページ名。ページ名は docs/sitemap.md §2）。
 * 公開前チェックで、これらが決められたURLにあるかを確認する。
 *
 * @return array<string,string>
 */
function spc_required_pages(): array {
	return array(
		'stain-removal'   => '特殊シミ抜き',
		'down-coat'       => '高級ダウン・コート',
		'price'           => '料金・送料',
		'about'           => '職人・運営会社',
		'guide'           => 'ご利用の流れ・よくある質問',
		'legal'           => '規約・法定表示',
		'legal/terms'     => '利用規約',
		'legal/tokushoho' => '特定商取引法に基づく表記',
		'legal/privacy'   => '個人情報・相談写真の取扱い',
	);
}

/**
 * 公開前に差し替えが必要な項目の一覧。空なら公開OK。
 *
 * 各項目の形：array{type:string,label:string,edit_url:string}
 * - type：'post'（仮データの投稿・固定ページ）/ 'line_url' / 'line_qr' / 'brand_name' / 'gtm_id' / 'reply_time_note' / 'page_missing'（必要な固定ページが無い）
 *   ／フィルタ spc_placeholder_report で追加されるもの（テーマの仮画像は 'theme_image'）。
 * - label：管理画面・CLIにそのまま出す日本語の説明。
 * - edit_url：直す画面のURL。管理画面で直せないもの（テーマのファイル）は ''（リンクなし）。
 *
 * @return array<int,array{type:string,label:string,edit_url:string}>
 */
function spc_placeholder_report(): array {
	$report = array();

	$posts = get_posts(
		array(
			'post_type'              => spc_placeholder_post_types(),
			'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'posts_per_page'         => -1,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_key'               => '_spc_is_placeholder', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'             => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $posts as $post ) {
		$type_obj = get_post_type_object( $post->post_type );
		$title    = '' !== $post->post_title ? $post->post_title : __( '（無題）', 'spc' );
		$report[] = array(
			'type'     => 'post',
			/* translators: 1: 投稿タイトル 2: 投稿の種類 */
			'label'    => sprintf( __( '仮データの投稿：%1$s（%2$s）', 'spc' ), $title, 'page' === $post->post_type ? __( '固定ページ', 'spc' ) : ( $type_obj ? $type_obj->labels->singular_name : $post->post_type ) ),
			'edit_url' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ), // CLIなど未ログインでも出す
		);
	}

	// 必要な固定ページ：決められたパスに無い、またはゴミ箱にあるものを知らせる。
	foreach ( spc_required_pages() as $path => $page_title ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( $page && 'trash' !== $page->post_status ) {
			continue;
		}
		$report[] = array(
			'type'     => 'page_missing',
			/* translators: 1: ページ名 2: パス */
			'label'    => sprintf( __( '必要な固定ページがありません：%1$s（/%2$s/）', 'spc' ), $page_title, $path ),
			'edit_url' => admin_url( 'edit.php?post_type=page' ),
		);
	}

	$settings_url = spc_settings_page_url();
	if ( SPC_LINE_URL_PLACEHOLDER === spc_line_url() ) {
		$report[] = array(
			'type'     => 'line_url',
			'label'    => __( 'LINE追加URLが仮のままです', 'spc' ),
			'edit_url' => $settings_url,
		);
	}
	$qr_id = (int) spc_get_setting( 'line_qr_id' );
	if ( $qr_id < 1 || ! wp_attachment_is_image( $qr_id ) ) {
		$report[] = array(
			'type'     => 'line_qr',
			'label'    => __( 'LINE QRコード画像が未設定です', 'spc' ),
			'edit_url' => $settings_url,
		);
	}
	if ( false !== strpos( (string) spc_get_setting( 'brand_name' ), '（仮）' ) ) {
		$report[] = array(
			'type'     => 'brand_name',
			'label'    => __( 'ブランド名に『（仮）』が含まれています', 'spc' ),
			'edit_url' => $settings_url,
		);
	}
	if ( '' === (string) spc_get_setting( 'gtm_id' ) ) {
		$report[] = array(
			'type'     => 'gtm_id',
			'label'    => __( 'GTMコンテナIDが未設定です', 'spc' ),
			'edit_url' => $settings_url,
		);
	}
	if ( false !== strpos( (string) spc_get_setting( 'reply_time_note' ), '（仮）' ) ) {
		$report[] = array(
			'type'     => 'reply_time_note',
			'label'    => __( '一次返信の目安が仮のままです', 'spc' ),
			'edit_url' => $settings_url,
		);
	}

	/**
	 * 公開前チェックの項目を追加する（テーマに直接置いた仮の写真など）。
	 * 項目の形は上の docblock と同じ。edit_url は '' でもよい。
	 *
	 * @param array<int,array{type:string,label:string,edit_url:string}> $report 項目の一覧。
	 */
	$filtered = apply_filters( 'spc_placeholder_report', $report );

	// 形の崩れた項目は捨てる（サイトヘルス・CLIの表示を壊さないため）。
	$report = array();
	foreach ( is_array( $filtered ) ? $filtered : array() as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['label'] ) || ! is_scalar( $item['label'] ) || '' === (string) $item['label'] ) {
			continue;
		}
		$report[] = array(
			'type'     => isset( $item['type'] ) && is_scalar( $item['type'] ) ? (string) $item['type'] : '',
			'label'    => (string) $item['label'],
			'edit_url' => isset( $item['edit_url'] ) && is_scalar( $item['edit_url'] ) ? (string) $item['edit_url'] : '',
		);
	}

	return $report;
}

/**
 * 本番で公開（予約含む）状態の仮データ（4タイプ＋固定ページ）を下書きに戻す。
 */
function spc_unpublish_if_placeholder( int $post_id ): void {
	$post = get_post( $post_id );
	if ( ! $post || ! in_array( $post->post_type, spc_placeholder_post_types(), true ) || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
		return;
	}
	if ( ! spc_is_production() || ! spc_is_placeholder( $post ) ) {
		return;
	}

	// 自分のフックを外してから更新する（再帰・二重通知の防止）。
	spc_unhook_placeholder_guard();
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	spc_hook_placeholder_guard();

	spc_remember_reverted_post( $post_id );
	spc_queue_placeholder_notice( $post );
}

/**
 * このリクエストで下書きに戻した投稿ID（引数ありで記録、なしで一覧を返す）。
 *
 * @return int[]
 */
function spc_remember_reverted_post( int $post_id = 0 ): array {
	static $ids = array();
	if ( $post_id > 0 ) {
		$ids[ $post_id ] = $post_id;
	}
	return array_values( $ids );
}

/**
 * 下書きに戻した投稿の保存後は、コアの「公開しました」ではなく「下書きを更新しました」（message=10）を出す。
 * 旧エディタの保存後リダイレクトだけが対象（ほかの投稿のメッセージは変えない）。
 *
 * @param string $location リダイレクト先。
 * @param int    $post_id  保存した投稿ID。
 */
function spc_placeholder_redirect_message( $location, $post_id ) {
	if ( ! in_array( (int) $post_id, spc_remember_reverted_post(), true ) ) {
		return $location;
	}
	return add_query_arg( 'message', 10, remove_query_arg( 'message', (string) $location ) );
}
add_filter( 'redirect_post_location', 'spc_placeholder_redirect_message', 10, 2 );

/**
 * wp_after_insert_post（メタ保存後）で確認する。
 */
function spc_guard_after_insert_post( int $post_id ): void {
	spc_unpublish_if_placeholder( $post_id );
}

/**
 * _spc_is_placeholder の追加・更新で確認する。
 *
 * @param int    $meta_id  メタID（未使用）。
 * @param int    $post_id  投稿ID。
 * @param string $meta_key メタキー。
 */
function spc_guard_on_meta( $meta_id, $post_id, $meta_key ): void {
	if ( '_spc_is_placeholder' === $meta_key ) {
		spc_unpublish_if_placeholder( (int) $post_id );
	}
}

/**
 * ガードのフックを付ける。
 */
function spc_hook_placeholder_guard(): void {
	add_action( 'wp_after_insert_post', 'spc_guard_after_insert_post', 20 );
	add_action( 'added_post_meta', 'spc_guard_on_meta', 10, 3 );
	add_action( 'updated_post_meta', 'spc_guard_on_meta', 10, 3 );
}

/**
 * ガードのフックを外す。
 */
function spc_unhook_placeholder_guard(): void {
	remove_action( 'wp_after_insert_post', 'spc_guard_after_insert_post', 20 );
	remove_action( 'added_post_meta', 'spc_guard_on_meta', 10 );
	remove_action( 'updated_post_meta', 'spc_guard_on_meta', 10 );
}
spc_hook_placeholder_guard();

/**
 * 下書きに戻した旨を、次の管理画面表示で知らせるために保存する。
 */
function spc_queue_placeholder_notice( WP_Post $post ): void {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}
	$key    = 'spc_placeholder_notice_' . $user_id;
	$titles = get_transient( $key );
	$titles = is_array( $titles ) ? $titles : array();
	$title  = '' !== $post->post_title ? $post->post_title : __( '（無題）', 'spc' );
	set_transient( $key, array_values( array_unique( array_merge( $titles, array( $title ) ) ) ), HOUR_IN_SECONDS );
}

/**
 * 保存しておいた通知を一度だけ表示する。
 */
function spc_render_placeholder_notice(): void {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}
	$key    = 'spc_placeholder_notice_' . $user_id;
	$titles = get_transient( $key );
	if ( ! is_array( $titles ) || ! $titles ) {
		return;
	}
	delete_transient( $key );
	foreach ( $titles as $title ) {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: 投稿タイトル */
					__( '『%s』は仮データのため、本番サイトでは公開できません。下書きに戻しました。内容を差し替えて『仮データ』のチェックを外してから公開してください。', 'spc' ),
					(string) $title
				)
			)
		);
	}
}
add_action( 'admin_notices', 'spc_render_placeholder_notice' );

/**
 * サイトヘルスの「公開前チェック」。本番だけ critical にする。
 *
 * @return array<string,mixed>
 */
function spc_site_health_placeholder(): array {
	$report = spc_placeholder_report();
	$result = array(
		'label'       => __( '仮データは残っていません', 'spc' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( '公開前チェック', 'spc' ),
			'color' => 'blue',
		),
		'description' => '<p>' . esc_html__( '仮の内容は見つかりませんでした。', 'spc' ) . '</p>',
		'actions'     => '',
		'test'        => 'spc_placeholder',
	);
	if ( ! $report ) {
		return $result;
	}

	$items = '';
	foreach ( $report as $item ) {
		$items .= '' !== $item['edit_url']
			? '<li><a href="' . esc_url( $item['edit_url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>'
			: '<li>' . esc_html( $item['label'] ) . '</li>';
	}
	$production            = spc_is_production();
	$result['status']      = $production ? 'critical' : 'recommended';
	$result['label']       = __( '仮データが残っています', 'spc' );
	$result['description'] = '<p>' . esc_html(
		$production
			? __( '本番サイトの公開前に、次の仮データを差し替えてください。', 'spc' )
			: __( '制作中のため仮データがあります。本番に移す前に差し替えてください。', 'spc' )
	) . '</p><ul>' . $items . '</ul>';
	return $result;
}

/**
 * サイトヘルスにテストを登録する。
 *
 * @param array<string,mixed> $tests 既存のテスト。
 * @return array<string,mixed>
 */
function spc_register_site_health_test( array $tests ): array {
	$tests['direct']['spc_placeholder'] = array(
		'label' => __( '公開前チェック（仮データ）', 'spc' ),
		'test'  => 'spc_site_health_placeholder',
	);
	return $tests;
}
add_filter( 'site_status_tests', 'spc_register_site_health_test' );

/**
 * 管理バーに「仮データ N件」を出す（投稿を編集できるユーザーのみ）。
 */
function spc_admin_bar_placeholder( WP_Admin_Bar $bar ): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$count = count( spc_placeholder_report() );
	if ( $count < 1 ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'spc-placeholder',
			'title' => sprintf( /* translators: %d: 件数 */ __( '仮データ %d件', 'spc' ), $count ),
			'href'  => admin_url( 'site-health.php' ), // サイトヘルスの「公開前チェック」に項目の一覧が出る
		)
	);
}
add_action( 'admin_bar_menu', 'spc_admin_bar_placeholder', 100 );
