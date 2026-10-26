<?php
/**
 * 表示用の関数（CTA・仮バッジ・仮枠・FV・メニュー）。
 * spc-core が無くても表示が壊れないよう、function_exists で確かめてから呼ぶ。
 */

defined( 'ABSPATH' ) || exit;

// spc-core が無いときの代替（spc-core の SPC_LINE_URL_PLACEHOLDER と同じ値）。
const SPC_THEME_LINE_URL_FALLBACK = 'https://lin.ee/PLACEHOLDER';

// CTA の設置場所（計測の cta_location）と見た目の種類。
const SPC_CTA_LOCATIONS = array( 'header', 'fv', 'steps', 'cases', 'final', 'sticky', 'qr_modal' );
const SPC_CTA_VARIANTS  = array( 'default', 'inverse', 'compact' );

/**
 * ブランド名（ロゴの代わりのテキスト）。
 */
function spc_brand_name(): string {
	$name = function_exists( 'spc_get_setting' ) ? (string) spc_get_setting( 'brand_name' ) : '';
	return '' !== $name ? $name : '（仮）ブランド名'; // TODO(仮): 公開前に差し替え（屋号確定後は管理画面で設定）
}

/**
 * CTA のリンク先（LINE追加URL）。
 */
function spc_theme_line_url(): string {
	return function_exists( 'spc_line_url' ) ? spc_line_url() : SPC_THEME_LINE_URL_FALLBACK;
}

/**
 * CTAボタン「写真を送って無料相談」を出力する。
 *
 * @param string $location 設置場所（header / fv / steps / cases / final / sticky / qr_modal）。
 * @param string $variant  default（墨地）/ inverse（墨地のセクション用の白地）/ compact（ヘッダー用・補足文なし）。
 */
function spc_cta( string $location, string $variant = 'default' ): void {
	if ( ! in_array( $location, SPC_CTA_LOCATIONS, true ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html( sprintf( '未知の CTA 設置場所です: %s', $location ) ), '0.1.0' );
		$location = 'final';
	}
	if ( ! in_array( $variant, SPC_CTA_VARIANTS, true ) ) {
		$variant = 'default';
	}
	get_template_part(
		'template-parts/cta-button',
		null,
		array(
			'location' => $location,
			'variant'  => $variant,
		)
	);
}

/**
 * ページ下部の相談ブロック（見出し・短い説明・CTA）。
 */
function spc_cta_block(): void {
	?>
	<section class="spc-section spc-cta-block" aria-labelledby="spc-cta-block-title" data-sticky-hide>
		<div class="spc-container spc-cta-block__inner">
			<h2 id="spc-cta-block-title" class="spc-cta-block__title"><?php esc_html_e( 'まずは写真でご相談ください', 'spc' ); ?></h2>
			<p class="spc-cta-block__text"><?php esc_html_e( '他店で落ちなかったシミのご相談も承ります。写真を拝見し、落とせる可能性とリスクを職人がお伝えします。', 'spc' ); ?></p>
			<?php spc_cta( 'final' ); ?>
		</div>
	</section>
	<?php
}

/**
 * 仮データの投稿に「仮」バッジを出す。
 *
 * @param int|null $post_id 投稿ID（省略時は現在の投稿）。
 * @param bool     $force   true なら投稿に関係なく出す（仮の金額などに添える用）。
 */
function spc_placeholder_badge( ?int $post_id = null, bool $force = false ): void {
	if ( ! $force ) {
		$post_id = $post_id ?? (int) get_the_ID();
		if ( ! function_exists( 'spc_is_placeholder' ) || ! spc_is_placeholder( $post_id ) ) {
			return;
		}
	}
	get_template_part( 'template-parts/placeholder-badge' );
}

/**
 * 添付ファイルが表示できる画像か（false なら仮枠を出す）。
 */
function spc_has_image( int $attachment_id ): bool {
	return $attachment_id > 0 && wp_attachment_is_image( $attachment_id );
}

/**
 * FVの class。スマホで横書きの設定なら hero--sp-horizontal を付ける（DESIGN.md §3）。
 */
function spc_hero_classes(): string {
	$writing = function_exists( 'spc_get_setting' ) ? (string) spc_get_setting( 'hero_sp_writing' ) : 'vertical';
	return 'horizontal' === $writing ? 'hero hero--sp-horizontal' : 'hero';
}

/**
 * 一次返信の目安（ブランド設定）。spc-core が無いときは空。
 */
function spc_reply_time_note(): string {
	return function_exists( 'spc_get_setting' ) ? (string) spc_get_setting( 'reply_time_note' ) : '';
}

/**
 * 画像、または未設定のときは仮枠を出す。
 *
 * @param int    $attachment_id 添付ファイルID（0なら仮枠）。
 * @param string $size          画像サイズ名（spc-hero / spc-portrait / spc-menu など）。
 * @param string $label         仮枠に書く撮影仕様（例：「職人の顔写真 4:5」）。
 * @param string $ratio         仮枠の縦横比（例：'4/5'）。省略時は 3:2。画像があるときは使わない。
 * @param array  $attr          画像の属性（例：FVの loading / fetchpriority）。仮枠のときは使わない。
 */
function spc_image_or_placeholder( int $attachment_id, string $size, string $label, string $ratio = '', array $attr = array() ): void {
	if ( spc_has_image( $attachment_id ) ) {
		echo wp_get_attachment_image( $attachment_id, $size, false, $attr );
		return;
	}
	get_template_part(
		'template-parts/placeholder-image',
		null,
		array(
			'label' => $label,
			'ratio' => $ratio,
		)
	);
}

/**
 * メニューが未設定のときに使う固定リンク。
 *
 * @return array<int,array{label:string,path:string}>
 */
function spc_default_menu_items( string $location ): array {
	if ( 'footer' === $location ) {
		return array(
			array( 'label' => __( '職人・運営会社', 'spc' ), 'path' => '/about/' ),
			array( 'label' => __( 'ご利用の流れ・よくある質問', 'spc' ), 'path' => '/guide/' ),
			array( 'label' => __( '利用規約', 'spc' ), 'path' => '/legal/terms/' ),
			array( 'label' => __( '特定商取引法に基づく表記', 'spc' ), 'path' => '/legal/tokushoho/' ),
			array( 'label' => __( '個人情報・相談写真の取扱い', 'spc' ), 'path' => '/legal/privacy/' ),
		);
	}
	return array(
		array( 'label' => __( '特殊シミ抜き', 'spc' ), 'path' => '/stain-removal/' ),
		array( 'label' => __( '高級ダウン・コート', 'spc' ), 'path' => '/down-coat/' ),
		array( 'label' => __( '料金', 'spc' ), 'path' => '/price/' ),
		array( 'label' => __( '施工事例', 'spc' ), 'path' => '/cases/' ),
		array( 'label' => __( 'ご利用の流れ', 'spc' ), 'path' => '/guide/#flow' ),
	);
}

/**
 * メニュー位置を出力する（未設定なら固定リンク）。
 */
function spc_nav_menu( string $location ): void {
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => 'spc-nav__list',
			'depth'          => 1,
			'fallback_cb'    => 'spc_nav_menu_fallback',
		)
	);
}

/**
 * wp_nav_menu の fallback_cb：固定リンクを同じ構造で出す。
 *
 * @param array<string,mixed> $args wp_nav_menu の引数。
 */
function spc_nav_menu_fallback( array $args ): void {
	$location = isset( $args['theme_location'] ) ? (string) $args['theme_location'] : 'primary';
	$current  = trailingslashit( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ) ); // 今のURLのパス

	echo '<ul class="spc-nav__list">';
	foreach ( spc_default_menu_items( $location ) as $item ) {
		$is_current = trailingslashit( (string) wp_parse_url( home_url( $item['path'] ), PHP_URL_PATH ) ) === $current && false === strpos( $item['path'], '#' );
		printf(
			'<li class="spc-nav__item"><a href="%s"%s>%s</a></li>',
			esc_url( home_url( $item['path'] ) ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
	}
	echo '</ul>';
}
