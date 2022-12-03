<?php
/**
 * Runs tests against the Newsletter integration.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Bounce_Handler_Test;
use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\ColorLogger\ColorLogger;
use Psr\Log\NullLogger;
use stdClass;
use TNP;

/**
 * Broadly tests each function in the Newsletter integration.
 *
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\Newsletter
 */
class Newsletter_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the text of the description is correct.
	 *
	 * @covers ::get_description
	 */
	public function test_description_text() {

		$logger = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

		$description = $newsletter_integration->get_description();

		$expected = 'Marks users as bounced and unsubscribes complaints';

		$this->assertSame( $expected, wp_kses( $description, wp_kses_allowed_html( 'strip' ) ) );

	}

	/**
	 * Test the description doesn't contain any unwelcome HTML.
	 *
	 * @covers ::get_description
	 */
	public function test_description_html() {

		$logger = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

		$description = $newsletter_integration->get_description();

		$this->assertSame( $description, wp_kses( $description, wp_kses_allowed_html( 'data' ) ) );
	}

	/**
	 * Set up a test subscriber, check the user is not bounced, bounce, check the user has bounced.
	 *
	 * @covers ::handle_ses_bounce
	 */
	public function test_bounced_email() {

		TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$tnp = \Newsletter::instance();

		$user_before = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'C', $user_before->status );

		$logger = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

		$newsletter_integration->handle_ses_bounce( 'brianhenryie@gmail.com', new stdClass(), new stdClass() );

		$user_after = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'B', $user_after->status );

	}

	/**
	 * Set up a test subscriber, check the user is not complained, complain, check the user has been unsubscribed.
	 *
	 * @covers ::handle_ses_complaint
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

		$option_name = 'newsletter_subscription_template';
		add_filter(
			'pre_option_' . $option_name,
			function( $result, $option, $default ) {
				$options             = array();
				$options['template'] = '{message}';
				return $options;
			},
			10,
			3
		);

		$option_name = 'newsletter_profile';
		add_filter(
			'pre_option_' . $option_name,
			function( $result, $option, $default ) {
				$options               = array();
				$options['title_none'] = 'title_none';
				return $options;
			},
			10,
			3
		);

		$option_name = 'newsletter_main_info';
		add_filter(
			'pre_option_' . $option_name,
			function( $result, $option, $default ) {
				$options                   = array();
				$options['footer_contact'] = 'footer_contact';
				$options['footer_title']   = 'footer_title';
				$options['footer_legal']   = 'footer_legal';
				return $options;
			},
			10,
			3
		);

		/**
		 * @see \NewsletterModule::process_ip()
		 */
		$_SERVER['REMOTE_ADDR']                          = '127.0.0.1';
		\Newsletter::instance()->options['ip']           = '127.0.0.1';
		\Newsletter::instance()->options['sender_email'] = 'sender_email';
		\Newsletter::instance()->options['sender_name']  = 'sender_name';
		\Newsletter::instance()->options['return_path']  = 'return_path';

		TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$tnp = \Newsletter::instance();

		$user_before = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'C', $user_before->status );

		$logger = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

		$newsletter_integration->handle_ses_complaint( 'brianhenryie@gmail.com', new stdClass(), new stdClass() );

		$user_after = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertSame( 'U', $user_after->status );

	}

	/**
	 * When a test is set up, a user should exist with the correct email address.
	 * The correct data should be return to the object starting the test. The
	 * created user should not have status Bounced already.
	 *
	 * @covers ::setup_test
	 */
	public function test_setup_test() {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );
		$test   = new Bounce_Handler_Test( $api, $logger );

		$tnp         = \Newsletter::instance();
		$user_before = $tnp->get_user( $test->get_email() );

		$this->assertNull( $user_before );

		$logger = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

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
	 *
	 * @covers ::verify_test
	 */
	public function test_verify_test() {

		$api    = $this->makeEmpty( API_Interface::class );
		$logger = new ColorLogger();
		$test   = new Bounce_Handler_Test( $api, $logger );

		$logger = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

		$test_data = $newsletter_integration->setup_test( $test );

		global $wpdb;
		$updated = $wpdb->update( NEWSLETTER_USERS_TABLE, array( 'status' => 'B' ), array( 'email' => $test->get_email() ) );

		$tnp         = \Newsletter::instance();
		$user_before = $tnp->get_user( $test->get_email() );

		assert( 'B' === $user_before->status );

		$test_verified = $newsletter_integration->verify_test( $test_data['data'] );

		$this->assertArrayHasKey( 'success', $test_verified );
		$this->assertArrayHasKey( 'html', $test_verified );

		$this->assertTrue( $test_verified['success'] );

	}

	/**
	 * Newsletter integration delete method should delete the user.
	 *
	 * @covers ::delete_test_data
	 */
	public function test_delete_test_data() {

		TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$tnp = \Newsletter::instance();

		$user_before = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertNotNull( $user_before );

		$test_data['tnp_user_id'] = $user_before->id;
		$logger                   = new ColorLogger();

		$newsletter_integration = new Newsletter( $logger );

		$newsletter_integration->delete_test_data( $test_data );

		$user_after = $tnp->get_user( 'brianhenryie@gmail.com' );

		$this->assertNull( $user_after );

	}

}
