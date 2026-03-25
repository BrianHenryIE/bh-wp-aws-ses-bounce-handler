<?php
/**
 * PHPUnit bootstrap file for wpunit tests. Since the plugin will not be otherwise autoloaded.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 */

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

if ( method_exists( Newsletter::class, 'upgrade' ) ) {
	Newsletter::instance()->upgrade();
	NewsletterUsers::instance()->upgrade();
	NewsletterEmails::instance()->upgrade();
	NewsletterSubscription::instance()->upgrade();
	NewsletterStatistics::instance()->upgrade();
	NewsletterProfile::instance()->upgrade();
}
