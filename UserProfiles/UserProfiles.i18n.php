<?php
/**
 * Internationalisation for UserProfiles extension
 *
 * @author Aran Dunkley
 * @file
 * @ingroup Extensions
 */
global $wgSitename;

$messages = array();

/** English
 * @author Dunkley
 */
$messages['en'] = array(
	'copyrightwarning' => "",
	'copyrightwarning2' => "",
	'newarticletext' => "",
	'welcomecreation' => "Welcome to $wgSitename! please check your email to complete registration.",
	'logouttext' => "You are now logged out of $wgSitename.",
	'nav-login-createaccount' => "Sign in",
	'userlogin' => "Sign in",
	'nologinlink' => "Sign up",
	'nologin' => "Don't have an account? [[Account registration|Sign up]].",
	'nosuchuser' => "There is no user by the name \"$1\". Usernames are case sensitive. Check your spelling, or [[Account registration|Sign up]].",
);
