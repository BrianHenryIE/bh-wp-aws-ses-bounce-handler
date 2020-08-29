<?php
/**
 * Tests the SNS class with sample data from AWS SNS.
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\rest;

use BH_WP_AWS_SES_Bounce_Handler\API\Settings;
use BH_WP_AWS_SES_Bounce_Handler\API\Settings_Interface;

/**
 * Check the route is correctly registered, send it some data.
 *
 * Class SNS_Test
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\rest
 */
class SNS_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Check the basic endpoint has been registered with WordPress.
	 */
	public function test_rest_endpoint_exists() {

		$rest_server = rest_get_server();

		$this->assertArrayHasKey( '/brianhenryie/v1/aws-ses', $rest_server->get_routes() );
	}

	/**
	 * Check we can POST to the endpoint.
	 */
	public function test_endpoint_accepts_post() {

		$rest_server = rest_get_server();

		$route = $rest_server->get_routes()['/brianhenryie/v1/aws-ses'][0];

		$this->assertArrayHasKey( 'POST', $route['methods'] );

		$this->assertTrue( $route['methods']['POST'] );
	}

	/**
	 * Verify POSTING a bounce invokes the bounce action.
	 */
	public function test_send_rest_post_for_bounce() {

		// Make sure the api secret works.
		$secret = 'secret';
		add_filter(
			'pre_option_' . Settings_Interface::SECRET_KEY,
			function( $result, $option, $default ) use ( $secret ) {
				return $secret;
			},
			10,
			3
		);

		// Make sure the allowed ARNs works.
		add_filter(
			'pre_option_' . Settings_Interface::CONFIRMED_ARNS,
			function( $result, $option, $default ) {
				return array( 'arn:aws:sns:us-east-1:112385421323:bounces' );
			},
			10,
			3
		);

		global $project_root_dir;

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$notification_json = file_get_contents( $project_root_dir . '/tests/_data/notification.json' );
		$notification      = json_decode( $notification_json );

		$request = new \WP_REST_Request( 'POST', '/brianhenryie/v1/aws-ses' );
		$request->set_headers( json_decode( $notification->headers ) );
		$request->set_body( $notification->body );

		$request->set_param( 'secret', $secret );

		remove_all_actions( 'handle_ses_bounce' );

		$success = false;
		add_action(
			'handle_ses_bounce',
			function( $email_address, $bounced_recipient, $message ) use ( &$success ) {

				$success = true;
			},
			10,
			3
		);

		rest_do_request( $request );

		$this->assertTrue( $success );
	}

}
