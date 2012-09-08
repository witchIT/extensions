<?php
/**
 * Name: Provide Registration Reason
 * Description: Adds a textbox for a user to provide a reason for their registration to help admins in their accept/declone decision
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function regreason_install() {
	register_hook('app_menu', 'addon/regreason/regreason.php', 'regreason');
}

function simpledetails_uninstall() {
	unregister_hook('app_menu', 'addon/regreason/regreason.php', 'regreason');
}

function regreason( $menu ) {
}
