<?php
/**
 * 構造化データ（JSON-LD）。404以外の全ページに出す。
 * トップ：Organization・DryCleaningOrLaundry・FAQPage。ほかのページ：Organization・BreadcrumbList（＋ページごとの追加）。
 * 未確定の情報（住所・電話・評価・口コミ・価格帯）は書かない。確定したら依頼元の確認を取ってから足す。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 運営会社（全ページ共通）。
 *
 * @return array<string,mixed>
 */
function spc_schema_organization(): array {
	return array(
		'@type'        => 'Organization',
		'@id'          => home_url( '/#organization' ),
		'name'         => '有限会社杉本クリーニング商会',
		'foundingDate' => '1954',
	);
}

/**
 * パンくず（spc_breadcrumb_items と同じ項目）。項目が2つ未満なら null。
 *
 * @return array<string,mixed>|null
 */
function spc_schema_breadcrumb_list(): ?array {
	$items = array_values( spc_breadcrumb_items() );
	if ( count( $items ) < 2 ) {
		return null;
	}
	$elements = array();
	foreach ( $items as $i => $item ) {
		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => spc_schema_text( $item['label'] ),
			'item'     => $item['url'],
		);
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $items[ count( $items ) - 1 ]['url'] . '#breadcrumb',
		'itemListElement' => $elements,
	);
}

/**
 * よくある質問（ページ上のFAQと同じクエリを渡す。本番では仮データは spc_query_args で除外済み）。
 *
 * @param WP_Query $faqs FAQ のクエリ。
 * @param string   $id   ノードの @id（例：home_url( '/#faq' )）。
 * @return array<string,mixed>
 */
function spc_schema_faq_page( WP_Query $faqs, string $id ): array {
	$questions = array();
	foreach ( $faqs->posts as $faq ) {
		$questions[] = array(
			'@type'          => 'Question',
			'name'           => spc_schema_text( get_the_title( $faq ) ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => spc_schema_text( strip_shortcodes( $faq->post_content ) ),
			),
		);
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => $id,
		'mainEntity' => $questions,
	);
}

/**
 * サービス（下層のサービスページ用）。提供者は運営会社。
 *
 * @return array<string,mixed>
 */
function spc_schema_service( string $name, string $description ): array {
	return array(
		'@type'       => 'Service',
		'name'        => spc_schema_text( $name ),
		'description' => spc_schema_text( $description ),
		'provider'    => array( '@id' => home_url( '/#organization' ) ),
	);
}

/**
 * 施工事例の Article。画像は施工後 → 施工前の順で、あるほうのURL。無ければ image は出さない。
 *
 * @return array<string,mixed>
 */
function spc_schema_case_article( WP_Post $post ): array {
	$article = array(
		'@type'            => 'Article',
		'headline'         => spc_schema_text( get_the_title( $post ) ),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		'mainEntityOfPage' => get_permalink( $post ),
	);
	foreach ( array( 'spc_after_id', 'spc_before_id' ) as $key ) {
		$image_id = absint( get_post_meta( $post->ID, $key, true ) );
		if ( spc_has_image( $image_id ) ) {
			$url = wp_get_attachment_image_url( $image_id, 'full' );
			if ( $url ) {
				$article['image'] = $url;
				break;
			}
		}
	}
	return $article;
}

/**
 * トップの @graph（運営会社・ブランド・FAQ）。
 *
 * @return array<int,array<string,mixed>>
 */
function spc_schema_front_graph(): array {
	$graph = array(
		spc_schema_organization(),
		array(
			'@type'              => 'DryCleaningOrLaundry',
			'@id'                => home_url( '/#business' ),
			'name'               => spc_brand_name(),
			'url'                => home_url( '/' ),
			'parentOrganization' => array( '@id' => home_url( '/#organization' ) ),
		),
	);

	// FAQPage：ページ上のFAQ（spc_top_faq_query）と同じ質問・回答。
	$faqs = spc_top_faq_query();
	if ( $faqs ) {
		$graph[] = spc_schema_faq_page( $faqs, home_url( '/#faq' ) );
	}
	return $graph;
}

/**
 * 今のページの @graph（404 では空）。
 *
 * @return array<int,array<string,mixed>>
 */
function spc_schema_graph(): array {
	if ( is_404() ) {
		return array();
	}
	if ( is_front_page() ) {
		return spc_schema_front_graph();
	}

	$graph      = array( spc_schema_organization() );
	$breadcrumb = spc_schema_breadcrumb_list();
	if ( $breadcrumb ) {
		$graph[] = $breadcrumb;
	}

	// ---- ページごとの追加（Task 4〜9 がここに1行ずつ足す） ----------------------
	// spc_page_type() と固定ページのスラッグで分ける。例：
	// if ( 'service' === $page_type && 'stain-removal' === $slug ) { $graph[] = spc_schema_service( …, … ); }
	$page_type = spc_page_type();
	$slug      = is_page() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
	if ( 'service' === $page_type ) {
		$service_pages = array(
			// TODO(仮): 文言は依頼元確認後に確定（Service の説明文） [copy:stain.schema]
			'stain-removal' => array( '特殊シミ抜き', '他店で落ちなかったシミ、時間がたったシミなどのご相談を、写真で承ります。', 'stain' ),
			// TODO(仮): 文言は依頼元確認後に確定（Service の説明文） [copy:down.schema]
			'down-coat'     => array( '高級ダウン・コートのクリーニング', '高級ダウンやコート、ジャケットのお手入れを、素材や装飾、洗濯表示を確認したうえでご相談いただけます。', 'down' ),
		);
		if ( isset( $service_pages[ $slug ] ) ) {
			list( $service_name, $service_text, $faq_group ) = $service_pages[ $slug ];
			$service               = spc_schema_service( $service_name, $service_text );
			$service['areaServed'] = 'JP';
			$graph[]               = $service;
			$service_faqs          = spc_faq_query( array( $faq_group ) );
			if ( $service_faqs ) {
				$graph[] = spc_schema_faq_page( $service_faqs, get_permalink( get_queried_object_id() ) . '#faq' );
			}
		}
	}
	if ( 'about' === $page_type ) {
		// 職人ごとの Person（ページの職人一覧と同じクエリ）。住所・電話は書かない。
		$staff = spc_lp_query( 'spc_staff', array( 'posts_per_page' => -1 ) );
		if ( $staff ) {
			foreach ( $staff->posts as $member ) {
				$person = array(
					'@type'    => 'Person',
					'name'     => spc_schema_text( get_the_title( $member ) ),
					'worksFor' => array( '@id' => home_url( '/#organization' ) ),
				);
				$role   = spc_schema_text( (string) get_post_meta( $member->ID, 'spc_role', true ) );
				if ( '' !== $role ) {
					$person['jobTitle'] = $role;
				}
				$graph[] = $person;
			}
		}
	}
	if ( 'case' === $page_type && is_singular( 'spc_case' ) ) {
		$graph[] = spc_schema_case_article( get_queried_object() );
	}
	// ---- ページごとの追加ここまで ------------------------------------------------

	return $graph;
}

/**
 * トップの構造化データ（@context と @graph）。互換のため残す（トップでは spc_schema_graph と同じ内容）。
 *
 * @return array<string,mixed>
 */
function spc_front_page_schema(): array {
	return array(
		'@context' => 'https://schema.org',
		'@graph'   => spc_schema_front_graph(),
	);
}

/**
 * タグを除き、文字参照を戻した1行のテキストにする。
 */
function spc_schema_text( string $html ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * wp_head に JSON-LD を出す（404以外の全ページ）。
 * JSON_HEX_TAG で「<」「>」を < などにし、本文に「</script>」が入っても script 要素を抜けないようにする。
 */
function spc_output_schema(): void {
	$graph = spc_schema_graph();
	if ( ! $graph ) {
		return;
	}
	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
	);
	if ( ! $json ) {
		return;
	}
	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode（JSON_HEX_TAG）で安全にした値
}
add_action( 'wp_head', 'spc_output_schema' );
