<?php
/**
 * Prevent sending email to bounced user.
 * Log emails.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes;

use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerAwareTrait;
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
	public function __construct(
		LoggerInterface $logger
	) {
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

	/**
	 * If the above function removed all the to: addresses, cancel sending the email on the `pre_wp_mail` hook. This
	 * hook is not implemented in wp-offload-ses, hence the address checking/removal is done above, but is implemented
	 * in fluent-smtp, and it should be used where possible.
	 *
	 * @hooked pre_wp_mail
	 * @see wp_mail()
	 *
	 * @param ?mixed                    $cancel Any non-null value will cancel sending the email.
	 * @param array{to:string|string[]} $wp_mail_atts The to, subject, message, headers, attachments of the email being sent.
	 *
	 * @return ?mixed
	 */
	public function cancel_sending_email_when_all_addresses_removed( $cancel, array $wp_mail_atts ) {

		if ( empty( $wp_mail_atts['to'] ) ) {
			return false;
		}

		return $cancel;
	}
}
