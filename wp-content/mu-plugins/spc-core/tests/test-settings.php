<?php
class Test_Settings extends WP_UnitTestCase {
	public function test_line_url_defaults_to_placeholder() {
		delete_option( 'spc_settings' );
		$this->assertSame( 'https://lin.ee/PLACEHOLDER', spc_line_url() );
	}

	/** @dataProvider invalid_line_urls */
	public function test_invalid_line_url_falls_back( $input ) {
		$out = spc_settings_sanitize( array( 'line_url' => $input ) );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, $out['line_url'] );
	}

	public function invalid_line_urls() {
		return array( array( '' ), array( 'http://lin.ee/abc' ), array( 'https://evil.example/abc' ), array( 'javascript:alert(1)' ),
			array( 'https://lin.ee.evil.com/x' ), array( 'https://lin.ee@evil.com/x' ), array( 'https://evil.com\@lin.ee' ), array( 'https://evil.com/?u=lin.ee' ), array( array( 'x' ) ) );
	}

	public function test_valid_line_urls_kept() {
		foreach ( array( 'https://lin.ee/AbC123', 'https://line.me/R/ti/p/@abc' ) as $url ) {
			$this->assertSame( $url, spc_settings_sanitize( array( 'line_url' => $url ) )['line_url'] );
		}
	}

	public function test_host_is_case_insensitive() {
		$this->assertSame( 'https://LIN.EE/abc', spc_settings_sanitize( array( 'line_url' => 'https://LIN.EE/abc' ) )['line_url'] );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_settings_sanitize( array( 'line_url' => 'HTTP://lin.ee/abc' ) )['line_url'] );
	}

	public function test_non_scalar_input_does_not_raise_notices() {
		$out = spc_settings_sanitize( array( 'brand_name' => array( 'a' ), 'gtm_id' => array( 'a' ), 'reply_time_note' => array( 'a' ), 'line_qr_id' => array( 1 ) ) );
		$defaults = spc_settings_defaults();
		$this->assertSame( $defaults['brand_name'], $out['brand_name'] );
		$this->assertSame( $defaults['reply_time_note'], $out['reply_time_note'] );
		$this->assertSame( '', $out['gtm_id'] );
		$this->assertSame( 0, $out['line_qr_id'] );
	}

	public function test_line_url_revalidated_on_read() {
		update_option( 'spc_settings', array( 'line_url' => 'https://evil.example/' ) );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_line_url() );
		update_option( 'spc_settings', array( 'line_url' => '' ) );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_line_url() );
		update_option( 'spc_settings', array( 'line_url' => 'https://lin.ee/ok' ) );
		$this->assertSame( 'https://lin.ee/ok', spc_line_url() );
	}

	public function test_gtm_id_format() {
		$this->assertSame( 'GTM-AB12CD', spc_settings_sanitize( array( 'gtm_id' => ' gtm-ab12cd ' ) )['gtm_id'] );
		$this->assertSame( '', spc_settings_sanitize( array( 'gtm_id' => '<script>' ) )['gtm_id'] );
	}

	public function test_hero_mode_whitelist() {
		$this->assertSame( 'vertical', spc_settings_sanitize( array( 'hero_sp_writing' => 'diagonal' ) )['hero_sp_writing'] );
		$this->assertSame( 'horizontal', spc_settings_sanitize( array( 'hero_sp_writing' => 'horizontal' ) )['hero_sp_writing'] );
	}

	public function test_text_fields_are_sanitized() {
		$out = spc_settings_sanitize( array( 'brand_name' => '<b>名前</b>', 'reply_time_note' => "<i>24時間</i>\n" ) );
		$this->assertSame( '名前', $out['brand_name'] );
		$this->assertSame( '24時間', $out['reply_time_note'] );
	}

	public function test_line_qr_id_resets_for_missing_or_non_image() {
		$this->assertSame( 0, spc_settings_sanitize( array( 'line_qr_id' => 999999 ) )['line_qr_id'] );
		$text = self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );
		$this->assertSame( 0, spc_settings_sanitize( array( 'line_qr_id' => $text ) )['line_qr_id'] );
		$img = self::factory()->attachment->create( array( 'post_mime_type' => 'image/png' ) );
		$this->assertSame( $img, spc_settings_sanitize( array( 'line_qr_id' => (string) $img ) )['line_qr_id'] );
	}

	public function test_get_setting_types_and_unknown_key() {
		delete_option( 'spc_settings' );
		$this->assertSame( '', spc_get_setting( 'unknown' ) );
		$this->assertSame( 0, spc_get_setting( 'line_qr_id' ) );
		$this->assertSame( '（仮）ブランド名', spc_get_setting( 'brand_name' ) );
		update_option( 'spc_settings', array( 'brand_name' => 'X' ) );
		$this->assertSame( 'X', spc_get_setting( 'brand_name' ) );
		$this->assertSame( 'vertical', spc_get_setting( 'hero_sp_writing' ) );
	}

	public function test_settings_page_renders_japanese_help() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		spc_register_settings();
		ob_start();
		spc_render_settings_page();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'ブランド設定', $html );
		$this->assertStringContainsString( 'ここで変えた内容はサイト全体に反映されます', $html );
		$this->assertStringContainsString( 'name="spc_settings[line_url]"', $html );
		$this->assertStringContainsString( 'スマートフォンで縦書き', $html );
		$this->assertStringContainsString( 'GTM-から始まる', $html );
		$this->assertStringContainsString( 'data-spc-media', $html );
	}

	public function test_menu_registered_under_settings_with_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		global $submenu;
		spc_register_settings_menu();
		$slugs = wp_list_pluck( $submenu['options-general.php'], 2 );
		$this->assertContains( 'spc-settings', $slugs );
	}
}
