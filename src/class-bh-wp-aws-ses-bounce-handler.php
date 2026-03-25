<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * sns-facing side of the site and the admin area.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Admin_Assets;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Ajax;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Plugins_Page;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Settings_Page;
use BrianHenryIE\AWS_SES_Bounce_Handler\Logger\TNP_User_Hyperlink;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\I18n;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\REST;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\WP_Mail;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerAwareTrait;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * sns-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class BH_WP_AWS_SES_Bounce_Handler {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the sns-facing side of the site.
	 *
	 * @since    1.1.0
	 *
	 * @param API_Interface      $api The main plugin functions which may be accessed via REST/AJAX/cron/CLI/etc.
	 * @param Settings_Interface $settings The settings the plugin should be run with.
	 * @param LoggerInterface    $logger A PSR logger for the plugin's classes to use.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Settings_Interface $settings,
		protected LoggerInterface $logger
	) {

		$this->logger = $logger;

		$this->set_locale();

		$this->define_admin_settings_page_hooks();
		$this->define_admin_ajax_hooks();
		$this->define_admin_plugins_page_hooks();
		$this->define_rest_hooks();
		$this->define_wp_mail_hooks();
		$this->define_logger_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register the hooks related to displaying the admin settings page.
	 */
	protected function define_admin_settings_page_hooks(): void {

		$settings_page = new Settings_Page( $this->api, $this->settings );
		add_action( 'admin_menu', array( $settings_page, 'add_settings_page' ) );

		$admin = new Admin_Assets( $this->api, $this->settings );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
	}

	/**
	 * Register the hooks related to handling AJAX functionality of the plugin.
	 */
	protected function define_admin_ajax_hooks(): void {

		$ajax = new Ajax( $this->api, $this->logger );

		add_action( 'wp_ajax_run_ses_bounce_test', array( $ajax, 'run_ses_bounce_test' ) );
		add_action( 'wp_ajax_fetch_test_results', array( $ajax, 'fetch_test_results' ) );
		add_action( 'wp_ajax_delete_test_data', array( $ajax, 'delete_test_data' ) );

		add_action( 'wp_ajax_bh_wp_aws_ses_bounce_handler_set_log_level', array( $ajax, 'set_log_level' ) );
	}

	/**
	 * Register the hooks related plugins.php.
	 */
	protected function define_admin_plugins_page_hooks(): void {

		$plugins_page    = new Plugins_Page( $this->settings );
		$plugin_basename = $this->settings->get_plugin_basename();
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $plugins_page, 'action_links' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta' ), 20, 4 );
	}

	/**
	 * Register the hooks related to the sns-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_rest_hooks(): void {

		$sns = new REST( $this->api, $this->settings, $this->logger );
		add_action( 'rest_api_init', array( $sns, 'add_bh_aws_ses_rest_endpoint' ) );
	}

	/**
	 * Hook into wp_mail to filter bounced email addresses from outgoing mail.
	 */
	protected function define_wp_mail_hooks(): void {

		$wp_mail = new WP_Mail( $this->logger );

		add_filter( 'wp_mail', array( $wp_mail, 'remove_bounced_destination_email_addresses' ) );
		add_filter( 'pre_wp_mail', array( $wp_mail, 'cancel_sending_email_when_all_addresses_removed' ), 10, 2 );
	}

	/**
	 * Add hooks to modify the recording/output of the logs.
	 */
	protected function define_logger_hooks(): void {

		$tnp_user_hyperlink = new TNP_User_Hyperlink();
		add_filter( "{$this->settings->get_plugin_slug()}_bh_wp_logger_column", array( $tnp_user_hyperlink, 'replace_tnp_user_id_with_link' ), 10, 5 );
	}
}
