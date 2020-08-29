<?php
/**
 * Simple logger.
 *
 * @link       https://BrianHenry.ie
 * @since      1.4.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 */

namespace BH_WP_AWS_SES_Bounce_Handler;

/**
 * Uses php's error_log.
 *
 * Class Logger
 *
 * @package BH_WP_AWS_SES_Bounce_Handler
 */
class Logger {

	/**
	 * Simple logger.
	 *
	 * @param string $message The log message to record.
	 * @param string $level The log level to record.
	 */
	public static function log( $message, $level = 'debug' ) {

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( __NAMESPACE__ . " [$level] " . $message, 0 );
	}
}
