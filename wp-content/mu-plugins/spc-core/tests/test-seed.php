<?php
class Test_Seed extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		add_filter( 'spc_is_production', '__return_false' );
	}

	public function tear_down() {
		remove_all_filters( 'spc_is_production' );
		delete_option( 'spc_settings' );
		parent::tear_down();
	}

	private function count_all(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','auto-draft') AND post_status <> 'trash'" );
	}

	/** @return int[] */
	private function seeded_ids(): array {
		return get_posts(
			array(
				'post_type'      => array_merge( spc_post_types(), array( 'page' ) ),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_spc_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	public function test_seed_is_idempotent() {
		$first = spc_seed_run();
		$this->assertGreaterThan( 0, $first['created'] );
		$count = $this->count_all();
		$again = spc_seed_run();
		$this->assertSame( 0, $again['created'] );
		$this->assertSame( 0, $again['updated'] );
		$this->assertSame( $first['created'], $again['skipped'] );
		$this->assertSame( $count, $this->count_all() );
	}

	public function test_all_seeded_posts_are_placeholders() {
		spc_seed_run();
		$ids = $this->seeded_ids();
		$this->assertNotEmpty( $ids );
		foreach ( $ids as $id ) {
			$this->assertSame( '1', get_post_meta( $id, '_spc_is_placeholder', true ), "post $id" );
			$this->assertSame( 'publish', get_post_status( $id ) );
			// 本文が空の固定ページはタイトルに『（仮）』が付かないため、フラグだけで仮データと分かる。
			if ( 'page' !== get_post_type( $id ) || '' !== get_post( $id )->post_content ) {
				$this->assertStringContainsString( '（仮）', get_post( $id )->post_title . get_post( $id )->post_content );
			}
		}
	}

	public function test_seed_creates_three_cases_and_five_top_faqs() {
		spc_seed_run();
		$cases = get_posts( array( 'post_type' => 'spc_case', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
		$this->assertCount( 3, $cases );
		$this->assertSame( array( '仮-001', '仮-002', '仮-003' ), array_map( fn( $p ) => get_post_meta( $p->ID, 'spc_case_no', true ), $cases ) );
		foreach ( $cases as $p ) {
			$this->assertStringStartsWith( '（仮）', $p->post_title );
			$this->assertSame( '', (string) get_post_meta( $p->ID, 'spc_before_id', true ) );
			$this->assertSame( '', (string) get_post_meta( $p->ID, 'spc_after_id', true ) );
		}
		$faqs = get_posts( array( 'post_type' => 'spc_faq', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'meta_key' => 'spc_show_on_top', 'meta_value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$this->assertCount( 5, $faqs );
		$this->assertSame( array( 1, 2, 3, 4, 5 ), array_map( fn( $p ) => $p->menu_order, $faqs ) );
		foreach ( $faqs as $p ) {
			$this->assertSame( '1', get_post_meta( $p->ID, 'spc_show_on_top', true ) );
		}
		$this->assertSame(
			array( 'shipping', 'other', 'payment', 'photo', 'other' ),
			array_map( fn( $p ) => get_post_meta( $p->ID, 'spc_faq_group', true ), $faqs )
		);
		$this->assertCount( 2, get_posts( array( 'post_type' => 'spc_staff', 'posts_per_page' => -1 ) ) );
	}

	public function test_seed_has_faint_remaining_case_and_price_amounts() {
		spc_seed_run();
		$results = array_map( fn( $id ) => get_post_meta( $id, 'spc_result', true ), get_posts( array( 'post_type' => 'spc_case', 'posts_per_page' => -1, 'fields' => 'ids' ) ) );
		$this->assertContains( '薄く残る（目立たない程度）（仮）', $results );
		$prices  = get_posts( array( 'post_type' => 'spc_price', 'posts_per_page' => -1 ) );
		$this->assertCount( 7, $prices );
		$amounts = array_map( fn( $p ) => (int) get_post_meta( $p->ID, 'spc_amount', true ), $prices );
		sort( $amounts );
		$this->assertSame( array( 0, 0, 2000, 3000, 5000, 12000, 15000 ), $amounts );
		foreach ( $prices as $p ) {
			$this->assertStringStartsWith( '（仮）', $p->post_title );
		}
	}

	public function test_seed_creates_fixed_pages_with_children() {
		spc_seed_run();
		foreach ( array( 'stain-removal', 'down-coat', 'price', 'about', 'guide', 'legal' ) as $slug ) {
			$page = get_page_by_path( $slug );
			$this->assertNotNull( $page, $slug );
			$this->assertSame( 'about' === $slug, '' !== $page->post_content, $slug );
		}
		foreach ( array( 'terms' => 1, 'tokushoho' => 2, 'privacy' => 3 ) as $slug => $order ) {
			$this->assertSame( $order, get_page_by_path( "legal/$slug" )->menu_order, $slug );
		}
		$legal = get_page_by_path( 'legal' );
		foreach ( array( 'terms', 'tokushoho', 'privacy' ) as $slug ) {
			$child = get_page_by_path( "legal/$slug" );
			$this->assertNotNull( $child, $slug );
			$this->assertSame( $legal->ID, $child->post_parent );
		}
	}

	public function test_seed_copy_avoids_exaggeration() {
		spc_seed_run();
		foreach ( $this->seeded_ids() as $id ) {
			$text = get_post( $id )->post_title . get_post( $id )->post_content . wp_json_encode( get_post_meta( $id ) );
			foreach ( array( '必ず落ちる', '100%', '完全に元通り', 'どんなシミも', '業界No.1', '!', '！' ) as $ng ) {
				$this->assertStringNotContainsString( $ng, $text, "post $id" );
			}
		}
	}

	public function test_every_seeded_post_is_listed_in_report() {
		update_option(
			'spc_settings',
			array(
				'brand_name'      => '杉本ブランド',
				'line_url'        => 'https://lin.ee/abc123',
				'line_qr_id'      => self::factory()->attachment->create( array( 'post_mime_type' => 'image/png', 'file' => 'line-qr.png' ) ),
				'gtm_id'          => 'GTM-AB12CD',
				'reply_time_note' => '営業時間内24時間以内',
			)
		);
		spc_seed_run();
		$report = spc_placeholder_report();
		$this->assertCount( count( $this->seeded_ids() ), $report );
		$this->assertStringContainsString( '（固定ページ）', implode( "\n", wp_list_pluck( $report, 'label' ) ) );
	}

	public function test_seed_creates_section_faqs_not_on_top() {
		spc_seed_run();
		foreach ( array( 'stain', 'down' ) as $group ) {
			$faqs = get_posts( array( 'post_type' => 'spc_faq', 'posts_per_page' => -1, 'meta_key' => 'spc_faq_group', 'meta_value' => $group ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			$this->assertCount( 2, $faqs, $group );
			foreach ( $faqs as $p ) {
				// チェックボックス項目は '0' が空文字に整えられて保存される（トップに出ない）。
			$this->assertNotSame( '1', get_post_meta( $p->ID, 'spc_show_on_top', true ) );
				$this->assertStringEndsWith( '（仮）', $p->post_content );
			}
		}
		$top = get_posts( array( 'post_type' => 'spc_faq', 'posts_per_page' => -1, 'meta_key' => 'spc_show_on_top', 'meta_value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$this->assertCount( 5, $top );
		$this->assertEquals( 9, wp_count_posts( 'spc_faq' )->publish );
	}

	public function test_seed_sets_case_menus() {
		spc_seed_run();
		foreach ( array( 'case-001' => 'stain', 'case-002' => 'down', 'case-003' => 'yellowing' ) as $key => $menu ) {
			$this->assertSame( $menu, get_post_meta( spc_seed_find( $key ), 'spc_case_menu', true ), $key );
		}
	}

	public function test_seed_backfills_missing_meta_and_legacy_page_content() {
		spc_seed_run();
		$case  = spc_seed_find( 'case-001' );
		$about = spc_seed_find( 'page-about' );
		delete_post_meta( $case, 'spc_case_menu' );
		wp_update_post( array( 'ID' => $about, 'post_content' => '準備中（仮）' ) );
		$result = spc_seed_run();
		$this->assertSame( 0, $result['created'] );
		$this->assertGreaterThanOrEqual( 2, $result['updated'] );
		$this->assertSame( 'stain', get_post_meta( $case, 'spc_case_menu', true ) );
		$this->assertStringContainsString( '有限会社杉本クリーニング商会', get_post( $about )->post_content );
		$this->assertSame( 0, spc_seed_run()['updated'] );
	}

	public function test_seed_does_not_overwrite_edited_content_or_meta() {
		spc_seed_run();
		$case  = spc_seed_find( 'case-001' );
		$about = spc_seed_find( 'page-about' );
		update_post_meta( $case, 'spc_case_menu', 'coat' );
		wp_update_post( array( 'ID' => $about, 'post_content' => '確定した本文' ) );
		spc_seed_run();
		$this->assertSame( 'coat', get_post_meta( $case, 'spc_case_menu', true ) );
		$this->assertSame( '確定した本文', get_post( $about )->post_content );
	}

	public function test_seed_backfill_skips_posts_no_longer_placeholders() {
		spc_seed_run();
		$case  = spc_seed_find( 'case-001' );
		$about = spc_seed_find( 'page-about' );
		delete_post_meta( $case, 'spc_case_menu' );
		wp_update_post( array( 'ID' => $about, 'post_content' => '準備中（仮）' ) );
		delete_post_meta( $case, '_spc_is_placeholder' );
		delete_post_meta( $about, '_spc_is_placeholder' );
		$this->assertSame( 0, spc_seed_run()['updated'] );
		$this->assertFalse( metadata_exists( 'post', $case, 'spc_case_menu' ) );
		$this->assertSame( '準備中（仮）', get_post( $about )->post_content );
	}

	public function test_seed_legal_skeletons_have_headings_and_no_articles() {
		spc_seed_run();
		$privacy = get_page_by_path( 'legal/privacy' )->post_content;
		$this->assertStringContainsString( '<h2>アクセス解析', $privacy );
		$this->assertStringContainsString( '<h2>相談写真の保管期間と削除</h2>', $privacy );
		$this->assertStringNotContainsString( '第1条', $privacy );
		$this->assertStringContainsString( '<table', get_page_by_path( 'legal/tokushoho' )->post_content );
		$this->assertStringContainsString( '<h2>免責</h2>', get_page_by_path( 'legal/terms' )->post_content );
	}

	public function test_launch_check_rows_match_report() {
		spc_seed_run();
		$rows = spc_launch_check_rows();
		$this->assertCount( count( spc_placeholder_report() ), $rows );
		$this->assertSame( array( '種類', '内容', '編集URL' ), array_keys( $rows[0] ) );
	}
}
