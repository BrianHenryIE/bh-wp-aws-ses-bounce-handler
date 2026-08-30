<?php

namespace BrianHenryIE\AWS_SES_Bounce_Handler\Logger;

use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\AWS_SES_Bounce_Handler\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\AWS_SES_Bounce_Handler\Logger\TNP_User_Hyperlink
 */
class TNP_User_Hyperlink_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::replace_tnp_user_id_with_link
	 */
	public function test_replace_user_id(): void {

		$sut = new TNP_User_Hyperlink();

		$item             = array();
		$settings         = $this->makeEmpty( Logger_Settings_Interface::class );
		$bh_wp_psr_logger = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$user = \TNP::add_subscriber( array( 'email' => 'brianhenryie@gmail.com' ) );

		$user_id = $user->id;

		$column_output = "Marked `tnp_user:{$user_id}` as bounced.";
		$column_name   = 'message';

		$result = $sut->replace_tnp_user_id_with_link( $column_output, $item, $column_name, $settings, $bh_wp_psr_logger );

		$this->assertStringContainsString( 'Newsletter subscriber brianhenryie@gmail.com', $result );
	}
}
