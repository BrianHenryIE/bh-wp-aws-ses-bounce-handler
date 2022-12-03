<?php
/**
 * A WordPress plugin to unsubscribe users from email lists when AWS SES sends a bounce or complaint report.
 *
 * @link              https://BrianHenry.ie
 * @since             1.0.0
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 *
 * @wordpress-plugin
 * Plugin Name:       AWS SES Bounce Handler
 * Plugin URI:        https://github.com/BrianHenryIE/bh-wp-aws-ses-bounce-handler
 * Description:       When AWS SES sends a bounce or complaint report, users & orders are marked; Newsletter users are unsubscribed.
 * Version:           1.6.0
 * Requires PHP:      7.4
 * Author:            BrianHenryIE
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-aws-ses-bounce-handler
 * Domain Path:       /languages
 *
 * GitHub Plugin URI: https://github.com/BrianHenryIE/bh-wp-aws-ses-bounce-handler
 * Release Asset:     true
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\API\API;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\Activator;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\Deactivator;
use BrianHenryIE\AWS_SES_Bounce_Handler\API\Settings;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Logger\Logger;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new \Exception( 'WPINC not defined' );
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';


register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Currently plugin version.
 */
define( 'BH_WP_AWS_SES_BOUNCE_HANDLER_VERSION', '1.6.0' );
define( 'BH_WP_AWS_SES_BOUNCE_HANDLER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Function to keep the loader and settings objects out of the namespace.
 *
 * @return BH_WP_AWS_SES_Bounce_Handler;
 */
function instantiate_bh_wp_aws_ses_bounce_handler() {

	$settings = new Settings();
	$logger   = Logger::instance( $settings );
	$api      = new API( $settings, $logger );

	$bh_wp_aws_ses_bounce_handler = new BH_WP_AWS_SES_Bounce_Handler( $api, $settings, $logger );

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
 */
$GLOBALS['bh_wp_aws_ses_bounce_handler'] = instantiate_bh_wp_aws_ses_bounce_handler();

