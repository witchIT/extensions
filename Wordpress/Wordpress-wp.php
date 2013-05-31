<?php
/**
 * Integrate MediaWiki with Wordpress
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2013 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

$mediawiki_url = ''; // URL of the local mediawiki ti sync users with
$mediawiki_db  = ''; // name of the DB the wiki uses
$mediawiki_pre = ''; // DB table prefix, if any, the wiki uses
 
function auto_login() {
	global $mediawiki_url, $mediawiki_db, $mediawiki_pre;

	// Check if a mediawiki user is logged in
	$cookie_prefix = implode( '_', $mediawiki_db, $mediawiki_pre );
	$idkey = $cookie_prefix . 'UserID';
	$tokenkey = $cookie_prefix . 'Token';
	$id = array_key_exists( $idkey, $_COOKIE ) ? $_COOKIE[$idkey] : false;
	$token = array_key_exists( $tokenkey, $_COOKIE ) ? $_COOKIE[$tokenkey] : false;
	if( $token ) {
		$mwuser = json_decode( file_get_contents( "$mediawiki_url?action=ajax&rs=Wordpress::user&rsargs[]=$id&rsargs=$token" ) );
	} else $mwuser = false;

	// If no current user returned, redirect to login
	if( !array( $mwuser ) || !array_key_exists( 'name', $mwuser ) ) {
		header( "Location: $mediawiki_url?title=Special:Userlogin&returnto=" . $_SERVER['REQUEST_URI'] );
		exit();
	}

	// If there is no equivalent Wordpress user, create now
	if( !$user_id = username_exists( $mwuser['name'] ) ) {
		$user_id = wp_create_user( $mwuser['name'], $mwuser['pass'], $mwuser['email'] );
	}

	// If the current Wordpress user is not the MediaWiki user, log them out
	if( $cur = get_current_user_id() ) {
		if( $cur != $user_id ) wp_logout();
	}

	// Log in as the user if not already logged in
	if( $cur != $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		do_action( 'wp_login', $mwuser['name'] );
	}
}
add_action( 'init', 'auto_login' );
