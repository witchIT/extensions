<?php
/**
 * Name: No Email Body
 * Description: Don't send the body content of site posts in emails
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function noemailbody_install() {
	register_hook('enotify', 'addon/noemailbody/noemailbody.php', 'noemailbody_item');
	register_hook('enotify_mail', 'addon/noemailbody/noemailbody.php', 'noemailbody');
}

function noemailbody_uninstall() {
	unregister_hook('enotify', 'addon/noemailbody/noemailbody.php', 'noemailbody_item');
	unregister_hook('enofity_mail', 'addon/noemailbody/noemailbody.php', 'noemailbody');
}

// If this notification is an item, store it for use by the email hook
function noemailbody_item($a,&$h) {
	global $noemailbody_item;
	$noemailbody_item = array_key_exists( 'item', $h['params'] ) ? $h['params']['item'] : false;
}

// If this email notification is a post, clear the body part
function noemailbody($a,&$data) {
	global $noemailbody_item;
	if( $noemailbody_item ) $data['htmlversion'] = $data['textversion'] = '';
}

