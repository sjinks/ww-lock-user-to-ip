<?php
/*
 * Plugin Name: WW Lock User to IP
 * Plugin URI:
 * Description: Locks a user to specific IP addresses
 * Version: 3.0.0
 * Author: Volodymyr Kolesnykov
 * Author URI: https://wildwolf.name/
 * License: MIT
 * Text Domain: lock-user
 * Domain Path: /lang
 */

use WildWolf\WordPress\LockUser\Plugin;

if ( defined( 'ABSPATH' ) ) {
	if ( defined( 'VENDOR_PATH' ) ) {
		/** @psalm-suppress UnresolvableInclude, MixedOperand */
		require constant( 'VENDOR_PATH' ) . '/vendor/autoload.php'; // NOSONAR
	} elseif ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	} elseif ( file_exists( ABSPATH . 'vendor/autoload.php' ) ) {
		/** @psalm-suppress UnresolvableInclude */
		require ABSPATH . 'vendor/autoload.php';
	}

	Plugin::instance();
}
