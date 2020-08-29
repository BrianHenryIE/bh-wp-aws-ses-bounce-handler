<?php
/**
 * Functionality for the Newsletter plugin to mark users as bounced and unsubscribe users who complain.
 *
 * @see https://wordpress.org/plugins/newsletter
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/integrations
 */

namespace BH_WP_AWS_SES_Bounce_Handler\integrations;

use BH_WP_AWS_SES_Bounce_Handler\admin\Bounce_Handler_Test;
use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Object;
use stdClass;
use TNP;

/**
 * `handle_ses_bounce` => Mark the user bounced.
 * `handle_ses_complaint` => Unsubscribe the user.
 *
 * Class Newsletter
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\integrations
 */
class Newsletter extends WPPB_Object implements SES_Bounce_Handler_Integration_Interface {

	/**
	 * Links to the Newsletter subscribers page if the plugin is active.
	 *
	 * @return string
	 */
	public function get_description(): string {

		$html = 'Marks users as bounced and unsubscribes complaints';

		return $html;
	}

	/**
	 * No initialization needed.
	 */
	public function init(): void {
	}

	/**
	 * Are the plugin classes we'll use present?
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return class_exists( \Newsletter::class ) && class_exists( \TNP::class );
	}

	/**
	 * Mark email addresses as bounced in Newsletter plugin.
	 *
	 * @hooked handle_ses_bounce
	 *
	 * @param string    $email_address     The email address that has bounced.
	 * @param \stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param \stdClass $message           Parent object of complete notification.
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	public function handle_ses_bounce( string $email_address, stdClass $bounced_recipient, stdClass $message ): void {

		if ( ! $this->is_enabled() ) {
			return;
		}

		global $wpdb;
		$updated = $wpdb->update( NEWSLETTER_USERS_TABLE, array( 'status' => 'B' ), array( 'email' => $email_address ) );
	}

	/**
	 * Unsubscribe user from future emails.
	 *
	 * @hooked handle_ses_complaint
	 *
	 * @param string   $email_address     The email address that has bounced.
	 * @param stdClass $complained_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message           Parent object of complete notification.
	 */
	public function handle_ses_complaint( string $email_address, stdClass $complained_recipient, stdClass $message ): void {

		if ( ! $this->is_enabled() ) {
			return;
		}

		$params          = array();
		$params['email'] = $email_address;

		TNP::unsubscribe( $params );

		// TODO: Associate the complaint with the particular newsletter sent.
	}

	/**
	 * Unsubscribe user from future emails.
	 *
	 * @hooked handle_ses_complaint
	 *
	 * @param string   $email_address     The email address that has bounced.
	 * @param stdClass $complained_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message           Parent object of complete notification.
	 */
	public function handle_unsubscribe_email( string $email_address, stdClass $complained_recipient, stdClass $message ): void {

		if ( ! $this->is_enabled() ) {
			return;
		}

		$params          = array();
		$params['email'] = $email_address;

		TNP::unsubscribe( $params );

		// TODO: Associate the complaint with the particular newsletter sent.
	}

	/**
	 * Create a subscriber with the appropriate email address.
	 *
	 * @param Bounce_Handler_Test $test The object orchestrating the test.
	 *
	 * @return array|void
	 */
	public function setup_test( Bounce_Handler_Test $test ): ?array {

		if ( ! $this->is_enabled() ) {
			return null;
		}

		$params = array();

		$params['email'] = $test->get_email();

		/**
		 * The Newsletter subscriber object.
		 *
		 * @var \TNP_User $tnp_user
		 */
		$tnp_user = TNP::add_subscriber( $params );

		$tnp_user_status = $tnp_user->status;

		$tnp_user_url = admin_url( 'admin.php?page=newsletter_users_edit&id=' . $tnp_user->id );

		$data                    = array();
		$data['tnp_user_id']     = $tnp_user->id;
		$data['tnp_user_status'] = $tnp_user_status;

		$html = '<p>Newsletter <a href="' . $tnp_user_url . '">subscriber ' . $tnp_user->id . '</a> created with status ' . $tnp_user_status . '</p>';

		return array(
			'data' => $data,
			'html' => $html,
		);

	}

	/**
	 * Verify the subscriber has been marked as Bounced.
	 *
	 * @param array $test_data {int:tnp_user_id, string:tnp_user_status}.
	 *
	 * @return array containing success boolean and html.
	 */
	public function verify_test( array $test_data ): ?array {

		if ( ! $this->is_enabled() ) {
			// This is an odd point to reach.
			return null;
		}

		$newsletter = \Newsletter::instance();

		$tnp_user = $newsletter->get_user( $test_data['tnp_user_id'] );

		$tnp_user_status = $tnp_user->status;

		$tnp_user_url = admin_url( 'admin.php?page=newsletter_users_edit&id=' . $tnp_user->id );

		$success = 'B' === $tnp_user_status;

		if ( $success ) {
			$html = '<p>Newsletter <a href="' . $tnp_user_url . '">subscriber ' . $tnp_user->id . '</a> found with new status ' . $tnp_user_status . '</p>';
		} else {
			$html = '<p>Newsletter user status not changed</p>';
		}

		return array(
			'success' => $success,
			'html'    => $html,
		);
	}

	/**
	 * Delete the subscriber created for the test, by user id.
	 *
	 * @param array $test_data {int: tnp_user_id}.
	 */
	public function delete_test_data( array $test_data ): bool {

		$user = null;

		if ( isset( $test_data['tnp_user_id'] ) ) {

			$user = \Newsletter::instance()->get_user( $test_data['tnp_user_id'] );

			\Newsletter::instance()->delete_user( $test_data['tnp_user_id'] );

		}

		return ! is_null( $user );
	}

}
