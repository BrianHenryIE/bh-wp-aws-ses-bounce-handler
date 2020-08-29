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

namespace BH_WP_AWS_SES_Bounce_Handler\includes;

use BH_WP_AWS_SES_Bounce_Handler\admin\Admin;
use BH_WP_AWS_SES_Bounce_Handler\admin\Ajax;
use BH_WP_AWS_SES_Bounce_Handler\admin\Plugins_Page;
use BH_WP_AWS_SES_Bounce_Handler\admin\Settings_Page;
use BH_WP_AWS_SES_Bounce_Handler\integrations\Newsletter;
use BH_WP_AWS_SES_Bounce_Handler\integrations\SES_Bounce_Handler_Integration_Interface;
use BH_WP_AWS_SES_Bounce_Handler\integrations\WooCommerce;
use BH_WP_AWS_SES_Bounce_Handler\integrations\WordPress;
use BH_WP_AWS_SES_Bounce_Handler\rest\SNS;
use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Loader_Interface;
use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Object;

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
class BH_WP_AWS_SES_Bounce_Handler extends WPPB_Object {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var     WPPB_Loader_Interface    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Public variable to allow accessing the i18n object.
	 *
	 * @var I18n
	 */
	public $i18n;

	/**
	 * Public variable to allow accessing the plugin's settings object.
	 *
	 * @var Settings_Interface
	 */
	public $settings;

	/**
	 * Public variable to allow modifying the SNS handler object.
	 *
	 * @var SNS
	 */
	public $sns;

	/**
	 * Public variable to allow modifying the settings page object.
	 *
	 * @var Settings_Page
	 */
	public $settings_page;

	/**
	 * Public variable for accessing Admin object.
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Make available to other code the class that handles the AJAX functions.
	 *
	 * @var Ajax
	 */
	public $ajax;

	/**
	 * Make accessible the plugins page.
	 *
	 * @var Plugins_Page
	 */
	public $plugins_page;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the sns-facing side of the site.
	 *
	 * @since    1.1.0
	 *
	 * @param WPPB_Loader_Interface $loader The WordPress Plugin Boilerplate loader object.
	 * @param Settings_Interface    $settings The setting the plugin should be run with.
	 */
	public function __construct( $loader, $settings ) {
		if ( defined( 'BH_WP_AWS_SES_BOUNCE_HANDLER_VERSION' ) ) {
			$version = BH_WP_AWS_SES_BOUNCE_HANDLER_VERSION;
		} else {
			$version = '1.0.0';
		}
		$plugin_name = 'bh-wp-aws-ses-bounce-handler';

		parent::__construct( $plugin_name, $version );

		$this->loader   = $loader;
		$this->settings = $settings;

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_sns_hooks();
		$this->define_integration_hooks();

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
	private function set_locale() {

		$this->i18n = $plugin_i18n = new I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->admin = new Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );

		$this->settings_page = new Settings_Page( $this->get_plugin_name(), $this->get_version(), $this->settings );
		$this->loader->add_action( 'admin_menu', $this->settings_page, 'add_settings_page' );

		$this->ajax = new Ajax( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_ajax_run_ses_bounce_test', $this->ajax, 'run_ses_bounce_test' );
		$this->loader->add_action( 'wp_ajax_fetch_test_results', $this->ajax, 'fetch_test_results' );
		$this->loader->add_action( 'wp_ajax_delete_test_data', $this->ajax, 'delete_test_data' );

		$this->plugins_page = new Plugins_Page( $this->get_plugin_name(), $this->get_version() );
		$plugin_basename    = $this->get_plugin_name() . '/' . $this->get_plugin_name() . '.php';
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $this->plugins_page, 'action_links' );
		$this->loader->add_filter( 'plugin_row_meta', $this->plugins_page, 'row_meta', 20, 4 );
	}

	/**
	 * Register all of the hooks related to the sns-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_sns_hooks() {

		$this->sns = new SNS( $this->get_plugin_name(), $this->get_version(), $this->settings );
		$this->loader->add_action( 'rest_api_init', $this->sns, 'add_bh_aws_ses_rest_endpoint' );
	}

	/**
	 * Instantiate integrations that will be called when an email bounces.
	 */
	private function define_integration_hooks() {

		$built_in_integrations                = array();
		$built_in_integrations['WordPress']   = new WordPress( $this->get_plugin_name(), $this->get_version() );
		$built_in_integrations['WooCommerce'] = new WooCommerce( $this->get_plugin_name(), $this->get_version() );
		$built_in_integrations['Newsletter']  = new Newsletter( $this->get_plugin_name(), $this->get_version() );

		$this->integrations = $built_in_integrations;

		$this->loader->add_filter( 'bh_wp_aws_ses_bounce_handler_integrations', $this, 'add_integrations' );
		$this->loader->add_action( 'plugins_loaded', $this, 'init_integrations' );
	}

	/**
	 * References to the built in integrations' objects.
	 *
	 * @var SES_Bounce_Handler_Integration_Interface[]
	 */
	private $integrations = array();

	/**
	 * Make available the built-in integrations.
	 *
	 * @hooked bh_wp_aws_ses_bounce_handler_integrations
	 * @param SES_Bounce_Handler_Integration_Interface[] $integrations WorrdPress, WooCommerce and Newsletter integrations.
	 *
	 * @return SES_Bounce_Handler_Integration_Interface[]
	 */
	public function add_integrations( $integrations ) {
		return $integrations + $this->integrations;
	}

	/**
	 * Initialize all registered integrations.
	 *
	 * @hooked plugins_loaded
	 */
	public function init_integrations() {

		$integrations = apply_filters( 'bh_wp_aws_ses_bounce_handler_integrations', array() );

		foreach ( $integrations as $integration ) {

			$integration->init();
			if ( $integration->is_enabled() ) {
				add_action( 'handle_ses_bounce', array( $integration, 'handle_ses_bounce' ), 10, 3 );
				add_action( 'handle_ses_complaint', array( $integration, 'handle_ses_complaint' ), 10, 3 );
				if ( method_exists( $integration, 'handle_unsubscribe_email' ) ) {
					add_action( 'handle_unsubscribe_email', array( $integration, 'handle_unsubscribe_email' ), 10, 3 );
				}
			}
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return   WPPB_Loader_Interface   Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

}
