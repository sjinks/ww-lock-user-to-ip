<?php

namespace WildWolf\WordPress\LockUser;

use WildWolf\Utils\Singleton;
use WP_User;

final class Admin {
	use Singleton;

	private function __construct() {
		$this->admin_init();
	}

	public function admin_init(): void {
		add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );
		add_action( 'edit_user_profile', [ $this, 'edit_user_profile' ] );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
			add_action( 'show_user_profile', [ $this, 'edit_user_profile' ] );
		}
	}

	/**
	 * @psalm-suppress UnusedVariable
	 */
	public static function edit_user_profile( WP_User $user ): void {
		/** @psalm-var string[]|false|"" */
		$ips = get_user_meta( $user->ID, 'psb_ip_list', true );
		$ips = is_array( $ips ) ? join( "\n", $ips ) : '';
		require __DIR__ . '/../views/profile.php';
	}

	private function is_valid_ip( string $ip ): bool {
		return ! empty( $ip ) && false !== inet_pton( $ip );
	}

	/**
	 * @param int $user_id
	 */
	public function edit_user_profile_update( $user_id ): void {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ips = sanitize_textarea_field( (string) ( $_POST['psb_ip_list'] ?? '' ) );
		$ips = explode( "\n", $ips );
		$ips = array_map( 'trim', $ips );
		$ips = array_filter( $ips, [ __CLASS__, 'is_valid_ip' ] );
		$ips = array_values( $ips );
		update_user_meta( $user_id, 'psb_ip_list', $ips );
	}
}
