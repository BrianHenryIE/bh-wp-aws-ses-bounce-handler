<?php
/**
 * Tests for BH_WP_AWS_SES_Bounce_Handler main setup class. Tests the actions are correctly added.
 *
 * @package BH_WP_AWS_SES_Bounce_Handler
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\includes;

/**
 * Class Develop_Test
 */
class BH_WP_AWS_SES_Bounce_Handler_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Verify admin_enqueue_scripts action is correctly added for styles, at priority 10.
	 */
	public function test_action_admin_enqueue_scripts_styles() {

		$action_name       = 'admin_enqueue_scripts';
		$expected_priority = 10;

		$bh_wp_aws_ses_bounce_handler = $GLOBALS['bh_wp_aws_ses_bounce_handler'];

		$class = $bh_wp_aws_ses_bounce_handler->admin;

		$function = array( $class, 'enqueue_styles' );

		$actual_action_priority = has_action( $action_name, $function );

		$this->assertNotFalse( $actual_action_priority );

		$this->assertEquals( $expected_priority, $actual_action_priority );

	}

	/**
	 * Verify admin_enqueue_scripts action is added for scripts, at priority 10.
	 */
	public function test_action_admin_enqueue_scripts_scripts() {

		$filter_name       = 'admin_enqueue_scripts';
		$expected_priority = 10;

		$bh_wp_aws_ses_bounce_handler = $GLOBALS['bh_wp_aws_ses_bounce_handler'];

		$class = $bh_wp_aws_ses_bounce_handler->admin;

		$function = array( $class, 'enqueue_scripts' );

		$actual_filter_priority = has_filter( $filter_name, $function );

		$this->assertNotFalse( $actual_filter_priority );

		$this->assertEquals( $expected_priority, $actual_filter_priority );

	}

	/**
	 * Verify action to call load textdomain is added.
	 */
	public function test_action_plugins_loaded_load_plugin_textdomain() {

		$action_name       = 'plugins_loaded';
		$expected_priority = 10;

		$bh_wp_aws_ses_bounce_handler = $GLOBALS['bh_wp_aws_ses_bounce_handler'];

		$class = $bh_wp_aws_ses_bounce_handler->i18n;

		$function = array( $class, 'load_plugin_textdomain' );

		$actual_action_priority = has_action( $action_name, $function );

		$this->assertNotFalse( $actual_action_priority );

		$this->assertEquals( $expected_priority, $actual_action_priority );

	}

	/**
	 * Check all three integrations are hooked onto handle_ses_bounce
	 */
	public function test_integrations_hooks_added() {

		do_action( 'plugins_loaded' );

		$action_name = 'handle_ses_bounce';

		global $wp_filter;

		$this->assertArrayHasKey( $action_name, $wp_filter );

		/**
		 * The handle_bounce_hook which should have 3 actions hooked at priority 10.
		 */
		$handle_bounce_hook = $wp_filter['handle_ses_bounce'];

		$this->assertEquals( 3, count( $handle_bounce_hook->callbacks[10] ) );

	}
}
