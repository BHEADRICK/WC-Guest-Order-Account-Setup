<?php
/**
 * WC_Guest_Order_Account_Setup.
 *
 * @since   0.0.0
 * @package WC_Guest_Order_Account_Setup
 */
class WC_Guest_Order_Account_Setup_Test extends WP_UnitTestCase {

	/**
	 * Test if our class exists.
	 *
	 * @since  0.0.0
	 */
	function test_class_exists() {
		$this->assertTrue( class_exists( 'WC_Guest_Order_Account_Setup') );
	}

	/**
	 * Test that our main helper function is an instance of our class.
	 *
	 * @since  0.0.0
	 */
	function test_get_instance() {
		$this->assertInstanceOf(  'WC_Guest_Order_Account_Setup', wc_guest_order_account_setup() );
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
