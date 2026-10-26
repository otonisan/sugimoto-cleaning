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
			$this->assertStringContainsString( '（仮）', get_post( $id )->post_title . get_post( $id )->post_content );
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
		$faqs = get_posts( array( 'post_type' => 'spc_faq', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
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
			$this->assertSame( '準備中（仮）', $page->post_content );
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
		update_option( 'spc_settings', array( 'brand_name' => '杉本ブランド', 'line_url' => 'https://lin.ee/abc123', 'gtm_id' => 'GTM-AB12CD' ) );
		spc_seed_run();
		$report = spc_placeholder_report();
		$this->assertCount( count( $this->seeded_ids() ), $report );
		$this->assertStringContainsString( '（固定ページ）', implode( "\n", wp_list_pluck( $report, 'label' ) ) );
	}

	public function test_launch_check_rows_match_report() {
		spc_seed_run();
		$rows = spc_launch_check_rows();
		$this->assertCount( count( spc_placeholder_report() ), $rows );
		$this->assertSame( array( '種類', '内容', '編集URL' ), array_keys( $rows[0] ) );
	}
}
