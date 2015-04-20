<?php
/**
 * UserProfiles extension - Adds easily customisable user sign-up and profile form/template
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
if( !defined( 'ARTICLEPROPS_VERSION' ) ) die( "The UderProfiles extension depends on the ArticleProperties extension." );
define( 'USERPROFILES_VERSION', "0.0.1, 2012-08-13" );
define( 'USERPROFILES_MSGOPT', 'up-msg-' );

$wgUserProfileStaff = array();

$wgExtensionFunctions[] = 'wfSetupUserProfiles';
$wgExtensionCredits['other'][] = array(
	'name'        => "UserProfiles",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Adds easily customisable user sign-up and profile form/template",
	'url'         => "http://www.organicdesign.co.nz/Extension:UserProfiles",
	'version'     => USERPROFILES_VERSION
);

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['UserProfiles'] = "$dir/UserProfiles.i18n.php";
require_once( "$dir/UserProfiles.class.php" );
require_once( "$dir/UserProfile.class.php" );

// And styles for the forms
$wgResourceModules['skins.userprofiles'] = array(
	'styles' => array( 'userprofiles.css' => array( 'media' => 'all' ) ),
	'remoteBasePath' => str_replace( $IP, $wgScriptPath, $dir ),
	'localBasePath' => $dir
);

function wfSetupUserProfiles() {
	global $wgUserProfiles;
	$wgUserProfiles = new Znazza();
}

