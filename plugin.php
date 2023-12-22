<?php
/*
 * Plugin Name: WW Lock User to IP
 * Plugin URI:
 * Description: Locks a user to specific IP addresses
 * Version: 3.0.1
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
		require_once constant( 'VENDOR_PATH' ) . '/vendor/autoload.php';
	} elseif ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} elseif ( file_exists( ABSPATH . 'vendor/autoload.php' ) ) {
		require_once ABSPATH . 'vendor/autoload.php';
	}

	Plugin::instance();
}
