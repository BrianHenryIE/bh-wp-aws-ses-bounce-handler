<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Settings_Interface;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Admin {

	protected Settings_Interface $settings;

	/**
	 * @var API_Interface
	 */
	protected API_Interface $api;

	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'bh-wp-aws-ses-bounce-handler' === filter_var( wp_unslash( $_GET['page'] ), FILTER_SANITIZE_STRING ) ) {
			$url = plugin_dir_url( __FILE__ ) . 'css/bh-wp-aws-ses-bounce-handler-admin.css';
			wp_enqueue_style( $this->settings->get_plugin_slug(), $url, array(), $this->settings->get_plugin_version(), 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.2.0
	 */
	public function enqueue_scripts(): void {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'bh-wp-aws-ses-bounce-handler' === filter_var( wp_unslash( $_GET['page'] ), FILTER_SANITIZE_STRING ) ) {

			wp_enqueue_script( $this->settings->get_plugin_slug(), plugin_dir_url( __FILE__ ) . 'js/bh-wp-aws-ses-bounce-handler-admin.js', array( 'jquery' ), $this->settings->get_plugin_version(), false );
		}
	}

}
