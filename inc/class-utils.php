<?php

namespace WildWolf\WordPress\LockUser;

abstract class Utils {
	public static function get_post_var( string $index ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return static::get_var( $_POST, $index );
	}

	public static function get_server_var( string $index ): string {
		return static::get_var( $_SERVER, $index );
	}

	private static function get_var( array $where, string $index ): string {
		return isset( $where[ $index ] ) && is_scalar( $where[ $index ] ) ? (string) $where[ $index ] : '';
	}
}
