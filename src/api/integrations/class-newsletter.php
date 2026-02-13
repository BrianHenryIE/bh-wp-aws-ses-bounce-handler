<?php
/**
 * Functionality for the Newsletter plugin to mark users as bounced and unsubscribe users who complain.
 *
 * @see https://wordpress.org/plugins/newsletter
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Bounce_Handler_Test;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\SES_Bounce_Handler_Integration_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerAwareTrait;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;
use stdClass;
use TNP;

/**
 * `handle_ses_bounce` => Mark the user bounced.
 * `handle_ses_complaint` => Unsubscribe the user.
 */
class Newsletter implements SES_Bounce_Handler_Integration_Interface {

	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

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
	 * @param string   $email_address    The email address that has bounced.
	 * @param stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
	 * @param stdClass $message           Parent object of complete notification.
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	public function handle_ses_bounce( string $email_address, stdClass $bounced_recipient, stdClass $message ): void {

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! defined( 'NEWSLETTER_USERS_TABLE' ) ) {
			return;
		}

		$newsletter = \Newsletter::instance();
		$user       = $newsletter->get_user( $email_address );

		if ( empty( $user ) ) {
			$this->logger->debug( "No matching TNP user found for Email address {$email_address}." );
			return;
		}

		if ( 'B' === $user->status ) {
			$this->logger->debug( "`TNP_User:{$user->id}` already has bounced status." );
			return;
		}

		$user = $newsletter->set_user_status( $user, 'B' );

		if ( 'B' === $user->status ) {
			$this->logger->info( "`TNP_User:{$user->id}` status set to bounced." );
		} else {
			$this->logger->error( "Error setting `TNP_User:{$user->id}` status to bounced." );
		}
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

		$newsletter = \Newsletter::instance();
		$user       = $newsletter->get_user( $email_address );

		if ( empty( $user ) ) {
			$this->logger->debug( "No matching TNP user found for Email address {$email_address}" );
			return;
		}

		$log_unsubscribe_action = function( $subscriber ) {
			$this->logger->info( "`tnp_user:{$subscriber->id}` unsubscribed after complaint." );
		};

		/**
		 * Hook into the Newsletter plugin's own unsubscribe confirmed action.
		 *
		 * @see TNP::unsubscribe()
		 */
		add_action( 'newsletter_unsubscribed', $log_unsubscribe_action );

		/** Returns WP_Error|void. */
		$result = TNP::unsubscribe( $params );

		if ( ! empty( $result ) ) {
			$this->logger->error( "Failed to unsubscribe {$email_address} after complaint: " . $result->get_error_message() );
		}

		remove_action( 'newsletter_unsubscribed', $log_unsubscribe_action );

		// TODO: Associate the complaint with the particular newsletter sent.
	}

	/**
	 * Unsubscribe user from future emails.
	 *
	 * @hooked handle_unsubscribe_email
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

		$newsletter = \Newsletter::instance();
		$user       = $newsletter->get_user( $email_address );

		if ( empty( $user ) ) {
			$this->logger->debug( "No matching TNP user found for Email address {$email_address}" );
			return;
		}

		$log_unsubscribe_action = function( $subscriber ) {
			$this->logger->info( "`tnp_user:{$subscriber->id}` unsubscribed after unsubscribe request." );
		};

		/**
		 * Hook into the Newsletter plugin's own unsubscribe confirmed action.
		 *
		 * @see TNP::unsubscribe()
		 */
		add_action( 'newsletter_unsubscribed', $log_unsubscribe_action );

		/** Returns WP_Error|void. */
		$result = TNP::unsubscribe( $params );

		if ( ! empty( $result ) ) {
			$this->logger->error( "Failed to unsubscribe {$email_address} after unsubscribe request: " . $result->get_error_message() );
		}

		remove_action( 'newsletter_unsubscribed', $log_unsubscribe_action );

		// TODO: Associate the response with the particular newsletter sent.
	}

	/**
	 * Create a subscriber with the appropriate email address.
	 *
	 * @param Bounce_Handler_Test $test The object orchestrating the test.
	 *
	 * @return ?array{data:array,html:string}
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
	 * @param array{tnp_user_id:int, tnp_user_status:string} $test_data The data generated earlier for the test.
	 *
	 * @return array{success:bool, html:string} containing success boolean and html.
	 */
	public function verify_test( array $test_data ): ?array {

		if ( ! $this->is_enabled() ) {
			// This is an odd point to reach.
			return null;
		}

		$newsletter = \Newsletter::instance();

		$tnp_user = $newsletter->get_user( $test_data['tnp_user_id'] );

		if ( empty( $tnp_user ) ) {
			return array(
				'success' => false,
				'html'    => "<p>Failed to get test user {$test_data['tnp_user_id']}.</p>",
			);
		}

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
	 * @param array{tnp_user_id: int} $test_data The data created and saved during setup_test().
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
