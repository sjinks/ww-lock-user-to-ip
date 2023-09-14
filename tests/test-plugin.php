<?php

use WildWolf\WordPress\LockUser\Plugin;

/**
 * @covers \WildWolf\WordPress\LockUser\Plugin
 */
class Test_Plugin extends WP_UnitTestCase /* NOSONAR */ {
	public static function wp_login_hook(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI
		throw new Exception( current_filter() ); // NOSONAR
	}

	public function test_init(): void {
		$inst = Plugin::instance();
		self::assertGreaterThan( 0, did_action( 'init' ) );

		self::assertEquals( 10, has_action( 'wp_login', [ $inst, 'wp_login' ] ) );
	}

	/**
	 * @dataProvider data_wp_login
	 * @uses \WildWolf\WordPress\LockUser\DateTimeUtils
	 * @param string[] $allowed_ips
	 */
	public function test_wp_login( array $allowed_ips, string $ip, string $message ): void {
		add_action( 'wwl2uip_user_allowed', [ __CLASS__, 'wp_login_hook' ] );
		add_action( 'wwl2uip_user_not_allowed_late', [ __CLASS__, 'wp_login_hook' ] );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $message );

		$_SERVER['REMOTE_ADDR'] = $ip;
		update_user_meta( 1, 'psb_ip_list', $allowed_ips );

		$user = new WP_User( 1 );

		do_action( 'wp_login', $user->user_login, $user );
	}

	/**
	 * @psalm-return iterable<array-key, array{string[], string, string}>
	 */
	public function data_wp_login(): iterable {
		return [
			[ [], '127.0.0.1', 'wwl2uip_user_allowed' ], // NOSONAR
			[ [ '127.0.0.1' ], '127.0.0.1', 'wwl2uip_user_allowed' ],
			[ [ 'fe80::2e4d:54ff:fed3:c585' ], 'fe80:00::2e4d:54ff:fed3:c585', 'wwl2uip_user_allowed' ],
			[ [ '::FFFF:129.144.52.38' ], '129.144.52.38', 'wwl2uip_user_not_allowed_late' ],
			[ [ '127.0.0.2' ], '127.0.0.1', 'wwl2uip_user_not_allowed_late' ],
		];
	}

	/**
	 * @uses \WildWolf\WordPress\LockUser\DateTimeUtils
	 */
	public function test_notify_admin(): void {
		add_action( 'wwl2uip_user_allowed', [ __CLASS__, 'wp_login_hook' ] );
		add_action( 'wwl2uip_user_not_allowed_late', [ __CLASS__, 'wp_login_hook' ] );

		$_SERVER['REMOTE_ADDR'] = '127.128.129.130';
		update_user_meta( 1, 'psb_ip_list', [ '1.2.3.4' ] );

		$user = new WP_User( 1 );

		reset_phpmailer_instance();

		try {
			do_action( 'wp_login', $user->user_login, $user );
			self::assertFalse( true );
		} catch ( \Exception $e ) {
			$msg = $e->getMessage();
			self::assertEquals( 'wwl2uip_user_not_allowed_late', $msg );

			$email = tests_retrieve_phpmailer_instance()->get_sent();
			self::assertNotEquals( false, $email );
			self::assertNotEmpty( $email->to[0][0] );
			self::assertEquals( get_option( 'admin_email' ), $email->to[0][0] );

			$body = $email->body;
			self::assertStringContainsString( $user->user_login, $body );
			self::assertStringContainsString( $_SERVER['REMOTE_ADDR'], $body );
		}
	}
}
