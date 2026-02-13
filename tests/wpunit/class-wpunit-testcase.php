<?php

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerTrait;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\Test\TestLogger;
use BrianHenryIE\ColorLogger\ColorLogger;
use lucatume\WPBrowser\TestCase\WPTestCase;
use MailPoetVendor\Sabberworm\CSS\Value\Color;

class WPUnit_Testcase extends WPTestCase {

	/**
	 * @var LoggerInterface|TestLogger $logger
	 */
	protected LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = new class() extends ColorLogger implements LoggerInterface {};
	}

	protected function get_installed_major_version( string $plugin_basename ): string {
		$plugin_headers = get_plugin_data( codecept_root_dir( WP_PLUGIN_DIR . '/' . $plugin_basename ) );
		if ( 1 === preg_match( '/(\d+)/', $plugin_headers['Version'], $output_array ) ) {
			return (int) $output_array[1];
		} else {
			return -1;
		}
	}

	protected function is_activate_and_major_version( string $plugin_basename, int $major_version ): bool {
		$is_active = is_plugin_active( 'newsletter/plugin.php' );
		if ( ! $is_active ) {
			return false;
		}
		return $this->get_installed_major_version( $plugin_basename ) === $major_version;
	}
}
