<?php
/**
 * Name: Home Redirect
 * Description: Redirects homepage (naked domain) if user is not logged in
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function homeredirect_install() {
	register_hook('page_header', 'addon/homeredirect/homeredirect.php', 'homeredirect');
}
function homeredirect_uninstall() {
	unregister_hook('page_header', 'addon/homeredirect/homeredirect.php', 'homeredirect');
}
function homeredirect(&$b) {
	global $a;
	if( $_SERVER['REQUEST_URI'] == '/' && !is_array( $a->user ) ) {
		readfile( $_SERVER['DOCUMENT_ROOT']."/anonymous-home.html" );
		exit;
	}
}

