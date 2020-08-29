<?php
/**
 * Tests for the WooCommerce integration: will it mark orders correctly?!
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\integrations;

use WC_Order;

/**
 * Checks does the delete test data button work.
 *
 * Class WooCommerce_Test
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\integrations
 */
class WooCommerce_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Create an order, see if the delete_test_data function successfully deletes it.
	 */
	public function test_delete_test_data() {

		$order = new WC_Order();
		$order->save();

		$test_data                = array();
		$test_data['wc_order_id'] = $order->get_id();

		$order_before = wc_get_order( $test_data['wc_order_id'] );

		$this->assertInstanceOf( WC_Order::class, $order_before );

		$woocommerce_integration = new WooCommerce( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );

		$woocommerce_integration->delete_test_data( $test_data );

		$order_after = wc_get_order( $test_data['wc_order_id'] );

		$this->assertFalse( $order_after );
	}
}
