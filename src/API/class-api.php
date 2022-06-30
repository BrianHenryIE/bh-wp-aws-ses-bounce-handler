<?php

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\MailPoet;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\Newsletter;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\WooCommerce;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\WordPress;
use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use stdClass;

class API implements API_Interface {

	use LoggerAwareTrait;

	/**
	 * The settings object contains the AWS ARNs to listen to, as configured by the user.
	 *
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Settings_Interface $settings The settings containing the ARNs to listen for.
	 * @param LoggerInterface    $logger PSR logger.
	 *
	 * @since    1.0.0
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
	}


	/**
	 * Find and return all integrations.
	 *
	 * @return SES_Bounce_Handler_Integration_Interface[]
	 */
	public function get_integrations(): array {

		$built_in_integrations                = array();
		$built_in_integrations['WordPress']   = new WordPress( $this->logger );
		$built_in_integrations['WooCommerce'] = new WooCommerce( $this->logger );
		$built_in_integrations['Newsletter']  = new Newsletter( $this->logger );
		$built_in_integrations['MailPoet']    = new MailPoet( $this->logger );

		$integrations = apply_filters( 'bh_wp_aws_ses_bounce_handler_integrations', $built_in_integrations );

		// Clean the data.
		$integrations = array_filter(
			$integrations,
			function( $integration ) {
				return $integration instanceof SES_Bounce_Handler_Integration_Interface;
			}
		);

		return $integrations;
	}


	/**
	 * When a bounce notification is received from SES fire the action for integrations and other plugins to hook into.
	 *
	 * @hooked bh_aws_ses_notification
	 *
	 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
	 *
	 * @param string   $notification_topic_arn  The ARN of the received notification.
	 * @param array    $headers                 HTTP headers received from AWS SNS.
	 * @param stdClass $body                    HTTP body received from AWS SNS.
	 * @param stdClass $message                 The (potential) bounce report object from AWS SES.
	 */
	public function handle_bounces( $notification_topic_arn, $headers, $body, $message ): void {

		if ( 'Bounce' !== $message->notificationType ) {
			return;
		}

		if ( 'Permanent' === $message->bounce->bounceType ) {

			foreach ( $message->bounce->bouncedRecipients as $bounced_recipient ) {

				$email_address = sanitize_email( $bounced_recipient->emailAddress );

				foreach ( $this->get_integrations() as $integration ) {

					if ( $integration->is_enabled() ) {
						$integration->init();
						$integration->handle_ses_bounce( $email_address, $bounced_recipient, $message );
					}
				}

				/**
				 * Action to allow other plugins to act on SES bounce notification.
				 *
				 * @param string $email_address     The email address that has bounced.
				 * @param stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
				 * @param stdClass $message           Parent object of complete notification.
				 *
				 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
				 */
				do_action( 'handle_ses_bounce', $email_address, $bounced_recipient, $message );
			}
		}
	}

	/**
	 * When a complaint notification is received from SES fire the action for integrations and other plugins to hook into.
	 *
	 * @hooked filter bh_aws_sns_notification
	 *
	 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
	 *
	 * @param string   $notification_topic_arn  The ARN of the received notification.
	 * @param array    $headers                 HTTP headers received from AWS SNS.
	 * @param stdClass $body                    HTTP body received from AWS SNS.
	 * @param stdClass $message                 The (potential) complaint report object from AWS SES.
	 */
	public function handle_complaints( $notification_topic_arn, $headers, $body, $message ): void {

		if ( 'Complaint' !== $message->notificationType ) {
			return;
		}

		foreach ( $message->complaint->complainedRecipients as $complained_recipient ) {

			$email_address = sanitize_email( $complained_recipient->emailAddress );

			foreach ( $this->get_integrations() as $integration ) {

				if ( $integration->is_enabled() ) {
					$integration->init();
					$integration->handle_ses_complaint( $email_address, $complained_recipient, $message );
				}
			}

			/**
			 * Action to allow other plugins to act on SES complaint notifications.
			 *
			 * @param string $email_address     The email address that has complained.
			 * @param stdClass $bounced_recipient Parent object with emailAddress, status, action, diagnosticCode.
			 * @param stdClass $message           Parent object of complete notification.
			 *
			 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
			 */
			do_action( 'handle_ses_complaint', $email_address, $complained_recipient, $message );
		}
	}


	/**
	 * When a complaint notification is received from SES fire the action for integrations and other plugins to hook into.
	 *
	 * @hooked filter ea_aws_sns_notification
	 *
	 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
	 *
	 * @param string   $notification_topic_arn  The ARN of the received notification.
	 * @param array    $headers                 HTTP headers received from AWS SNS.
	 * @param stdClass $body                    HTTP body received from AWS SNS.
	 * @param stdClass $message                 The (potential) complaint report object from AWS SES.
	 */
	public function handle_unsubscribe_emails( $notification_topic_arn, $headers, $body, $message ): void {

		if ( 'Received' !== $message->notificationType ) {
			return;
		}

		$email = $message->mail;

		$email_address = sanitize_email( $email->source );

		foreach ( $this->get_integrations() as $integration ) {

			if ( $integration->is_enabled() ) {
				$integration->init();
				if ( method_exists( $integration, 'handle_unsubscribe_email' ) ) {
					$integration->handle_unsubscribe_email( $email_address, $email, $message );
				}
			}
		}

		/**
		 * Action to allow other plugins to act on SES complaint notifications.
		 *
		 * @param string $email_address     The email address that has complained.
		 * @param stdClass $email Parent object with emailAddress, status, action, diagnosticCode.
		 * @param stdClass $message           Parent object of complete notification.
		 *
		 * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
		 */
		do_action( 'handle_unsubscribe_email', $email_address, $email, $message );

	}

}
