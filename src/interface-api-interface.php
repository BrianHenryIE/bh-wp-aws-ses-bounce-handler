<?php
/**
 *
 * @link       https://BrianHenry.ie
 * @since      1.4.0
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\SES_Bounce_Handler_Integration_Interface;
use Psr\Log\LogLevel;
use stdClass;

interface API_Interface {

	public function handle_bounces( string $topic_arn, array $headers, stdClass $body, stdClass $message ): void;
	public function handle_complaints( string $topic_arn, array $headers, stdClass $body, stdClass $message ): void;
	public function handle_unsubscribe_emails( string $topic_arn, array $headers, stdClass $body, stdClass $message ): void;

	/**
	 * Find and return all integrations.
	 *
	 * @return SES_Bounce_Handler_Integration_Interface[]
	 */
	public function get_integrations(): array;

	/**
	 * Set the log level.
	 *
	 * @used-by \BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Ajax::set_log_level()
	 *
	 * @see LogLevel
	 *
	 * @param string $level A PSR log level.
	 *
	 * @return array{success:bool, message:string}
	 */
	public function set_log_level( string $level ): array;
}
