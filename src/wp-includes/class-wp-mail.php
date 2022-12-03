<?php
/**
 * Prevent sending email to bounced user.
 * Log emails.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use WP_User;

/**
 * Filter wp_mail to remove the to: address, log to the PSR logger.
 */
class WP_Mail {
	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Check is the destination address a bounced email address, remove the email address from the to: field if so.
	 * Log to the PSR logger when it happens.
	 *
	 * @hooked wp_mail
	 *
	 * @param array{to:string|string[],subject:string} $wp_mail_atts The to, subject, message, headers, attachments of the email being sent.
	 *
	 * @return array{to:string|string[],subject:string}
	 * @see wp_mail()
	 */
	public function remove_bounced_destination_email_addresses( array $wp_mail_atts ): array {

		$emails = (array) $wp_mail_atts['to'];

		$valid_emails = array();

		foreach ( $emails as $email ) {
			$wp_user = get_user_by( 'email', $email );

			if ( $wp_user instanceof WP_User ) {

				$caps  = array_keys( $wp_user->caps );
				$roles = $wp_user->roles;

				if ( in_array( 'bounced_email', array_merge( $roles, $caps ), true ) ) {

					$this->logger->notice( "Attempting to email `wp_user:{$wp_user->ID}` with previously bounced email address. Email suppressed: {$wp_mail_atts['subject']}" );

					continue;
				}
			}

			$valid_emails[] = $email;
		}

		$wp_mail_atts['to'] = $valid_emails;

		return $wp_mail_atts;
	}
}
