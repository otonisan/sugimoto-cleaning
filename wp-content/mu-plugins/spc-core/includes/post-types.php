<?php
/**
 * カスタム投稿タイプ（施工事例・料金・よくある質問・職人）。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 4つの投稿タイプを登録する。
 */
function spc_register_post_types(): void {
	// 施工事例：公開（/cases/ とアーカイブあり）。
	register_post_type(
		'spc_case',
		array(
			'labels'       => spc_post_type_labels( __( '施工事例', 'spc' ), __( '施工事例を追加', 'spc' ) ),
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'cases' ),
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-images-alt2',
			'supports'     => array( 'title' ),
		)
	);

	// 料金・FAQ・職人：サイト内部のデータ置き場（単独ページは持たない）。並び順は menu_order。
	$internal = array(
		'spc_price' => array( __( '料金', 'spc' ), __( '料金を追加', 'spc' ), 'dashicons-tag' ),
		'spc_faq'   => array( __( 'よくある質問', 'spc' ), __( '質問を追加', 'spc' ), 'dashicons-editor-help' ),
		'spc_staff' => array( __( '職人', 'spc' ), __( '職人を追加', 'spc' ), 'dashicons-admin-users' ),
	);
	foreach ( $internal as $type => $info ) {
		register_post_type(
			$type,
			array(
				'labels'              => spc_post_type_labels( $info[0], $info[1] ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'menu_icon'           => $info[2],
				'supports'            => array( 'title', 'page-attributes' ),
			)
		);
	}
}
add_action( 'init', 'spc_register_post_types' );

/**
 * 管理画面用の日本語ラベル。
 */
function spc_post_type_labels( string $name, string $add_new ): array {
	return array(
		'name'               => $name,
		'singular_name'      => $name,
		'menu_name'          => $name,
		'all_items'          => sprintf( /* translators: %s: 種類名 */ __( '%sの一覧', 'spc' ), $name ),
		'add_new'            => __( '新規追加', 'spc' ),
		'add_new_item'       => $add_new,
		'edit_item'          => sprintf( /* translators: %s: 種類名 */ __( '%sを編集', 'spc' ), $name ),
		'new_item'           => $add_new,
		'view_item'          => sprintf( /* translators: %s: 種類名 */ __( '%sを見る', 'spc' ), $name ),
		'search_items'       => sprintf( /* translators: %s: 種類名 */ __( '%sを検索', 'spc' ), $name ),
		'not_found'          => __( 'まだ登録がありません。', 'spc' ),
		'not_found_in_trash' => __( 'ゴミ箱は空です。', 'spc' ),
	);
}

/**
 * 4つとも旧エディタ（ブロックエディタ不使用）。
 */
function spc_disable_block_editor( $use, $post_type ) {
	return in_array( $post_type, spc_post_types(), true ) ? false : $use;
}
add_filter( 'use_block_editor_for_post_type', 'spc_disable_block_editor', 10, 2 );

/**
 * このプラグインが扱う投稿タイプ一覧。
 *
 * @return string[]
 */
function spc_post_types(): array {
	return array( 'spc_case', 'spc_price', 'spc_faq', 'spc_staff' );
}

/**
 * タイトル欄の案内文（何を入れるかを平易に示す）。
 */
function spc_enter_title_here( $text, $post ) {
	$hints = array(
		'spc_case'  => __( '事例のタイトル（例：ウールコートの赤ワインのシミ）', 'spc' ),
		'spc_price' => __( '品目名（例：特殊シミ抜き）', 'spc' ),
		'spc_faq'   => __( '質問文', 'spc' ),
		'spc_staff' => __( 'お名前', 'spc' ),
	);
	$type  = $post->post_type ?? '';
	return $hints[ $type ] ?? $text;
}
add_filter( 'enter_title_here', 'spc_enter_title_here', 10, 2 );

/**
 * 管理一覧の列：仮データ印と、料金・FAQの要点。
 */
function spc_register_list_columns(): void {
	foreach ( spc_post_types() as $type ) {
		add_filter( "manage_{$type}_posts_columns", 'spc_list_columns' );
		add_action( "manage_{$type}_posts_custom_column", 'spc_list_column_content', 10, 2 );
	}
}
add_action( 'init', 'spc_register_list_columns' );

function spc_list_columns( array $columns ): array {
	$screen_type = current_filter();
	$extra       = array();
	if ( 'manage_spc_price_posts_columns' === $screen_type ) {
		$extra = array(
			'spc_group'  => __( '区分', 'spc' ),
			'spc_amount' => __( '目安金額', 'spc' ),
		);
	} elseif ( 'manage_spc_faq_posts_columns' === $screen_type ) {
		$extra = array(
			'spc_group' => __( '分類', 'spc' ),
			'spc_top'   => __( 'トップに表示', 'spc' ),
		);
	}
	$columns['spc_placeholder'] = __( '仮', 'spc' );
	return array_merge( $columns, $extra );
}

function spc_list_column_content( string $column, int $post_id ): void {
	$type = get_post_type( $post_id );
	switch ( $column ) {
		case 'spc_placeholder':
			if ( '1' === get_post_meta( $post_id, '_spc_is_placeholder', true ) ) {
				echo esc_html__( '仮', 'spc' );
			}
			break;
		case 'spc_group':
			$key  = 'spc_price' === $type ? 'spc_price_group' : 'spc_faq_group';
			$defs = spc_field_definitions( (string) $type );
			$val  = (string) get_post_meta( $post_id, $key, true );
			echo esc_html( $defs[ $key ]['options'][ $val ] ?? '' );
			break;
		case 'spc_amount':
			$amount = (int) get_post_meta( $post_id, 'spc_amount', true );
			echo esc_html( $amount > 0 ? number_format_i18n( $amount ) . '円' : __( 'お見積り', 'spc' ) );
			break;
		case 'spc_top':
			echo '1' === get_post_meta( $post_id, 'spc_show_on_top', true ) ? esc_html__( '表示', 'spc' ) : '';
			break;
	}
}
