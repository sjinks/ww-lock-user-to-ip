<?php

use WildWolf\WordPress\LockUser\Admin;

/**
 * @covers \WildWolf\WordPress\LockUser\Admin
 */
class AdminTest extends WP_UnitTestCase {
	/** @var int */
	private static $admin_id;
	/** @var int */
	private static $editor_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$admin_id  = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @dataProvider data_admin_init
	 */
	public function test_admin_init( string $property, bool $admin ): void {
		$inst = Admin::instance();

		wp_set_current_user( self::${$property} );

		$inst->admin_init();

		self::assertEquals( 10, has_action( 'edit_user_profile', [ $inst, 'edit_user_profile' ] ) );
		self::assertEquals( 10, has_action( 'edit_user_profile_update', [ $inst, 'edit_user_profile_update' ] ) );

		self::assertEquals( $admin ? 10 : false, has_action( 'show_user_profile', [ $inst, 'edit_user_profile' ] ) );
		self::assertEquals( $admin ? 10 : false, has_action( 'personal_options_update', [ $inst, 'edit_user_profile_update' ] ) );
	}

	/**
	 * @psalm-return iterable<array-key,array{string, boolean}>
	 */
	public function data_admin_init(): iterable {
		return [
			[ 'editor_id', false ],
			[ 'admin_id', true ],
		];
	}

	public function test_edit_user_profile(): void {
		$inst = Admin::instance();

		update_user_meta( 1, 'psb_ip_list', [ '127.128.129.130', '131.132.133.134' ] );

		ob_start();
		$inst->edit_user_profile( new WP_User( 1 ) );
		$haystack = ob_get_clean();

		$needle = "127.128.129.130\n131.132.133.134";
		self::assertStringContainsString( $needle, $haystack );
	}

	/**
	 * @dataProvider data_edit_user_profile_update
	 */
	public function test_edit_user_profile_update( string $ips, string $expected ): void {
		$inst = Admin::instance();

		$_POST['psb_ip_list'] = $ips;
		$inst->edit_user_profile_update( 1 );

		$actual = join( "\n", get_user_meta( 1, 'psb_ip_list', true ) );
		self::assertEquals( $expected, $actual );
	}

	/**
	 * @psalm-return iterable<array-key,array{string,string}>
	 */
	public function data_edit_user_profile_update(): iterable {
		return [
			[ '127.0.0.1', '127.0.0.1' ], // NOSONAR
			[ "\n127.0.0.1\n", '127.0.0.1' ],
			[ "   127.0.0.1\t\n", '127.0.0.1' ],
			[ '256.0.0.0', '' ],
			[ '255.0.0.0.0', '' ],
		];
	}
}
