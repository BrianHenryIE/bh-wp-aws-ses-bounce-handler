<?php
/**
 * The wp-admin settings page to configure the ARNs to listen to.
 *
 * @link
 * @since      1.0.0
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 */

namespace BH_WP_AWS_SES_Bounce_Handler\admin;

use BH_WP_AWS_SES_Bounce_Handler\admin\settings\AWS_SNS_ARN_Abstract;
use BH_WP_AWS_SES_Bounce_Handler\admin\partials\AWS_SNS_ARN_Complaints;
use BH_WP_AWS_SES_Bounce_Handler\admin\settings\Settings_Section_Element_Abstract;
use BH_WP_AWS_SES_Bounce_Handler\API\Settings_Interface;
use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Object;

/**
 * Adds a wp-admin Settings submenu. Adds a page with input for bounces ARN and copmlaints ARN.
 *
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Settings_Page extends WPPB_Object {

	/**
	 * The settings, to pass to the individual fields for populating.
	 *
	 * @var Settings_Interface $settings The previously saved settings for the plugin.
	 */
	private $settings;

	/**
	 * Settings_Page constructor.
	 *
	 * @param string             $plugin_name The plugin name.
	 * @param string             $version The plugin version.
	 * @param Settings_Interface $settings The previously saved settings for the plugin.
	 */
	public function __construct( $plugin_name, $version, $settings ) {
		parent::__construct( $plugin_name, $version );

		$this->settings = $settings;
	}

	/**
	 * Add the AWS SES Bounce Handler settings menu-item/page as a submenu-item of the Settings menu.
	 *
	 * /wp-admin/options-general.php?page=bh-wp-aws-ses-bounce-handler
	 *
	 * @hooked admin_menu
	 */
	public function add_settings_page() {

		add_options_page(
			'AWS SES Bounce Handler',
			'AWS SES Bounce Handler',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Registered above, called by WordPress to display the admin settings page.
	 */
	public function display_plugin_admin_page() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display.php';
	}

	/**
	 * Figure out if WP_Mail is being overridden.
	 *
	 * @return string Admin notice showing how wp_mail is operating.
	 */
	public function get_wp_mail_info() {

		$wp_mail_reflector = new \ReflectionFunction( 'wp_mail' );
		$wp_mail_filename  = $wp_mail_reflector->getFileName();

		$built_in_wp_mail_filename = 'wp-includes/pluggable.php';

		// If wp_mail has been overridden.
		if ( substr( $wp_mail_filename, - 1 * strlen( $built_in_wp_mail_filename ) ) !== $built_in_wp_mail_filename ) {

			$plugin = $this->get_plugin_from_path( $wp_mail_filename );

			if ( null === $plugin ) {
				return '<div class="notice inline notice-warning"><p>WordPress is sending mail using <em>' . $wp_mail_filename . '</em>.</p></div>';
			}

			$notice_type = 'warning';
			if ( stristr( $plugin['Name'], ' ses' )
				|| stristr( $plugin['Description'], ' ses' ) ) {
				$notice_type = 'success';
			}

			return '<div class="notice inline notice-' . $notice_type . '"><p>WordPress is sending mail using <em>' . $plugin['Name'] . '</em> plugin.</p></div>';

		}

		// If phpmailer has been set, check is it the built-in WordPress class.
		global $phpmailer;
		if ( ! empty( $phpmailer ) ) {
			try {
				$phpmailer_reflector = new \ReflectionClass( get_class( $phpmailer ) );

			} catch ( \ReflectionException $e ) {
				return '<div class="notice inline notice-error"><p>Error checking PHPMailer class: ' . $e->getMessage() . ' – ' . get_class( $phpmailer ) . '</p></div>';

			}
			$phpmailer_filename = $phpmailer_reflector->getFileName();

			$built_in_phpmailer_filename = 'wp-includes/class-phpmailer.php';

			// If phpMailer has been overridden (this happens in tests too).
			if ( substr( $phpmailer_filename, - 1 * strlen( $built_in_phpmailer_filename ) ) !== $built_in_phpmailer_filename ) {

				$plugin = $this->get_plugin_from_path( $phpmailer_filename );
				if ( null === $plugin ) {
					return '<div class="notice inline notice-warning"><p>WordPress is sending mail using <em>' . $phpmailer_filename . '</em>.</p></div>';
				}

				$notice_type = 'warning';
				if ( stristr( $plugin['Name'], ' ses' )
					|| stristr( $plugin['Description'], ' ses' ) ) {
					$notice_type = 'success';
				}

				return '<div class="notice inline notice-' . $notice_type . '"><p>WordPress is sending mail using <em>' . $plugin['Name'] . '</em> plugin.</p></div>';
			}
		}

		return '<div class="notice inline notice-error"><p>Email is being sent using WordPress\'s built in <code>wp_mail()</code> function. It is probably not being sent using AWS SES.</p></div>';

	}

	/**
	 * Given a filename, figure out what plugin it is from.
	 *
	 * @param string $filename The file path we're trying to deterime the plugin for.
	 *
	 * @return array|null
	 */
	private function get_plugin_from_path( $filename ) {

		// If the file is outside the plugins dir, whats's up? MU plugins?
		if ( ! stristr( $filename, WP_PLUGIN_DIR ) ) {
			return null;
		}

		$plugin_file = trim( substr( $filename, strlen( realpath( WP_PLUGIN_DIR ) ) ), DIRECTORY_SEPARATOR );

		$plugins = get_plugins();

		if ( array_key_exists( $plugin_file, $plugins ) ) {

			return $plugins[ $plugin_file ];
		}

		$plugin_slug = substr( $plugin_file, 0, strpos( $plugin_file, DIRECTORY_SEPARATOR ) );

		foreach ( $plugins as $file => $plugin ) {

			if ( stristr( $file, $plugin_slug ) ) {
				return $plugin;
			}
		}

		return null;
	}
}
