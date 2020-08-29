<?php
/**
 * Tests for the wp-admin Settnigs page.
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\admin;

use BH_WP_AWS_SES_Bounce_Handler\includes\Settings;

/**
 * Tests the wp_mail function introspection.
 *
 * Class Settings_Page_Test
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\admin
 */
class Settings_Page_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the code that detects what class/plugin is being used to send mail from WordPress.
	 * This should tell us the WordPress core pluggable.php.
	 */
	public function test_get_wp_mail_info() {

		$settings = new Settings();

		$settings_page = new Settings_Page( 'plugin_name', 'version', $settings );

		$wp_mail_info = $settings_page->get_wp_mail_info();

		$eg = '<div class="notice inline notice-warning"><p>WordPress is sending mail using <em>/Users/BrianHenryIE/Sites/bh-wp-aws-ses-bounce-handler/vendor/wordpress/wordpress/tests/phpunit/includes/mock-mailer.php</em>.</p></div>';

		$pattern = '/.*mock-mailer.php.*/';

		$this->assertTrue( 1 === preg_match( $pattern, $wp_mail_info ) );
	}

}
