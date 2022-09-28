<?php
/**
 * Tests for BH_WP_AWS_SES_Bounce_Handler main setup class. Tests the actions are correctly added.
 *
 * @package BH_WP_AWS_SES_Bounce_Handler
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Admin_Assets;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\WordPress;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\I18n;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\REST;
use BrianHenryIE\ColorLogger\ColorLogger;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\BH_WP_AWS_SES_Bounce_Handler
 */
class BH_WP_AWS_SES_Bounce_Handler_Unit_Test extends \Codeception\Test\Unit {

	protected function _before() {
		\WP_Mock::setUp();
	}

	protected function _tearDown() {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 */
	public function test_set_locale_hooked() {

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
		$logger   = new ColorLogger();

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_hooks
	 */
	public function test_admin_hooks() {

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
		$logger   = new ColorLogger();

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );
	}

	/**
	 * Check all three integrations are hooked onto handle_ses_bounce
	 *
	 * @covers ::define_integrations_hooks
	 */
	public function test_integrations_hooks_added() {

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
	public function test_sns_hooks_added() {

		\WP_Mock::expectActionAdded(
			'rest_api_init',
			array( new AnyInstance( REST::class ), 'add_bh_aws_ses_rest_endpoint' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );

	}

}
