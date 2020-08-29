<?php
/**
 * Class implementing the required settings for the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 */

namespace BH_WP_AWS_SES_Bounce_Handler\API;

use BH_WP_AWS_SES_Bounce_Handler\integrations\SES_Bounce_Handler_Integration_Interface;

/**
 * The plugin settings.
 *
 * @since      1.0.0
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Settings implements Settings_Interface {

	/**
	 * List of ARNs which have successfully been confirmed with AWS SNS.
	 *
	 * @return string[]
	 */
	public function get_confirmed_arns(): array {
		return get_option( self::CONFIRMED_ARNS, array() );
	}

	/**
	 * Add an ARN string to the list of confirmed ARNs and save the option.
	 *
	 * @param string $arn AWS SNS ARN.
	 */
	public function set_confirmed_arn( string $arn ) {
		$confirmed_arns   = $this->get_confirmed_arns();
		$confirmed_arns[] = $arn;
		update_option( self::CONFIRMED_ARNS, array_unique( $confirmed_arns ) );
	}

	/**
	 * Find and return all integrations.
	 *
	 * @return SES_Bounce_Handler_Integration_Interface[]
	 */
	public function get_integrations(): array {
		return apply_filters( 'bh_wp_aws_ses_bounce_handler_integrations', array() );
	}

	/**
	 * Return the secret key autogenerated on plugin activation, which must be present in all
	 * requests or the REST endpoint will disregard them.
	 *
	 * @return string
	 */
	public function get_secret_key(): string {
		return get_option( self::SECRET_KEY );
	}

	/**
	 * The full endpoint URL: https://brianhenry.ie/wp-json/brianhenryie/v1/aws-ses/?secret=autogend.
	 *
	 * @return string
	 */
	public function get_endpoint(): string {
		return get_rest_url( null, 'brianhenryie/v1/aws-ses/?secret=' . $this->get_secret_key() );
	}
}