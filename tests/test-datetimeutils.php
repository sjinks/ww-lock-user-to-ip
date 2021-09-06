<?php

use WildWolf\WordPress\LockUser\DateTimeUtils;

class Test_DateTimeUtils extends WP_UnitTestCase /* NOSONAR */ {
	/**
	 * @covers \WildWolf\WordPress\LockUser\DateTimeUtils::format_date_time
	 */
	public function test_format_date_time(): void {
		$expected = 'January 1, 1970 12:00 am';
		$actual   = DateTimeUtils::format_date_time( 0 );
		self::assertSame( $expected, $actual );
	}

	/**
	 * @covers \WildWolf\WordPress\LockUser\DateTimeUtils::format_date_time_full
	 */
	public function test_format_date_time_full(): void {
		$expected = '1970-01-01 00:00:00';
		$actual   = DateTimeUtils::format_date_time_full( 0 );
		self::assertSame( $expected, $actual );
	}
}
