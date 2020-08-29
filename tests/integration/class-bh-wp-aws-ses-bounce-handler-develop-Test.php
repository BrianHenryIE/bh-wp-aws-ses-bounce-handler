<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package BH_WP_AWS_SES_Bounce_Handler
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler;

use BH_WP_AWS_SES_Bounce_Handler\includes\BH_WP_AWS_SES_Bounce_Handler;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wp_aws_ses_bounce_handler', $GLOBALS );

		$this->assertInstanceOf( BH_WP_AWS_SES_Bounce_Handler::class, $GLOBALS['bh_wp_aws_ses_bounce_handler'] );
	}

}
