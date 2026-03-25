<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.0
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
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
