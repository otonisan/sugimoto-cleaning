<?php
/**
 * カスタムフィールド（WordPress標準のみ：register_post_meta + メタボックス）。
 * 定義は spc_field_definitions() に集約し、登録・入力欄・保存をそこから自動生成する。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 投稿タイプごとのフィールド定義（表示順どおり）。
 * 末尾の _spc_is_placeholder は全タイプ共通。固定ページ（page）はこのチェックだけを持つ。
 * _spc_seed_key は管理画面に出さないため含めない。
 *
 * @return array<string, array{label:string, type:string, description:string, options?:array}>
 */
function spc_field_definitions( string $post_type ): array {
	$defs = array();

	switch ( $post_type ) {
		case 'spc_case':
			$defs = array(
				'spc_case_no'         => array(
					'label'       => __( '受付番号', 'spc' ),
					'type'        => 'text',
					'description' => __( 'お預かり時の受付番号です。事例カードの左上に『No.』として表示されます。', 'spc' ),
				),
				'spc_item'            => array(
					'label'       => __( '品目', 'spc' ),
					'type'        => 'text',
					'description' => __( '預かった衣類の種類です（例：ウールコート）。', 'spc' ),
				),
				'spc_material'        => array(
					'label'       => __( '素材', 'spc' ),
					'type'        => 'text',
					'description' => __( '生地の素材です（例：ウール100%）。', 'spc' ),
				),
				'spc_stain'           => array(
					'label'       => __( 'シミの種類', 'spc' ),
					'type'        => 'text',
					'description' => __( '何のシミかを書いてください（例：赤ワイン）。', 'spc' ),
				),
				'spc_elapsed'         => array(
					'label'       => __( 'シミが付いてからの期間', 'spc' ),
					'type'        => 'text',
					'description' => __( 'お客様に伺った、シミが付いてからの経過期間です（例：約1年）。', 'spc' ),
				),
				'spc_prior_treatment' => array(
					'label'       => __( '他店での処置', 'spc' ),
					'type'        => 'text',
					'description' => __( '他店で受けた処置があれば書いてください（例：他店で2回洗い、落ちず）。なければ空欄で構いません。', 'spc' ),
				),
				'spc_treatment'       => array(
					'label'       => __( '施工内容', 'spc' ),
					'type'        => 'text',
					'description' => __( '行った作業を簡潔に書いてください（例：部分処理のあと、手作業でシミ抜き）。', 'spc' ),
				),
				'spc_result'          => array(
					'label'       => __( '仕上がり', 'spc' ),
					'type'        => 'text',
					'description' => __( '完全に落ちなかった場合も、そのまま正直に書いてください（例：薄く残る（目立たない程度））。', 'spc' ),
				),
				'spc_days'            => array(
					'label'       => __( '作業日数', 'spc' ),
					'type'        => 'int',
					'description' => __( 'お預かりからお返しまでの日数を数字だけで入力します（例：10）。', 'spc' ),
				),
				'spc_before_id'       => array(
					'label'       => __( 'Before画像（施工前）', 'spc' ),
					'type'        => 'image',
					'description' => __( '同じ角度・同じ明るさで撮った写真を選んでください。シミを消す加工はしないでください。', 'spc' ),
				),
				'spc_after_id'        => array(
					'label'       => __( 'After画像（施工後）', 'spc' ),
					'type'        => 'image',
					'description' => __( '同じ角度・同じ明るさで撮った写真を選んでください。シミを消す加工はしないでください。', 'spc' ),
				),
				'spc_permission_id'   => array(
					'label'       => __( '掲載許諾ID', 'spc' ),
					'type'        => 'text',
					'description' => __( 'お客様から掲載の許可をいただいた記録の番号です。サイトには表示されません。許可のない写真は掲載しないでください。', 'spc' ),
				),
			);
			break;

		case 'spc_price':
			$defs = array(
				'spc_price_group' => array(
					'label'       => __( '区分', 'spc' ),
					'type'        => 'select',
					'options'     => array(
						'stain'     => __( '特殊シミ抜き', 'spc' ),
						'yellowing' => __( '黄ばみ・黒ずみ', 'spc' ),
						'down'      => __( '高級ダウン', 'spc' ),
						'coat'      => __( 'コート', 'spc' ),
						'shipping'  => __( '送料', 'spc' ),
					),
					'description' => __( '料金表のどのグループに入れるかを選びます。', 'spc' ),
				),
				'spc_amount'      => array(
					'label'       => __( '目安金額（円）', 'spc' ),
					'type'        => 'int',
					'description' => __( '数字だけを入力します（例：15000）。0のときはサイトに『お見積り』と表示されます。', 'spc' ),
				),
				'spc_from'        => array(
					'label'       => __( '「〜」を付ける', 'spc' ),
					'type'        => 'bool',
					'description' => __( 'チェックすると『15,000円〜』のように表示します。', 'spc' ),
				),
				'spc_note'        => array(
					'label'       => __( '補足', 'spc' ),
					'type'        => 'text',
					'description' => __( '金額の横に小さく添える一言です（例：税込、状態により変動）。', 'spc' ),
				),
			);
			break;

		case 'spc_faq':
			$defs = array(
				'spc_faq_group'   => array(
					'label'       => __( '分類', 'spc' ),
					'type'        => 'select',
					'options'     => array(
						'shipping' => __( '送料・配送', 'spc' ),
						'price'    => __( '料金', 'spc' ),
						'payment'  => __( 'お支払い', 'spc' ),
						'photo'    => __( '写真相談', 'spc' ),
						'other'    => __( 'その他', 'spc' ),
					),
					'description' => __( 'よくある質問ページで、どの見出しの下に並べるかを選びます。', 'spc' ),
				),
				'spc_show_on_top' => array(
					'label'       => __( 'トップに表示', 'spc' ),
					'type'        => 'bool',
					'description' => __( 'チェックした質問はトップページのよくある質問に表示されます（最大5件）。', 'spc' ),
				),
			);
			break;

		case 'spc_staff':
			$defs = array(
				'spc_role' => array(
					'label'       => __( '肩書き', 'spc' ),
					'type'        => 'text',
					'description' => __( 'お名前の下に表示します（例：染み抜き担当）。', 'spc' ),
				),
				'spc_bio'  => array(
					'label'       => __( '紹介文', 'spc' ),
					'type'        => 'textarea',
					'description' => __( '経歴や仕事への姿勢を数行で書いてください。職人紹介ページに表示されます。', 'spc' ),
				),
			);
			break;

		case 'page':
			break; // 固定ページは「仮データ」のチェックだけ。

		default:
			return array();
	}

	$defs['_spc_is_placeholder'] = array(
		'label'       => __( '仮データ（公開前に必ず差し替え）', 'spc' ),
		'type'        => 'bool',
		'description' => __( '仮の内容のあいだはチェックを入れたままにしてください。本番サイトでは、チェックが入った投稿は公開されません。', 'spc' ),
	);

	return $defs;
}

/**
 * 1件の値を定義の型に合わせて整える。
 *
 * @param mixed $value 入力値。
 * @return string|int
 */
function spc_sanitize_field_value( $value, array $def ) {
	$value = is_scalar( $value ) ? (string) $value : '';

	switch ( $def['type'] ) {
		case 'int':
			// 全角数字は半角に直し、数字だけで構成された値のみ受け付ける（それ以外は 0）。
			$value = trim( function_exists( 'mb_convert_kana' ) ? mb_convert_kana( $value, 'n' ) : $value );
			return preg_match( '/^\d{1,9}$/', $value ) ? (int) $value : 0;
		case 'bool':
			return in_array( $value, array( '1', 'true', 'on' ), true ) ? '1' : '';
		case 'select':
			return array_key_exists( $value, $def['options'] ?? array() ) ? $value : '';
		case 'image':
			$id = absint( $value );
			if ( $id && ( 'attachment' !== get_post_type( $id ) || 0 !== strpos( (string) get_post_mime_type( $id ), 'image/' ) ) ) {
				$id = 0;
			}
			return $id;
		case 'textarea':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * メタキーを登録する（REST非公開・編集権限必須）。固定ページは _spc_is_placeholder（と _spc_seed_key）だけ。
 */
function spc_register_meta_fields(): void {
	foreach ( spc_placeholder_post_types() as $type ) {
		$defs = spc_field_definitions( $type );
		// 初期データ投入用の識別子。管理画面には出さない。
		$defs['_spc_seed_key'] = array(
			'label' => '',
			'type'  => 'text',
		);

		foreach ( $defs as $key => $def ) {
			$data_type = array(
				'int'   => 'integer',
				'image' => 'integer',
			)[ $def['type'] ] ?? 'string';

			register_post_meta(
				$type,
				$key,
				array(
					'type'              => $data_type,
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => static function ( $value ) use ( $def ) {
						return spc_sanitize_field_value( $value, $def );
					},
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'spc_register_meta_fields', 20 );

/**
 * メタボックスを追加する（本体は左、仮データのチェックは右サイドに常時表示）。
 * 固定ページは右サイドの「公開前チェック」だけ（ブロックエディタでも従来のメタボックスとして保存される）。
 */
function spc_add_meta_boxes( string $post_type ): void {
	if ( ! in_array( $post_type, spc_placeholder_post_types(), true ) ) {
		return;
	}
	if ( in_array( $post_type, spc_post_types(), true ) ) {
		add_meta_box( 'spc_fields', __( '詳細情報', 'spc' ), 'spc_render_meta_box', $post_type, 'normal', 'high' );
	}
	add_meta_box( 'spc_placeholder', __( '公開前チェック', 'spc' ), 'spc_render_placeholder_box', $post_type, 'side', 'high' );
}
add_action( 'add_meta_boxes', 'spc_add_meta_boxes' );

/**
 * 1項目分の入力欄（説明文つき）を出力する。
 */
function spc_render_field_input( string $key, array $def, $value ): void {
	$id   = 'spc_meta_' . $key;
	$name = 'spc_meta[' . $key . ']';

	switch ( $def['type'] ) {
		case 'textarea':
			printf( '<textarea class="large-text" rows="5" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
			break;
		case 'int':
			printf( '<input type="number" min="0" class="small-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			break;
		case 'bool':
			printf( '<label><input type="checkbox" id="%s" name="%s" value="1"%s> %s</label>', esc_attr( $id ), esc_attr( $name ), checked( '1', (string) $value, false ), esc_html( $def['label'] ) );
			break;
		case 'select':
			printf( '<select id="%s" name="%s"><option value="">%s</option>', esc_attr( $id ), esc_attr( $name ), esc_html__( '選択してください', 'spc' ) );
			foreach ( $def['options'] as $opt => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $opt ), selected( (string) $value, (string) $opt, false ), esc_html( $label ) );
			}
			echo '</select>';
			break;
		case 'image':
			$image = $value ? wp_get_attachment_image( (int) $value, 'medium', false, array( 'class' => 'spc-media-img' ) ) : '';
			printf(
				'<div data-spc-media><input type="hidden" id="%1$s" name="%2$s" value="%3$d" data-spc-media-input><div data-spc-media-preview style="margin-bottom:8px">%4$s</div><button type="button" class="button" data-spc-media-select data-title="%5$s" data-button="%6$s">%7$s</button> <button type="button" class="button" data-spc-media-remove%8$s>%9$s</button></div>',
				esc_attr( $id ),
				esc_attr( $name ),
				(int) $value,
				wp_kses_post( $image ),
				esc_attr( $def['label'] ),
				esc_attr__( 'この画像を使う', 'spc' ),
				esc_html__( '画像を選択', 'spc' ),
				$value ? '' : ' hidden',
				esc_html__( '削除', 'spc' )
			);
			break;
		default:
			printf( '<input type="text" class="large-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
	}

	if ( '' !== $def['description'] ) {
		echo '<p class="description">' . esc_html( $def['description'] ) . '</p>';
	}
}

/**
 * 保存用の nonce。どちらのメタボックスからも出す（固定ページは右サイドだけのため。同じ値なので重複してよい）。
 */
function spc_render_meta_nonce(): void {
	printf( '<input type="hidden" name="spc_meta_nonce" value="%s">', esc_attr( wp_create_nonce( 'spc_save_meta' ) ) );
}

/**
 * 本体のメタボックス（表形式）。
 */
function spc_render_meta_box( WP_Post $post ): void {
	spc_render_meta_nonce();
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( spc_field_definitions( $post->post_type ) as $key => $def ) {
		if ( '_spc_is_placeholder' === $key ) {
			continue;
		}
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th scope="row">';
		if ( 'bool' !== $def['type'] ) {
			printf( '<label for="%s">%s</label>', esc_attr( 'spc_meta_' . $key ), esc_html( $def['label'] ) );
		} else {
			echo esc_html( $def['label'] );
		}
		echo '</th><td>';
		spc_render_field_input( $key, $def, $value );
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * 右サイドの「公開前チェック」。
 */
function spc_render_placeholder_box( WP_Post $post ): void {
	spc_render_meta_nonce();
	$def = spc_field_definitions( $post->post_type )['_spc_is_placeholder'];
	spc_render_field_input( '_spc_is_placeholder', $def, get_post_meta( $post->ID, '_spc_is_placeholder', true ) );
}

/**
 * メタボックスの保存（save_post_{type}）。
 */
function spc_save_meta_box( int $post_id ): void {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$nonce = isset( $_POST['spc_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['spc_meta_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'spc_save_meta' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$defs = spc_field_definitions( (string) get_post_type( $post_id ) );
	if ( ! $defs ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- 上で検証済み。値は各フィールドの sanitize_callback で整える。
	$posted = isset( $_POST['spc_meta'] ) && is_array( $_POST['spc_meta'] ) ? wp_unslash( $_POST['spc_meta'] ) : array();

	foreach ( $defs as $key => $def ) {
		$value = $posted[ $key ] ?? '';
		// 未チェックのチェックボックスは送信されない：メタごと削除する。
		if ( 'bool' === $def['type'] && '1' !== spc_sanitize_field_value( $value, $def ) ) {
			delete_post_meta( $post_id, $key );
			continue;
		}
		update_post_meta( $post_id, $key, wp_slash( $value ) );
	}
}

/**
 * 4タイプ＋固定ページの保存フック。
 */
function spc_hook_meta_box_saves(): void {
	foreach ( spc_placeholder_post_types() as $type ) {
		add_action( "save_post_{$type}", 'spc_save_meta_box' );
	}
}
add_action( 'init', 'spc_hook_meta_box_saves', 30 );

/**
 * 画像選択のスクリプトは、画像欄を持つ投稿の編集画面だけ読み込む。
 */
function spc_meta_enqueue( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, spc_post_types(), true ) ) {
		return;
	}
	if ( ! in_array( 'image', wp_list_pluck( spc_field_definitions( $screen->post_type ), 'type' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script(
		'spc-admin-media',
		content_url( 'mu-plugins/spc-core/assets/admin-media.js' ),
		array( 'jquery' ),
		'0.1.0',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'spc_meta_enqueue' );
