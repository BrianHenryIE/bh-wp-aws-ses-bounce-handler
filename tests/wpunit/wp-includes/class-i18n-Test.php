<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes;

use BrianHenryIE\AWS_SES_Bounce_Handler\WPUnit_Testcase;

/**
 * Class I18n_Test
 *
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes\I18n
 */
class I18n_Test extends WPUnit_Testcase {

	/**
	 * Checks if the filter run by WordPress in the load_plugin_textdomain() function is called.
	 *
	 * @covers ::load_plugin_textdomain
	 *
	 * @see load_plugin_textdomain()
	 */
	public function test_load_plugin_textdomain_function() {

		$this->markTestSkipped();

		$called        = false;
		$actual_domain = null;

		$filter = function ( $locale, $domain ) use ( &$called, &$actual_domain ) {

			$called        = true;
			$actual_domain = $domain;

			return $locale;
		};

		add_filter( 'plugin_locale', $filter, 10, 2 );

		$i18n = new I18n();

		$i18n->load_plugin_textdomain();

		$this->assertTrue( $called, 'plugin_locale filter not called within load_plugin_textdomain() suggesting it has not been set by the plugin.' );
		$this->assertEquals( 'bh-wp-aws-ses-bounce-handler', $actual_domain );
	}
}
