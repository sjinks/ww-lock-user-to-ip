<?php
namespace WildWolf\LockUser;

final class Plugin
{
	public static function instance()
	{
		static $self = null;

		if (!$self) {
			// @codeCoverageIgnoreStart
			// The plugin is loaded before code coverage processing
			// is initialized, therefore the system thinks
			// that this code never executes
			$self = new self();
			// @codeCoverageIgnoreEnd
		}

		return $self;
	}

	/**
	 * @codeCoverageIgnore the plugin is initialized before the coverage processing starts
	 */
	private function __construct()
	{
		\add_action('init', [$this, 'init']);
	}

	/**
	 * @codeCoverageIgnore the plugin is initialized before the coverage processing starts
	 */
	public function init()
	{
		\load_plugin_textdomain('lock-user', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

		if (!empty($_SERVER['REMOTE_ADDR'])) {
			\add_action('wp_login', [$this, 'wp_login'], 10, 2);
		}

		if (\is_admin()) {
			Admin::instance();
		}
	}

	public function wp_login($user_login, \WP_User $user)
	{
		$cur   = \inet_pton($_SERVER['REMOTE_ADDR']);
		$ips   = (array)(\get_user_meta($user->ID, 'psb_ip_list', true) ?: []);
		$ips   = \apply_filters('wwlu2ip_allowed_ips', $ips, $user);
		$ips   = \array_map('inet_pton', /** @scrutinizer ignore-type */ $ips);
		$found = empty($ips) || \in_array($cur, $ips, true);

		if (!$found) {
			\do_action('wwl2uip_user_not_allowed', $user);

			self::notify_admin((string)$user_login);

			\do_action('wwl2uip_user_not_allowed_late', $user);

			// @codeCoverageIgnoreStart
			\wp_logout();
			\wp_redirect(\wp_login_url());
			exit();
			// @codeCoverageIgnoreEnd
		}

		\do_action('wwl2uip_user_allowed', $user);
	// @codeCoverageIgnoreStart
	}
	// @codeCoverageIgnoreEnd

	private static function notify_admin(string $user_login)
	{
		$ip      = $_SERVER['REMOTE_ADDR'];
		$ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$now     = \current_time('timestamp', false);
		$message = \sprintf(
			\__("User %1\$s has tried to log in from %2\$s\nTime: %3\$s %4\$s\nBrowser: %5\$s\n", 'lock-user'),
			$user_login,
			$ip,
			\date_i18n((string)\get_option('date_format'), $now),
			\date('H:i:s', $now),
			$ua
		);

		if (\function_exists('geoip_record_by_name')) {
			// @codeCoverageIgnoreStart
			$rec      = (array)\geoip_record_by_name($ip);
			$message .= \sprintf(\__("Country: %s\n", 'lock-user'), $rec['country_name'] ?? '');
			$message .= \sprintf(\__("City: %s\n",    'lock-user'), $rec['city']         ?? '');
			// @codeCoverageIgnoreEnd
		}

		\wp_mail((string)\get_option('admin_email'), \__('Suspicious login attempt', 'lock-user'), $message);
	}
}
