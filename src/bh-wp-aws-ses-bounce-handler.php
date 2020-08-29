<?php
/**
 * A WordPress plugin to unsubscribe users from email lists when AWS SES sends a bounce or complaint report.
 *
 * @link              https://BrianHenry.ie
 * @since             1.0.0
 * @package           BH_WP_AWS_SES_Bounce_Handler
 *
 * @wordpress-plugin
 * Plugin Name:       AWS SES Bounce Handler
 * Plugin URI:        https://github.com/BrianHenryIE/bh-wp-aws-ses-bounce-handler
 * Description:       When AWS SES sends a bounce or complaint report, users & orders are marked; Newsletter users are unsubscribed.
 * Version:           1.3.0
 * Author:            BrianHenryIE
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-aws-ses-bounce-handler
 * Domain Path:       /languages
 */

namespace {

	use BH_WP_AWS_SES_Bounce_Handler\includes\Activator;
	use BH_WP_AWS_SES_Bounce_Handler\includes\Deactivator;

	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-activator.php
	 */
	function activate_bh_wp_aws_ses_bounce_handler() {

		Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-deactivator.php
	 */
	function deactivate_bh_wp_aws_ses_bounce_handler() {

		Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_bh_wp_aws_ses_bounce_handler' );
	register_deactivation_hook( __FILE__, 'deactivate_bh_wp_aws_ses_bounce_handler' );

}

namespace BH_WP_AWS_SES_Bounce_Handler {

	use BH_WP_AWS_SES_Bounce_Handler\includes\BH_WP_AWS_SES_Bounce_Handler;
	use BH_WP_AWS_SES_Bounce_Handler\API\Settings;
	use BH_WP_AWS_SES_Bounce_Handler\WPPB\WPPB_Loader;

	require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

	/**
	 * Currently plugin version.
	 */
	define( 'BH_WP_AWS_SES_BOUNCE_HANDLER_VERSION', '1.1.0' );


	/**
	 * Function to keep the loader and settings objects out of the namespace.
	 *
	 * @return BH_WP_AWS_SES_Bounce_Handler;
	 */
	function instantiate_bh_wp_aws_ses_bounce_handler() {

		$loader = new WPPB_Loader();

		$settings = new Settings();

		$bh_wp_aws_ses_bounce_handler = new BH_WP_AWS_SES_Bounce_Handler( $loader, $settings );

		return $bh_wp_aws_ses_bounce_handler;
	}

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 *
	 * phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found
	 */
	$GLOBALS['bh_wp_aws_ses_bounce_handler'] = $bh_wp_aws_ses_bounce_handler = instantiate_bh_wp_aws_ses_bounce_handler();
	$bh_wp_aws_ses_bounce_handler->run();
}
