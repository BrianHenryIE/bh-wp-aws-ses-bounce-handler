<?php
/**
 * Tests for Admin.
 *
 * @see Admin
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Admin
 */
class Admin_Test extends \Codeception\Test\Unit {

    protected function _before() {
        \WP_Mock::setUp();
    }

    protected function _tearDown() {
        parent::_tearDown();
        \WP_Mock::tearDown();
    }

	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @see Admin::enqueue_styles()
	 * @see wp_enqueue_style()
	 *
	 * @covers ::enqueue_styles
	 */
	public function test_enqueue_styles_on_settings_page() {

		global $plugin_root_dir;

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
				'return' => $plugin_root_dir . '/admin/',
			)
		);

		$css_file = $plugin_root_dir . '/admin/css/bh-wp-aws-ses-bounce-handler-admin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 1,
				'args'  => array( 'bh-wp-aws-ses-bounce-handler', $css_file, array(), '2.0.0', 'all' ),
			)
		);

		$api = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class,
            array(
                'get_plugin_slug' => 'bh-wp-aws-ses-bounce-handler',
                'get_plugin_version' => '2.0.0'
            )
        );

		$bh_wp_aws_ses_bounce_handler_admin = new Admin( $api, $settings );

		$bh_wp_aws_ses_bounce_handler_admin->enqueue_styles();

		$this->assertFileExists( $css_file );
	}


	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @see Admin::enqueue_styles()
	 * @see wp_enqueue_style()
	 *
	 * @covers ::enqueue_styles
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
				'args'  => array( 'bh-wp-aws-ses-bounce-handler', $css_file, array(), '2.0.0', 'all' ),
			)
		);

		$api = $this->makeEmpty( API_Interface::class );
        $settings = $this->makeEmpty( Settings_Interface::class,
            array(
                'get_plugin_slug' => 'bh-wp-aws-ses-bounce-handler',
                'get_plugin_version' => '2.0.0'
            )
        );

        $bh_wp_aws_ses_bounce_handler_admin = new Admin( $api, $settings );

		$bh_wp_aws_ses_bounce_handler_admin->enqueue_styles();

	}
}
