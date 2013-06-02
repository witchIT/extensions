<?php
/**
 * Integrate MediaWiki with Wordpress
 *
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2013 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

//$mediawiki_url = ''; // URL of the local mediawiki ti sync users with
//$mediawiki_db  = ''; // name of the DB the wiki uses
//$mediawiki_pre = ''; // DB table prefix, if any, the wiki uses

// If this is being called from wp-login.php then redirect to wiki
if( preg_match( '|wp-login\.php|', $_SERVER['SCRIPT_NAME'] ) ) {
	$location = 'Special:UserLogin';
	if( array_key_exists( 'action', $_REQUEST ) ) {
		$action = $_REQUEST['action'];
		if( $action == 'logout' ) $location = 'Special:UserLogout';
		elseif( $action == 'register' ) $location .= '&type=signup';
	}
	$return = preg_match( "|^/(\w+)|", $_SERVER['REQUEST_URI'], $m ) ? "&returnto=$m[1]" : '';
	header( "Location: $mediawiki_url?title=$location$return" );
	exit();
}
 
// Check if there are cookies for a logged in MediaWiki user in this domain
$cookie_prefix = $mediawiki_pre ? $mediawiki_db . '_' . $mediawiki_pre : $mediawiki_db;
$ikey = $cookie_prefix . 'UserID';
$tkey = $cookie_prefix . 'Token';
$id = array_key_exists( $ikey, $_COOKIE ) ? $_COOKIE[$ikey] : false;
$token = array_key_exists( $tkey, $_COOKIE ) ? $_COOKIE[$tkey] : false;

// If cookies found, check with the wiki that the token is valid, if it is user info is returned
if( $token ) {
	$mwuser = json_decode( file_get_contents( $x="$mediawiki_url?action=ajax&rs=Wordpress::user&rsargs[]=$id&rsargs[]=$token" ) );
} else $mwuser = false;

// If no user info returned, log any Wordpress user out and return allowing anonymous browsing of the Wordpress site
if( is_null( $mwuser ) || !array( $mwuser ) || !array_key_exists( 'name', $mwuser ) ) {
	wp_logout();
	header( 'Location: ' . $_SERVER['REQUEST_URI'] );
	exit();
}

// If there is no equivalent Wordpress user, create user now
if( !$user_id = username_exists( $mwuser->name ) ) {
	$user_id = wp_create_user( $mwuser->name, $mwuser->pass, $mwuser->email );
}

// If the current Wordpress user is not the MediaWiki user, log them out
if( $cur = get_current_user_id() ) {
	if( $cur != $user_id ) {
		wp_logout();
		header( 'Location: ' . $_SERVER['REQUEST_URI'] );
		exit();
	}
}

// Log in as the wiki user if not already logged in
if( $cur != $user_id ) {
	wp_set_password( $mwuser->pass, $user_id ); // force pwd to the mw one incase user already existed
	$creds = array(
		'user_login' => $mwuser->name,
		'user_password' => $mwuser->pass,
		'remember' => false
	);
	wp_signon( $creds );
	header( 'Location: ' . $_SERVER['REQUEST_URI'] );
	exit();
}

