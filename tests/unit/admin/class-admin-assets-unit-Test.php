<?php
/**
 * Tests for Admin.
 *
 * @see Admin_Assets
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Admin_Assets
 */
class Admin_Assets_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
		\WP_Mock::passthruFunction( 'sanitize_key' );
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @see Admin_Assets::enqueue_styles()
	 * @see wp_enqueue_style()
	 *
	 * @covers ::enqueue_styles
	 */
	public function test_enqueue_styles_on_settings_page() {

		global $plugin_root_dir;
		$plugin_slug = 'bh-wp-aws-ses-bounce-handler';

		global $pagenow;
		$pagenow = 'options-general.php';

		$_GET['page'] = 'bh-wp-aws-ses-bounce-handler';

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => 'bh-wp-aws-ses-bounce-handler',
			)
		);

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => "http://localhost/{$plugin_slug}/",
			)
		);

		$css_url  = "http://localhost/{$plugin_slug}/assets/bh-wp-aws-ses-bounce-handler-admin.css";
		$css_file = $plugin_root_dir . '/assets/bh-wp-aws-ses-bounce-handler-admin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 1,
				'args'  => array( 'bh-wp-aws-ses-bounce-handler', $css_url, array(), '2.1.1', 'all' ),
			)
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'     => 'bh-wp-aws-ses-bounce-handler',
				'get_plugin_basename' => 'bh-wp-aws-ses-bounce-handler/bh-wp-aws-ses-bounce-handler.php',
				'get_plugin_version'  => '2.1.1',
			)
		);

		$bh_wp_aws_ses_bounce_handler_admin = new Admin_Assets( $api, $settings );

		$bh_wp_aws_ses_bounce_handler_admin->enqueue_styles();

		$this->assertFileExists( $css_file );
	}


	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @see Admin_Assets::enqueue_styles()
	 * @see wp_enqueue_style()
	 *
	 * @covers ::enqueue_styles
	 */
	public function test_does_not_enqueue_styles_on_non_settings_pages() {

		global $plugin_root_dir;
		global $plugin_slug;

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => 'http://localhost/',
			)
		);

		$css_url  = "http://localhost/{$plugin_slug}/assets/bh-wp-aws-ses-bounce-handler-admin.css";
		$css_file = $plugin_root_dir . '/src/Admin/css/bh-wp-aws-ses-bounce-handler-admin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 0,
				'args'  => array( 'bh-wp-aws-ses-bounce-handler', $css_url, array(), '2.1.1', 'all' ),
			)
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'     => 'bh-wp-aws-ses-bounce-handler',
				'get_plugin_basename' => 'bh-wp-aws-ses-bounce-handler/bh-wp-aws-ses-bounce-handler.php',
				'get_plugin_version'  => '2.1.1',
			)
		);

		$bh_wp_aws_ses_bounce_handler_admin = new Admin_Assets( $api, $settings );

		$bh_wp_aws_ses_bounce_handler_admin->enqueue_styles();
	}
}
