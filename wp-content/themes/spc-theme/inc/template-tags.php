<?php
/**
 * 表示用の関数（CTA・仮バッジ・仮枠・FV・メニュー・FAQ・ページの種類・お知らせ欄）。
 * spc-core が無くても表示が壊れないよう、function_exists で確かめてから呼ぶ。
 */

defined( 'ABSPATH' ) || exit;

// spc-core が無いときの代替（spc-core の SPC_LINE_URL_PLACEHOLDER と同じ値）。
// TODO(仮): 仮のURL。本物のLINE追加URLは管理画面の「ブランド設定」で設定する（spc-core が有効なら、こちらは使われない）。
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
			<?php // TODO(仮): 文言は依頼元確認後に確定（見出し・説明文） [copy:common.cta-block] ?>
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
 * 料金の表示（15000, true → 「15,000円〜」、0 → 「お見積り」）。
 *
 * @param int  $amount 目安金額（円）。0 以下は未設定として扱う。
 * @param bool $from   true なら末尾に「〜」を付ける。
 */
function spc_format_price( int $amount, bool $from ): string {
	if ( $amount <= 0 ) {
		return __( 'お見積り', 'spc' );
	}
	return number_format( $amount ) . '円' . ( $from ? '〜' : '' );
}

/**
 * 料金の表示文字。「表示用テキスト」があればそれを、なければ目安金額を整形して返す。
 *
 * @param int $post_id 料金（spc_price）の投稿ID。
 */
function spc_price_label( int $post_id ): string {
	$text = trim( (string) get_post_meta( $post_id, 'spc_display_text', true ) );
	if ( '' !== $text ) {
		return $text;
	}
	return spc_format_price( (int) get_post_meta( $post_id, 'spc_amount', true ), '1' === (string) get_post_meta( $post_id, 'spc_from', true ) );
}

/**
 * 料金の仕組み（写真で概算 → 現物を拝見して正式見積 → ご承認後に施工）の3段。
 * LP の料金セクションと料金ページで同じものを使う。
 *
 * @return array<int,array{0:string,1:string}> array( 見出し, 説明文 ) の配列。
 */
function spc_price_steps(): array {
	// TODO(仮): 文言は依頼元確認後に確定（3段階の説明文） [copy:lp.price-steps]
	return array(
		array( __( '写真で概算', 'spc' ), __( '写真から分かる範囲で、料金の目安をお伝えします。', 'spc' ) ),
		array( __( '現物を拝見して正式見積', 'spc' ), __( 'お送りいただいた衣類を職人が確認し、正式な金額をお伝えします。', 'spc' ) ),
		array( __( 'ご承認後に施工', 'spc' ), __( '金額と仕上がりの見込みにご納得いただいてから、施工を始めます。', 'spc' ) ),
	);
}

/**
 * spc-core のクエリ引数（本番では仮データを除外）で WP_Query を作る。
 * 0件、または spc-core が無いときは null を返す（呼び出し側はセクションごと出さない）。
 * ループのあとは wp_reset_postdata() を呼ぶこと。
 *
 * @param string              $post_type 投稿タイプ。
 * @param array<string,mixed> $args      追加の引数。
 */
function spc_lp_query( string $post_type, array $args = array() ): ?WP_Query {
	if ( ! function_exists( 'spc_query_args' ) ) {
		return null;
	}
	$query = new WP_Query( spc_query_args( $post_type, $args + array( 'no_found_rows' => true ) ) );
	return $query->have_posts() ? $query : null;
}

/**
 * よくある質問のクエリ。0件、または spc-core が無いときは null（spc_lp_query と同じ）。
 *
 * @param string[] $groups   分類（spc_faq_group）。空なら全分類。
 * @param bool     $top_only true なら「トップに表示」付きだけ。
 * @param int      $limit    最大件数（-1 で全件）。
 */
function spc_faq_query( array $groups = array(), bool $top_only = false, int $limit = -1 ): ?WP_Query {
	$meta_query = array();
	if ( $groups ) {
		$meta_query[] = array(
			'key'     => 'spc_faq_group',
			'value'   => array_values( array_map( 'strval', $groups ) ),
			'compare' => 'IN',
		);
	}
	if ( $top_only ) {
		// チェックありは '1'。なしは '' か未保存のため、'1' だけを見る。
		$meta_query[] = array(
			'key'   => 'spc_show_on_top',
			'value' => '1',
		);
	}

	$args = array( 'posts_per_page' => $limit );
	if ( $meta_query ) {
		$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	return spc_lp_query( 'spc_faq', $args );
}

/**
 * FAQ の分類（キー => 表示名）。順番は spc-core の選択肢の順。
 *
 * @return array<string,string>
 */
function spc_faq_group_labels(): array {
	$defs    = function_exists( 'spc_field_definitions' ) ? spc_field_definitions( 'spc_faq' ) : array();
	$options = $defs['spc_faq_group']['options'] ?? array();
	return is_array( $options ) ? array_map( 'strval', $options ) : array();
}

/**
 * FAQ を分類ごとにまとめる。キーは分類の選択肢の順、FAQ が無い分類は含めない。
 * 分類が空・選択肢にないものは「その他」（other）に入れる。同じ分類の中は渡された順のまま。
 *
 * @param WP_Post[] $posts FAQ の投稿。
 * @return array<string,WP_Post[]>
 */
function spc_group_faqs( array $posts ): array {
	$keys     = array_keys( spc_faq_group_labels() );
	$fallback = 'other';
	if ( ! in_array( $fallback, $keys, true ) ) {
		$keys[] = $fallback;
	}
	$buckets = array_fill_keys( $keys, array() );
	foreach ( $posts as $post ) {
		$group = (string) get_post_meta( $post->ID, 'spc_faq_group', true );
		if ( ! isset( $buckets[ $group ] ) ) {
			$group = $fallback;
		}
		$buckets[ $group ][] = $post;
	}
	return array_filter( $buckets );
}

/**
 * トップのFAQ（「トップに表示」付き・最大5件）。FAQセクションと構造化データで同じものを使う。
 */
function spc_top_faq_query(): ?WP_Query {
	return spc_faq_query( array(), true, 5 );
}

/**
 * 今のページの種類（<body data-page-type> と計測の page_type。DESIGN.md §6）。
 * lp / service / price / case / about / guide / legal / other のどれか。
 */
function spc_page_type(): string {
	if ( is_front_page() ) {
		return 'lp';
	}
	if ( is_post_type_archive( 'spc_case' ) || is_singular( 'spc_case' ) ) {
		return 'case';
	}
	if ( ! is_page() ) {
		return 'other';
	}
	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return 'other';
	}
	// 規約・法定表示の子ページ（利用規約・特商法・個人情報）。
	if ( $page->post_parent && 'legal' === get_post_field( 'post_name', $page->post_parent ) ) {
		return 'legal';
	}
	$types = array(
		'stain-removal' => 'service',
		'down-coat'     => 'service',
		'price'         => 'price',
		'about'         => 'about',
		'guide'         => 'guide',
		'legal'         => 'legal',
	);
	return $types[ $page->post_name ] ?? 'other';
}

/**
 * 固定ページの本文を「お知らせ・補足」として出すときのHTML（the_content を通したもの）。
 * タグと &nbsp; を除いて文字が残らなければ空文字（欄ごと出さない）。
 */
function spc_page_notes_html( int $post_id ): string {
	$raw = (string) get_post_field( 'post_content', $post_id );
	if ( '' === trim( $raw ) ) {
		return '';
	}
	$html = (string) apply_filters( 'the_content', $raw ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress 標準のフィルタ
	$text = str_replace( array( '&nbsp;', '&#160;', "\u{00A0}" ), '', wp_strip_all_tags( $html ) );
	return '' === trim( $text ) ? '' : $html;
}

/**
 * 「お知らせ・補足」に出す本文があるか。
 */
function spc_has_page_notes( int $post_id ): bool {
	return '' !== spc_page_notes_html( $post_id );
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

/**
 * 前後の施工事例（公開中。本番では仮データを除く）。一覧と同じ並び（新しい順）で、
 * 「前」は1つ古い事例、「次」は1つ新しい事例。無ければ null。
 */
function spc_adjacent_case( int $post_id, bool $previous ): ?WP_Post {
	if ( ! function_exists( 'spc_query_args' ) ) {
		return null;
	}
	$ids = get_posts(
		spc_query_args(
			'spc_case',
			array(
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'no_found_rows'  => true,
			)
		)
	);
	$index = array_search( $post_id, array_map( 'intval', $ids ), true );
	if ( false === $index ) {
		return null;
	}
	$target = $previous ? $index + 1 : $index - 1;
	if ( ! isset( $ids[ $target ] ) ) {
		return null;
	}
	$post = get_post( (int) $ids[ $target ] );
	return $post instanceof WP_Post ? $post : null;
}
