<?php
/**
 * テーマの下層ページ共通部品（page_type・パンくず・お知らせ欄・FAQ・仮文言の一覧）。
 * テストの環境ではテーマが有効になっていないため、set_up でテーマを有効にした扱いにして読み込む。
 */
class Test_ThemeSubpages extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		$dir = get_theme_root() . '/spc-theme';
		if ( ! file_exists( $dir . '/functions.php' ) ) {
			$this->markTestSkipped( 'spc-theme が見つかりません。' );
		}
		// テーマを有効にした扱いにする（フィルタは tear_down で外れる）。
		add_filter( 'stylesheet', static fn() => 'spc-theme' );
		add_filter( 'template', static fn() => 'spc-theme' );
		require_once $dir . '/functions.php';

		// 施工事例の /cases/ は、パーマリンク設定後に投稿タイプを登録し直すと有効になる。
		$this->set_permalink_structure( '/%postname%/' );
		spc_register_post_types();
		flush_rewrite_rules();
		$this->create_required_pages();
	}

	public function tear_down() {
		remove_all_filters( 'spc_is_production' );
		delete_option( 'spc_settings' );
		parent::tear_down();
	}

	/** 必要な固定ページ9ページを作る。 */
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

	private function create_case( string $title ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'spc_case',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
	}

	public function page_type_urls(): array {
		return array(
			'トップ'      => array( '/', 'lp' ),
			'特殊シミ抜き'   => array( '/stain-removal/', 'service' ),
			'高級ダウン'    => array( '/down-coat/', 'service' ),
			'料金'       => array( '/price/', 'price' ),
			'職人・運営会社'  => array( '/about/', 'about' ),
			'ご利用の流れ'   => array( '/guide/', 'guide' ),
			'規約・法定表示'  => array( '/legal/', 'legal' ),
			'利用規約'     => array( '/legal/terms/', 'legal' ),
			'施工事例の一覧'  => array( '/cases/', 'case' ),
			'存在しないURL'  => array( '/no-such-page-xyz/', 'other' ),
		);
	}

	/**
	 * @dataProvider page_type_urls
	 */
	public function test_page_type_by_url( string $path, string $expected ) {
		$this->create_case( '事例' );
		$this->go_to( home_url( $path ) );
		$this->assertSame( $expected, spc_page_type() );
	}

	public function test_page_type_for_case_single() {
		$id = $this->create_case( 'ウールコートの赤ワインのシミ' );
		$this->go_to( get_permalink( $id ) );
		$this->assertSame( 'case', spc_page_type() );
	}

	public function test_breadcrumb_items_for_child_page() {
		$this->go_to( home_url( '/legal/terms/' ) );
		$items = spc_breadcrumb_items();
		$this->assertSame( array( 'ホーム', '規約・法定表示', '利用規約' ), wp_list_pluck( $items, 'label' ) );
		$this->assertSame(
			array( home_url( '/' ), get_permalink( get_page_by_path( 'legal' ) ), get_permalink( get_page_by_path( 'legal/terms' ) ) ),
			wp_list_pluck( $items, 'url' )
		);
	}

	public function test_breadcrumb_items_for_case_single() {
		$id = $this->create_case( 'ウールコートの赤ワインのシミ' );
		$this->go_to( get_permalink( $id ) );
		$items = spc_breadcrumb_items();
		$this->assertSame( array( 'ホーム', '施工事例', 'ウールコートの赤ワインのシミ' ), wp_list_pluck( $items, 'label' ) );
		$this->assertSame( array( home_url( '/' ), get_post_type_archive_link( 'spc_case' ), get_permalink( $id ) ), wp_list_pluck( $items, 'url' ) );
	}

	public function test_breadcrumb_items_empty_on_front_and_404() {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array(), spc_breadcrumb_items() );
		$this->go_to( home_url( '/no-such-page-xyz/' ) );
		$this->assertSame( array(), spc_breadcrumb_items() );
	}

	public function test_has_page_notes() {
		$cases = array(
			''                   => false,
			"  \n"               => false,
			'<p>&nbsp;</p>'      => false,
			'<p>年末年始の受付</p>' => true,
		);
		foreach ( $cases as $content => $expected ) {
			$id = self::factory()->post->create(
				array(
					'post_type'    => 'page',
					'post_content' => (string) $content,
				)
			);
			$this->assertSame( $expected, spc_has_page_notes( $id ), var_export( $content, true ) );
		}
	}

	public function test_faq_query_filters_by_group_top_and_limit() {
		$make = function ( string $group, string $top ): int {
			return self::factory()->post->create(
				array(
					'post_type'  => 'spc_faq',
					'meta_input' => array(
						'spc_faq_group'   => $group,
						'spc_show_on_top' => $top,
					),
				)
			);
		};
		$price_top = $make( 'price', '1' );
		$price     = $make( 'price', '' );
		$shipping  = $make( 'shipping', '1' );
		$stain     = $make( 'stain', '' );

		$ids = static fn( ?WP_Query $q ): array => $q ? wp_list_pluck( $q->posts, 'ID' ) : array();
		$this->assertEqualsCanonicalizing( array( $price_top, $price, $shipping, $stain ), $ids( spc_faq_query() ) );
		$this->assertEqualsCanonicalizing( array( $price_top, $price, $shipping ), $ids( spc_faq_query( array( 'price', 'shipping' ) ) ) );
		$this->assertEqualsCanonicalizing( array( $price_top ), $ids( spc_faq_query( array( 'price' ), true ) ) );
		$this->assertCount( 1, $ids( spc_faq_query( array(), false, 1 ) ) );
		$this->assertNull( spc_faq_query( array( 'down' ) ) );
		$this->assertEqualsCanonicalizing( array( $price_top, $shipping ), $ids( spc_top_faq_query() ) );
	}

	/**
	 * テーマのPHPファイルの「TODO(仮): 文言」には必ず [copy:キー] が付き、そのキーは一覧にある。
	 * 一覧の未確定のキーは、どこかのファイルに出てくる。
	 */
	public function test_every_copy_todo_has_registered_key() {
		$registry = spc_theme_placeholder_copy();
		$this->assertNotEmpty( $registry );
		foreach ( array_keys( $registry ) as $key ) {
			$this->assertMatchesRegularExpression( '/^[a-z0-9-]+\.[a-z0-9-]+$/', $key );
		}

		$dir   = get_template_directory();
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
		$found = array();
		$todos = 0;
		foreach ( $files as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}
			$relative = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) ) ), '/' );
			foreach ( file( $file->getPathname() ) as $no => $line ) {
				if ( false === strpos( $line, 'TODO(仮): 文言' ) ) {
					continue;
				}
				++$todos;
				$where = $relative . ':' . ( $no + 1 );
				$this->assertSame( 1, preg_match( '/\[copy:([a-z0-9-]+\.[a-z0-9-]+)\]/', $line, $m ), "キーがありません：{$where}" );
				$this->assertArrayHasKey( $m[1], $registry, "一覧にないキーです：{$where}" );
				$found[ $m[1] ] = true;
			}
		}
		$this->assertGreaterThanOrEqual( 11, $todos );

		foreach ( $registry as $key => $item ) {
			if ( empty( $item['confirmed'] ) ) {
				$this->assertArrayHasKey( $key, $found, "どのファイルにも出てこないキーです：{$key}" );
			}
		}
	}

	public function test_report_lists_unconfirmed_theme_copy() {
		add_filter( 'spc_placeholder_report', 'spc_theme_placeholder_copy_report' ); // フックは前のテストの後始末で外れることがある

		$copy   = array_values( array_filter( spc_placeholder_report(), fn( $i ) => 'theme_copy' === $i['type'] ) );
		$wanted = array_filter( spc_theme_placeholder_copy(), fn( $i ) => empty( $i['confirmed'] ) );
		$this->assertNotEmpty( $copy );
		$this->assertCount( count( $wanted ), $copy );
		$first = reset( $wanted );
		$this->assertSame( sprintf( '%sの文章が仮のままです（制作側でテーマを差し替え：%s）', $first['label'], $first['file'] ), $copy[0]['label'] );
		$this->assertSame( '', $copy[0]['edit_url'] );

		add_filter(
			'spc_theme_placeholder_copy',
			static function ( $list ) {
				foreach ( $list as $key => $item ) {
					$list[ $key ]['confirmed'] = true;
				}
				return $list;
			}
		);
		$this->assertSame( array(), array_filter( spc_placeholder_report(), fn( $i ) => 'theme_copy' === $i['type'] ) );
	}
}
