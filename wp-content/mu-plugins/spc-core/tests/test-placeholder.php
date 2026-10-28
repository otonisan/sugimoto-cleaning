<?php
class Test_Placeholder extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		// 既定では必要な固定ページが揃っている状態にする（欠けている場合のテストは個別に作る）。
		$this->create_required_pages();
	}

	public function tear_down() {
		remove_all_filters( 'spc_is_production' );
		delete_option( 'spc_settings' );
		parent::tear_down();
	}

	private function production(): void {
		add_filter( 'spc_is_production', '__return_true' );
	}

	private function good_settings( array $override = array() ): void {
		update_option(
			'spc_settings',
			$override + array(
				'brand_name'      => '杉本ブランド',
				'line_url'        => 'https://lin.ee/abc123',
				'line_qr_id'      => self::factory()->attachment->create( array( 'post_mime_type' => 'image/png', 'file' => 'line-qr.png' ) ),
				'gtm_id'          => 'GTM-AB12CD',
				'reply_time_note' => '営業時間内24時間以内',
			)
		);
	}

	private function admin(): int {
		$id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $id );
		return $id;
	}

	public function test_is_production_follows_environment_and_filter() {
		$this->assertSame( 'production' === wp_get_environment_type(), spc_is_production() );
		$this->production();
		$this->assertTrue( spc_is_production() );
		remove_all_filters( 'spc_is_production' );
		add_filter( 'spc_is_production', '__return_false' );
		$this->assertFalse( spc_is_production() );
	}

	public function test_is_placeholder_accepts_id_and_post() {
		$id = self::factory()->post->create( array( 'post_type' => 'spc_case' ) );
		$this->assertFalse( spc_is_placeholder( $id ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		$this->assertTrue( spc_is_placeholder( $id ) );
		$this->assertTrue( spc_is_placeholder( get_post( $id ) ) );
		$this->assertFalse( spc_is_placeholder( 999999 ) );
	}

	public function test_production_reverts_placeholder_publish_to_draft() {
		$this->production();
		$id = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'draft' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	public function test_non_production_allows_placeholder_publish() {
		add_filter( 'spc_is_production', '__return_false' );
		$id = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'draft' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	public function test_marking_published_post_as_placeholder_in_production_unpublishes() {
		$this->production();
		$id = self::factory()->post->create( array( 'post_type' => 'spc_faq', 'post_status' => 'publish' ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' ); // 追加の経路
		$this->assertSame( 'draft', get_post_status( $id ) );

		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		delete_post_meta( $id, '_spc_is_placeholder' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
		update_post_meta( $id, '_spc_is_placeholder', '0' );
		update_post_meta( $id, '_spc_is_placeholder', '1' ); // 更新の経路
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	public function test_insert_with_placeholder_meta_input_ends_as_draft() {
		$this->production();
		$id = self::factory()->post->create(
			array( 'post_type' => 'spc_price', 'post_status' => 'publish', 'meta_input' => array( '_spc_is_placeholder' => '1' ) )
		);
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	public function test_other_post_types_are_untouched() {
		$this->production();
		$id = self::factory()->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	public function test_revert_reattaches_hooks_and_notifies_once() {
		$this->production();
		$uid = $this->admin();
		$id  = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_title' => '赤ワイン', 'post_status' => 'draft' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertNotFalse( has_action( 'wp_after_insert_post', 'spc_guard_after_insert_post' ) );
		$this->assertNotFalse( has_action( 'added_post_meta', 'spc_guard_on_meta' ) );
		$this->assertNotFalse( has_action( 'updated_post_meta', 'spc_guard_on_meta' ) );
		$this->assertSame( array( '赤ワイン' ), get_transient( 'spc_placeholder_notice_' . $uid ) );
	}

	public function test_reverted_publish_redirects_with_draft_message_instead_of_published() {
		$this->production();
		$this->admin();
		$fake = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'draft' ) );
		$real = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'draft' ) );
		update_post_meta( $fake, '_spc_is_placeholder', '1' );
		wp_update_post( array( 'ID' => $fake, 'post_status' => 'publish' ) );
		wp_update_post( array( 'ID' => $real, 'post_status' => 'publish' ) );
		$this->assertSame( 'draft', get_post_status( $fake ) );

		$location = admin_url( 'post.php?post=' . $fake . '&action=edit&message=6' );
		$filtered = apply_filters( 'redirect_post_location', $location, $fake );
		$this->assertStringContainsString( 'message=10', $filtered ); // 「下書きを更新しました」
		$this->assertStringNotContainsString( 'message=6', $filtered );
		$this->assertStringContainsString( 'post=' . $fake, $filtered );

		// 下書きに戻していない投稿の「公開しました」はそのまま。
		$location = admin_url( 'post.php?post=' . $real . '&action=edit&message=6' );
		$this->assertSame( $location, apply_filters( 'redirect_post_location', $location, $real ) );
	}

	public function test_notice_renders_once_then_clears() {
		$uid = $this->admin();
		set_transient( 'spc_placeholder_notice_' . $uid, array( '<b>事例</b>' ), 60 );
		ob_start();
		spc_render_placeholder_notice();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( '『&lt;b&gt;事例&lt;/b&gt;』は仮データのため、本番サイトでは公開できません。下書きに戻しました。内容を差し替えて『仮データ』のチェックを外してから公開してください。', $html );
		$this->assertFalse( get_transient( 'spc_placeholder_notice_' . $uid ) );
		ob_start();
		spc_render_placeholder_notice();
		$this->assertSame( '', ob_get_clean() );
	}

	public function test_query_args_exclude_placeholder_only_in_production() {
		add_filter( 'spc_is_production', '__return_false' );
		$real = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'publish' ) );
		$fake = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'publish' ) );
		$zero = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'publish' ) );
		update_post_meta( $fake, '_spc_is_placeholder', '1' );
		update_post_meta( $zero, '_spc_is_placeholder', '0' );

		$this->assertCount( 3, get_posts( spc_query_args( 'spc_case', array( 'posts_per_page' => -1 ) ) ) );

		remove_all_filters( 'spc_is_production' );
		$this->production();
		$ids = wp_list_pluck( get_posts( spc_query_args( 'spc_case', array( 'posts_per_page' => -1 ) ) ), 'ID' );
		sort( $ids );
		$this->assertSame( array( $real, $zero ), $ids );
	}

	public function test_query_args_keep_caller_meta_query_and_set_default_order() {
		$this->production();
		$args = spc_query_args( 'spc_case', array( 'meta_query' => array( array( 'key' => 'x', 'value' => '1' ) ) ) );
		$this->assertSame( 'date', $args['orderby'] );
		$this->assertSame( 'DESC', $args['order'] );
		$this->assertSame( 'spc_case', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 'AND', $args['meta_query']['relation'] );
		$this->assertSame( 'x', $args['meta_query'][0][0]['key'] );

		$faq = spc_query_args( 'spc_faq', array( 'order' => 'DESC' ) );
		$this->assertSame( 'menu_order', $faq['orderby'] );
		$this->assertSame( 'DESC', $faq['order'] );
		$this->assertSame( 'ASC', spc_query_args( 'spc_faq' )['order'] );
	}

	public function test_query_args_orders_faq_by_menu_order() {
		$b = self::factory()->post->create( array( 'post_type' => 'spc_faq', 'menu_order' => 2 ) );
		$a = self::factory()->post->create( array( 'post_type' => 'spc_faq', 'menu_order' => 1 ) );
		$this->assertSame( array( $a, $b ), wp_list_pluck( get_posts( spc_query_args( 'spc_faq', array( 'posts_per_page' => -1 ) ) ), 'ID' ) );
	}

	public function test_report_lists_all_issue_types() {
		delete_option( 'spc_settings' );
		$types = wp_list_pluck( spc_placeholder_report(), 'type' );
		foreach ( array( 'line_url', 'line_qr', 'brand_name', 'gtm_id', 'reply_time_note' ) as $t ) {
			$this->assertContains( $t, $types );
		}
		foreach ( spc_placeholder_report() as $item ) {
			$this->assertStringContainsString( 'page=spc-settings', $item['edit_url'] );
		}
	}

	/** 必要な固定ページ9ページを、仮データではない通常の固定ページとして作る。 */
	private function create_required_pages(): void {
		foreach ( spc_required_pages() as $path => $title ) {
			$parts  = explode( '/', $path );
			$slug   = array_pop( $parts );
			$parent = $parts ? get_page_by_path( implode( '/', $parts ) ) : null;
			self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => $slug,
					'post_parent' => $parent ? $parent->ID : 0,
				)
			);
		}
	}

	public function test_report_flags_missing_required_page() {
		$this->good_settings();
		$page = get_page_by_path( 'legal/privacy' );
		wp_trash_post( $page->ID );
		$missing = array_values( array_filter( spc_placeholder_report(), fn( $i ) => 'page_missing' === $i['type'] ) );
		$this->assertCount( 1, $missing );
		$this->assertStringContainsString( '/legal/privacy/', $missing[0]['label'] );
		$this->assertStringContainsString( '個人情報・相談写真の取扱い', $missing[0]['label'] );
		$this->assertSame( admin_url( 'edit.php?post_type=page' ), $missing[0]['edit_url'] );

		// ゴミ箱から戻してスラッグを変えると、同じパスでは見つからない。
		wp_untrash_post( $page->ID );
		wp_update_post(
			array(
				'ID'          => $page->ID,
				'post_status' => 'publish',
				'post_name'   => 'privacy-2',
			)
		);
		$missing = array_filter( spc_placeholder_report(), fn( $i ) => 'page_missing' === $i['type'] );
		$this->assertCount( 1, $missing );
	}

	public function test_report_has_no_page_missing_when_all_pages_exist() {
		$this->good_settings();
		$this->assertSame( array(), array_filter( spc_placeholder_report(), fn( $i ) => 'page_missing' === $i['type'] ) );
		$this->assertSame( array( 'stain-removal', 'down-coat', 'price', 'about', 'guide', 'legal', 'legal/terms', 'legal/tokushoho', 'legal/privacy' ), array_keys( spc_required_pages() ) );
	}

	public function test_report_empty_when_everything_set() {
		$this->good_settings();
		$this->assertSame( array(), spc_placeholder_report() );
	}

	public function test_report_includes_placeholder_posts_with_labels() {
		$this->good_settings();
		$this->admin();
		$id = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_title' => '（仮）ウールコートの赤ワインのシミ', 'post_status' => 'draft' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		$report = spc_placeholder_report();
		$this->assertCount( 1, $report );
		$this->assertSame( 'post', $report[0]['type'] );
		$this->assertSame( '仮データの投稿：（仮）ウールコートの赤ワインのシミ（施工事例）', $report[0]['label'] );
		$this->assertStringContainsString( 'post=' . $id, $report[0]['edit_url'] );
	}

	public function test_report_includes_placeholder_pages_but_not_plain_pages() {
		$this->good_settings();
		$this->admin();
		self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => '通常ページ' ) );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => '料金・送料' ) );
		update_post_meta( $page, '_spc_is_placeholder', '1' );
		$report = spc_placeholder_report();
		$this->assertCount( 1, $report );
		$this->assertSame( '仮データの投稿：料金・送料（固定ページ）', $report[0]['label'] );
		$this->assertStringContainsString( 'post=' . $page, $report[0]['edit_url'] );
	}

	public function test_publish_guard_reverts_placeholder_pages_too() {
		$this->production();
		$id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		$this->assertSame( 'draft', get_post_status( $id ) );

		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		$this->assertSame( 'draft', get_post_status( $id ) );

		delete_post_meta( $id, '_spc_is_placeholder' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	public function test_production_placeholder_page_is_404_and_plain_page_is_not() {
		add_filter( 'spc_is_production', '__return_false' );
		$fake = self::factory()->post->create( array( 'post_type' => 'page', 'post_name' => 'price', 'post_status' => 'publish' ) );
		update_post_meta( $fake, '_spc_is_placeholder', '1' );
		$real = self::factory()->post->create( array( 'post_type' => 'page', 'post_name' => 'about', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $fake ) );
		$this->assertTrue( is_page( $fake ) );

		remove_all_filters( 'spc_is_production' );
		$this->production();
		$this->go_to( get_permalink( $fake ) );
		$this->assertTrue( is_404() );
		$this->go_to( get_permalink( $real ) );
		$this->assertTrue( is_page( $real ) );
		$this->assertSame( array( $real ), wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	}

	public function test_production_page_guard_leaves_admin_alone() {
		$this->production();
		set_current_screen( 'edit-page' );
		$q = new WP_Query();
		$q->parse_query( array( 'post_type' => 'page' ) );
		$GLOBALS['wp_the_query'] = $q; // 管理画面のメインクエリとして扱う
		spc_exclude_placeholder_from_main_query( $q );
		$this->assertEmpty( $q->get( 'meta_query' ) );
		set_current_screen( 'front' );
	}

	private function placeholder_case( string $title ): int {
		add_filter( 'spc_is_production', '__return_false' );
		$id = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_title' => $title, 'post_status' => 'publish' ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		remove_all_filters( 'spc_is_production' );
		return $id;
	}

	public function test_production_front_archive_hides_published_placeholder_cases() {
		$fake = $this->placeholder_case( '（仮）事例' );
		$real = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'publish' ) );

		add_filter( 'spc_is_production', '__return_false' );
		$this->go_to( get_post_type_archive_link( 'spc_case' ) );
		$this->assertEqualSets( array( $fake, $real ), wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );

		remove_all_filters( 'spc_is_production' );
		$this->production();
		$this->go_to( get_post_type_archive_link( 'spc_case' ) );
		$this->assertSame( array( $real ), wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	}

	public function test_production_placeholder_case_single_is_404() {
		$fake = $this->placeholder_case( '（仮）事例' );
		$this->production();
		$this->go_to( get_permalink( $fake ) );
		$this->assertTrue( is_404() );

		remove_all_filters( 'spc_is_production' );
		add_filter( 'spc_is_production', '__return_false' );
		$this->go_to( get_permalink( $fake ) );
		$this->assertTrue( is_singular( 'spc_case' ) );
	}

	public function test_production_guard_leaves_secondary_queries_alone() {
		$fake = $this->placeholder_case( '（仮）事例' );
		$this->production();
		$q = new WP_Query( array( 'post_type' => 'spc_case', 'fields' => 'ids' ) );
		$this->assertSame( array( $fake ), $q->posts );
	}

	public function test_production_sitemap_query_args_exclude_placeholders() {
		$fake = $this->placeholder_case( '（仮）事例' );
		$real = self::factory()->post->create( array( 'post_type' => 'spc_case', 'post_status' => 'publish' ) );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_post_meta( $page, '_spc_is_placeholder', '1' );

		$ids_for = function ( string $type ): array {
			$args = apply_filters( 'wp_sitemaps_posts_query_args', array( 'post_type' => $type, 'fields' => 'ids', 'posts_per_page' => -1, 'post_status' => 'publish' ), $type );
			return get_posts( $args );
		};

		add_filter( 'spc_is_production', '__return_false' );
		$this->assertContains( $fake, $ids_for( 'spc_case' ) );
		remove_all_filters( 'spc_is_production' );

		$this->production();
		$cases = $ids_for( 'spc_case' );
		$this->assertContains( $real, $cases );
		$this->assertNotContains( $fake, $cases );
		$this->assertNotContains( $page, $ids_for( 'page' ) );
	}

	public function test_report_flags_brand_name_with_fullwidth_marker_only() {
		$this->good_settings( array( 'brand_name' => '（仮）名' ) );
		$this->assertSame( array( 'brand_name' ), wp_list_pluck( spc_placeholder_report(), 'type' ) );
		$this->assertSame( 'ブランド名に『（仮）』が含まれています', spc_placeholder_report()[0]['label'] );
	}

	public function test_report_flags_missing_or_non_image_line_qr() {
		$this->good_settings( array( 'line_qr_id' => 0 ) );
		$report = spc_placeholder_report();
		$this->assertSame( array( 'line_qr' ), wp_list_pluck( $report, 'type' ) );
		$this->assertSame( 'LINE QRコード画像が未設定です', $report[0]['label'] );
		$this->assertStringContainsString( 'page=spc-settings', $report[0]['edit_url'] );

		// DBに直接、画像でない添付や存在しないIDが入っていても未設定として扱う。
		$pdf = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf', 'file' => 'qr.pdf' ) );
		$this->good_settings( array( 'line_qr_id' => $pdf ) );
		$this->assertSame( array( 'line_qr' ), wp_list_pluck( spc_placeholder_report(), 'type' ) );
		$this->good_settings( array( 'line_qr_id' => 999999 ) );
		$this->assertSame( array( 'line_qr' ), wp_list_pluck( spc_placeholder_report(), 'type' ) );
	}

	public function test_report_flags_reply_time_note_with_placeholder_marker() {
		$this->good_settings( array( 'reply_time_note' => '営業時間内24時間以内（仮）' ) );
		$report = spc_placeholder_report();
		$this->assertSame( array( 'reply_time_note' ), wp_list_pluck( $report, 'type' ) );
		$this->assertSame( '一次返信の目安が仮のままです', $report[0]['label'] );
		$this->assertStringContainsString( 'page=spc-settings', $report[0]['edit_url'] );

		delete_option( 'spc_settings' ); // 初期値は（仮）付き
		$this->assertContains( 'reply_time_note', wp_list_pluck( spc_placeholder_report(), 'type' ) );
	}

	public function test_report_filter_can_add_items_and_drops_malformed_ones() {
		$this->good_settings();
		add_filter(
			'spc_placeholder_report',
			static function ( $items ) {
				$items[] = array( 'type' => 'theme_image', 'label' => 'トップ画像がまだ仮の枠です', 'edit_url' => '' );
				$items[] = 'broken';
				$items[] = array( 'type' => 'x' ); // label なし
				return $items;
			}
		);
		$this->assertSame(
			array( array( 'type' => 'theme_image', 'label' => 'トップ画像がまだ仮の枠です', 'edit_url' => '' ) ),
			spc_placeholder_report()
		);
	}

	public function test_site_health_lists_items_without_edit_url_as_plain_text() {
		$this->good_settings();
		add_filter(
			'spc_placeholder_report',
			static function ( $items ) {
				$items[] = array( 'type' => 'theme_image', 'label' => '<b>作業風景</b>の写真がまだ仮の枠です', 'edit_url' => '' );
				return $items;
			}
		);
		$html = spc_site_health_placeholder()['description'];
		$this->assertStringContainsString( '<li>&lt;b&gt;作業風景&lt;/b&gt;の写真がまだ仮の枠です</li>', $html );
		$this->assertStringNotContainsString( 'href=""', $html );
	}

	public function test_theme_registers_its_placeholder_images() {
		$file = get_theme_root() . '/spc-theme/inc/placeholders.php';
		if ( ! file_exists( $file ) ) {
			$this->markTestSkipped( 'spc-theme が見つかりません。' );
		}
		require_once $file;
		add_filter( 'spc_placeholder_report', 'spc_theme_placeholder_report' ); // フックは前のテストの後始末で外れることがある
		remove_filter( 'spc_placeholder_report', 'spc_theme_placeholder_copy_report' ); // 仮の文章は test-theme-subpages.php で確かめる
		$this->good_settings();

		$images = spc_theme_placeholder_images();
		// 下層ページの写真枠は後ろに足されていく。ここでは数を決め打ちしない。
		$this->assertSame( array( 'hero', 'menu_stain', 'menu_yellowing', 'menu_down', 'menu_coat', 'workshop' ), array_slice( array_keys( $images ), 0, 6 ) );
		$this->assertContains( 'stain_work', array_keys( $images ) );
		$this->assertContains( 'stain_yellowing', array_keys( $images ) );

		$report = spc_placeholder_report();
		$this->assertCount( count( $images ), $report );
		$this->assertSame( array( 'theme_image' ), array_values( array_unique( wp_list_pluck( $report, 'type' ) ) ) );
		$this->assertSame( 'トップ画像（繊維マクロ写真）がまだ仮の枠です（制作側でテーマを差し替え：template-parts/lp/hero.php）', $report[0]['label'] );
		$this->assertSame( '', $report[0]['edit_url'] );

		// 画像IDが入ったものは出なくなる。
		$img = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg', 'file' => 'hero.jpg' ) );
		add_filter(
			'spc_theme_placeholder_images',
			static function ( $list ) use ( $img ) {
				$list['hero']['attachment_id'] = $img;
				return $list;
			}
		);
		$this->assertSame( $img, spc_theme_image_id( 'hero' ) );
		$this->assertCount( count( $images ) - 1, spc_placeholder_report() );
		$this->assertSame( 0, spc_theme_image_id( 'unknown' ) );
	}

	public function test_site_health_status_by_environment() {
		add_filter( 'spc_is_production', '__return_false' );
		delete_option( 'spc_settings' );
		$this->assertSame( 'recommended', spc_site_health_placeholder()['status'] );
		remove_all_filters( 'spc_is_production' );
		$this->production();
		$r = spc_site_health_placeholder();
		$this->assertSame( 'critical', $r['status'] );
		$this->assertStringContainsString( 'LINE追加URLが仮のままです', $r['description'] );
		$this->assertStringContainsString( 'page=spc-settings', $r['description'] );
		$this->good_settings();
		$this->assertSame( 'good', spc_site_health_placeholder()['status'] );
	}

	public function test_site_health_test_is_registered_as_direct() {
		$tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$this->assertSame( 'spc_site_health_placeholder', $tests['direct']['spc_placeholder']['test'] );
		$this->assertSame( '公開前チェック（仮データ）', $tests['direct']['spc_placeholder']['label'] );
	}

	private function bar(): WP_Admin_Bar {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$bar = new WP_Admin_Bar();
		spc_admin_bar_placeholder( $bar );
		return $bar;
	}

	public function test_admin_bar_node_only_when_items_and_capability() {
		delete_option( 'spc_settings' ); // 5件（LINE URL・QR・ブランド名・GTM・返信目安）
		$this->admin();
		$node = $this->bar()->get_node( 'spc-placeholder' );
		$this->assertNotNull( $node );
		$this->assertSame( '仮データ 5件', $node->title );
		$this->assertSame( admin_url( 'site-health.php' ), $node->href ); // 依頼元も開けて、項目の一覧が見られる

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertNull( $this->bar()->get_node( 'spc-placeholder' ) );

		$this->admin();
		$this->good_settings();
		$this->assertNull( $this->bar()->get_node( 'spc-placeholder' ) );
	}
}
