<?php
/**
 * Tests for plugin activation – code that need only run once.
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes;

/**
 * Add a WordPress role for tagging users.
 *
 * Class Activator_Test
 *
 * @package BrianHenryIE\AWS_SES_Bounce_Handler\WP_Includes
 * @coversNothing
 */
class Activator_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Check the role does not exists, run activation, then verify it does.
	 */
	public function test_role_added_on_activation() {

		$roles = wp_roles();

		$this->assertNull( $roles->get_role( 'bounced_email' ) );

		Activator::activate();

		$this->assertNotNull( $roles->get_role( 'bounced_email' ) );
	}
}
