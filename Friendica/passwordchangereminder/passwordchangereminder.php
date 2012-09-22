<?php
/**
 * Name: Password Change Reminder
 * Description: Adds a new column in the user database table and records the timestamp of the last password change in it. A reminder email is sent to users each day if they have never changed their password.
 * Version: 1.0
 * Author: Aran Dunkley <http://www.organicdesign.co.nz/nad>
 *
 */
function passwordchangereminder_install() {
	register_hook('settings_post', 'addon/passwordchangereminder/passwordchangereminder.php', 'passwordchangereminder_timestamp');
}

function passwordchangereminder_uninstall() {
	unregister_hook('settings_post', 'addon/passwordchangereminder/passwordchangereminder.php', 'passwordchangereminder_timestamp');
}

/**
 * Record the password change in the pwd_change_time columns, create column if necessary
 */
function passwordchangereminder_timestamp( &$a, $post ) {
	if((x($post,'npassword')) || (x($post,'confirm'))) {
		$newpass = $post['npassword'];
		$confirm = $post['confirm'];
		if($newpass == $confirm && x($newpass)) {
			$password = hash('whirlpool',$newpass);
			$r = q("SELECT `password` FROM `user` WHERE `uid` = %d", intval(local_user()));
			if($r[0]['password'] != $password) {

				// Check if the columns exists and create if not
				$r = q("DESCRIBE `user`");
				if($r[count($r)-1]['Field'] != 'pwd_change_time') {
					q("ALTER TABLE `user` ADD `pwd_change_time` DATETIME");
				}

				// Store the timestamp
				$r = q("UPDATE `user` SET `pwd_change_time` = '%s' WHERE `uid` = %d",dbesc(datetime_convert()),intval(local_user()));
			}
		}
	}
}

