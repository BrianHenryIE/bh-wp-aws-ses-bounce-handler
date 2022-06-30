<?php
/**
 * The sns-invoked functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/sns
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Includes;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use stdClass;

/**
 * The sns-invoked functionality of the plugin.
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/sns
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 *
 * Ignore snake case warnings for JSON objects.
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class REST {

	use LoggerAwareTrait;

	/**
	 * The settings object contains the AWS ARNs to listen to, as configured by the user.
	 *
	 * @var Settings_Interface
	 */
	protected $settings;

	protected API_Interface $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Settings_Interface $settings The settings containing the ARNs to listen for.
	 * @param LoggerInterface    $logger PSR logger.
	 *
	 * @since    1.0.0
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Defines the REST endpoint itself. Added on WordPress `rest_api_init` action.
	 */
	public function add_bh_aws_ses_rest_endpoint(): void {

		register_rest_route(
			'brianhenryie/v1',
			'/aws-ses/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'process_new_aws_sns_notification' ),
				'permission_callback' => array( $this, 'check_secret' ),
			)
		);
	}

	/**
	 * Check does the request have the 'secret' parameter and does it match what's in the database.
	 *
	 * @param \WP_REST_Request $request Request used to generate the response.
	 *
	 * @return bool
	 */
	public function check_secret( $request ) {

		$request_secret = $request->get_param( 'secret' );

		// Check the URL `secret` querystring.
		if ( ! $request_secret ) {
			$this->logger->info( 'secret not present' );
			return false;
		}

		$saved_secret = $this->settings->get_secret_key();

		if ( $saved_secret !== $request_secret ) {
			$this->logger->info( "secret incorrect. Was $request_secret expected $saved_secret." );
			return false;
		}

		return true;
	}


	/**
	 * Parse the REST request for the SNS notification.
	 *
	 * @param \WP_REST_Request $request The HTTP request received at our REST endpoint.
	 *
	 * @return bool
	 */
	public function process_new_aws_sns_notification( \WP_REST_Request $request ) {

		$headers = $request->get_headers();

		// If this is not an AWS SNS message.
		if ( ! isset( $headers['x_amz_sns_message_type'] ) || ! isset( $headers['x_amz_sns_topic_arn'] ) ) {
			return false;
		}

		$body = json_decode( $request->get_body() );

		/**
		 * The possible message type values are SubscriptionConfirmation, Notification, and UnsubscribeConfirmation.
		 *
		 * @see https://docs.aws.amazon.com/sns/latest/dg/sns-message-and-json-formats.html
		 */
		$message_type = $headers['x_amz_sns_message_type'][0];

		switch ( $message_type ) {
			case 'SubscriptionConfirmation':
				$this->blindly_confirm_subscription_requests( $headers, $body );
				return true;

			case 'UnsubscribeConfirmation':
				// It doesn't seem that SNS sends a confirmation when the subscription is deleted in the AWS console.
				return false;

			case 'Notification':
				$topic_arn = $body->TopicArn;

				// Do not process data from unknown sources.
				if ( ! in_array( $topic_arn, $this->settings->get_confirmed_arns(), true ) ) {
					return false;
				}

				$message = json_decode( $body->Message );

				$this->api->handle_bounces( $topic_arn, $headers, $body, $message );
				$this->api->handle_complaints( $topic_arn, $headers, $body, $message );
				$this->api->handle_unsubscribe_emails( $topic_arn, $headers, $body, $message );

				break;
			default:
				// Unexpected.
				return false;
		}

		return true;
	}

	/**
	 * Respond to AWS subscription requests with "yes"!
	 *
	 * @param array    $headers  The HTTP headers received from AWS SNS.
	 * @param stdClass $body     The parsed JSON received from AWS SNS.
	 *
	 * @return array
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	 */
	public function blindly_confirm_subscription_requests( $headers, $body ) {

		$subscription_topic = $body->TopicArn;

		$confirmation_url = $body->SubscribeURL;

		$request_response = wp_remote_get( $confirmation_url );

		if ( is_wp_error( $request_response ) ) {
			/**
			 * The request_response is an error, usually when there is no response whatsoever, or a problem
			 * initiating the communication.
			 *
			 * @var \WP_Error $request_response
			 */

			// TODO: Reschedule confirmation.
			// Pretty unusual that this would fail, given it runs when being pinged by AWS.

			$error_message = 'Error confirming subscription <b><i>' . $subscription_topic . '</i></b>: ' . $request_response->get_error_message();

			$this->logger->error( $error_message );

			return array(
				'error'   => $request_response->get_error_code(),
				'message' => $error_message,
			);
		}

		// If unsuccessful.
		if ( 2 !== intval( $request_response['response']['code'] / 100 ) ) {

			$xml = new SimpleXMLElement( $request_response['body'] );

			$error_message = 'Error confirming subscription for topic <b><i>' . $subscription_topic . '</i></b>. ' . $request_response['response']['message'] . ' : ' . $xml->{'Error'}->{'Message'};

			return array(
				'error'   => $request_response['response']['code'],
				'message' => $error_message,
			);
		}

		$message = "AWS SNS topic <b><i>$subscription_topic</i></b> subscription confirmed.";

		$this->settings->set_confirmed_arn( $subscription_topic );

		return array(
			'success' => $subscription_topic,
			'message' => $message,
		);
	}

}

