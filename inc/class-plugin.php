<?php

namespace WildWolf\WordPress\LockUser;

use WildWolf\Utils\Singleton;
use WP_User;

final class Plugin {
	use Singleton;

	/**
	 * @codeCoverageIgnore the plugin is initialized before the coverage processing starts
	 */
	private function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * @codeCoverageIgnore the plugin is initialized before the coverage processing starts
	 *
	 * @return void
	 */
	public function init(): void {
		load_plugin_textdomain( 'lock-user', false, plugin_basename( dirname( __DIR__ ) ) . '/lang/' );

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			add_action( 'wp_login', [ $this, 'wp_login' ], 10, 2 );
		}

		if ( is_admin() ) {
			add_action( 'admin_init', [ Admin::class, 'instance' ] );
		}
	}

	/**
	 * @return string[]
	 */
	private static function get_allowed_ips( WP_User $user ): array {
		$ips = (array) ( get_user_meta( $user->ID, 'psb_ip_list', true ) ?: [] );
		/** @var string[] */
		return apply_filters( 'wwlu2ip_allowed_ips', $ips, $user );
	}

	/**
	 * @param string $user_login
	 * @return void
	 * @psalm-suppress RedundantCastGivenDocblockType
	 */
	public function wp_login( $user_login, WP_User $user ): void {
		$cur   = inet_pton( (string) $_SERVER['REMOTE_ADDR'] );
		$ips   = self::get_allowed_ips( $user );
		$ips   = array_map( 'inet_pton', $ips );
		$found = empty( $ips ) || in_array( $cur, $ips, true );

		if ( ! $found ) {
			do_action( 'wwl2uip_user_not_allowed', $user );

			self::notify_admin( (string) $user_login );

			do_action( 'wwl2uip_user_not_allowed_late', $user );

			// @codeCoverageIgnoreStart
			wp_logout();
			wp_safe_redirect( wp_login_url() );
			exit();
			// @codeCoverageIgnoreEnd
		}

		do_action( 'wwl2uip_user_allowed', $user );
		// @codeCoverageIgnoreStart
	}
	// @codeCoverageIgnoreEnd

	private static function notify_admin( string $user_login ): void {
		$ip      = (string) ( $_SERVER['REMOTE_ADDR'] );
		$ua      = (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		$now     = time();
		$message = sprintf(
			// translators 1: user login, 2: IP address, 3: local time, 4: UTC time, 5: user agent
			__( "User %1\$s has tried to log in from %2\$s\nTime: %3\$s (%4\$s UTC)\nBrowser: %5\$s\n", 'lock-user' ),
			$user_login,
			$ip,
			DateTimeUtils::format_date_time( $now ),
			DateTimeUtils::format_date_time_full( $now ),
			$ua
		);

		$message = apply_filters( 'wwl2uip_admin_notification_email', $message );
		if ( ! empty( $message ) ) {
			wp_mail( (string) get_option( 'admin_email' ), __( 'Suspicious login attempt', 'lock-user' ), $message );
		}
	}
}
