<?php
/**
 * Tests for Admin.
 *
 * @see Admin
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\admin;

/**
 * Class Admin_Test
 */
class Admin_Test extends \Codeception\Test\Unit {

	protected function _before() {
		\WP_Mock::setUp();
	}

	/**
	 * The plugin name. Unlikely to change.
	 *
	 * @var string Plugin name.
	 */
	private $plugin_name = 'bh-wp-aws-ses-bounce-handler';

	/**
	 * The plugin version, matching the version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @see Admin::enqueue_styles()
	 * @see wp_enqueue_style()
	 */
	public function test_enqueue_styles_on_settings_page() {

		global $plugin_root_dir;

		global $pagenow;
		$pagenow = 'options-general.php';

		$_GET['page'] = $this->plugin_name;

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return' => $this->plugin_name,
			)
		);

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => $plugin_root_dir . '/admin/',
			)
		);

		$css_file = $plugin_root_dir . '/admin/css/bh-wp-aws-ses-bounce-handler-admin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 1,
				'args'  => array( $this->plugin_name, $css_file, array(), $this->version, 'all' ),
			)
		);

		$bh_wp_aws_ses_bounce_handler_admin = new Admin( $this->plugin_name, $this->version );

		$bh_wp_aws_ses_bounce_handler_admin->enqueue_styles();

		$this->assertFileExists( $css_file );
	}


	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @see Admin::enqueue_styles()
	 * @see wp_enqueue_style()
	 */
	public function test_does_not_enqueue_styles_on_non_settings_pages() {

		global $plugin_root_dir;

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => $plugin_root_dir . '/admin/',
			)
		);

		$css_file = $plugin_root_dir . '/admin/css/bh-wp-aws-ses-bounce-handler-admin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 0,
				'args'  => array( $this->plugin_name, $css_file, array(), $this->version, 'all' ),
			)
		);

		$bh_wp_aws_ses_bounce_handler_admin = new Admin( $this->plugin_name, $this->version );

		$bh_wp_aws_ses_bounce_handler_admin->enqueue_styles();

	}
}
