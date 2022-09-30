<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WP_AWS_SES_Bounce_Handler
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\API;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Settings;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Logger\Logger;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include(): void {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( BH_WP_AWS_SES_Bounce_Handler::class, '__construct' ),
			function( $api, $settings, $logger ) {}
		);

		\Patchwork\redefine(
			array( Logger::class, '__construct' ),
			function() {}
		);

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook',
			array(
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook',
			array(
				'times' => 1,
			)
		);

		ob_start();

		include $plugin_root_dir . '/bh-wp-aws-ses-bounce-handler.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wp_aws_ses_bounce_handler', $GLOBALS );

		$this->assertInstanceOf( BH_WP_AWS_SES_Bounce_Handler::class, $GLOBALS['bh_wp_aws_ses_bounce_handler'] );

	}

}
