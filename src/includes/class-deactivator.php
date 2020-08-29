<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 */

namespace BH_WP_AWS_SES_Bounce_Handler\includes;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * Class Deactivator
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\includes
 */
class Deactivator {

	/**
	 * Remove the previously registered Bounced Email user role.
	 *
	 * @since 1.2.0
	 */
	public static function deactivate(): void {
		remove_role( 'bounced_email' );
	}

}

