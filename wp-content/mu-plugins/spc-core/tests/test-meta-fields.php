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

	public function test_definitions_contain_brief_keys_in_order() {
		$this->assertSame(
			array( 'spc_case_no', 'spc_item', 'spc_material', 'spc_stain', 'spc_elapsed', 'spc_prior_treatment', 'spc_treatment', 'spc_result', 'spc_days', 'spc_before_id', 'spc_after_id', 'spc_permission_id', '_spc_is_placeholder' ),
			array_keys( spc_field_definitions( 'spc_case' ) )
		);
		$this->assertSame( array( 'stain', 'yellowing', 'down', 'coat', 'shipping' ), array_keys( spc_field_definitions( 'spc_price' )['spc_price_group']['options'] ) );
		$this->assertSame( array( 'shipping', 'price', 'payment', 'photo', 'other' ), array_keys( spc_field_definitions( 'spc_faq' )['spc_faq_group']['options'] ) );
		$this->assertSame( 'textarea', spc_field_definitions( 'spc_staff' )['spc_bio']['type'] );
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
