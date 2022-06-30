<?php
/**
 * Tests for the wp-admin Settnigs page.
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\Settings;
use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;

/**
 * Tests the wp_mail function introspection.
 *
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Settings_Page
 *
 * @package BrianHenryIE\AWS_SES_Bounce_Handler\Admin
 */
class Settings_Page_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the code that detects what class/plugin is being used to send mail from WordPress.
	 * This should tell us the WordPress core pluggable.php.
	 *
	 * @covers ::get_wp_mail_info
	 */
	public function test_get_wp_mail_info() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = new Settings();

		$settings_page = new Settings_Page( $api, $settings );

		$wp_mail_info = $settings_page->get_wp_mail_info();

		$eg = '<div class="notice inline notice-warning"><p>WordPress is sending mail using <em>/Users/BrianHenryIE/Sites/bh-wp-aws-ses-bounce-handler/vendor/wordpress/wordpress/tests/phpunit/includes/mock-mailer.php</em>.</p></div>';

		$pattern = '/.*mock-mailer.php.*/';

		$this->assertTrue( 1 === preg_match( $pattern, $wp_mail_info ) );
	}

}
