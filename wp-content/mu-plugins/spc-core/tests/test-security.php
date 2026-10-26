<?php
class Test_Security extends WP_UnitTestCase {
	public function test_xmlrpc_is_disabled() {
		$this->assertFalse( apply_filters( 'xmlrpc_enabled', true ) );
	}

	public function test_pingback_header_is_removed() {
		$headers = apply_filters( 'wp_headers', array( 'X-Pingback' => 'http://example.org/xmlrpc.php', 'Content-Type' => 'text/html' ) );
		$this->assertArrayNotHasKey( 'X-Pingback', $headers );
		$this->assertArrayHasKey( 'Content-Type', $headers );
	}

	public function test_anonymous_users_list_is_unauthorized() {
		wp_set_current_user( 0 );
		$res = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/users' ) );
		$this->assertSame( 401, $res->get_status() );
	}

	public function test_anonymous_single_user_is_unauthorized() {
		wp_set_current_user( 0 );
		$id  = self::factory()->user->create( array( 'role' => 'author' ) );
		$res = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/users/' . $id ) );
		$this->assertSame( 401, $res->get_status() );
	}

	public function test_logged_in_admin_can_list_users() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$res = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/users' ) );
		$this->assertSame( 200, $res->get_status() );
	}

	public function test_other_rest_routes_stay_public() {
		wp_set_current_user( 0 );
		$res = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/types' ) );
		$this->assertSame( 200, $res->get_status() );
		$res = rest_do_request( new WP_REST_Request( 'GET', '/' ) );
		$this->assertSame( 200, $res->get_status() );
	}

	private function redirect_target_for( string $url ): ?array {
		$captured = null;
		$filter   = static function ( $location, $status ) use ( &$captured ) {
			$captured = array( $location, $status );
			throw new RuntimeException( 'redirect' );
		};
		add_filter( 'wp_redirect', $filter, 10, 2 );
		$this->go_to( $url );
		try {
			do_action( 'template_redirect' );
		} catch ( RuntimeException $e ) {
			// リダイレクトを捕捉した。
		}
		remove_filter( 'wp_redirect', $filter, 10 );
		return $captured;
	}

	public function test_author_query_redirects_home() {
		$id     = self::factory()->user->create( array( 'role' => 'author' ) );
		$result = $this->redirect_target_for( home_url( '/?author=' . $id ) );
		$this->assertNotNull( $result );
		$this->assertSame( home_url( '/' ), $result[0] );
		$this->assertSame( 301, $result[1] );
	}

	public function test_author_archive_redirects_home() {
		$user   = self::factory()->user->create_and_get( array( 'role' => 'author' ) );
		self::factory()->post->create( array( 'post_author' => $user->ID ) );
		$result = $this->redirect_target_for( get_author_posts_url( $user->ID ) );
		$this->assertNotNull( $result );
		$this->assertSame( home_url( '/' ), $result[0] );
		$this->assertSame( 301, $result[1] );
	}

	public function test_generator_meta_is_not_output() {
		ob_start();
		do_action( 'wp_head' );
		$head = ob_get_clean();
		$this->assertStringNotContainsString( 'name="generator"', $head );
		$this->assertSame( '', apply_filters( 'the_generator', '<meta name="generator" content="WordPress 7.1.3" />', 'html' ) );
	}

	public function test_users_route_is_blocked_regardless_of_case() {
		wp_set_current_user( 0 );
		foreach ( array( '/WP/V2/Users', '/wp/V2/USERS/1', '/wp/v2/users/1', '/wp/v2/users/me' ) as $route ) {
			$res = rest_do_request( new WP_REST_Request( 'GET', $route ) );
			$this->assertSame( 401, $res->get_status(), $route );
		}
	}

	public function test_oembed_response_hides_author() {
		$user = self::factory()->user->create( array( 'role' => 'author', 'display_name' => '杉本 太郎' ) );
		$post = self::factory()->post->create( array( 'post_author' => $user, 'post_type' => 'page', 'post_status' => 'publish' ) );
		$data = get_oembed_response_data( $post, 600 );
		$this->assertIsArray( $data );
		$this->assertArrayNotHasKey( 'author_name', $data );
		$this->assertArrayNotHasKey( 'author_url', $data );
		$this->assertArrayHasKey( 'title', $data );
	}

	public function test_users_sitemap_provider_is_removed() {
		$registry = wp_sitemaps_get_server()->registry;
		$this->assertNull( $registry->get_provider( 'users' ) );
		$this->assertNotNull( $registry->get_provider( 'posts' ) );
	}
}
