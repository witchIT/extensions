<?php
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
/**
 * Integrate MediaWiki with Wordpress
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2013 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
define( 'WORDPRESS_VERSION', "0.0.1, 2013-05-30" );

$wgAjaxExportList[] = 'Wordpress::user';
$wgExtensionFunctions[] = 'wfSetupWordpress';
$wgExtensionCredits['other'][] = array(
	'name'        => "Wordpress",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Integrate MediaWiki with Wordpress",
	'url'         => "http://www.organicdesign.co.nz/wordpress",
	'version'     => WORDPRESS_VERSION
);

class Wordpress {

	/**
	 * Return info for the passed user if its token matches the passed token
	 */
	public static function user( $id, $token ) {
		global $wgUser;
		header( 'Content-Type: application/json' );
		$data = array();
		if( $wgUser->isLoggedIn() && $wgUser->getToken() == $token ) {
			$data['id'] = $wgUser->getId();
			$data['name'] = $wgUser->getName();
			$data['email'] = $wgUser->getEmail();
			$data['pass'] = md5( $wgUser->getRegistration() ); // used as wordpress internal user password
		}
		return json_encode( $data );
	}

	/**
	 * Bail if request not from a local IP address
	 */
	static function isLocal() {
		if( preg_match_all( "|inet6? addr:\s*([0-9a-f.:]+)|", `/sbin/ifconfig`, $m ) && !in_array( $_SERVER['REMOTE_ADDR'], $m[1] ) ) {
			return false;
		}
		return true;
	}

}

function wfSetupWordpress() {
	global $wgWordpress;
	$wgWordpress = new Wordpress();
}

