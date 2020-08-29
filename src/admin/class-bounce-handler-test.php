<?php
/**
 * An object for orchestrating tests, holding test data and verifing tests.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 */

namespace BH_WP_AWS_SES_Bounce_Handler\admin;

use BH_WP_AWS_SES_Bounce_Handler\integrations\SES_Bounce_Handler_Integration_Interface;

/**
 * Create a uid, bounce simulator email address, setup integrations, save the data, verify tests, delete data.
 *
 * Class Bounce_Handler_Test
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\admin
 */
class Bounce_Handler_Test {

	/**
	 * Uid for referencing the test. Created from time().
	 * Public for saving.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The bounce simulator email address being used.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Array of arrays of test data from the integrations, for saving.
	 *
	 * @var array
	 */
	public $test_data;

	/**
	 * An id for the test. Made from the timestamp.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * AWS SES bounce simulator email address.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Use time() to create a uid for creating a bounce simulator email address.
	 *
	 * Bounce_Handler_Test constructor.
	 */
	public function __construct() {

		$this->id    = time();
		$this->email = "bounce+{$this->id}@simulator.amazonses.com";

	}

	/**
	 * Get the registered integrations.
	 *
	 * @return SES_Bounce_Handler_Integration_Interface[]
	 */
	private function get_integrations() {

		return apply_filters( 'bh_wp_aws_ses_bounce_handler_integrations', array() );
	}

	/**
	 * Starts each integration's test, returns html to be output to the user.
	 *
	 * @return array {string: html, string: message}
	 */
	public function run_test() {

		$data         = array();
		$data['html'] = '';

		$data['html'] .= '<p>Test started at time: <em>' . $this->get_id() . '</em></p>';
		$data['html'] .= '<p>Using email address: <em>' . $this->get_email() . '</em></p>';

		foreach ( $this->get_integrations() as $name => $integration ) {

			if ( ! $integration->is_enabled() ) {
				continue;
			}

			$test_setup = $integration->setup_test( $this );

			$this->test_data[ $name ] = $test_setup['data'];

			$data['html'] .= $test_setup['html'];

		}

		$to      = $this->get_email();
		$subject = 'BH WP AWS SES Bounce Handler Test Email';
		$message = 'BH WP AWS SES Bounce Handler Test Email';

		$mail_send = wp_mail( $to, $subject, $message );

		if ( ! $mail_send ) {

			$data['message'] = 'wp_mail() failed';
			wp_send_json_error( $data, 500 );
		}

		$data['html'] .= '<p>Test email sent to: <em>' . $this->get_email() . '</em></p>';

		return $data;

	}

	/**
	 * Checks with each integration if the expected changes have occurred.
	 *
	 * @return array {bool: testSuccess, string: html}
	 */
	public function verify_test() {

		$integrations = $this->get_integrations();

		$data                = array();
		$data['html']        = '';
		$data['testSuccess'] = true;

		foreach ( $this->test_data as $name => $test_data ) {

			$test_verify   = $integrations[ $name ]->verify_test( $test_data );
			$data['html'] .= $test_verify['html'];
			if ( false === $test_verify['success'] ) {
				$data['testSuccess'] = false;
			}
		}

		return $data;

	}

	/**
	 * Passes test data to integrations to delete.
	 */
	public function delete_test_data() {

		$integrations = $this->get_integrations();

		foreach ( $this->test_data as $name => $data ) {

			$integrations[ $name ]->delete_test_data( $data );
		}
	}

}
