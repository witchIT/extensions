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
function regreason_form( &$a, &$arr ) {

	$label = t('Please describe your association with the Mathaba Community or the reason you wish to become a member :');

	$textbox = "<div id=\"register-reason-wrapper\">
		<p><br />$label</p>
		<textarea name=\"reason\" id=\"register-reason\" style=\"width:580px;height:75px\"></textarea>
		<br /><br />
	</div>";

	$arr['template'] = str_replace( '$publish', "$textbox\n\n\t\$publish\n\n", $arr['template'] );
}

/**
 * Send an email to admins with the textbox content
 */
function regreason_register( &$a, $uid ) {
	if( array_key_exists( 'reason', $_POST ) ) {
		$reason = $_POST['reason'];
		$r = q("SELECT * FROM `contact` WHERE uid=%d",$uid);
		$name = $r[0]['name'];
		$nick = $r[0]['nick'];
		$subject = sprintf(t('Registration reason provided by %s'), $name);
		$body = sprintf(
			t("%s (nickname \"%s\") has provided the following reason for their registration:\n\n%s"),
			$name,
			$nick,
			$reason
		);
		$res = mail(
			$a->config['admin_email'],
			$subject,
			$body,
			'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] .
				"\nContent-type: text/plain; charset=UTF-8\nContent-transfer-encoding: 8bit"
		);
	}
}
