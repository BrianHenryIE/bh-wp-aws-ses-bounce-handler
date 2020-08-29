<?php
/**
 * An interface for integrations to conform to to add consistency to testing in wp-admin.
 *
 * ```
 * add_filter( 'bh_wp_aws_ses_bounce_handler_integrations', function( $integrations ) {
 *   $integrations[] = new class() implements SES_Bounce_Handler_Integration_Interface {};
 *   return $integrations;
 * }
 * ```
 *
 * @since      1.2.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/integrations
 */

namespace BH_WP_AWS_SES_Bounce_Handler\integrations;

use BH_WP_AWS_SES_Bounce_Handler\admin\Bounce_Handler_Test;
use stdClass;

interface SES_Bounce_Handler_Integration_Interface {

	/**
	 * Called by this plugin on all integrations added to the 'bh_wp_aws_ses_bounce_handler_integrations' filter
	 * after 'plugins_loaded', so code for each integration can be self contained.
	 */
	public function init(): void;

	/**
	 * Used to deterimine if the integration should be hooked to the notifications and if it should be used in the
	 * admin page tests. In most cases this will be a `class_exists` call, unnecessary if the SES Bounce Handler
	 * integration is contained in the plugin it's integrating with, in which case just `return true`.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool;

	/**
	 * Description of what the integration does with bounce and complaint notifications. (unsubscribes, deletes,
	 * adds a notice...).
	 *
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * The function that will be called with the bounce data.
	 *
	 * @param string   $email_address     The email address that has bounced (sanitized).
	 * @param stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message           Parent object of complete notification.
	 *
	 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
	 */
	public function handle_ses_bounce( string $email_address, stdClass $bounced_recipient, stdClass $message ): void;

	/**
	 * The function that will be called with the complaint data.
	 *
	 * @param string   $email_address        The email address that has complained.
	 * @param stdClass $complained_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message              Parent object of complete notification.
	 *
	 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
	 */
	public function handle_ses_complaint( string $email_address, stdClass $complained_recipient, stdClass $message ): void;

	/**
	 * First step in admin page tests. The integration should set up some dummy data (accounts, orders...) using
	 * `Bounce_Handler_Test::get_email()`. It should return an associative array { string: html, array: data } with
	 * information to be printed for the user and a data array which will be saved and passed to verify_test().
	 *
	 * @param Bounce_Handler_Test $test A test of integrations for the user to see in WordPress admin.
	 *
	 * @return array
	 */
	public function setup_test( Bounce_Handler_Test $test ): ?array;

	/**
	 * Should use the earlier saved array of test data and check if the bounce notification has been received and
	 * has affected the data correctly. Should return an associative array containing `html` to be displayed to the
	 * user and boolean `success` to inform if the test has completed correctly.
	 *
	 * @param array $test_data Earlier saved references to objects that will change, and their state.
	 *
	 * @return array {bool: success, string: html}
	 */
	public function verify_test( array $test_data ): ?array;

	/**
	 * The test data is not deleted automatically so the user can see the outcome of the bounce.
	 *
	 * @param array $test_data The data created and saved during setup_test().
	 *
	 * @return bool
	 */
	public function delete_test_data( array $test_data ): bool;
}
