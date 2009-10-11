<?php
/**
 * Extension:WikiaInfo
 * {{Category:Extensions}}{{php}}{{Category:Extensions created with Template:SpecialPage}}
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if ( !defined('MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WIKIAADMIN_VERSION', '0.0.1, 2009-10-08' );

$wgExtensionFunctions[] = 'wfSetupWikiaAdmin';

$wgExtensionCredits['specialpage'][] = array(
	'name'        => 'Special:WikiaAdmin',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Manage the wikis in this wikia',
	'url'         => 'http://www.organicdesign.co.nz/Extension:WikiaAdmin.php',
	'version'     => WIKIAADMIN_VERSION
);

require_once( "$IP/includes/SpecialPage.php" );

/**
 * Define a new class based on the SpecialPage class
 */
class SpecialWikiaAdmin extends SpecialPage {

	function SpecialWikiaAdmin() {
		SpecialPage::SpecialPage( 'WikiaAdmin', 'sysop', true, false, false, false );
	}

	/**
	 * Return passed size as Bytes, KB, MB, GB
	 */
	function friendlySize( $size ) {
		$suffix = 'Bytes';
		if ( $size >> 30 )     { $size >>= 30; $suffix = 'GB'; }
		elseif ( $size >> 20 ) { $size >>= 20; $suffix = 'MB'; }
		elseif ( $size >> 10 ) { $size >>= 10; $suffix = 'KB'; }
		return "$size $suffix";
	}

	/**
	 * Override SpecialPage::execute()
	 */
	function execute() {
		global $wgOut, $wgRequest, $domains, $settings, $wgWikiaAdminDomains;
		$this->setHeaders();
		$wgOut->AddHtml( "<table cellpadding=\"4\" cellspacing=\"0\">" );

		# Retrieve any posted data
		$newid    = $wgRequest->getText( 'newid' );
		$curid    = $wgRequest->getText( 'curid' );
		$sitename = $wgRequest->getText( 'sitename' );
		$domains  = $wgRequest->getText( 'domains' );
		$codebase = $wgRequest->getText( 'codebase' );
		$user     = $wgRequest->getText( 'user', 'WikiSysop' );
		$pass     = $wgRequest->getText( 'pass' );
		$pass2    = $wgRequest->getText( 'pass2' );

		# Process request if form posted
		if ( $wgRequest->getText( 'submit' ) ) {
		}

		# ID for a new wiki
		$wgOut->AddHtml( "<tr><td>Enter ID for new wiki:</td> " );
		$wgOut->AddHtml( "<td><input name=\"newid\" value=\"$newid\" /></td><td><i>(lowercase a-z, used for FS location and for DB prefix)</i></td></tr>" );

		# or an ID of an existing one to modify
		$wgOut->AddHtml( "<tr><td>Or modify an existing wiki:</td> " );
		$options = "<option />";
		foreach ( glob( '/var/www/wikis/*' ) as $wiki ) {
			if ( ereg( '([^/]+)$', $wiki, $m ) ) {
				$selected = $curid == $m[1] ? ' selected' : '';
				$options .= "<option$selected>$m[1]</option>";
			}
		}
		$wgOut->AddHtml( "<td><select name=\"curid\">$options</select></td></tr>" );

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );

		# Site name
		$wgOut->AddHtml( "<tr><td>Full site name:</td> " );
		$wgOut->AddHtml( "<td><input name=\"sitename\" value=\"$sitename\" /></td></tr>" );

		# Domain selection
		$wgOut->AddHtml( "<tr><td valign=\"top\">Domains:</td>" );
		$wgOut->AddHtml( "<td><textarea name=\"domains\">$domains</textarea></td>" );
		$wgOut->AddHtml( "<td valign=\"top\"><i>(naked domains automatically<br />work for www and wiki as well)</i></td></tr>" );

		# Codebase selection
		$wgOut->AddHtml( "<tr><td>MediaWiki version:</td> " );
		$options = "";
		foreach ( glob( '/var/www/empty*.sql' ) as $cb ) {
			if ( ereg( '([0-9.]+).*\.sql', $cb, $m ) ) $options .= "<option>$m[1]</option>";
		}
		$wgOut->AddHtml( "<td><select name=\"codebase\">$options</select></td></tr>" );

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );

		# Sysop details
		$wgOut->AddHtml( "<tr><td>Sysop user:</td> " );
		$wgOut->AddHtml( "<td><input name=\"sysopuser\" value=\"$user\" /></td></tr>" );
		$wgOut->AddHtml( "<tr><td>Sysop password:</td> " );
		$wgOut->AddHtml( "<td><input type=\"password\" name=\"sysoppass\" /></td></tr>" );
		$wgOut->AddHtml( "<tr><td>Confirm password:</td> " );
		$wgOut->AddHtml( "<td><input type=\"password\" name=\"confirmpass\" /></td></tr>" );

		# Get list of domain symlinks by inode
		$dlist = array();
		foreach ( glob( "/var/www/domains/*" ) as $link ) {
			$stat  = stat( $link );
			$inode = $stat[1];
			if ( isset( $dlist[$inode] ) && is_array( $dlist[$inode] ) ) $dlist[$inode][] = basename( $link );
			else $dlist[$inode] = array( basename( $link ) );
		}

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );
		$wgOut->AddHtml( "<tr><td /><td align=\"right\"><input type=\"submit\" name=\"wpSubmit\" value=\"Submit\" /></td></tr>" );

		$wgOut->AddHtml( "</table>" );

	}

}

/*
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWikiaAdmin() {
	global $wgLanguageCode, $wgMessageCache;
	$wgMessageCache->addMessages( array( 'wikiaadmin' => 'Wikia Administration' ) );
	SpecialPage::addPage( new SpecialWikiaAdmin() );
}

