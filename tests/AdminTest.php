<?php

use WildWolf\LockUser\Admin;

class AdminTest extends WP_UnitTestCase
{
	public function testInstance()
	{
		$i1 = Admin::instance();
		$i2 = Admin::instance();

		$this->assertSame($i1, $i2);
	}

	public function testAdminInit()
	{
		$inst = Admin::instance();

		$this->assertFalse(has_action('edit_user_profile',        [$inst, 'edit_user_profile']));
		$this->assertFalse(has_action('edit_user_profile_update', [$inst, 'edit_user_profile_update']));

		$inst->admin_init();

		$this->assertEquals(10, has_action('edit_user_profile',        [$inst, 'edit_user_profile']));
		$this->assertEquals(10, has_action('edit_user_profile_update', [$inst, 'edit_user_profile_update']));
	}

	public function testEditUserProfile()
	{
		$inst = Admin::instance();

		update_user_meta(1, 'psb_ip_list', ['127.128.129.130', '131.132.133.134']);

		ob_start();
		$inst->edit_user_profile(new \WP_User(1));
		$haystack = ob_get_clean();

		$needle = "127.128.129.130\n131.132.133.134";
		$this->assertContains($needle, $haystack);
	}

	public function editUserProfileUpdateDataProvider()
	{
		return [
			["127.0.0.1", "127.0.0.1"],
			["\n127.0.0.1\n", "127.0.0.1"],
			["   127.0.0.1\t\n", "127.0.0.1"],
			["256.0.0.0", ""],
			["255.0.0.0.0", ""],
		];
	}

	/**
	 * @dataProvider editUserProfileUpdateDataProvider
	 * @param string $ips
	 * @param string $expected
	 */
	public function testEditUserProfileUpdate(string $ips, string $expected)
	{
		$inst = Admin::instance();

		$_POST['psb_ip_list'] = $ips;
		$inst->edit_user_profile_update(1);

		$actual = join("\n", get_user_meta(1, 'psb_ip_list', true));
		$this->assertEquals($expected, $actual);
	}
}
