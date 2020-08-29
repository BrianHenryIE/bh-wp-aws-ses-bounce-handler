<?php
/**
 * Runs tests against the Newsletter integration.
 *
 * @package bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_AWS_SES_Bounce_Handler\integrations;

use BH_WP_AWS_SES_Bounce_Handler\admin\Bounce_Handler_Test;
use stdClass;
use TNP;

/**
 * Broadly tests each function in the Newsletter integration.
 *
 * Class Newsletter_Test
 *
 * @package BH_WP_AWS_SES_Bounce_Handler\integrations
 */
class Newsletter_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the text of the description is correct.
	 */
	public function test_description_text() {

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );

		$description = $newsletter_integration->get_description();

		$expected = 'Marks users as bounced and unsubscribes complaints';

		$this->assertSame( $expected, wp_kses( $description, wp_kses_allowed_html( 'strip' ) ) );

	}

	/**
	 * Test the description doesn't contain any unwelcome HTML.
	 */
	public function test_description_html() {

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );

		$description = $newsletter_integration->get_description();

		$this->assertSame( $description, wp_kses( $description, wp_kses_allowed_html( 'data' ) ) );
	}

	/**
	 * Set up a test subscriber, check the user is not bounced, bounce, check the user has bounced.
	 */
	public function test_bounced_email() {

		TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$tnp = \Newsletter::instance();

		$user_before = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'C', $user_before->status );

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );
		$newsletter_integration->handle_ses_bounce( 'brianhenryie@gmail.com', new stdClass(), new stdClass() );

		$user_after = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'B', $user_after->status );

	}

	/**
	 * Set up a test subscriber, check the user is not complained, complain, check the user has been unsubscribed.
	 */
	public function test_complained_email() {

		$option_name = 'newsletter_unsubscription';
		add_filter(
			'pre_option_' . $option_name,
			function( $result, $option, $default ) {
				$options                         = array();
				$options['unsubscribed_message'] = 'message';
				$options['unsubscribed_subject'] = 'subject';
				return $options;
			},
			10,
			3
		);

		TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$tnp = \Newsletter::instance();

		$user_before = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'C', $user_before->status );

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );
		$newsletter_integration->handle_ses_complaint( 'brianhenryie@gmail.com', new stdClass(), new stdClass() );

		$user_after = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'U', $user_after->status );

	}

	/**
	 * When a test is set up, a user should exist with the correct email address.
	 * The correct data should be return to the object starting the test. The
	 * created user should not have status Bounced already.
	 */
	public function test_setup_test() {

		$test = new Bounce_Handler_Test();

		$tnp         = \Newsletter::instance();
		$user_before = $tnp->get_user( $test->get_email() );

		$this->assertNull( $user_before );

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );

		$test_data = $newsletter_integration->setup_test( $test );

		$this->assertArrayHasKey( 'data', $test_data );
		$this->assertArrayHasKey( 'html', $test_data );

		$user_after = $tnp->get_user( $test->get_email() );

		$this->assertNotNull( $user_after );
		$this->assertNotEquals( 'B', $user_after->status );
	}


	/**
	 * A test verification should respond affirmatively when the test user has bounced.
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	public function test_verify_test() {

		$test = new Bounce_Handler_Test();

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );
		$test_data              = $newsletter_integration->setup_test( $test );

		global $wpdb;
		$updated = $wpdb->update( NEWSLETTER_USERS_TABLE, array( 'status' => 'B' ), array( 'email' => $test->get_email() ) );

		$tnp         = \Newsletter::instance();
		$user_before = $tnp->get_user( $test->get_email() );

		$this->assertSame( 'B', $user_before->status );

		$test_verified = $newsletter_integration->verify_test( $test_data['data'] );

		$this->assertArrayHasKey( 'success', $test_verified );
		$this->assertArrayHasKey( 'html', $test_verified );

		$this->assertTrue( $test_verified['success'] );

	}

	/**
	 * Newsletter integration delete method should delete the user.
	 */
	public function test_delete_test_data() {

		TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$tnp = \Newsletter::instance();

		$user_before = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertNotNull( $user_before );

		$test_data['tnp_user_id'] = $user_before->id;

		$newsletter_integration = new Newsletter( 'bh-wp-aws-ses-bounce-handler', '1.2.0' );

		$newsletter_integration->delete_test_data( $test_data );

		$user_after = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertNull( $user_after );

	}

}
