<?php
class Test_MetaFields extends WP_UnitTestCase {
	private function post( string $type ): int {
		return self::factory()->post->create( array( 'post_type' => $type ) );
	}

	private function submit( int $id, array $meta, bool $nonce = true ): void {
		$_POST = array( 'spc_meta' => $meta );
		if ( $nonce ) {
			$_POST['spc_meta_nonce'] = wp_create_nonce( 'spc_save_meta' );
		}
		spc_save_meta_box( $id );
	}

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	public function test_every_definition_has_japanese_label_and_description() {
		foreach ( array( 'spc_case', 'spc_price', 'spc_faq', 'spc_staff' ) as $type ) {
			$defs = spc_field_definitions( $type );
			$this->assertNotEmpty( $defs, $type );
			foreach ( $defs as $key => $def ) {
				$this->assertNotSame( '', $def['label'], $key );
				$this->assertNotSame( '', $def['description'], $key );
				$this->assertSame( 1, preg_match( '/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $def['description'] ), $key );
				$this->assertContains( $def['type'], array( 'text', 'textarea', 'int', 'bool', 'image', 'select' ), $key );
			}
		}
		$this->assertSame( array(), spc_field_definitions( 'post' ) );
	}

	public function test_page_has_only_the_placeholder_checkbox() {
		$defs = spc_field_definitions( 'page' );
		$this->assertSame( array( '_spc_is_placeholder' ), array_keys( $defs ) );
		$this->assertSame( spc_field_definitions( 'spc_case' )['_spc_is_placeholder'], $defs['_spc_is_placeholder'] );
		$this->assertContains( 'page', spc_placeholder_post_types() );
	}

	public function test_page_placeholder_meta_is_registered_and_sanitized() {
		spc_register_meta_fields(); // 前のテストの後始末で登録表が空になるため再登録
		$keys = get_registered_meta_keys( 'post', 'page' );
		$this->assertArrayHasKey( '_spc_is_placeholder', $keys );
		$this->assertFalse( $keys['_spc_is_placeholder']['show_in_rest'] );
		$id = $this->post( 'page' );
		update_post_meta( $id, '_spc_is_placeholder', 'yes' );
		$this->assertSame( '', get_post_meta( $id, '_spc_is_placeholder', true ) );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		$this->assertSame( '1', get_post_meta( $id, '_spc_is_placeholder', true ) );

		// 権限：編集できないユーザーはメタを書けない（REST・カスタムフィールド経由の保険）。
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertFalse( current_user_can( 'edit_post_meta', $id, '_spc_is_placeholder' ) );
	}

	public function test_page_gets_only_the_side_placeholder_box() {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();
		spc_add_meta_boxes( 'page' );
		$this->assertArrayHasKey( 'spc_placeholder', $wp_meta_boxes['page']['side']['high'] );
		$this->assertSame( '公開前チェック', $wp_meta_boxes['page']['side']['high']['spc_placeholder']['title'] );
		$this->assertArrayNotHasKey( 'normal', $wp_meta_boxes['page'] );

		$wp_meta_boxes = array();
		spc_add_meta_boxes( 'post' );
		$this->assertEmpty( $wp_meta_boxes );
	}

	public function test_placeholder_box_renders_nonce_and_checkbox_for_page() {
		$id = $this->post( 'page' );
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		ob_start();
		spc_render_placeholder_box( get_post( $id ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'name="spc_meta_nonce"', $html );
		$this->assertStringContainsString( 'name="spc_meta[_spc_is_placeholder]"', $html );
		$this->assertStringContainsString( 'checked', $html );
		$this->assertStringContainsString( '仮データ（公開前に必ず差し替え）', $html );
	}

	public function test_page_placeholder_checkbox_saves_and_clears_via_save_post() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = $this->post( 'page' );
		// ブロックエディタのメタボックスは post.php への従来の送信で保存される（save_post_page が動く）。
		$_POST = array(
			'spc_meta'       => array( '_spc_is_placeholder' => '1' ),
			'spc_meta_nonce' => wp_create_nonce( 'spc_save_meta' ),
		);
		wp_update_post( array( 'ID' => $id, 'post_title' => '料金・送料' ) );
		$this->assertSame( '1', get_post_meta( $id, '_spc_is_placeholder', true ) );

		$_POST = array( 'spc_meta_nonce' => wp_create_nonce( 'spc_save_meta' ) ); // チェックなし
		wp_update_post( array( 'ID' => $id, 'post_title' => '料金・送料' ) );
		$this->assertSame( '', get_post_meta( $id, '_spc_is_placeholder', true ) );

		// nonce のない保存（REST・クイック編集など）ではメタを触らない。
		update_post_meta( $id, '_spc_is_placeholder', '1' );
		$_POST = array();
		wp_update_post( array( 'ID' => $id, 'post_title' => '料金・送料' ) );
		$this->assertSame( '1', get_post_meta( $id, '_spc_is_placeholder', true ) );
	}

	public function test_page_placeholder_save_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$id = $this->post( 'page' );
		$this->submit( $id, array( '_spc_is_placeholder' => '1' ) );
		$this->assertSame( '', get_post_meta( $id, '_spc_is_placeholder', true ) );
	}

	public function test_definitions_contain_brief_keys_in_order() {
		$this->assertSame(
			array( 'spc_case_no', 'spc_item', 'spc_material', 'spc_stain', 'spc_elapsed', 'spc_prior_treatment', 'spc_treatment', 'spc_result', 'spc_days', 'spc_before_id', 'spc_after_id', 'spc_permission_id', '_spc_is_placeholder' ),
			array_keys( spc_field_definitions( 'spc_case' ) )
		);
		$this->assertSame( array( 'stain', 'yellowing', 'down', 'coat', 'shipping' ), array_keys( spc_field_definitions( 'spc_price' )['spc_price_group']['options'] ) );
		$this->assertSame( array( 'shipping', 'price', 'payment', 'photo', 'other' ), array_keys( spc_field_definitions( 'spc_faq' )['spc_faq_group']['options'] ) );
		$this->assertSame( 'textarea', spc_field_definitions( 'spc_staff' )['spc_bio']['type'] );
	}

	public function test_labels_match_the_site_wording() {
		// LP・仮データと同じ呼び方にする。
		$this->assertSame( '黄ばみ・汗汚れ', spc_field_definitions( 'spc_price' )['spc_price_group']['options']['yellowing'] );
		$this->assertStringContainsString( 'トップページと職人紹介ページに表示されます', spc_field_definitions( 'spc_staff' )['spc_bio']['description'] );
	}

	public function test_int_meta_sanitized() {
		$id = $this->post( 'spc_price' );
		update_post_meta( $id, 'spc_amount', '-500abc' );
		$this->assertEquals( 0, get_post_meta( $id, 'spc_amount', true ) );
		update_post_meta( $id, 'spc_amount', '１５０００' );
		$this->assertEquals( 15000, get_post_meta( $id, 'spc_amount', true ) );
	}

	public function test_select_meta_rejects_unknown() {
		$id = $this->post( 'spc_price' );
		update_post_meta( $id, 'spc_price_group', 'hack' );
		$this->assertSame( '', get_post_meta( $id, 'spc_price_group', true ) );
		update_post_meta( $id, 'spc_price_group', 'down' );
		$this->assertSame( 'down', get_post_meta( $id, 'spc_price_group', true ) );
	}

	public function test_image_meta_stores_int_and_rejects_non_image() {
		$id    = $this->post( 'spc_case' );
		$image = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_post_meta( $id, 'spc_before_id', (string) $image );
		$this->assertSame( $image, (int) get_post_meta( $id, 'spc_before_id', true ) );
		update_post_meta( $id, 'spc_after_id', $id ); // 添付ではない投稿
		$this->assertEquals( 0, get_post_meta( $id, 'spc_after_id', true ) );
	}

	public function test_text_meta_strips_tags() {
		$id = $this->post( 'spc_case' );
		update_post_meta( $id, 'spc_item', '<b>コート</b>' );
		$this->assertSame( 'コート', get_post_meta( $id, 'spc_item', true ) );
	}

	public function test_meta_not_exposed_in_rest() {
		spc_register_meta_fields(); // 前のテストの後始末で登録表が空になるため再登録
		$keys = get_registered_meta_keys( 'post', 'spc_case' );
		$this->assertArrayHasKey( 'spc_item', $keys );
		$this->assertFalse( $keys['spc_item']['show_in_rest'] );
		$this->assertArrayHasKey( '_spc_seed_key', $keys );
	}

	public function test_save_requires_nonce() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = $this->post( 'spc_case' );
		$this->submit( $id, array( 'spc_item' => 'コート' ), false );
		$this->assertSame( '', get_post_meta( $id, 'spc_item', true ) );
	}

	public function test_save_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$id = $this->post( 'spc_case' );
		$this->submit( $id, array( 'spc_item' => 'コート' ) );
		$this->assertSame( '', get_post_meta( $id, 'spc_item', true ) );
	}

	public function test_save_stores_values_for_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = $this->post( 'spc_price' );
		$this->submit( $id, array( 'spc_price_group' => 'coat', 'spc_amount' => '15000', 'spc_from' => '1', 'spc_note' => '<i>税込</i>' ) );
		$this->assertSame( 'coat', get_post_meta( $id, 'spc_price_group', true ) );
		$this->assertEquals( 15000, get_post_meta( $id, 'spc_amount', true ) );
		$this->assertSame( '1', get_post_meta( $id, 'spc_from', true ) );
		$this->assertSame( '税込', get_post_meta( $id, 'spc_note', true ) );
	}

	public function test_placeholder_checkbox_saves_and_clears() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = $this->post( 'spc_faq' );
		$this->submit( $id, array( '_spc_is_placeholder' => '1' ) );
		$this->assertSame( '1', get_post_meta( $id, '_spc_is_placeholder', true ) );
		$this->submit( $id, array() ); // チェックなし
		$this->assertSame( '', get_post_meta( $id, '_spc_is_placeholder', true ) );
	}

	public function test_seed_key_is_never_overwritten_by_form() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = $this->post( 'spc_faq' );
		update_post_meta( $id, '_spc_seed_key', 'faq-1' );
		$this->submit( $id, array( '_spc_seed_key' => 'evil' ) );
		$this->assertSame( 'faq-1', get_post_meta( $id, '_spc_seed_key', true ) );
		$this->assertArrayNotHasKey( '_spc_seed_key', spc_field_definitions( 'spc_faq' ) );
	}

	public function test_revision_is_skipped() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id  = $this->post( 'spc_case' );
		$rev = wp_insert_post( array( 'post_type' => 'revision', 'post_parent' => $id, 'post_status' => 'inherit', 'post_title' => 'r' ) );
		$this->submit( $rev, array( 'spc_item' => 'x' ) );
		$this->assertSame( '', get_post_meta( $rev, 'spc_item', true ) );
	}
}
