<?php
/* EmailToWiki extension - Allows emails to be sent to the wiki and added to an existing or new article
 * Started: 2007-05-25, version 2 started 2011-11-13
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 - 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'EMAILTOWIKI_VERSION', '2.0.0, 2011-11-13' );

$wgEmailToWikiTmpDir = dirname( __FILE__ ) . '/EmailToWiki.tmp';

$wgExtensionFunctions[] = 'wfSetupEmailToWiki';
$wgExtensionCredits['other'][] = array(
	'name'        => 'EmailToWiki',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Allows emails to be sent to the wiki and added to an existing or new article',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:EmailToWiki',
	'version'     => EMAILTOWIKI_VERSION
);

class EmailToWiki {

	function __construct() {
		global $wgHooks;
		$wgHooks['UnknownAction'][] = $this;
	}

	/**
	 * Add a new email to the wiki
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut, $wgRequest;
		if( $action == 'emailtowiki' ) {
			$wgOut->disable();
			if( preg_match_all( "|inet6? addr:\s*([0-9a-f.:]+)|", `/sbin/ifconfig`, $matches ) && !in_array( $_SERVER['REMOTE_ADDR'], $matches[1] ) ) {
				header( 'Bad Request', true, 400 );
				print "Emails can only be added by the EmailToWiki.pl script running on the local host!";
			} else $this->processEmails();
		}
	}

	/**
	 * Process any unprocesseed email files created by EmailToWiki.pl
	 */
	function processEmails() {
		global $wgEmailToWikiTmpDir;
		if( !is_dir( $wgEmailToWikiTmpDir ) ) mkdir( $wgEmailToWikiTmpDir );

		// Scan messages in folder
		foreach( glob( "$wgEmailToWikiTmpDir/*" ) as $msg ) {
			// upload each file
			// create article for bodytext
			// add file links
		}
	}

}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupEmailToWiki() {
	global $wgEmailToWiki;
	$wgEmailToWiki = new EmailToWiki();
}
