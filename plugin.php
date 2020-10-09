<?php
/*
 * Plugin Name: WW Lock User to IP
 * Plugin URI:
 * Description: Locks a user to specific IP addresses
 * Version: 2.0.2
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: lock-user
 * Domain Path: /lang
 */

defined('ABSPATH') || die();

if (defined('VENDOR_PATH')) {
	require VENDOR_PATH . '/vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}
elseif (file_exists(ABSPATH . 'vendor/autoload.php')) {
	require ABSPATH . 'vendor/autoload.php';
}

WildWolf\LockUser\Plugin::instance();
