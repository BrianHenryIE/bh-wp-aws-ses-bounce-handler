<?php
/**
 * Fired during plugin activation
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 */

namespace BH_WP_AWS_SES_Bounce_Handler\includes;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * Class Activator
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\includes
 */
class Activator {


	/**
	 * Register the Bounced Email WordPress user role.
	 * The role has no capabilities.
	 *
	 * @since 1.2.0
	 */
	public static function activate(): void {
		add_role( 'bounced_email', 'Bounced Email' );

		$secret_key = get_option( Settings_Interface::SECRET_KEY );
		if ( false === $secret_key ) {
			$secret_key = wp_generate_password( 12, false );
			update_option( Settings_Interface::SECRET_KEY, $secret_key );
		}
	}

}

