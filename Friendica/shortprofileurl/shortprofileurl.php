<?php
/**
 * Name: Short Profile URL
 * Description: Allow people to get to a users profile page and tab with domain/user
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function shortprofileurl_install() {
	register_hook('init_1', 'addon/shortprofileurl/shortprofileurl.php', 'shortprofileurl');
	//register_hook('app_menu', 'addon/shortprofileurl/shortprofileurl.php', 'shortprofileurl_js');
}

function shortprofileurl_uninstall() {
	unregister_hook('init_1', 'addon/shortprofileurl/shortprofileurl.php', 'shortprofileurl');
	//unregister_hook('app_menu', 'addon/shortprofileurl/shortprofileurl.php', 'shortprofileurl_js');
}

function shortprofileurl(&$a) {

	// If this is a profile/nick request, redirect it to short form
	//if( preg_match( "|^profile/([^\/\?]+)(\/[\?&]tab=profile)?$|", $a->query_string, $m ) ) {
	//	header( "Location: " . $a->get_baseurl() . "/$m[1]" );
	//	exit;
	//}

	// If this is no such module and it matches a nickname, change environment to profile/nick
	if(!file_exists("mod/{$a->module}.php")) {
		if(q("select 1 from `user` where `nickname`='{$a->module}'")) {
			$nick = $a->module;
			$a->argv = array( 'profile', $nick );
			$a->argc = 2;
			//$a->query_string = "/profile/$nick/&tab=profile";
			$a->cmd = "/profile/$nick";
			$a->module = 'profile';
			//$_GET['tab'] = 'profile';
		}
	}
}

/**
 * Add our javascript
 */
function shortprofileurl_js( $menu ) {
	global $a;
	$a->page['htmlhead'] .= '<script src="' . $a->get_baseurl(true) . '/addon/shortprofileurl/shortprofileurl.js" ></script>';
}
