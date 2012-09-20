<?php
/**
 * EximMailList - Adds a parser function to add a list of people in the wiki
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/nad Nad]
 * @copyright © 2012 [http://www.organicdesign.co.nz/nad Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'EXIMLIST_VERSION', '0.0.1, 2012-09-20' );

$wgEximMailListName            = 'WikiMailList';
$wgEximMailListAddress         = 'wiki@' . $_SERVER['HTTP_HOST'];
$wgExtensionFunctions[]        = 'wfSetupEximMailList';
$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'People',
	'author'      => '[http://www.organicdesign.co.nz/nad Nad]',
	'description' => 'Adds a parser function to add a list of people in the wiki',
	'url'         => 'http://www.organicdesign.co.nz/Extension:People',
	'version'     => EXIMLIST_VERSION
);

class EximMailList {

	function __construct() {
		global $wgHooks;
		$wgHooks['UnknownAction'][] = $this;
	}

	function onUnknownAction( $action, $article ) {
		if( $action == 'ajaxcomments' ) {

			// Bail if not called from local host
			if( preg_match_all( "|inet6? addr:\s*([0-9a-f.:]+)|", `/sbin/ifconfig`, $matches )
				&& !in_array( $_SERVER['REMOTE_ADDR'], $matches[1] ) ) {
					header( 'Forbidden', true, 403 );
					die;
				}
				
    		global $wgOut, $wgEximMailListName, $wgEximMailListAddress;
			$wgOut->disable();
			$dbr = &wfGetDB(DB_SLAVE);
			$list = array();
			$res = $dbr->select( $dbr->tableName( 'user' ), 'user_email' );
			while( $row = $dbr->fetchRow( $res ) ) $list[] = $row[0];
			$dbr->freeResult( $res );
			print "# Exim filter\n";
				. "\tseen mail\n";
				. "\tfrom \$reply_address\n";
				. "\treply_to \"$wgEximMailListName<$wgEximMailListAddress>\"\n"
				. "\tsubject \$h_subject\n";
				. "\ttext \$message_body\n";
				. "\tto \"$wgEximMailListName<$wgEximMailListAddress>\"\n"
				. "\tbcc \"" . join( ',', $list ) . "\n"
		}
		return true;
	}

}

function wfSetupEximFilter() {
	global $wgEximMailList;
	$wgEximMailList = new EximMailList();
}
