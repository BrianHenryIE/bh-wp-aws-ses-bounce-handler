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
 * @package   BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Includes;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\API_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Settings_Interface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Admin;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Ajax;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Plugins_Page;
use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Settings_Page;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * sns-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BH_WP_AWS_SES_Bounce_Handler
 * @subpackage BH_WP_AWS_SES_Bounce_Handler/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 *
 * phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found
 */
class BH_WP_AWS_SES_Bounce_Handler {

	use LoggerAwareTrait;

	protected Settings_Interface $settings;
	protected API_Interface $api;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the sns-facing side of the site.
	 *
	 * @since    1.1.0
	 *
	 * @param Settings_Interface $settings The setting the plugin should be run with.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_rest_hooks();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function set_locale() {

		$plugin_i18n = new I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks() {

		$admin = new Admin( $this->api, $this->settings );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );

		$settings_page = new Settings_Page( $this->api, $this->settings );
		add_action( 'admin_menu', array( $settings_page, 'add_settings_page' ) );

		$ajax = new Ajax( $this->api, $this->logger );
		add_action( 'wp_ajax_run_ses_bounce_test', array( $ajax, 'run_ses_bounce_test' ) );
		add_action( 'wp_ajax_fetch_test_results', array( $ajax, 'fetch_test_results' ) );
		add_action( 'wp_ajax_delete_test_data', array( $ajax, 'delete_test_data' ) );

		$plugins_page    = new Plugins_Page( $this->settings );
		$plugin_basename = $this->settings->get_plugin_basename();
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $plugins_page, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta' ), 20, 4 );
	}

	/**
	 * Register all of the hooks related to the sns-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function define_rest_hooks() {

		$sns = new REST( $this->api, $this->settings, $this->logger );
		add_action( 'rest_api_init', array( $sns, 'add_bh_aws_ses_rest_endpoint' ) );
	}

}
