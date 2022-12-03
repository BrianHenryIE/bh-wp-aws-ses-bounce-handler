<?php
/**
 * Tests for MailPoet.
 *
 * @package brianhenryie/bh-wp-aws-ses-bounce-handler
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations;

use BrianHenryIE\AWS_SES_Bounce_Handler\Admin\Bounce_Handler_Test;
use BrianHenryIE\AWS_SES_Bounce_Handler\API_Interface;
use BrianHenryIE\ColorLogger\ColorLogger;
use Psr\Log\NullLogger;

/**
 * Class MailPoet_Test
 *
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\API\Integrations\MailPoet
 */
class MailPoet_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * "test" here means the admin UI test to verify all is working.
	 *
	 * @covers ::setup_test
	 */
	public function test_setup_test() {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$mailpoet_integration = new MailPoet( $logger );

		$bounce_handler_test = new Bounce_Handler_Test( $api, $logger );

		$test_data = $mailpoet_integration->setup_test( $bounce_handler_test );

		$this->assertIsArray( $test_data );

		// It should have a 'html' entry with the message to show the user.
		$this->assertArrayHasKey( 'html', $test_data );

	}


}
