<?php
/**
 * Name: Password Change Reminder
 * Description: Adds a new column in the user database table and records the timestamp of the last password change in it. A reminder email is sent to users each day if they have never changed their password.
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function regreason_install() {
	register_hook('settings_post', 'addon/passwordchangereminder/passwordchangereminder.php', 'passwordchangereminder_timestamp');
}

function regreason_uninstall() {
	unregister_hook('settings_post', 'addon/passwordchangereminder/passwordchangereminder.php', 'passwordchangereminder_timestamp');
}

/**
 * Record the password change in the pwd_change_time columns, create column if necessary
 */
function passwordchangereminder_timestamp( &$a, &$arr ) {
	if((x($_POST,'npassword')) || (x($_POST,'confirm'))) {
		$newpass = $_POST['npassword'];
		$confirm = $_POST['confirm'];
		if($newpass == $confirm && x($newpass)) {
			$password = hash('whirlpool',$newpass);
			$r = q("SELECT `password` FROM `user` WHERE `uid` = %d", intval(local_user()));
			if($r[0]['password'] != $password) {

				// Check if the columns exists and create if not
				$r = q("DESCRIBE `user`");
				if($r[count($r)-1]['Field'] != 'pwd_change_time') {
					q("ALTER TABLE `user` ADD `pwd_change_time` BIGINT");
				}

				// Store the timestamp
				$r = q("UPDATE `user` SET `pwd_change_time` = '%d' WHERE `uid` = %d",time(),intval(local_user()));
			}
		}
	}
}
