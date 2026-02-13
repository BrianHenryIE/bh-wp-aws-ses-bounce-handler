<?php
/**
 * The wp-admin settings page to configure the ARNs to listen to.
 *
 * @link
 * @since      1.0.0
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Admin;

use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Settings_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LogLevel;


/**
 * Adds a wp-admin Settings submenu. Adds a page with input for bounces ARN and complaints ARN.
 */
class Settings_Page {

	/**
	 * The settings, to pass to the individual fields for populating.
	 *
	 * @var Settings_Interface $settings The previously saved settings for the plugin.
	 */
	protected Settings_Interface $settings;

	/**
	 * Needed to display what integrations are available or enabled.
	 *
	 * @uses API_Interface::get_integrations()
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Add the AWS SES Bounce Handler settings menu-item/page as a submenu-item of the Settings menu.
	 *
	 * /wp-admin/options-general.php?page=bh-wp-aws-ses-bounce-handler
	 *
	 * @hooked admin_menu
	 */
	public function add_settings_page(): void {

		add_options_page(
			'AWS SES Bounce Handler',
			'AWS SES Bounce Handler',
			'manage_options',
			$this->settings->get_plugin_slug(),
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Registered above, called by WordPress to display the admin settings page.
	 *
	 * @see API::set_log_level() for valid levels
	 */
	public function display_plugin_admin_page(): void {

		$settings = $this->settings;
		$api      = $this->api;

		$current_log_level = $this->settings->get_log_level();
		$logs_url          = admin_url( 'admin.php?page=bh-wp-aws-ses-bounce-handler-logs' );

		$allowed_log_levels = array(
			'none'            => 'None',
			LogLevel::ERROR   => 'Error',
			LogLevel::WARNING => 'Warning',
			LogLevel::NOTICE  => 'Notice',
			LogLevel::INFO    => 'Info',
			LogLevel::DEBUG   => 'Debug',
		);

		$template = 'admin/settings-page.php';

		$template_admin_settings_page = WP_PLUGIN_DIR . '/' . plugin_dir_path( $this->settings->get_plugin_basename() ) . 'templates/' . $template;

		// Check the child theme for template overrides.
		if ( file_exists( get_stylesheet_directory() . $template ) ) {
			$template_admin_settings_page = get_stylesheet_directory() . $template;
		} elseif ( file_exists( get_stylesheet_directory() . 'templates/' . $template ) ) {
			$template_admin_settings_page = get_stylesheet_directory() . 'templates/' . $template;
		}

		/**
		 * Allow overriding the admin settings template.
		 */
		$filtered_template_admin_settings_page = apply_filters( 'bh_wp_aws_ses_bounce_handler_admin_settings_page_template', $template_admin_settings_page );

		if ( file_exists( $filtered_template_admin_settings_page ) ) {
			include $filtered_template_admin_settings_page;
		} else {
			include $template_admin_settings_page;
		}
	}

	/**
	 * Figure out if WP_Mail is being overridden.
	 *
	 * @return string Admin notice showing how wp_mail is operating.
	 */
	public function get_wp_mail_info(): string {

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
	 * I.e. given the file that has the phpmailer, determine what plugin it is part of.
	 *
	 * TODO: See `global $wp_plugin_paths` if there are problems with this method.
	 *
	 * @see get_plugins()
	 *
	 * @param string $filename The file path we're trying to determine the plugin for.
	 *
	 * @return ?array<string, mixed> The plugin entry from get_plugins().
	 */
	private function get_plugin_from_path( string $filename ): ?array {

		// If the file is outside the plugins' dir, what's up? MU plugins?
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
