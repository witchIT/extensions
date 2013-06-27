<?php
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
/*
 * Copyright (C) 2007-2010 Aran Dunkley and others
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */
ini_set( 'memory_limit', '128M' );

// Need to turn of strict warnings as too many third-party extensions raise errors
ini_set('display_errors', 'Off'); 
error_reporting(E_ALL ^ E_STRICT);

// Constants
define( 'WIKIA_VERSION', '1.2.10, 2013-06-27' );

// Read the DB access and bot name info from wikid.conf
$wgWikidAddr = '127.0.0.1';
foreach( file( '/var/www/tools/wikid.conf' ) as $line ) {
	if( preg_match( "|^\s*\\\$addr\s*=\s*['\"](.+?)[\"']|m", $line, $m ) ) $wgWikidAddr = $m[1];
	if( preg_match( "|^\s*\\\$(wgDB.+?)\s*=\s*['\"](.+?)[\"']|m", $line, $m ) ) $$m[1] = $m[2];
}

// Namespaces
define( 'NS_FORM',           106  );
define( 'NS_EXTENSION',      1000 );
define( 'NS_CONFIG',         1004 );
define( 'NS_PORTAL',         1010 );
define( 'NS_CREATE',         1014 );
define( 'NS_EMAIL',          1016 );

$wgNamespacesWithSubpages[NS_MAIN] = true;
$wgExtraNamespaces[NS_FORM]        = 'Form';
$wgExtraNamespaces[NS_FORM+1]      = 'Form_talk';
$wgExtraNamespaces[NS_EXTENSION]   = 'Extension';
$wgExtraNamespaces[NS_EXTENSION+1] = 'Extension_talk';
$wgExtraNamespaces[NS_CONFIG]      = 'Config';
$wgExtraNamespaces[NS_CONFIG+1]    = 'Config_talk';
$wgExtraNamespaces[NS_PORTAL]      = 'Portal';
$wgExtraNamespaces[NS_PORTAL+1]    = 'Portal_talk';
$wgExtraNamespaces[NS_CREATE]      = 'Create';
$wgExtraNamespaces[NS_CREATE+1]    = 'Create_talk';
$wgExtraNamespaces[NS_EMAIL]       = 'Email';
$wgExtraNamespaces[NS_EMAIL+1]     = 'Email_talk';

// Default globals defined before specific LocalSettings inclusion
$wgArticlePath            = '/$1';
$wgScriptPath             = '/wiki';

$wgUseDatabaseMessages    = true;
$wgSecurityUseDBHook      = true;
$wgDBmysql5               = false;

$wgTruncatedCommentLength = 50;
$wgVerifyMimeType         = false;
$wgUseTeX                 = true;
$wgSVGConverter           = 'rsvg';
$wgRewriteRule            = 'Friendly'; // rewrite.pl URL transformation function name
$wgSiteDown               = false;
$wgEmergencyContact       = false;

$wgMaxShellMemory         = 262144;
$wgAllowDisplayTitle      = true;
$wgAllowPageInfo          = true;
$wgRawHtml                = true;
$wgUseSiteCss             = true;
$wgUseSiteJs              = true;
$wgUseWikiaCss            = true;

// File upload settings
$wgEnableUploads          = true;
$wgAllowCopyUploads       = true;
$wgUploadPath             = '/files';
$wgFileExtensions         = array(
	'jpeg', 'jpg', 'png', 'gif', 'svg', 'swf',
	'pdf', 'xls', 'xlsx', 'ods', 'odt', 'odp', 'doc', 'docx', 'mm',
	'zip', '7z', 'gz', 'tgz', 't7z',
	'avi', 'divx', 'mpeg', 'mpg', 'ogv', 'ogm', 'mp3', 'mp4', 'flv',
	'torrent'
);
$wgGroupPermissions['sysop']['upload_by_url'] = true;

// Messages
$wgExtensionMessagesFiles['OD'] = dirname( __FILE__ ) . '/wikia.i18n.php';

// Allow fallback to OD images
$wgUseSharedUploads       = true;
$wgSharedUploadDirectory  = '/var/www/wikis/od/files';
$wgSharedUploadPath       = 'http://www.organicdesign.co.nz/files';

// Global wikia configuration
$settings                 = '/var/www/wikis';
$domains                  = '/var/www/domains';
$extensions               = dirname( __FILE__ );

// If running from command-line set the DBadmin user and pass from the ones in wikid.conf
if( $wgCommandLineMode ) {
	$wgDBadminuser = $wgDBuser;
	$wgDBadminpassword = $wgDBpassword;
	$root = "$domains/$domain";
}

// If it's a normal web request, set the root from SERVER_NAME
else {
	$domain = preg_replace( "/^(www\.|wiki\.)/", "", $_SERVER['SERVER_NAME'] );
	$root   = $_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] . "/$domain";
	$domain = $_SERVER['SERVER_NAME'];
}

// Add google analytics code
$wgExtensionFunctions[] = 'wfGoogleAnalytics';
$wgGoogleTrackingCodes = array();
function wfGoogleAnalytics() {
	global $wgOut, $wgGoogleTrackingCodes;
	foreach( $wgGoogleTrackingCodes as $code ) $wgOut->addScript( '<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
		</script><script type="text/javascript">
		var pageTracker = _gat._getTracker("' . $code . '");
		pageTracker._trackPageview();</script>' );
}

// Include the LocalSettings file for the domain
$wgUploadDirectory = "$root$wgUploadPath";
include( "$root/LocalSettings.php" );

// Add our specials to the OD group
$wgSpecialPageGroups['RecordAdmin'] = 'od';
$wgSpecialPageGroups['EmailPage'] = 'od';
$wgSpecialPageGroups['NukeDPL'] = 'od';

// Display a maintenance page if $wgSiteDown set (unless request is from command line)
if( $wgSiteDown && !$wgCommandLineMode ) {
	while( @ob_end_clean() );
	$msg = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>Down for maintenance</title></head>
	<body bgcolor="white"><table width="100%"><tr><td align="center">
	<img border="0" src="http://www.organicdesign.co.nz/files/9/9c/Cone.png" style="padding-top:100px"/><br>
	<div style="font-family:sans;font-weight:bold;color:#89a;font-size:16pt;padding-top:25px">
	This site is temporarily down for maintenance<br><br><small>Please try again soon</small>
	</div></td></tr></table></body></html>';
	if ( in_array('Content-Encoding: gzip', headers_list() ) ) $msg = gzencode( $msg );
	echo $msg;
	die;
}

// Post LocalSettings globals
$wgUploadDirectory = $_SERVER['DOCUMENT_ROOT'] . "$wgUploadPath"; // allows wiki's settings to change images location
$wgLocalInterwiki  = $wgSitename;
$wgMetaNamespace   = $wgSitename;
if( $wgEmergencyContact === false ) $wgEmergencyContact = $wgPasswordSender = 'admin@' . str_replace( 'www.', '', $domain );

$wgNoReplyAddress = "";

// Add wikia.css
if( $wgUseWikiaCss ) $wgHooks['BeforePageDisplay'][] = 'odAddWikiaCss';
function odAddWikiaCss( &$out, $skin = false ) {
	global $wgScriptPath;
	$out->addScript("<link rel=\"stylesheet\" type=\"text/css\" href=\"$wgScriptPath/extensions/wikia.css\" />");
	return true;
}

// Map naked URL to different articles depending on domain
function domainRedirect( $list ) {
	if ( basename( $_SERVER['SCRIPT_FILENAME'] ) !== 'index.php' ) return;
	$d = $_SERVER['SERVER_NAME'];
	$t = $_REQUEST['title'];
	if( empty( $t ) ) $t = preg_replace( "|^/|", "", isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '' );
	if( empty( $t ) || $t == 'Main_Page' )
		foreach( $list as $regexp => $title )
			if( preg_match( "|$regexp|", $d ) ) header( "Location: $wgServer/$title" ) && die;
}

// Automatically log the server user in
//$wgHooks['UserLoadFromSession'][] = 'odWikidAccess';
$wgWikidUserId = 1;
function odWikidAccess( &$user, &$result ) {
	global $wgWikidUserId, $wgWikidAddr;
	if( $wgWikidUserId && $_SERVER['REMOTE_ADDR'] == $wgWikidAddr ) {
		$user->setId( $wgWikidUserId );
		$result = false;
	}
	return true;
}

// Block problem users, bots and requests
//$wgExtensionFunctions[] = 'odLogActivity';
function odLogActivity() {
	global $wgUser, $wgShortName, $wgRequest;
	$user = $wgUser->getUserPage()->getText();
	$sesh = preg_match( "|_session=([0-9a-z]+)|", isset( $_SERVER['HTTP_COOKIE'] ) ? $_SERVER['HTTP_COOKIE'] : '', $m ) ? $m[1] : '';
	if( $sesh ) $user .= ":$sesh";
	if( !$wgUser->isAnon() ) $user .= ':' . $_SERVER['REMOTE_ADDR'];
	$url = $_SERVER['REQUEST_URI'];
	if( $wgRequest->wasPosted() ) {
		$post = array();
		foreach( $wgRequest->getValues() as $k => $v ) {
			if( is_array( $v ) ) $v = implode(',', $v);
			if( strlen( $v ) > 10 ) $v = substr( $v, 0, 9 ) . '...';
			$v = urlencode( $v );
			$post[] = "$k=$v";
		}
		$post = join( ',', $post );
		$url = '/' . $wgRequest->getText( 'title' ) . " (POST:$post)";
	}
	$block = '';

	// IP/User based blocks
	$list = array(        // nslookup on ipaddresses;
		'148.243.232.98', // Bot attempting shell hacks
	);
	
	foreach( $list as $i ) if( $block == '' and preg_match( "|$i|", $user ) ) $block .= '(ip-block)';

	// Session-based blocks
	if(
		$sesh == '2297d58013571cb3a6adddb9c5e3c36f'
		|| $sesh == '0bbf7493262a75e3258c8da11a303296'
	) $block .= '(sesh-block)';

	// Silently block requests
	if( preg_match( "/(favicon|robots.txt|action=rawxxx)/i", $url ) ) return;

	// Write log entry
	$handle = fopen( "/var/www/activity.log", "a" );
	fwrite( $handle, date( 'Y-m-d H:i:s' ) . " ($wgShortName)($user)$block: $url\n" );
	//if ($block) { sleep(1); die; }
}

