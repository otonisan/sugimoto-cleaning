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
	// FAQ は回答を本文（旧エディタ）に、職人は顔写真をアイキャッチに入れる。
	$extra_supports = array(
		'spc_faq'   => array( 'editor' ),
		'spc_staff' => array( 'thumbnail' ),
	);
	foreach ( $internal as $type => $info ) {
		$labels = spc_post_type_labels( $info[0], $info[1] );
		if ( 'spc_staff' === $type ) {
			$labels += array(
				'featured_image'        => __( '顔写真', 'spc' ),
				'set_featured_image'    => __( '顔写真を設定', 'spc' ),
				'remove_featured_image' => __( '顔写真を削除', 'spc' ),
				'use_featured_image'    => __( '顔写真として使う', 'spc' ),
			);
		}
		register_post_type(
			$type,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'menu_icon'           => $info[2],
				'supports'            => array_merge( array( 'title', 'page-attributes' ), $extra_supports[ $type ] ?? array() ),
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
 * 4つの投稿タイプと固定ページは旧エディタ（ブロックエディタ不使用）。
 * 「公開前チェック」の入力欄を持つ種類（spc_placeholder_post_types）と同じ集合にして、判断の元を1つにする。
 * 旧エディタなら入力欄が save_post（公開ガードより前）に保存され、解除と公開を1回の操作で行える。
 */
function spc_disable_block_editor( $use, $post_type ) {
	return in_array( $post_type, spc_placeholder_post_types(), true ) ? false : $use;
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
 * 「仮データ」のチェックを持つ投稿タイプ（4タイプ＋固定ページ）。
 * 公開前チェックの入力欄・一覧の「仮」列・本番の公開ガード・本番の非表示の対象。
 *
 * @return string[]
 */
function spc_placeholder_post_types(): array {
	return array_merge( spc_post_types(), array( 'page' ) );
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
 * FAQ の編集画面：本文欄の上に、何を書く欄かを一行で示す。
 *
 * @param WP_Post $post 編集中の投稿。
 */
function spc_faq_editor_note( $post ): void {
	if ( ! $post instanceof WP_Post || 'spc_faq' !== $post->post_type ) {
		return;
	}
	printf(
		'<p class="description">%s</p>',
		esc_html__( 'ここに回答を書きます。トップページと『よくある質問』ページに表示されます。', 'spc' )
	);
}
add_action( 'edit_form_after_title', 'spc_faq_editor_note' );

/**
 * 管理一覧の列：仮データ印（固定ページにも）と、料金・FAQの要点。
 */
function spc_register_list_columns(): void {
	foreach ( spc_placeholder_post_types() as $type ) {
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
			$text   = trim( (string) get_post_meta( $post_id, 'spc_display_text', true ) );
			$amount = (int) get_post_meta( $post_id, 'spc_amount', true );
			if ( '' !== $text ) {
				echo esc_html( $text );
			} else {
				echo esc_html( $amount > 0 ? number_format_i18n( $amount ) . '円' : __( 'お見積り', 'spc' ) );
			}
			break;
		case 'spc_top':
			echo '1' === get_post_meta( $post_id, 'spc_show_on_top', true ) ? esc_html__( '表示', 'spc' ) : '';
			break;
	}
}
