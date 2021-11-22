<?php
/**
 * Tests for core module's admin features.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */

use Codeception\Util\Locator;

/**
 * Test core module admin features.
 */
class AdminCest {

	/**
	 * Test module versions are displayed correctly on about page.
	 *
	 * @param AcceptanceTester $I Tester
	 *
	 * @return void
	 */
	public function testSupportTicketLink( AcceptanceTester $I ) {
		$I->wantToTest( 'Support ticket link is correct.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/' );

		$I->moveMouseOver( '.altis-logo-wrapper' );
		$I->see( 'Open Support Ticket', Locator::href( 'https://dashboard.altis-dxp.com/#/support/new' ) );
	}

	/**
	 * Test module versions are displayed correctly on about page.
	 *
	 * @param AcceptanceTester $I Tester
	 *
	 * @return void
	 */
	public function testSupportTicketLinkWithCloud( AcceptanceTester $I ) {
		// Schedule running of _configSupportTicketLinkWithCloud in next Acceptance Test.
		$rollback = $I->bootstrapWith( [ __CLASS__, '_configSupportTicketLinkWithCloud' ] );

		$I->wantToTest( 'Support ticket link with application is correct.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/' );

		$I->moveMouseOver( '.altis-logo-wrapper' );
		$I->see( 'Open Support Ticket', Locator::href( 'https://dashboard.altis-dxp.com/#/support/new?applications%5B%5D=testing_test' ) );

		// Clean up the scheduled callback.
		$rollback();
	}

	/**
	 * Define necessary constants to test support ticket links in Cloud.
	 *
	 * @return void
	 */
	public static function _configSupportTicketLinkWithCloud() {
		define( 'HM_ENV', 'testing_test' );
	}

}
