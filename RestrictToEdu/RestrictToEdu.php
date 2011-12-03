<?php
/**
 * RestrictToEdu extension - restricts account creation to users with .edu email addresses
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'RESTRICTTOEDU_VERSION', "0.0.0, 2011-12-03" );

$wgEduEmailPattern = "|\.edu$|";
$wgEduPagesWithLogin = array( 'Main Page' );

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['RestrictToEdu'] = "$dir/RestrictToEdu.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );

$wgExtensionFunctions[] = 'wfSetupRestrictToEdu';
$wgSpecialPages['RestrictToEdu'] = 'RestrictToEdu';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "RestrictToEdu",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Restricts account creation to users with .edu email addresses (for Elance job 27354439)",
	'url'         => "https://www.elance.com/php/collab/main/collab.php?bidid=27354439",
	'version'     => RESTRICTTOEDU_VERSION
);

class RestrictToEdu extends SpecialPage {

	function __construct() {

		SpecialPage::SpecialPage( 'RestrictToEdu', false, true, false, false, false );

	}


	/**
	 * Render the special page
	 */
	function execute( $param ) {
		global $wgOut;
		$this->setHeaders();
		$wgOut->addWikiText( 'forgot password stuff to go here....' );
	}

}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupRestrictToEdu() {
	global $wgRestrictToEdu;
	$wgRestrictToEdu = new RestrictToEdu();
	SpecialPage::addPage( $wgRestrictToEdu );
}

