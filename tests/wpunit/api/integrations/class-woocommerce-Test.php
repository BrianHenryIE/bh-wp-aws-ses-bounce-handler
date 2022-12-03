<?php
/**
 * Tests for the WooCommerce integration: will it mark orders correctly?!
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use Psr\Log\NullLogger;
use WC_Order;

/**
 * Checks does the delete test data button work.
 *
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\WooCommerce
 */
class WooCommerce_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Create an order, see if the delete_test_data function successfully deletes it.
	 *
	 * @covers ::delete_test_data
	 */
	public function test_delete_test_data() {

		$order = new WC_Order();
		$order->save();

		$test_data                = array();
		$test_data['wc_order_id'] = $order->get_id();

		$order_before = wc_get_order( $test_data['wc_order_id'] );

		$this->assertInstanceOf( WC_Order::class, $order_before );

		$logger = new ColorLogger();

		$woocommerce_integration = new WooCommerce( $logger );

		$woocommerce_integration->delete_test_data( $test_data );

		$order_after = wc_get_order( $test_data['wc_order_id'] );

		$this->assertFalse( $order_after );
	}
}
