<?php
class Test_PostTypes extends WP_UnitTestCase {
	public function test_case_post_type_is_public_with_cases_slug() {
		$pt = get_post_type_object( 'spc_case' );
		$this->assertNotNull( $pt );
		$this->assertTrue( $pt->public );
		$this->assertTrue( (bool) $pt->has_archive );
		$this->assertSame( 'cases', $pt->rewrite['slug'] );
	}

	public function test_internal_types_not_public() {
		foreach ( array( 'spc_price', 'spc_faq', 'spc_staff' ) as $type ) {
			$pt = get_post_type_object( $type );
			$this->assertNotNull( $pt, $type );
			$this->assertFalse( $pt->public, $type );
			$this->assertFalse( $pt->publicly_queryable, $type );
			$this->assertTrue( $pt->show_ui, $type );
		}
	}

	public function test_block_editor_disabled() {
		foreach ( array( 'spc_case', 'spc_price', 'spc_faq', 'spc_staff' ) as $type ) {
			$this->assertFalse( use_block_editor_for_post_type( $type ), $type );
		}
	}

	public function test_menu_order_supported_for_sortable_types() {
		foreach ( array( 'spc_price', 'spc_faq', 'spc_staff' ) as $type ) {
			$this->assertTrue( post_type_supports( $type, 'page-attributes' ), $type );
		}
	}

	public function test_staff_supports_featured_image_as_portrait() {
		$this->assertTrue( post_type_supports( 'spc_staff', 'thumbnail' ) );
		$labels = get_post_type_object( 'spc_staff' )->labels;
		$this->assertSame( '顔写真', $labels->featured_image );
		$this->assertSame( '顔写真を設定', $labels->set_featured_image );
		$this->assertSame( '顔写真を削除', $labels->remove_featured_image );
		$this->assertSame( '顔写真として使う', $labels->use_featured_image );
	}

	public function test_faq_supports_editor_for_answer() {
		$this->assertTrue( post_type_supports( 'spc_faq', 'editor' ) );
		$this->assertFalse( post_type_supports( 'spc_price', 'editor' ) );
		$this->assertFalse( post_type_supports( 'spc_staff', 'editor' ) );
	}

	public function test_faq_editor_help_note_only_for_faq() {
		ob_start();
		do_action( 'edit_form_after_title', self::factory()->post->create_and_get( array( 'post_type' => 'spc_faq' ) ) );
		$this->assertStringContainsString( 'ここに回答を書きます。トップページと『よくある質問』ページに表示されます。', ob_get_clean() );

		ob_start();
		do_action( 'edit_form_after_title', self::factory()->post->create_and_get( array( 'post_type' => 'spc_staff' ) ) );
		$this->assertStringNotContainsString( 'ここに回答を書きます', ob_get_clean() );
	}

	public function test_labels_are_japanese() {
		$this->assertSame( '施工事例', get_post_type_object( 'spc_case' )->labels->name );
		$this->assertSame( '料金', get_post_type_object( 'spc_price' )->labels->name );
		$this->assertSame( 'よくある質問', get_post_type_object( 'spc_faq' )->labels->name );
		$this->assertSame( '職人', get_post_type_object( 'spc_staff' )->labels->name );
	}

	public function test_title_placeholder_is_plain_japanese() {
		$post = (object) array( 'post_type' => 'spc_faq' );
		$this->assertSame( '質問文', apply_filters( 'enter_title_here', 'Add title', $post ) );
	}

	public function test_placeholder_column_marks_flagged_posts() {
		$flagged = self::factory()->post->create( array( 'post_type' => 'spc_faq' ) );
		$plain   = self::factory()->post->create( array( 'post_type' => 'spc_faq' ) );
		update_post_meta( $flagged, '_spc_is_placeholder', '1' );

		$cols = apply_filters( 'manage_spc_faq_posts_columns', array( 'title' => 'Title' ) );
		$this->assertArrayHasKey( 'spc_placeholder', $cols );

		ob_start();
		do_action( 'manage_spc_faq_posts_custom_column', 'spc_placeholder', $flagged );
		$this->assertSame( '仮', ob_get_clean() );

		ob_start();
		do_action( 'manage_spc_faq_posts_custom_column', 'spc_placeholder', $plain );
		$this->assertSame( '', ob_get_clean() );
	}
}
