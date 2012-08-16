<?php
/**
 * Name: Simple Details
 * Description: Simplify the personal details form
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function simpledetails_install() {
	register_hook('app_menu', 'addon/simpledetails/simpledetails.php', 'simpledetails_js');
}

function simpledetails_uninstall() {
	unregister_hook('app_menu', 'addon/simpledetails/simpledetails.php', 'simpledetails_js');
}

/**
 * Add our javascript
 */
function simpledetails_js( $menu ) {
	global $a;
	$a->page['htmlhead'] .= '<script src="' . $a->get_baseurl(true) . '/addon/simpledetails/simpledetails.js" ></script>';
}
