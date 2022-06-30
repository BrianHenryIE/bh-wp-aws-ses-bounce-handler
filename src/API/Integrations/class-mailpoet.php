<?php
/**
 * @see https://wordpress.org/plugins/mailpoet/
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Bounce_Handler_Test;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\SES_Bounce_Handler_Integration_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use MailPoet\API\MP\v1\APIException;
use MailPoet\Models\Subscriber;
use stdClass;

/**
 * @see https://github.com/mailpoet/mailpoet/blob/master/doc/api_methods/GetSubscriber.md
 *
 * Class MailPoet
 * @package BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations
 */
class MailPoet implements SES_Bounce_Handler_Integration_Interface {

	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	public function init(): void {
		// Create lists AWS Complaints, AWS Bounces?
		// Or just unsubscribe users?
	}

	public function is_enabled(): bool {
		return class_exists( \MailPoet\API\API::class );
	}

	public function get_description(): string {
		if ( $this->is_enabled() ) {

			$bounced_list_url      = admin_url( 'admin.php?page=mailpoet-subscribers#/page[1]/sort_by[created_at]/sort_order[desc]/group[bounced]' );
			$unsubscribed_list_url = admin_url( 'admin.php?page=mailpoet-subscribers#/page[1]/sort_by[created_at]/sort_order[desc]/group[unsubscribed]' );

			return 'Marks users as <a href="' . esc_url( $bounced_list_url ) . '">bounced</a> or <a href="' . esc_url( $unsubscribed_list_url ) . '">unsubscribed</a>';
		} else {
			return 'Marks users as bounced and unsubscribes complaints';
		}
	}

	/**
	 *
	 * Set the MailPoet's subscriber status as 'bounced'
	 *
	 * @param string   $email_address
	 * @param stdClass $bounced_recipient
	 * @param stdClass $message
	 */
	public function handle_ses_bounce( string $email_address, stdClass $bounced_recipient, stdClass $message ): void {

		$subscriber = Subscriber::findOne( $email_address );

		if ( false === $subscriber ) {
			$this->logger->warning( "Bounced email address {$email_address} not found as Mailpoet Subscriber. Unable to unsubscribe", array( 'email_address' => $email_address ) );
			return;
		}
		$subscriber->status = Subscriber::STATUS_BOUNCED;

		$result = $subscriber->save();
	}

	/**
	 * Since it's a complaint, it makes sense to unsubscribe from all lists.
	 *
	 * @param string   $email_address
	 * @param stdClass $complained_recipient
	 * @param stdClass $message
	 */
	public function handle_ses_complaint( string $email_address, stdClass $complained_recipient, stdClass $message ): void {

		try {
			$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		} catch ( \Exception $e ) {
			// TODO.
			return;
		}

		try {
			$subscriber = $mailpoet_api->getSubscriber( $email_address );
		} catch ( \MailPoet\API\MP\v1\APIException $e ) {
			// Subscriber probably does not exist
			return;
		}

		$subscriber_id = $subscriber['id'];
		$list_ids      = array();
		foreach ( $subscriber['subscriptions'] as $subscription ) {
			$list_ids[] = $subscription['segment_id'];
		}

		try {
			$mailpoet_api->unsubscribeFromLists( $subscriber_id, $list_ids );
		} catch ( \MailPoet\API\MP\v1\APIException $e ) {

		}
	}

	/**
	 * @param Bounce_Handler_Test $test
	 * @return array|null
	 */
	public function setup_test( Bounce_Handler_Test $test ): ?array {

		$email_address = $test->get_email();

		try {
			$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		} catch ( \Exception $e ) {
			// TODO.
			return array(
				'error' => $e->getMessage(),
			);
		}

		// Create a subscriber
		$subscriber_array = array(
			'email' => $test->get_email(),
		);

		try {
			$new_subscriber = $mailpoet_api->addSubscriber( $subscriber_array );
		} catch ( APIException $e ) {
			return array(
				'error' => $e->getMessage(),
			);
		}

		// Save the subscriber reference for checking later.

		$test_data                  = array();
		$test_data['email_address'] = $email_address;
		$test_data['subscriber_id'] = $new_subscriber['id'];

		$mailpoet_user_url = admin_url( "admin.php?page=mailpoet-subscribers#/edit/{$new_subscriber['id']}" );
		$html              = '<p>MailPoet <a href="' . $mailpoet_user_url . '">subscriber ' . $new_subscriber['id'] . '</a> created with status ' . $new_subscriber['status'] . '</p>';

		return array(
			'data' => $test_data,
			'html' => $html,
		);
	}

	/**
	 * @param array{email_address: string, subscriber_id: int} $test_data
	 *
	 * @return array|null
	 */
	public function verify_test( array $test_data ): ?array {

		try {
			$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		} catch ( \Exception $e ) {
			// Probably MailPoet "Invalid API version".
			return array(
				'success' => false,
				'html'    => $e->getMessage(),
			);
		}

		$email_address = $test_data['email_address'];

		try {
			$subscriber = $mailpoet_api->getSubscriber( $email_address );
		} catch ( \MailPoet\API\MP\v1\APIException $e ) {
			// Weird.
			return array(
				'success' => false,
				'html'    => $e->getMessage(),
			);
		}

		$subscriber_status = $subscriber['status'];

		$success = 'bounced' === $subscriber_status;

		$subscriber_id = $subscriber['status'];

		$user_profile_url = admin_url( 'admin.php?page=mailpoet-subscribers#/edit/' . $subscriber_id );

		if ( $success ) {
			$html = '<p>MailPoet <a href="' . $user_profile_url . '">subscriber ' . $subscriber_id . '</a> found with new status ' . $subscriber_status . '</p>';
		} else {
			$html = '<p>MailPoet user status not changed</p>';
		}

		return array(
			'success' => $success,
			'html'    => $html,
		);
	}

	/**
	 * Move the newly created subscriber to trash.
	 *
	 * @param array{email_address: string, subscriber_id: int} $test_data
	 *
	 * @return bool
	 */
	public function delete_test_data( array $test_data ): bool {

		$subscriber_id = $test_data['subscriber_id'];
		$email_address = $test_data['email_address'];

		$subscriber = Subscriber::findOne( $subscriber_id );

		$subscriber->trash();

		$result = $subscriber->save();

		return true;

	}
}
