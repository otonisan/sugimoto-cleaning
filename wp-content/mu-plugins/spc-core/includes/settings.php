<?php
/**
 * ブランド設定（設定 > ブランド設定）とLINE URLの一元管理。
 */

defined( 'ABSPATH' ) || exit;

// LINE URL 未設定時の仮URL（本番では管理画面で差し替える）。
const SPC_LINE_URL_PLACEHOLDER = 'https://lin.ee/PLACEHOLDER';

/**
 * 設定の初期値。
 */
function spc_settings_defaults(): array {
	return array(
		'brand_name'      => '（仮）ブランド名', // 仮置き：屋号確定後に管理画面で変更
		'line_url'        => SPC_LINE_URL_PLACEHOLDER,
		'line_qr_id'      => 0,
		'gtm_id'          => '',
		'hero_sp_writing' => 'vertical',
		'reply_time_note' => '営業時間内24時間以内（仮）', // 仮置き：運用確定後に変更
	);
}

/**
 * LINE追加URLとして許可できるか（https かつ lin.ee / line.me のホストのみ）。
 */
function spc_is_valid_line_url( string $url ): bool {
	$url = trim( $url );
	if ( 0 !== strpos( $url, 'https://' ) || esc_url_raw( $url, array( 'https' ) ) !== $url ) {
		return false;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return is_string( $host ) && in_array( strtolower( $host ), array( 'lin.ee', 'line.me' ), true );
}

/**
 * 保存前の入力値を検証・整形する。
 */
function spc_settings_sanitize( $input ): array {
	$input = is_array( $input ) ? $input : array();
	// 配列などスカラー以外の値は無かったことにして初期値へ戻す。
	$input    = array_filter( $input, 'is_scalar' );
	$defaults = spc_settings_defaults();
	$out      = $defaults;

	if ( isset( $input['brand_name'] ) ) {
		$name              = sanitize_text_field( (string) $input['brand_name'] );
		$out['brand_name'] = '' !== $name ? $name : $defaults['brand_name'];
	}

	// LINE URL は https の lin.ee / line.me のみ許可。
	$line = isset( $input['line_url'] ) ? trim( (string) $input['line_url'] ) : '';
	if ( spc_is_valid_line_url( $line ) ) {
		$out['line_url'] = esc_url_raw( $line, array( 'https' ) );
	}

	// QR画像は実在する画像添付のみ。
	$qr_id = isset( $input['line_qr_id'] ) ? absint( $input['line_qr_id'] ) : 0;
	if ( $qr_id && ( 'attachment' !== get_post_type( $qr_id ) || 0 !== strpos( (string) get_post_mime_type( $qr_id ), 'image/' ) ) ) {
		$qr_id = 0;
	}
	$out['line_qr_id'] = $qr_id;

	// GTM ID は大文字化して形式一致のみ。
	$gtm = isset( $input['gtm_id'] ) ? strtoupper( trim( (string) $input['gtm_id'] ) ) : '';
	if ( preg_match( '/^GTM-[A-Z0-9]{4,10}$/', $gtm ) ) {
		$out['gtm_id'] = $gtm;
	}

	if ( isset( $input['hero_sp_writing'] ) && in_array( $input['hero_sp_writing'], array( 'vertical', 'horizontal' ), true ) ) {
		$out['hero_sp_writing'] = $input['hero_sp_writing'];
	}

	if ( isset( $input['reply_time_note'] ) ) {
		$note                   = sanitize_text_field( (string) $input['reply_time_note'] );
		$out['reply_time_note'] = '' !== $note ? $note : $defaults['reply_time_note'];
	}

	return $out;
}

/**
 * 設定値を1件取得する。未保存は初期値、未知のキーは ''。
 *
 * @return string|int
 */
function spc_get_setting( string $key ) {
	$defaults = spc_settings_defaults();
	if ( ! array_key_exists( $key, $defaults ) ) {
		return '';
	}
	$saved = get_option( 'spc_settings', array() );
	$value = ( is_array( $saved ) && isset( $saved[ $key ] ) ) ? $saved[ $key ] : $defaults[ $key ];
	return is_int( $defaults[ $key ] ) ? absint( $value ) : (string) $value;
}

/**
 * サイト全体で使うLINE追加URL（唯一の取得口）。
 */
function spc_line_url(): string {
	$url = (string) spc_get_setting( 'line_url' );
	// DB直接更新などで不正な値が入っても、CTAのリンク先は必ずLINEにする。
	return spc_is_valid_line_url( $url ) ? $url : SPC_LINE_URL_PLACEHOLDER;
}

/**
 * 設定画面のメニュー登録。
 */
function spc_register_settings_menu(): void {
	add_options_page(
		__( 'ブランド設定', 'spc' ),
		__( 'ブランド設定', 'spc' ),
		'manage_options',
		'spc-settings',
		'spc_render_settings_page'
	);
}
add_action( 'admin_menu', 'spc_register_settings_menu' );

/**
 * Settings API の登録（セクション・各項目）。
 */
function spc_register_settings(): void {
	register_setting(
		'spc_settings_group',
		'spc_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'spc_settings_sanitize',
			'default'           => spc_settings_defaults(),
		)
	);
	add_settings_section( 'spc_main', '', '__return_empty_string', 'spc-settings' );

	$fields = array(
		'brand_name'      => __( 'ブランド名', 'spc' ),
		'line_url'        => __( 'LINE追加URL', 'spc' ),
		'line_qr_id'      => __( 'LINE QRコード画像', 'spc' ),
		'gtm_id'          => __( 'GTMコンテナID', 'spc' ),
		'hero_sp_writing' => __( 'FVキャッチの組み方（スマホ）', 'spc' ),
		'reply_time_note' => __( '一次返信の目安', 'spc' ),
	);
	foreach ( $fields as $key => $label ) {
		add_settings_field(
			'spc_' . $key,
			$label,
			'spc_render_settings_field',
			'spc-settings',
			'spc_main',
			array(
				'key'       => $key,
				'label_for' => 'spc_' . $key,
			)
		);
	}
}
add_action( 'admin_init', 'spc_register_settings' );

/**
 * 項目ごとの入力欄と説明文を出力する。
 */
function spc_render_settings_field( array $args ): void {
	$key   = $args['key'];
	$id    = 'spc_' . $key;
	$name  = 'spc_settings[' . $key . ']';
	$value = spc_get_setting( $key );
	$help  = '';

	switch ( $key ) {
		case 'brand_name':
			printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			$help = __( 'サイト上部やタイトルに表示する屋号です。確定するまでは『（仮）ブランド名』のままにしてください。', 'spc' );
			break;
		case 'line_url':
			printf( '<input type="url" class="large-text" id="%s" name="%s" value="%s" placeholder="https://lin.ee/...">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			$help = __( 'LINE公式アカウントの友だち追加URL（https://lin.ee/… または https://line.me/…）を貼り付けてください。空欄や形式が違う場合は仮のURLに戻ります。', 'spc' );
			break;
		case 'line_qr_id':
			$image = $value ? wp_get_attachment_image( (int) $value, 'medium', false, array( 'class' => 'spc-media-img' ) ) : '';
			printf(
				'<div data-spc-media><input type="hidden" id="%1$s" name="%2$s" value="%3$d" data-spc-media-input><div data-spc-media-preview style="margin-bottom:8px">%4$s</div><button type="button" class="button" data-spc-media-select data-title="%5$s" data-button="%6$s">%7$s</button> <button type="button" class="button" data-spc-media-remove%8$s>%9$s</button></div>',
				esc_attr( $id ),
				esc_attr( $name ),
				(int) $value,
				wp_kses_post( $image ),
				esc_attr__( 'QRコード画像を選択', 'spc' ),
				esc_attr__( 'この画像を使う', 'spc' ),
				esc_html__( '画像を選択', 'spc' ),
				$value ? '' : ' hidden',
				esc_html__( '削除', 'spc' )
			);
			$help = __( 'パソコンでボタンを押したときに表示するQRコード画像です。LINE公式アカウントの管理画面から保存した画像を選んでください。', 'spc' );
			break;
		case 'gtm_id':
			printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s" placeholder="GTM-XXXXXX">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			$help = __( 'Googleタグマネージャーのコンテナ ID（GTM-から始まる文字列）。未設定の間は計測タグを出力しません。', 'spc' );
			break;
		case 'hero_sp_writing':
			$options = array(
				'vertical'   => __( '縦書き', 'spc' ),
				'horizontal' => __( '横書き', 'spc' ),
			);
			echo '<fieldset id="' . esc_attr( $id ) . '">';
			foreach ( $options as $opt => $label ) {
				printf(
					'<label style="margin-right:16px"><input type="radio" name="%s" value="%s"%s> %s</label>',
					esc_attr( $name ),
					esc_attr( $opt ),
					checked( $value, $opt, false ),
					esc_html( $label )
				);
			}
			echo '</fieldset>';
			$help = __( 'トップ画面の大きな見出しを、スマートフォンで縦書きにするか横書きにするかを選びます。パソコンでは常に縦書きです。', 'spc' );
			break;
		case 'reply_time_note':
			printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			$help = __( '最終CTAの近くに表示する、LINE相談への最初の返信の目安です。', 'spc' );
			break;
	}

	if ( '' !== $help ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
}

/**
 * 設定画面本体。
 */
function spc_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'ブランド設定', 'spc' ) . '</h1>';
	echo '<p>' . esc_html__( 'LINEのURLや計測の設定をまとめて管理する画面です。ここで変えた内容はサイト全体に反映されます。', 'spc' ) . '</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'spc_settings_group' );
	do_settings_sections( 'spc-settings' );
	submit_button();
	echo '</form></div>';
}

/**
 * 設定画面だけメディア選択用のスクリプトを読み込む。
 */
function spc_settings_enqueue( string $hook ): void {
	if ( 'settings_page_spc-settings' !== $hook ) {
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
add_action( 'admin_enqueue_scripts', 'spc_settings_enqueue' );
