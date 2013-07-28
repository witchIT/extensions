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
define( 'OD_VERSION', "1.0.1, 2013-07-27" );

$wgExtensionCredits['other'][] = array(
	'name'		=> "OrganicDesign",
	'author'	  => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "An extension to encapsulate all the functionality specific to the Organic Design wiki",
	'url'		 => "http://www.organicdesign.co.nz",
	'version'	 => OD_VERSION
);

// Register the CSS file for the OrganicDesign skin
$wgResourceModules['skins.organicdesign'] = array(
		'styles' => array( 'organicdesign.css' => array( 'media' => 'screen' ) ),
		'remoteBasePath' => "$wgStylePath/organicdesign",
		'localBasePath' => "$IP/skins/organicdesign",
);

class OrganicDesign {

	public static $title = false;

	function __construct() {
		global $wgExtensionFunctions, $wgHooks;

		$wgExtensionFunctions[] = array( $this, 'setup' );
		$wgHooks['AjaxCommentsCheckTitle'][] = $this;
		$wgHooks['jQueryUploadAddAttachLink'][] = $this;
	}

	function setup() {
		global $wgUser;
		self::$title = array_key_exists( 'title', $_REQUEST ) ? Title::newFromText( $_REQUEST['title'] ) : false;

		// Bounce requests to https for sysops and non-https for non-sysops, and force www prefix
		// - conditions must be such that redirects only happen if something needs to change
		// - works for standard ports 80 & 443 (scheme://od/uri) and 8080/8989 (scheme://od:port/uri)
		$host = preg_match( "|^(.+):\d+$|", $_SERVER['HTTP_HOST'], $m ) ? $m[1] : $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];
		$ssl = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on';
		$port = isset( $_SERVER['SERVER_PORT'] ) ? $_SERVER['SERVER_PORT'] : '';
		if( $port == 80 || $port == 443 ) $port = ''; else $port = ":$port";
		$od = preg_match( "|^www\.organicdesign\.co\.nz$|", $host );
		if( in_array( 'sysop', $wgUser->getEffectiveGroups() ) ) {

			// Sysops are bounced to the https www (if they're not https or not www)
			if( !$od || !$ssl ) {
				if( $port ) $port = ':8989';
				header( "Location: https://www.organicdesign.co.nz$port$uri" );
				exit;
			}
		} else {

			// Logins by non-sysop are bounced to the https www (if they're not https or not www)
			if( array_key_exists( 'title', $_REQUEST ) || $_REQUEST['title'] != 'Special:UserLogin' ) {
				if( !$od || !$ssl ) {
					if( $port ) $port = ':8989';
					header( "Location: https://www.organicdesign.co.nz$port$uri" );
					exit;
				}
			}

			// Non-login pages bounce to non-https www (if they're https or they're not www)
			else if( $ssl || !$od ) {
				if( $port ) $port = ':8080';
				header( "Location: http://www.organicdesign.co.nz$port$uri" );
				exit;
			}
		}
	}

	/**
	 * Only use AjaxComments if the title's not in the "No files or comments" category
	 */
	function onAjaxCommentsCheckTitle( $title, &$ret ) {
		$ret = $this->commentsAndUploads( $title );
		return true;
	}

	/**
	 * Only use jQuery uploads if it's a loan page and the current user can edit the talk page
	 */
	function onjQueryUploadAddAttachLink( $title, &$ret ) {
		$ret = $this->commentsAndUploads( $title );
		return true;
	}

	/**
	 * Return whether or now comments and uploads are allowed for the passed title
	 */
	function commentsAndUploads( $title ) {
		$ns = $title->getNamespace();
		if( $ns == 2 || $ns == 8 || $ns == 10 ) return false;
		return !self::inCat( 'No files or comments', $title );
	}

	/**
	 * Add group info to body tag
	 */
	public static function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ) {
		global $wgUser;

		// Add user group information
		if( $wgUser->isAnon() ) $bodyAttrs['class'] .= ' anon';
		if( $wgUser->isLoggedIn() ) $bodyAttrs['class'] .= ' user';
		if( in_array( 'sysop', $wgUser->getEffectiveGroups() ) ) $bodyAttrs['class'] .= ' sysop';
		else $bodyAttrs['class'] .= ' notsysop';

		// Add hide-cats if in Category:Hide categories
		if( self::inCat( 'Hide categories' ) ) $bodyAttrs['class'] .= ' hide-cats';

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
		$dbr  = wfGetDB( DB_SLAVE );
		$cat  = $dbr->addQuotes( Title::newFromText( $cat )->getDBkey() );
		return $dbr->selectRow( 'categorylinks', '1', "cl_from = $id AND cl_to = $cat" );
	}

}
new OrganicDesign();

