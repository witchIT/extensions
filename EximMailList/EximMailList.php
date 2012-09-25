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
define( 'EXIMLIST_VERSION', '1.0.2, 2012-09-25' );

$wgEximMailListName     = 'WikiMailListName';
$wgEximMailListSubject  = '';
$wgEximMailListAddress  = 'wiki@' . $_SERVER['HTTP_HOST'];
$wgExtensionFunctions[] = 'wfSetupEximMailList';
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
		if( $action == 'eximfilter' ) {

			// Bail if not called from local host
			if( preg_match_all( "|inet6? addr:\s*([0-9a-f.:]+)|", `/sbin/ifconfig`, $matches )
			&& !in_array( $_SERVER['REMOTE_ADDR'], $matches[1] ) ) {
				header( 'Forbidden', true, 403 );
				die;
			}

			global $wgOut, $wgEximMailListName, $wgEximMailListSubject, $wgEximMailListAddress;
			if( $wgEximMailListSubject == '' ) $wgEximMailListSubject = $wgEximMailListName;
			$wgOut->disable();
			$dbr = &wfGetDB(DB_SLAVE);
			$list = array();
			$res = $dbr->select( $dbr->tableName( 'user' ), 'user_email' );
			while( $row = $dbr->fetchRow( $res ) ) $list[] = $row[0];
			$dbr->freeResult( $res );
			$list = join( ',', $list );
			print "# Exim filter\n"
				. "if \$h_subject contains \"[$wgEximMailListSubject]\" then\n"
				. "\tseen mail\n"
				. "\tfrom \$reply_address\n"
				. "\treply_to \"$wgEximMailListName<$wgEximMailListAddress>\"\n"
				. "\tsubject \$h_subject\n"
				. "\ttext \$message_body\n"
				. "\tto \"$wgEximMailListName<$wgEximMailListAddress>\"\n"
				. "\tbcc \"$list\"\n"
				. "\textra_headers \"Content-type: \$h_content-type\\nContent-transfer-encoding: \$h_Content-transfer-encoding\"\n"
				. "else\n"
				. "\tseen mail\n"
				. "\tfrom \$reply_address\n"
				. "\treply_to \"$wgEximMailListName<$wgEximMailListAddress>\"\n"
				. "\tsubject \"[$wgEximMailListSubject] \$h_subject\"\n"
				. "\ttext \$message_body\n"
				. "\tto \"$wgEximMailListName<$wgEximMailListAddress>\"\n"
				. "\tbcc \"$list\"\n"
				. "\textra_headers \"Content-type: \$h_content-type\\nContent-transfer-encoding: \$h_Content-transfer-encoding\"\n"
				. "endif\n";
		}
		return true;
	}
}

function wfSetupEximMailList() {
	global $wgEximMailList;
	$wgEximMailList = new EximMailList();
}
