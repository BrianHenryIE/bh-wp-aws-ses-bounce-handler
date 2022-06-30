<?php

class IntegrationsCest {

	/**
	 * Test the text explaining the integration is added to the settings page.
	 *
	 * @param AcceptanceTester $I
	 */
	public function testPluginsPageForName( AcceptanceTester $I ) {

		$I->loginAsAdmin();

		$I->amOnAdminPage( 'options-general.php?page=bh-wp-aws-ses-bounce-handler' );

		// TODO: Check the links in this text to MailPoet's bounce list (if active).

		$I->canSee( 'MailPoet: Marks users as bounced and unsubscribes complaints' );
	}

}
