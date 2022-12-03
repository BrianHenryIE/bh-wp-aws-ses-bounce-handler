<?php

namespace BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;

/**
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\WP_Mail
 */
class WP_Mail_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	public function setUp(): void {
		parent::setUp();
		$wp_user = get_user_by( 'email', 'brianhenryie@gmail.com' );
		if ( $wp_user instanceof \WP_User ) {
			self::delete_user( $wp_user->ID );
		}
		add_role( 'bounced_email', 'Bounced Email' );
	}

	/**
	 * @covers ::remove_bounced_destination_email_addresses
	 */
	public function test_check_is_destination_email_bounced_happy(): void {

		$logger = new ColorLogger();

		$sut = new WP_Mail( $logger );

		$atts = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'subject',
		);

		$user_id = wp_create_user( 'brianhenryie', 'password', 'brianhenryie@gmail.com' );
		/** @var WP_User $wp_user */
		$wp_user = get_user_by( 'ID', $user_id );
		$wp_user->add_role( 'bounced_email' );

		$result = $sut->remove_bounced_destination_email_addresses( $atts );

		$this->assertNotContains( 'brianhenryie@gmail.com', $result['to'] );

		$this->assertTrue( $logger->hasNoticeRecords() );
	}

	/**
	 * @covers ::remove_bounced_destination_email_addresses
	 */
	public function test_check_is_destination_email_bounced_not_bounced(): void {

		$logger = new ColorLogger();

		$sut = new WP_Mail( $logger );

		$atts = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'subject',
		);

		wp_create_user( 'brianhenryie', 'password', 'brianhenryie@gmail.com' );

		$result = $sut->remove_bounced_destination_email_addresses( $atts );

		$this->assertContains( 'brianhenryie@gmail.com', $result['to'] );
	}
}
