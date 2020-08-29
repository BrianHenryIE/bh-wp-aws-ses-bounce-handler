<?php
/**
 * Handle AJAX requests on the settings page. Primarily for testing the configuration.

 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\admin;

use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Object;

/**
 * Code to run the ses test and to poll for its completion.
 */
class Ajax extends WPPB_Object {

	const AWS_SES_BOUNCE_TESTS = 'aws_ses_bounce_tests';

	/**
	 * Creates a new test – the time is used as a uid and in the AWS bounce simulator email address.
	 */
	public function run_ses_bounce_test() {

		$data = array();

		// Verify nonce.
		if ( ! check_ajax_referer( 'run-ses-bounce-test-form', false, false ) ) {

			$data['message'] = 'Referrer/nonce failure';

			wp_send_json_error( $data, 400 );
		}

		// TODO Verify settings: ARN exists, wp_mail correct, before enabling button.

		$test = new Bounce_Handler_Test();

		$data = $test->run_test();

		$data['notice']       = 'info';
		$data['bounceTestId'] = $test->get_id();

		$all_bounce_test_data                    = (array) get_option( self::AWS_SES_BOUNCE_TESTS, array() );
		$all_bounce_test_data[ $test->get_id() ] = $test;
		update_option( self::AWS_SES_BOUNCE_TESTS, $all_bounce_test_data );

		$data['newNonce'] = wp_create_nonce( 'run-ses-bounce-test-form' );

		wp_send_json( $data );
	}

	/**
	 * Check the nonce, get the saved test data, check has the bounce been received and processed correctly.
	 *
	 * Return an array { 'testSuccess', 'testComplete', 'html', 'newNonce' }
	 */
	public function fetch_test_results() {

		$data = array();

		// Verify nonce.
		if ( ! check_ajax_referer( 'run-ses-bounce-test-form', false, false ) ) {

			$data['message'] = 'Referrer/nonce failure';

			wp_send_json_error( $data, 400 );
		}

		if ( ! isset( $_POST['bounce_test_id'] ) ) {

			$data['message'] = 'bounce_test_id not set.';

			wp_send_json_error( $data, 400 );
		}

		$bounce_test_id = intval( $_POST['bounce_test_id'] );

		/**
		 * The previously saved tests.
		 *
		 * @var Bounce_Handler_Test[] $all_bounce_test_data
		 */
		$all_bounce_test_data = (array) get_option( self::AWS_SES_BOUNCE_TESTS, array() );

		$test = $all_bounce_test_data[ $bounce_test_id ];

		$data = $test->verify_test();
		// The test is complete if it was successful.
		$data['testComplete'] = $data['testSuccess'];

		if ( time() - intval( $bounce_test_id ) > MINUTE_IN_SECONDS ) {
			$data['testSuccess']  = false;
			$data['testComplete'] = true;
			$data['html']         = '<p><b>Test failed to complete within ' . MINUTE_IN_SECONDS . ' seconds. Test data remained unchanged.</b></p>';
		}

		$data['newNonce'] = wp_create_nonce( 'run-ses-bounce-test-form' );

		wp_send_json( $data );
	}

	/**
	 * Delete saved test data for a specified bounce handler test.
	 */
	public function delete_test_data() {

		$data = array();
		// Verify nonce.
		if ( ! check_ajax_referer( 'run-ses-bounce-test-form', false, false ) ) {

			$data['message'] = 'Referrer/nonce failure';

			wp_send_json_error( $data, 400 );
		}

		if ( ! isset( $_POST['bounce_test_id'] ) ) {

			$data['message'] = 'bounce_test_id not set.';

			wp_send_json_error( $data, 400 );
		}

		$bounce_test_id = intval( $_POST['bounce_test_id'] );

		/**
		 * The previously saved tests.
		 *
		 * @var Bounce_Handler_Test[] $all_bounce_test_data
		 */
		$all_bounce_test_data = (array) get_option( self::AWS_SES_BOUNCE_TESTS, array() );

		$all_bounce_test_data[ $bounce_test_id ]->delete_test_data();

		wp_send_json( $data );
	}
}
