<?php
/**
 * Settings for Ligmincha Brasil volunteers wiki
 */
$wgCookieDomain         = ".ligmincha.com.br";
$wgLogo                 = "/wiki/skins/Ligmincha/images/logo-ligmincha-wiki.png";
$wgRawHtml              = true;
$wgDefaultSkin          = 'monobook';
$wgLanguageCode         = 'pt-br';

// Bounce naked domain to account request page
if( $_SERVER['REQUEST_URI'] == '/' ) {
	header( "Location: http://wiki.ligmincha.com.br/Especial:Pedir_conta" );
	exit;
}

// Make red-link edits use Visual Editor
if( array_key_exists( 'redlink', $_GET ) && array_key_exists( 'action', $_GET ) && $_GET['action'] == 'edit' ) {
	$url = str_replace( 'action', 'veaction', $_SERVER['REQUEST_URI'] );
	header( "Location: http://wiki.ligmincha.com.br$url" );
	exit;
}

// Bounce requests to the old domain to the new one
if( $_SERVER['HTTP_HOST'] == 'ligmincha.odnz.co' ) {
	header( "Location: http://wiki.ligmincha.com.br" . $_SERVER['REQUEST_URI'] );
	exit;
}

// Permissions
$wgGroupPermissions['*']['edit']                   = false;
$wgGroupPermissions['*']['read']                   = $_SERVER['REMOTE_ADDR'] == '68.168.101.71';
$wgGroupPermissions['*']['upload']                 = false;
$wgGroupPermissions['user']['upload']              = true;
$wgGroupPermissions['user']['upload_by_url']       = true;
$wgGroupPermissions['*']['createpage']             = false;
$wgGroupPermissions['user']['createpage']          = true;
$wgGroupPermissions['sysop']['createpage']         = true;
$wgGroupPermissions['joomla']['edit']              = true;

// User merge extension
include( "$IP/extensions/UserMerge/UserMerge.php" );
$wgGroupPermissions['bureaucrat']['usermerge'] = true;

// Confirm Account extension
include( "$IP/extensions/ConfirmAccount/ConfirmAccount.php" );
$wgWhitelistRead[] = 'Especial:Pedir conta';
$wgWhitelistRead[] = 'Special:UserLogout';
$wgConfirmAccountRequestFormItems = array(
	'UserName'        => array( 'enabled' => true ),
	'RealName'        => array( 'enabled' => true ),
	'Biography'       => array( 'enabled' => true, 'minWords' => 2 ),
	'AreasOfInterest' => array( 'enabled' => false ),
	'CV'              => array( 'enabled' => false ),
	'Notes'           => array( 'enabled' => false ),
	'Links'           => array( 'enabled' => false ),
	'TermsOfService'  => array( 'enabled' => false ),
);
$wgConfirmAccountContact = 'aran@organicdesign.co.nz';

// Wiki editor extensions
wfLoadExtension( 'WikiEditor' );
$wgDefaultUserOptions['usebetatoolbar']            = 1;
$wgDefaultUserOptions['usebetatoolbar-cgd']        = 1;
$wgDefaultUserOptions['wikieditor-preview']        = 1;
$wgDefaultUserOptions['watchdefault']              = false;
include( "$IP/extensions/VisualEditor/VisualEditor.php" );
$wgDefaultUserOptions['visualeditor-enable'] = 1; // enabled by default for all
$wgHiddenPrefs[] = 'visualeditor-enable'; // don't allow disabling
$wgDefaultUserOptions['visualeditor-enable-experimental'] = 1;
$wgVisualEditorParsoidURL = 'http://localhost:8142';
$wgVisualEditorParsoidPrefix = 'ligmincha';
$wgVisualEditorSupportedSkins[] = 'monobook';

// Organic Design extensions
wfLoadExtension( 'ExtraMagic' );
wfLoadExtension( 'HighlightJS' );
wfLoadExtension( 'AjaxComments' );
$wgAjaxCommentsPollServer = 5;

// Force users to use old changes format
$wgExtensionFunctions[] = 'wfOldChanges';
function wfOldChanges() {
	global $wgUser;
	$wgUser->setOption( 'usenewrc', false );
}

// Always give users a token cookie
$wgExtensionFunctions[] = 'wfTokenAlways';
function wfTokenAlways() {
	global $wgUser, $wgRequest;
	if( $wgUser->isLoggedIn() && !$wgRequest->getCookie( 'Token' ) ) {
		$token = $wgUser->getToken( true );
		WebResponse::setcookie( 'Token', $token );
	}
}

// Clear out the cookies from the old domain so that there's not login trouble
// (cookie domain was changed to top-level domain so that joomla can access them for SSO)
$wgExtensionFunctions[] = 'wfClearOldCookies';
function wfClearOldCookies() {
	global $wgUser, $wgCookieDomain;
	$domain = $wgCookieDomain;
	$wgCookieDomain = '';
	foreach( array( 'UserID', 'UserName', 'Token', 'LoggedOut', '_session' ) as $k ) {
		$wgUser->clearCookie( $k );
	}
	$wgCookieDomain = $domain;
}

// Set up a private sysop-only Admin namespace
define( 'NS_ADMIN', 1020 );
$wgExtraNamespaces[NS_ADMIN]     = 'Admin';
$wgExtraNamespaces[NS_ADMIN + 1] = 'Admin_talk';
Hooks::register( 'ParserFirstCallInit', 'wfProtectAdminNamespace' );
function wfProtectAdminNamespace( Parser $parser ) {
	global $wgTitle, $wgUser, $wgOut, $mediaWiki;
	if( is_object( $wgTitle) && $wgTitle->getNamespace() == NS_ADMIN && !in_array( 'bureaucrat', $wgUser->getEffectiveGroups() ) ) {
		if( is_object( $mediaWiki ) ) $mediaWiki->restInPeace();
		$wgOut->disable();
		wfResetOutputBuffers();
		header( "Location: http://wiki.ligmincha.com.br/Página_principal" );
	}
	return true;
}

// Set "remember me" on by default
Hooks::register( 'UserLoginForm', 'wfRememberMe' );
function wfRememberMe( &$template ) {
	$template->data['remember'] = true;
	return true;
}
