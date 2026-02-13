<?php
/**
 * An object for orchestrating tests, holding test data and verifing tests.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\SES_Bounce_Handler_Integration_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerAwareTrait;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;

/**
 * Create a uid, bounce simulator email address, setup integrations, save the data, verify tests, delete data.
 */
class Bounce_Handler_Test {

	use LoggerAwareTrait;

	protected API_Interface $api;

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
	public array $test_data = array();

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
	public function __construct( API_Interface $api, LoggerInterface $logger ) {

		$this->logger = $logger;
		$this->api    = $api;

		$this->id    = time();
		$this->email = "bounce+{$this->id}@simulator.amazonses.com";

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

		foreach ( $this->api->get_integrations() as $name => $integration ) {

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

		$integrations = $this->api->get_integrations();

		$results_data                = array();
		$results_data['html']        = '';
		$results_data['testSuccess'] = true;

		$this->logger->debug( json_encode( $this->test_data ) );
		$this->logger->debug( ' test data is an array ' . ( is_array( $this->test_data ) ? 'yes' : 'no' ) );

		foreach ( $this->test_data as $name => $test_data ) {

			$this->logger->debug( $name . '    ' . json_encode( $test_data ) );

			$test_verify           = $integrations[ $name ]->verify_test( $test_data );
			$results_data['html'] .= $test_verify['html'];
			if ( false === $test_verify['success'] ) {
				$results_data['testSuccess'] = false;
			}
		}

		return $results_data;

	}

	/**
	 * Passes test data to integrations to delete.
	 */
	public function delete_test_data() {

		$integrations = $this->api->get_integrations();

		foreach ( $this->test_data as $name => $data ) {

			$integrations[ $name ]->delete_test_data( $data );
		}
	}

}
