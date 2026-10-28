<?php
/**
 * パンくずリストの項目（表示は template-parts/breadcrumbs.php、構造化データは inc/schema.php の BreadcrumbList）。
 */

defined( 'ABSPATH' ) || exit;

/**
 * 今のページのパンくずの項目。先頭は「ホーム」、最後は今のページ（url は今のページのURL。表示ではリンクにしない）。
 * 固定ページは親をたどり、施工事例の詳細は「施工事例」（一覧）を間に入れる。トップと404では空配列。
 *
 * @return array<int,array{label:string,url:string}>
 */
function spc_breadcrumb_items(): array {
	if ( is_front_page() || is_404() ) {
		return array();
	}

	$items = array( spc_breadcrumb_item( __( 'ホーム', 'spc' ), home_url( '/' ) ) );

	if ( is_post_type_archive( 'spc_case' ) || is_singular( 'spc_case' ) ) {
		$type    = get_post_type_object( 'spc_case' );
		$items[] = spc_breadcrumb_item( $type ? (string) $type->labels->name : __( '施工事例', 'spc' ), (string) get_post_type_archive_link( 'spc_case' ) );
		if ( is_singular( 'spc_case' ) ) {
			$items[] = spc_breadcrumb_post_item( get_queried_object_id() );
		}
		return $items;
	}

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		if ( is_page() ) {
			foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor_id ) {
				$items[] = spc_breadcrumb_post_item( (int) $ancestor_id );
			}
		}
		$items[] = spc_breadcrumb_post_item( $post_id );
		return $items;
	}

	if ( is_search() ) {
		$items[] = spc_breadcrumb_item( __( '検索結果', 'spc' ), get_search_link() );
	} elseif ( is_archive() ) {
		$items[] = spc_breadcrumb_item( get_the_archive_title(), spc_breadcrumb_current_url() );
	} else {
		return array();
	}
	return $items;
}

/**
 * パンくずの1項目（タグを除いた文字）。
 *
 * @return array{label:string,url:string}
 */
function spc_breadcrumb_item( string $label, string $url ): array {
	return array(
		'label' => trim( wp_strip_all_tags( $label ) ),
		'url'   => $url,
	);
}

/**
 * 投稿・固定ページの項目（タイトルとパーマリンク）。
 *
 * @return array{label:string,url:string}
 */
function spc_breadcrumb_post_item( int $post_id ): array {
	return spc_breadcrumb_item( get_the_title( $post_id ), (string) get_permalink( $post_id ) );
}

/**
 * アーカイブの今のURL（分類・投稿タイプ・投稿者。それ以外はリクエストのパス）。
 */
function spc_breadcrumb_current_url(): string {
	$object = get_queried_object();
	if ( $object instanceof WP_Term ) {
		$link = get_term_link( $object );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	} elseif ( $object instanceof WP_Post_Type ) {
		return (string) get_post_type_archive_link( $object->name );
	} elseif ( $object instanceof WP_User ) {
		return get_author_posts_url( $object->ID );
	}
	global $wp;
	return home_url( trailingslashit( (string) ( $wp->request ?? '' ) ) );
}
