<?php
/**
 *
 * @link       https://BrianHenry.ie
 * @since      1.4.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/API
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API;

/**
 * The settings interface.
 *
 * @since      1.4.0
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/API
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
interface API_Interface {

	public function handle_bounces( $topic_arn, $headers, $body, $message ): void;
	public function handle_complaints( $topic_arn, $headers, $body, $message ): void;
	public function handle_unsubscribe_emails( $topic_arn, $headers, $body, $message ): void;

}
