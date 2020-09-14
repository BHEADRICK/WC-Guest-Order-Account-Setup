<?php
/**
 * WC Guest Order Account Setup Order_account_check Tests.
 *
 * @since   0.0.0
 * @package WC_Guest_Order_Account_Setup
 */
class WCGOAS_Order_account_check_Test extends WP_UnitTestCase {

	/**
	 * Test if our class exists.
	 *
	 * @since  0.0.0
	 */
	function test_class_exists() {
		$this->assertTrue( class_exists( 'WCGOAS_Order_account_check') );
	}

	/**
	 * Test that we can access our class through our helper function.
	 *
	 * @since  0.0.0
	 */
	function test_class_access() {
		$this->assertInstanceOf( 'WCGOAS_Order_account_check', wc_guest_order_account_setup()->order-account-check );
	}

	/**
	 * Replace this with some actual testing code.
	 *
	 * @since  0.0.0
	 */
	function test_sample() {
		$this->assertTrue( true );
	}
}
