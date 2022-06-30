<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package   BH_WP_AWS_SES_Bounce_Handler
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bh-wp-aws-ses-bounce-handler-secret-key' );
delete_option( 'bh-wp-aws-ses-bounce-handler-confirmed-arns' );
delete_option( 'aws_ses_bounce_tests' );

$delete_all = true;
delete_metadata( null, null, 'bh_wp_aws_ses_bounce_hander_bounced', null, $delete_all );
