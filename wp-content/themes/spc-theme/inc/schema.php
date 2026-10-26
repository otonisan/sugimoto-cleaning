<?php
/**
 * 構造化データ（JSON-LD）。トップだけに出す。
 * 未確定の情報（住所・電話・評価・口コミ・価格帯）は書かない。確定したら依頼元の確認を取ってから足す。
 */

defined( 'ABSPATH' ) || exit;

/**
 * トップの構造化データ（@graph）を組み立てる。
 *
 * @return array<string,mixed>
 */
function spc_front_page_schema(): array {
	$org_id = home_url( '/#organization' );

	$graph = array(
		array(
			'@type'        => 'Organization',
			'@id'          => $org_id,
			'name'         => '有限会社杉本クリーニング商会',
			'foundingDate' => '1954',
		),
		array(
			'@type'              => 'DryCleaningOrLaundry',
			'@id'                => home_url( '/#business' ),
			'name'               => spc_brand_name(),
			'url'                => home_url( '/' ),
			'parentOrganization' => array( '@id' => $org_id ),
		),
	);

	// FAQPage：ページ上のFAQ（spc_top_faq_query）と同じ質問・回答。本番では仮データは spc_query_args で除外済み。
	$faqs = spc_top_faq_query();
	if ( $faqs ) {
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
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => home_url( '/#faq' ),
			'mainEntity' => $questions,
		);
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
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
 * wp_head に JSON-LD を出す（トップのみ）。
 * JSON_HEX_TAG で「<」「>」を < などにし、本文に「</script>」が入っても script 要素を抜けないようにする。
 */
function spc_output_front_page_schema(): void {
	if ( ! is_front_page() ) {
		return;
	}
	$json = wp_json_encode( spc_front_page_schema(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );
	if ( ! $json ) {
		return;
	}
	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode（JSON_HEX_TAG）で安全にした値
}
add_action( 'wp_head', 'spc_output_front_page_schema' );
