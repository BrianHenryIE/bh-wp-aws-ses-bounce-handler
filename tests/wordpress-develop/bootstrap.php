<?php
/**
 * PHPUnit bootstrap file for wordpress-develop.
 *
 * @package ea-wp-aws-ses-bounce-handler
 */

$project_root_dir = dirname( __FILE__, 3 ); // No trailing slash/.
$plugin_root_dir  = $project_root_dir . '/src';
$plugin_name      = basename( $project_root_dir );
$plugin_name_php  = $plugin_name . '.php';
$plugin_path_php  = $plugin_root_dir . '/' . $plugin_name_php;
$plugin_basename  = $plugin_name . '/' . $plugin_name_php;

$_wp_tests_tools_dir = $project_root_dir . '/vendor/wordpress/wordpress/tests/phpunit';

$_wp_tests_config = $project_root_dir . '/tests/wordpress-develop/wp-tests-config.php';

if ( ! file_exists( $_wp_tests_config ) ) {
	echo 'wp-tests-config.php not found.';
	exit( 1 );
}

define( 'WP_TESTS_CONFIG_FILE_PATH', $_wp_tests_config );

// Verify that Composer dependencies have been installed.
if ( ! file_exists( $_wp_tests_tools_dir . '/includes/functions.php' ) ) {
	echo 'Unable to find WordPress. Run `composer install`.';
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_wp_tests_tools_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {

	$project_root_dir = dirname( __FILE__, 3 );

	// Assumes the plugin's directory name is the same as its filename.
	$plugin_name = basename( $project_root_dir );

	$option_name = 'newsletter_main';
	add_filter(
		'pre_option_' . $option_name,
		function( $result, $option, $default ) {
			$options                  = array();
			$options['scheduler_max'] = 123;
			return $options;
		},
		10,
		3
	);
	require_once $project_root_dir . '/wp-content/plugins/newsletter/plugin.php';

	Newsletter::instance()->hook_activate();

	require_once $project_root_dir . '/wp-content/plugins/woocommerce/woocommerce.php';

	require_once $project_root_dir . '/src/' . $plugin_name . '.php';

}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_wp_tests_tools_dir . '/includes/bootstrap.php';
