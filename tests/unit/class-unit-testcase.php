<?php

namespace BrianHenryIE\AWS_SES_Bounce_Handler;

use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerInterface;
use BrianHenryIE\AWS_SES_Bounce_Handler\Psr\Log\LoggerTrait;
use BrianHenryIE\ColorLogger\ColorLogger;
use WP_Mock;

class Unit_Testcase extends \Codeception\Test\Unit {

	protected LoggerInterface $logger;

	protected function setup(): void {
		WP_Mock::setUp();

		$this->logger = new class() implements LoggerInterface {
			use LoggerTrait;

			protected \Psr\Log\LoggerInterface $logger;
			public function __construct() {
				$this->logger = new ColorLogger();
			}

			public function log( $level, $message, array $context = array() ) {
				$this->logger->log( $level, $message, $context );
			}
		};
	}

	protected function tearDown(): void {
		parent::_tearDown();
		WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}
}
