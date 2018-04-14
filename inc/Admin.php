<?php
namespace WildWolf\LockUser;

final class Admin
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
		$this->init();
	}

	public function init()
	{
		\add_action('admin_init', [$this, 'admin_init']);
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
		require __DIR__ . '/../views/profile.php';
	}

	private function is_valid_ip(string $ip) : bool
	{
		return !empty($ip) && false !== @\inet_pton($ip);
	}

	public function edit_user_profile_update($id)
	{
		$ips = $_POST['psb_ip_list'] ?? '';
		$ips = \explode("\n", $ips);
		$ips = \array_map('trim', $ips);
		$old = \error_reporting();
		\error_reporting(0);
		$ips = \array_filter($ips, [__CLASS__, 'is_valid_ip']);
		\error_reporting($old);
		$ips = \array_values($ips);
		\update_user_meta($id, 'psb_ip_list', $ips);
	}
}
