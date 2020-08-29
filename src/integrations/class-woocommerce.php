<?php
/**
 * Functionality for WooCommerce to highlight in orders when the user's email address is incorrect.
 *
 * @see https://wordpress.org/plugins/woocommerce
 *
 * @link       https://BrianHenry.ie
 * @since      1.1.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/integrations
 */

namespace BH_WP_AWS_SES_Bounce_Handler\integrations;

use BH_WP_AWS_SES_Bounce_Handler\admin\Bounce_Handler_Test;
use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Object;
use stdClass;
use WC_Order;

/**
 * Hook onto `handle_ses_bounce` to add order note and meta key, hook onto `admin_notices` to display notice on orders.
 *
 * Class WooCommerce
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\integrations
 */
class WooCommerce extends WPPB_Object implements SES_Bounce_Handler_Integration_Interface {

	const BOUNCED_META_KEY = 'bh_wp_aws_ses_bounce_hander_bounced';

	/**
	 * Return a description for the admin UI, explaining a note and notice will be added to orders.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return 'Adds a note and notice on orders whose email address bounced';
	}

	/**
	 * Add the hook for displaying the order notice.
	 */
	public function init(): void {
		add_action( 'admin_notices', array( $this, 'display_bounce_notification' ) );
	}

	/**
	 * Check is WooCommerce installed.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return class_exists( \WooCommerce::class );
	}

	/**
	 * Add a note to orders whose email addresses are invalid.
	 *
	 * @hooked handle_ses_bounce
	 *
	 * @param string   $email_address     The email address that has bounced.
	 * @param stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message           Parent object of complete notification.
	 */
	public function handle_ses_bounce( string $email_address, stdClass $bounced_recipient, stdClass $message ): void {

		if ( ! $this->is_enabled() ) {
			return;
		}

		/**
		 * Find any orders made with this email address.
		 *
		 * @var WC_Order[] $customer_orders
		 */
		$customer_orders = wc_get_orders( array( 'customer' => $email_address ) );

		foreach ( $customer_orders as $order ) {

			$order->add_order_note( 'Email address bounced' );

			$order->add_meta_data( self::BOUNCED_META_KEY, $email_address, true );

			$order->save();
		}
	}

	/**
	 * Do nothing.
	 *
	 * @hooked handle_ses_complaint
	 *
	 * @param string   $email_address The email address which complained about our email.
	 * @param stdClass $complained_recipient The SES notification the email address was received in.
	 * @param stdClass $message The SNS notification the SES notification was received in.
	 */
	public function handle_ses_complaint( string $email_address, stdClass $complained_recipient, stdClass $message ): void {}

	/**
	 * Create an order with the bounce simulator email address.
	 *
	 * @param Bounce_Handler_Test $test The test orchestrator and configuration.
	 *
	 * @return array|void
	 */
	public function setup_test( Bounce_Handler_Test $test ): ?array {

		if ( ! $this->is_enabled() ) {
			return null;
		}

		$order   = wc_create_order();
		$address = array(
			'email' => $test->get_email(),
		);
		$order->set_address( $address, 'billing' );
		$order_bounced_meta_value = $order->get_meta( self::BOUNCED_META_KEY );
		$order_bounced_meta_value = empty( $order_bounced_meta_value ) ? 'empty' : $order_bounced_meta_value;

		$order_url = admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );

		$data['wc_order_id']                 = $order->get_id();
		$data['wc_order_bounced_meta_value'] = $order_bounced_meta_value;

		$html = '<p>WooCommerce <a href="' . $order_url . '">order ' . $order->get_id() . '</a> created with meta key <em>' . self::BOUNCED_META_KEY . '</em> value: <em>' . $order_bounced_meta_value . '</em></p>';

		return array(
			'data' => $data,
			'html' => $html,
		);
	}

	/**
	 * Verify the order has metadata added.
	 *
	 * @param array $test_data {int: wc_order_id}.
	 *
	 * @return array
	 */
	public function verify_test( array $test_data ): ?array {

		$order = wc_get_order( intval( $test_data['wc_order_id'] ) );

		if ( ! $order instanceof \WC_Order ) {

			return array(
				'success' => false,
				'html'    => '<p>WooCommerce order not found</p>',
			);
		}

		$order_bounced_meta_value = $order->get_meta( self::BOUNCED_META_KEY );

		if ( empty( $order_bounced_meta_value ) ) {

			return array(
				'success' => false,
				'html'    => '<p>WooCommerce order meta key not found</p>',
			);
		}

		$success = true;

		$order_url = admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );

		$html = '<p>WooCommerce <a href="' . $order_url . '">order ' . $order->get_id() . '</a> found with meta key <em>' . self::BOUNCED_META_KEY . '</em> value: <em>' . $order_bounced_meta_value . '</em></p>';

		return array(
			'success' => $success,
			'html'    => $html,
		);

	}

	/**
	 * Delete the order created for the test.
	 *
	 * @param array $test_data {int: wc_order_id}.
	 */
	public function delete_test_data( array $test_data ): bool {

		if ( isset( $test_data['wc_order_id'] ) && class_exists( \WooCommerce::class ) ) {
			wp_delete_post( intval( $test_data['wc_order_id'] ) );
		}

		return true;
	}

	/**
	 * Display an admin notice if this order's customer email has bounced.
	 *
	 * @hooked admin_notices
	 *
	 * @see https://stackoverflow.com/questions/56971501/how-to-add-admin-notices-on-woocommerce-order-edit-page
	 */
	public function display_bounce_notification() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		$id = get_the_ID();

		if ( false === $id ) {
			return;
		}

		$order = wc_get_order( $id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$bounced_email = $order->get_meta( self::BOUNCED_META_KEY );

		if ( ! empty( $bounced_email ) ) {

			$notice = sprintf(
				"<div class='notice notice-warning'><p>%s <em>%s</em> %s.</p></div>",
				__( "The customer's email address", 'bh-wp-aws-ses-bounce-handler' ),
				$bounced_email,
				__( 'is invalid', 'bh-wp-aws-ses-bounce-handler' )
			);

			$allowed_html = array(
				'div' => array(
					'class' => array(),
				),
				'p'   => array(),
				'em'  => array(),
			);

			echo wp_kses( $notice, $allowed_html );
		}
	}

}
