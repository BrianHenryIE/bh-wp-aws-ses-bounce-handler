<?php
/**
 * Functionality to mark WordPress user accounts as Bounced Email.
 *
 * @link       https://BrianHenry.ie
 * @since      1.1.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/integrations
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Bounce_Handler_Test;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use stdClass;
use WP_User;

/**
 * Hook into `handle_ses_bounce` to add Bounced Email role it to user accounts.
 *
 * Class WordPress
 *
 * @package BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations
 */
class WordPress implements SES_Bounce_Handler_Integration_Interface {

	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * The WordPress integration can always be enabled since it does not depend on external classes.
	 * It can still be removed in the `bh_wp_aws_ses_bounce_handler_integrations` filter.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return true;
	}

	/**
	 * Nothing needed.
	 */
	public function init(): void {
	}

	/**
	 * Describe the effect of the integration: a new user role is added ot bounced accounts.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return 'Adds <a href=' . esc_url( admin_url( 'users.php?role=bounced_email' ) ) . '">Bounced Email</a> role to users';
	}

	/**
	 * Add Bounced Email role to user so it can be filtered on the Users admin page.
	 *
	 * @hooked handle_ses_bounce
	 *
	 * @param string   $email_address     The email address that has bounced.
	 * @param stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message           Parent object of complete notification.
	 */
	public function handle_ses_bounce( string $email_address, stdClass $bounced_recipient, stdClass $message ): void {

		$this->logger->info( __CLASS__ . __FUNCTION__ . $email_address );

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			$this->logger->info( 'user not found for email : ' . $email_address );
			return;
		}

		$user->add_role( 'bounced_email' );

		$this->logger->info( 'Added bounced_email role to user ' . $user->ID . ' / ' . $email_address );
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
	public function handle_ses_complaint( string $email_address, stdClass $complained_recipient, stdClass $message ): void {
	}

	/**
	 * Create a WordPress user with the AWS SES bounce simulator email address.
	 *
	 * @param Bounce_Handler_Test $test The test configuration.
	 *
	 * @return array [values to save, html to print]
	 */
	public function setup_test( Bounce_Handler_Test $test ): ?array {

		$test_data = array();

		$user_id = wp_create_user( $test->get_email(), wp_generate_password( 30 ), $test->get_email() );

		if ( is_wp_error( $user_id ) ) {
			return null;
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! ( $user instanceof WP_User ) ) {
			// TODO Log.
			return null;
		}

		$user_roles = $user->roles;

		$test_data['wp_user_id']    = $user_id;
		$test_data['wp_user_roles'] = $user_roles;

		$user_url = admin_url( 'user-edit.php?user_id=' . $user_id );

		$test_html_output = '<p>WordPress <a href="' . $user_url . '">user ' . $user_id . '</a> created with roles: <em>' . implode( ', ', $user_roles ) . '</em></p>';

		return array(
			'data' => $test_data,
			'html' => $test_html_output,
		);
	}

	/**
	 * The test succeeded if the user had the bounced_email role added.
	 *
	 * @param array $test_data { string: wp_user_id, string[]: wp_user_roles }.
	 *
	 * @return array<string, mixed>
	 */
	public function verify_test( array $test_data ): ?array {

		$user = get_user_by( 'id', intval( $test_data['wp_user_id'] ) );

		if ( ! ( $user instanceof WP_User ) ) {
			$this->logger->error( 'Unexpectedly could not find user', array( 'test_data' => $test_data ) );
			return array();
		}

		$user_roles = $user->roles;

		$previous_user_roles = (array) $test_data['wp_user_roles'];

		$new_roles = array_diff( $user_roles, $previous_user_roles );

		// TODO: Check for the bounced_email role, not just for "a role was added".
		$success = ! empty( $new_roles );

		$user_url = admin_url( 'user-edit.php?user_id=' . $user->ID );

		if ( $success ) {

			$html = 'WordPress <a href="' . esc_url( $user_url ) . '">user ' . $user->ID . '</a> found with new roles: <em>' . implode( ', ', $new_roles ) . '</em>.';

		} else {

			$html = 'No new roles added to <a href="' . esc_url( $user_url ) . '">user ' . $user->ID . '</a>.';
		}

		return array(
			'success' => $success,
			'html'    => $html,
		);

	}

	/**
	 * Delete the user created during the test.
	 *
	 * @param array{'wp_user_id': int} $test_data The data created and saved during setup_test().
	 *
	 * @return bool
	 */
	public function delete_test_data( array $test_data ): bool {

		if ( isset( $test_data['wp_user_id'] ) ) {
			$result = wp_delete_user( intval( $test_data['wp_user_id'] ) );

			return $result;
		}
		return false;
	}
}
