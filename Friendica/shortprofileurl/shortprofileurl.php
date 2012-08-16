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
}

function shortprofileurl_uninstall() {
	unregister_hook('init_1', 'addon/shortprofileurl/shortprofileurl.php', 'shortprofileurl');
}

function shortprofileurl($a) {
	if(!file_exists("mod/{$a->module}.php")) {
		if(q("select 1 from `user` where `nickname`='{$a->module}'")) {
			$url = $a->get_baseurl() . "/profile/{$a->module}/?tab=profile";
			header("Location: $url");
			exit;
		}
	}
}
