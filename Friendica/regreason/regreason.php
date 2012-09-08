<?php
/**
 * Name: Provide Registration Reason
 * Description: Adds a textbox for a user to provide a reason for their registration to help admins in their accept/declone decision
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function regreason_install() {
	register_hook('register_form', 'addon/regreason/regreason.php', 'regreason_form');
	register_hook('register_account', 'addon/regreason/regreason.php', 'regreason_register');
}

function regreason_uninstall() {
	unregister_hook('register_form', 'addon/regreason/regreason.php', 'regreason_form');
	unregister_hook('register_account', 'addon/regreason/regreason.php', 'regreason_register');
}

/**
 * Add the new textbox to the form
 */
function register_form( $a, $form ) {
}

/**
 * Send an email to admins with the textbox content
 */
function regreason_register( $a, $uid ) {

	// Send email to admin
	$res = mail($a->config['admin_email'], sprintf(t('Registration request at %s'), $a->config['sitename']),
		$email_tpl,
			'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
			. 'Content-type: text/plain; charset=UTF-8' . "\n"
			. 'Content-transfer-encoding: 8bit' );

}
