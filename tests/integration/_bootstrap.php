<?php

activate_plugin( 'woocommerce/woocommerce.php' );

// WP Browser activating the plugin results in console errors. This seems to fix it.
$option_name = 'newsletter_main';
add_filter(
	'pre_option_' . $option_name,
	function ( $result, $option, $default ) {
		$options                  = array();
		$options['scheduler_max'] = 123;
		return $options;
	},
	10,
	3
);

activate_plugin( 'newsletter/plugin.php' );
