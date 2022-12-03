<?php
/**
 * When logging with bh-wp-logger, replace mentions of Newsletter subscribers with links to their user info page.
 *
 * @see admin.php?page=newsletter_users_edit&id=
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Logger;

use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Logger\Logger_Settings_Interface;

/**
 * Filter on `bh-wp-aws-bounce-handler_bh_wp_logger_column` and replace `tnp_user:123` with links to `admin.php?page=newsletter_users_edit&id=`.
 */
class TNP_User_Hyperlink {

	/**
	 * Update `tnp_user:123` with links to the Newsletter subscriber page.
	 * Use preg_replace_callback to find and replace all instances in the string.
	 *
	 * @hooked {$plugin_slug}_bh_wp_logger_column
	 *
	 * @param string                                                          $column_output The column output so far.
	 * @param array{time:string, level:string, message:string, context:array} $item The log entry row.
	 * @param string                                                          $column_name The current column name.
	 * @param Logger_Settings_Interface                                       $logger_settings The logger settings.
	 * @param BH_WP_PSR_Logger                                                $bh_wp_psr_logger The logger API instance.
	 *
	 * @return string
	 */
	public function replace_tnp_user_id_with_link( string $column_output, array $item, string $column_name, Logger_Settings_Interface $logger_settings, BH_WP_PSR_Logger $bh_wp_psr_logger ): string {

		if ( 'message' !== $column_name ) {
			return $column_output;
		}

		$callback = function( array $matches ): string {

			$tnp_user_id           = $matches[1];
			$tnp_user_display_name = $tnp_user_id;

			if ( class_exists( \Newsletter::class ) ) {
				$newsletter = \Newsletter::instance();
				$user       = $newsletter->get_user( $tnp_user_id );

				if ( ! empty( $user ) ) {
					$tnp_user_display_name = $user->email;
				}
			}

			$url  = admin_url( "admin.php?page=newsletter_users_edit&id={$matches[1]}" );
			$link = "<a href=\"{$url}\">Newsletter subscriber {$tnp_user_display_name}</a>";

			return $link;
		};

		$message = preg_replace_callback( '/`tnp_user:(\d+)`/', $callback, $column_output ) ?? $column_output;

		return $message;
	}
}
