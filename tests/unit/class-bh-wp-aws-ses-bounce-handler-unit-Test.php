<?php
/**
 * Tests for BH_WP_AWS_SES_Bounce_Handler main setup class. Tests the actions are correctly added.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Admin_Assets;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\WordPress;
use BrianHenryIE\AWS_SES_Bounce_Handler\Logger\TNP_User_Hyperlink;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\I18n;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\REST;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\WP_Mail;
use BrianHenryIE\ColorLogger\ColorLogger;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\BH_WP_AWS_SES_Bounce_Handler
 */
class BH_WP_AWS_SES_Bounce_Handler_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 * @covers ::__construct
	 */
	public function test_set_locale_hooked(): void {

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => 'bh-wp-aws-ses-bounce-handler/bh-wp-aws-ses-bounce-handler.php',
			)
		);
		$logger   = new class() extends ColorLogger implements LoggerInterface{};

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_settings_page_hooks
	 */
	public function test_admin_hooks(): void {

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin_Assets::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin_Assets::class ), 'enqueue_scripts' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new class() extends ColorLogger implements LoggerInterface{};

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}

	/**
	 * Check all three integrations are hooked onto handle_ses_bounce
	 *
	 * @covers ::define_integrations_hooks
	 */
	public function test_integrations_hooks_added(): void {

		$this->markTestSkipped();

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( WordPress::class ), 'handle_ses_bounce' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}


	/**
	 *
	 * @covers ::define_rest_hooks
	 */
	public function test_sns_hooks_added(): void {

		\WP_Mock::expectActionAdded(
			'rest_api_init',
			array( new AnyInstance( REST::class ), 'add_bh_aws_ses_rest_endpoint' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new class() extends ColorLogger implements LoggerInterface{};

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}

	/**
	 *
	 * @covers ::define_wp_mail_hooks
	 */
	public function test_wp_mail_hooks_added(): void {

		\WP_Mock::expectFilterAdded(
			'wp_mail',
			array( new AnyInstance( WP_Mail::class ), 'remove_bounced_destination_email_addresses' )
		);

		\WP_Mock::expectFilterAdded(
			'pre_wp_mail',
			array( new AnyInstance( WP_Mail::class ), 'cancel_sending_email_when_all_addresses_removed' ),
			10,
			2
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new class() extends ColorLogger implements LoggerInterface{};

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}


	/**
	 *
	 * @covers ::define_logger_hooks
	 */
	public function test_logger_hooks_added(): void {

		\WP_Mock::expectFilterAdded(
			'bh-wp-aws-ses-bounce-handler_bh_wp_logger_column',
			array( new AnyInstance( TNP_User_Hyperlink::class ), 'replace_tnp_user_id_with_link' ),
			10,
			5
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug' => 'bh-wp-aws-ses-bounce-handler',
			)
		);
		$logger   = new class() extends ColorLogger implements LoggerInterface{};

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}
}
