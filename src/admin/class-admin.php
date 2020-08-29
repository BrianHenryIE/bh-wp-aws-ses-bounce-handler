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

namespace BH_WP_AWS_SES_Bounce_Handler\admin;

use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Object;

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
class Admin extends WPPB_Object {

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'bh-wp-aws-ses-bounce-handler' === filter_var( wp_unslash( $_GET['page'] ), FILTER_SANITIZE_STRING ) ) {

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bh-wp-aws-ses-bounce-handler-admin.css', array(), $this->get_version(), 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.2.0
	 */
	public function enqueue_scripts() {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'bh-wp-aws-ses-bounce-handler' === filter_var( wp_unslash( $_GET['page'] ), FILTER_SANITIZE_STRING ) ) {

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bh-wp-aws-ses-bounce-handler-admin.js', array( 'jquery' ), $this->get_version(), false );
		}
	}

}
