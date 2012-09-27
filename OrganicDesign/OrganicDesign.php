<?php
/**
 * OrganicDesign extension - an extension to encapsulate all the functionality specific to the OD wiki
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'OD_VERSION', "0.0.0, 2012-09-27" );

$wgExtensionCredits['other'][] = array(
	'name'        => "OrganicDesign",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "An extension to encapsulate all the functionality specific to the Organic Design wiki",
	'url'         => "http://www.organicdesign.co.nz",
	'version'     => OD_VERSION
);

// Register the CSS file for the OrganicDesign skin
$wgResourceModules['skins.organicdesign'] = array(
        'styles' => array( 'organicdesign.css' => array( 'media' => 'screen' ) ),
        'remoteBasePath' => "$wgStylePath/organicdesign",
        'localBasePath' => "$IP/skins/organicdesign",
);

class OrganicDesign {

	function __construct() {
		global $wgExtensionFunctions, $wgHooks;
		$wgExtensionFunctions[] = array( $this, 'setup' );
		$wgHooks['AjaxCommentsCheckTitle'][] = $this;
	}

	function setup() {
		global $wgUser;

		// Bounce requests to https for sysops and non-https for non-sysops, and force www prefix
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $ssl = isset( $_SERVER['HTTPS'] );
        $www = preg_match( "|^www|", $host ) ? '' : 'www.';
        if( in_array( 'sysop', $wgUser->getEffectiveGroups() ) ) {
                if( $www || !$ssl ) {
                        header( "Location: https://$www$host$uri" );
                        exit;
                }
        } else {
                if( $ssl && ( !array_key_exists( 'title', $_REQUEST ) || $_REQUEST['title'] != 'Special:UserLogin' ) ) {
                        header( "Location: http://$www$host$uri" );
                        exit;
                }
                if( $www && preg_match( '|organicdesign.+[^t]$|', $host ) ) {
                        header( "Location: http://$www$host$uri" );
                        exit;
                }
        }
	}

	/**
	 * Only use AjaxComments if the title's not in the "No files or comments" category
	 */
	function onAjaxCommentsCheckTitle( &$ret ) {
		$ret = false; //!self::inCat( 'No files or comments' );
		die;
		return true;
	}

	/**
	 * Only use jQuery uploads if it's a loan page and the current user can edit the talk page
	 */
	function onjQueryUploadAddAttachLink( $title, &$ret ) {
		$ret = !self::inCat( 'No files or comments', $title );
		return true;
	}

	/**
	 * Return whether or not the passed title is a member of the passed cat
	 */
	public static function inCat( $cat, $title = false ) {
		global $wgTitle;
		if( $title === false ) $title = $wgTitle;
		if( !is_object( $title ) ) $title = Title::newFromText( $title, NS_CATEGORY );
		$id   = $title->getArticleID();
		$dbr  = &wfGetDB( DB_SLAVE );
		$cat  = $dbr->addQuotes( Title::newFromText( $cat )->getDBkey() );
		$cl   = $dbr->tableName( 'categorylinks' );
		return $dbr->selectRow( $cl, '0', "cl_from = $id AND cl_to = $cat", __METHOD__ );
	}

}
new OrganicDesign();

