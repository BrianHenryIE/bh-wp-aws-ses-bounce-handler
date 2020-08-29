<?php
/**
 * Interface defining the required settings the plugin needs, and the constants used.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 */

namespace BH_WP_AWS_SES_Bounce_Handler\API;

/**
 * The settings interface.
 *
 * @since      1.0.0
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
interface Settings_Interface {

	const SECRET_KEY     = 'bh-wp-aws-ses-bounce-handler-secret-key';
	const CONFIRMED_ARNS = 'bh-wp-aws-ses-bounce-handler-confirmed-arns';

	/**
	 * The secret key generated on plugin activation, used in calls from AWS SNS to the site.
	 * Because the plugin auto-confirms subscriptions, the secret is needed to stop everyone being able to
	 * create one.
	 *
	 * @return string
	 */
	public function get_secret_key(): string;

	/**
	 * The full endpoint URL including the site URL and secret.
	 *
	 * @return string
	 */
	public function get_endpoint(): string;

	/**
	 * Return the list of ARNs that have been successfully confirmed.
	 *
	 * @return string[]
	 */
	public function get_confirmed_arns(): array;

	/**
	 * Record a successfully confirmed ARN subscription.
	 *
	 * @param string $arn AWS SNS ARN.
	 */
	public function set_confirmed_arn( string $arn );

}
