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

function shortprofileurl(&$a) {
	if(!file_exists("mod/{$a->module}.php")) {
		if(q("select 1 from `user` where `nickname`='{$a->module}'")) {
			$nick = $a->module;
			$a->argv = array( 'profile', $nick );
			$a->argc = 2;
			$a->query_string = "/profile/$nick/&tab=profile";
			$a->cmd = "/profile/$nick";
			$a->module = 'profile';
			$_GET['tab'] = 'profile';
		}
	}
}
