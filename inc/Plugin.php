<?php
namespace WildWolf\LockUser;

final class Plugin
{
	public static function instance()
	{
		static $self = null;

		if (!$self) {
			$self = new self();
		}

		return $self;
	}

	private function __construct()
	{
		\add_action('init', [$this, 'init']);
	}

	public function init()
	{
		\load_plugin_textdomain('lock-user', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

		if (!empty($_SERVER['REMOTE_ADDR'])) {
			\add_action('wp_login', [$this, 'wp_login'], 10, 2);
		}

		if (\is_admin()) {
			\add_action('admin_init', [$this, 'admin_init']);
		}
	}

	public function admin_init()
	{
		\add_action('edit_user_profile_update', [$this, 'edit_user_profile_update']);
		\add_action('edit_user_profile',        [$this, 'edit_user_profile']);
	}

	public function edit_user_profile(\WP_User $user)
	{
		$ips = \get_user_meta($user->ID, 'psb_ip_list', true);
		$ips = \is_array($ips) ? \join("\n", $ips) : '';
		require __DIR__ . '/views/profile.php';
	}

	private function is_valid_ip(string $ip) : bool
	{
		$ip = \trim($ip);
		return !empty($ip) && false !== \inet_pton($ip);
	}

	private function canonicalize_ip(string $ip) : string
	{
		return \inet_ntop(\inet_pton($ip));
	}

	public function edit_user_profile_update($id)
	{
		$ips = $_POST['psb_ip_list'] ?? '';
		$ips = \explode("\n", $ips);
		$ips = \array_filter($ips, [__CLASS__, 'is_valid_ip']);
		$ips = \array_map([__CLASS__, 'canonicalize_ip'], $ips);
		$ips = \array_values($ips);
		\update_user_meta($id, 'psb_ip_list', $ips);
	}

	public function wp_login($user_login, \WP_User $user)
	{
		$cur = \inet_pton($_SERVER['REMOTE_ADDR']);
		$ips = \get_user_meta($user->ID, 'psb_ip_list', true);

		if (empty($ips)) {
			return;
		}

		\assert(\is_array($ips));

		$ips   = \array_map('inet_pton', /** @scrutinizer ignore-type */ $ips);
		$found = \in_array($cur, $ips, true);

		if (!$found) {
			self::notify_admin((string)$user_login);
			\wp_logout();
			\wp_redirect(\wp_login_url());
			exit();
		}
	}

	private static function notify_admin(string $user_login)
	{
		$ip      = $_SERVER['REMOTE_ADDR'];
		$ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$message = \sprintf(
			\__("User %1\$s has tried to log in from %2\$s\nTime: %3\$s\nBrowser: %4\$s\n", 'lock-user'),
			$user_login, $ip, \date_i18n((string)\get_option('date_format'), \time()), $ua
		);

		if (\function_exists('geoip_record_by_name')) {
			$rec      = (array)\geoip_record_by_name($ip);
			$message .= \sprintf(\__("Country: %s\n", 'lock-user'), $rec['country_name'] ?? '');
			$message .= \sprintf(\__("City: %s\n",    'lock-user'), $rec['city']         ?? '');
		}

		\wp_mail((string)\get_option('admin_email'), \__('Suspicious login attempt', 'lock-user'), $message);
	}
}
