<?php
class Test_Boot extends WP_UnitTestCase {
	public function test_core_is_loaded() {
		$this->assertTrue( function_exists( 'spc_core_loaded' ) );
	}
}
