<?php

use WildWolf\LockUser\Plugin;

class PluginTest extends WP_UnitTestCase
{
	public static function wp_login_hook()
	{
		throw new \Exception(current_filter());
	}

	public function testInstance()
	{
		$i1 = Plugin::instance();
		$i2 = Plugin::instance();

		$this->assertSame($i1, $i2);
	}

	public function testInit()
	{
		$inst = Plugin::instance();
		$this->assertTrue(did_action('init') > 0);

		$this->assertEquals(10, has_action('wp_login', [$inst, 'wp_login']));
	}

	public function wpLoginDataProvider()
	{
		return [
			[[], '127.0.0.1', 'wwl2uip_user_allowed'],
			[['127.0.0.1'], '127.0.0.1', 'wwl2uip_user_allowed'],
			[['fe80::2e4d:54ff:fed3:c585'], 'fe80:00::2e4d:54ff:fed3:c585', 'wwl2uip_user_allowed'],
			[['::FFFF:129.144.52.38'], '129.144.52.38', 'wwl2uip_user_not_allowed_late'],
			[['127.0.0.2'], '127.0.0.1', 'wwl2uip_user_not_allowed_late'],
		];
	}

	/**
	 * @dataProvider wpLoginDataProvider
	 * @param array $ips
	 * @param string $ip
	 * @param string $message
	 */
	public function testWPLogin(array $ips, string $ip, string $message)
	{
		add_action('wwl2uip_user_allowed',          [__CLASS__, 'wp_login_hook']);
		add_action('wwl2uip_user_not_allowed_late', [__CLASS__, 'wp_login_hook']);

		$this->setExpectedException(\Exception::class, $message);

		$_SERVER['REMOTE_ADDR'] = $ip;
		update_user_meta(1, 'psb_ip_list', $ips);

		$user = new WP_User(1);

		do_action('wp_login', $user->user_login, $user);
	}

	public function testNotifyAdmin()
	{
		add_action('wwl2uip_user_allowed',          [__CLASS__, 'wp_login_hook']);
		add_action('wwl2uip_user_not_allowed_late', [__CLASS__, 'wp_login_hook']);

		$_SERVER['REMOTE_ADDR'] = '127.128.129.130';
		update_user_meta(1, 'psb_ip_list', ['1.2.3.4']);

		$user = new WP_User(1);

		reset_phpmailer_instance();

		try {
			do_action('wp_login', $user->user_login, $user);
			$this->assertFalse(true);
		}
		catch (\Exception $e) {
			$msg = $e->getMessage();
			$this->assertEquals('wwl2uip_user_not_allowed_late', $msg);

			$email = tests_retrieve_phpmailer_instance()->get_sent();
			$this->assertNotEquals(false, $email);
 			$this->assertNotEmpty($email->to[0][0]);
 			$this->assertEquals(get_option('admin_email'), $email->to[0][0]);

 			$body = $email->body;
 			$this->assertContains($user->user_login, $body);
 			$this->assertContains($_SERVER['REMOTE_ADDR'], $body);
		}
	}
}
