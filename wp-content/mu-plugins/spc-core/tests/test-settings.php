<?php
class Test_Settings extends WP_UnitTestCase {
	public function tear_down() {
		$GLOBALS['wp_settings_errors'] = array();
		delete_option( 'spc_settings' );
		parent::tear_down();
	}

	/** @return string[] */
	private function error_codes(): array {
		return wp_list_pluck( get_settings_errors( 'spc_settings' ), 'code' );
	}

	public function test_line_url_defaults_to_placeholder() {
		delete_option( 'spc_settings' );
		$this->assertSame( 'https://lin.ee/PLACEHOLDER', spc_line_url() );
	}

	/** @dataProvider invalid_line_urls */
	public function test_invalid_line_url_keeps_previous_value_and_shows_error( $input ) {
		update_option( 'spc_settings', array( 'line_url' => 'https://lin.ee/saved' ) );
		$out = spc_settings_sanitize( array( 'line_url' => $input ) );
		$this->assertSame( 'https://lin.ee/saved', $out['line_url'] );
		$this->assertSame( array( 'spc_invalid_line_url' ), $this->error_codes() );
		$error = get_settings_errors( 'spc_settings' )[0];
		$this->assertSame( 'error', $error['type'] );
		$this->assertSame( 'LINE追加URLの形式が正しくないため、変更を保存しませんでした。https://lin.ee/… または https://line.me/… の形式で入力してください。', $error['message'] );
	}

	public function invalid_line_urls() {
		return array( array( 'http://lin.ee/abc' ), array( 'https://evil.example/abc' ), array( 'javascript:alert(1)' ),
			array( 'https://lin.ee.evil.com/x' ), array( 'https://lin.ee@evil.com/x' ), array( 'https://evil.com\@lin.ee' ), array( 'https://evil.com/?u=lin.ee' ) );
	}

	public function test_invalid_line_url_without_saved_value_uses_placeholder() {
		delete_option( 'spc_settings' );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_settings_sanitize( array( 'line_url' => 'https://evil.example/' ) )['line_url'] );
		$this->assertSame( array( 'spc_invalid_line_url' ), $this->error_codes() );

		// 保存済みの値がDB直接更新などで不正なら、それも使わない。
		update_option( 'spc_settings', array( 'line_url' => 'https://evil.example/' ) );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_settings_sanitize( array( 'line_url' => 'nope' ) )['line_url'] );
	}

	public function test_empty_line_url_resets_to_placeholder_without_error() {
		update_option( 'spc_settings', array( 'line_url' => 'https://lin.ee/saved' ) );
		$GLOBALS['wp_settings_errors'] = array();
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_settings_sanitize( array( 'line_url' => '  ' ) )['line_url'] );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_settings_sanitize( array( 'line_url' => array( 'x' ) ) )['line_url'] ); // スカラー以外は無かったことにする
		$this->assertSame( array(), $this->error_codes() );
	}

	public function test_saving_through_update_option_keeps_good_value_and_registers_one_error() {
		update_option( 'spc_settings', spc_settings_sanitize( array( 'line_url' => 'https://lin.ee/good', 'gtm_id' => 'GTM-AB12CD' ) ) );
		$this->assertSame( array(), $this->error_codes() );

		// 設定画面の保存と同じ経路（register_setting の sanitize_callback）。
		spc_register_settings();
		update_option( 'spc_settings', array( 'line_url' => 'https://evil.example/', 'gtm_id' => 'bad id' ) );
		$this->assertSame( 'https://lin.ee/good', spc_line_url() );
		$this->assertSame( 'GTM-AB12CD', spc_get_setting( 'gtm_id' ) );
		$this->assertSame( array( 'spc_invalid_line_url', 'spc_invalid_gtm_id' ), $this->error_codes() );
	}

	public function test_valid_line_urls_kept() {
		foreach ( array( 'https://lin.ee/AbC123', 'https://line.me/R/ti/p/@abc' ) as $url ) {
			$this->assertSame( $url, spc_settings_sanitize( array( 'line_url' => $url ) )['line_url'] );
		}
	}

	public function test_host_is_case_insensitive() {
		$this->assertSame( 'https://LIN.EE/abc', spc_settings_sanitize( array( 'line_url' => 'https://LIN.EE/abc' ) )['line_url'] );
		$this->assertSame( SPC_LINE_URL_PLACEHOLDER, spc_settings_sanitize( array( 'line_url' => 'HTTP://lin.ee/abc' ) )['line_url'] ); // 未保存のため仮URL
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
		$this->assertSame( array(), $this->error_codes() );
		$this->assertSame( '', spc_settings_sanitize( array( 'gtm_id' => '<script>' ) )['gtm_id'] ); // 未保存のため未設定
		$this->assertSame( array( 'spc_invalid_gtm_id' ), $this->error_codes() );
	}

	public function test_invalid_gtm_id_keeps_previous_value_and_shows_error() {
		update_option( 'spc_settings', array( 'gtm_id' => 'GTM-SAVED1' ) );
		$this->assertSame( 'GTM-SAVED1', spc_settings_sanitize( array( 'gtm_id' => 'UA-12345' ) )['gtm_id'] );
		$error = get_settings_errors( 'spc_settings' )[0];
		$this->assertSame( 'spc_invalid_gtm_id', $error['code'] );
		$this->assertSame( 'error', $error['type'] );
		$this->assertSame( 'GTMコンテナIDの形式が正しくないため、変更を保存しませんでした。GTM-から始まるIDを入力してください。', $error['message'] );
	}

	public function test_empty_gtm_id_unsets_without_error() {
		update_option( 'spc_settings', array( 'gtm_id' => 'GTM-SAVED1' ) );
		$GLOBALS['wp_settings_errors'] = array();
		$this->assertSame( '', spc_settings_sanitize( array( 'gtm_id' => ' ' ) )['gtm_id'] );
		$this->assertSame( array(), $this->error_codes() );
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
		$this->assertStringContainsString( '形式が違う場合はエラーを表示し、それまでのURLのまま変更しません', $html );
		$this->assertStringContainsString( '形式が違う場合はエラーを表示し、それまでの値のまま変更しません', $html );
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
