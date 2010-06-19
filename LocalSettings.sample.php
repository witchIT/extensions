<?php
# This is a sample of settings to be appended to a LocalSettings.php file
# for a wiki which is not part of an Organic Design wikia installation.
#
# These settings prepare a wiki for the Wiki Organisation extensions
#
$wgSitename          = "Sample";
$wgShortName         = "foo";
$wgDBname            = "foo";
$wgDBprefix          = "foo_";



# Namespaces
define( 'NS_FORM', 106  );
$wgExtraNamespaces[NS_FORM]        = 'Form';
$wgExtraNamespaces[NS_FORM + 1]    = 'Form_talk';

# General settings
$wgUseDatabaseMessages    = true;
$wgSecurityUseDBHook      = true;
$wgVerifyMimeType         = false;
$wgSVGConverter           = 'rsvg';
$wgEmergencyContact       = false;
$wgRawHtml                = true;
$wgUseSiteCss             = true;
$wgUseSiteJs              = true;
$wgUseWikiaCss            = true;

# File upload settings
$wgEnableUploads          = true;
$wgAllowCopyUploads       = true;
$wgFileExtensions         = array(
        'jpeg', 'jpg', 'png', 'gif', 'svg', 'swf',
        'pdf', 'xls', 'ods', 'odt', 'doc', 'docx', 'mm',
        'zip', '7z', 'gz', 'tgz', 't7z',
        'avi', 'divx', 'mpeg', 'mpg', 'ogv', 'ogm', 'mp3', 'mp4', 'flv',
        'torrent'
);
$wgGroupPermissions['sysop']['upload_by_url'] = true;

# Add Organic Design i18n messages
$wgExtensionMessagesFiles['OD'] = "$IP/extensions/wikia.i18n.php";

# Don't include extension code if running from shell for maintenance
if ( !$wgCommandLineMode ) {

	# Add Organic Design CSS
	if ( $wgUseWikiaCss ) $wgHooks['BeforePageDisplay'][] = 'odAddWikiaCss';

	# Force skin to DCS
	$wgDefaultSkin = 'foo';
	$wgExtensionFunctions[] = 'wfForceSkin';

	# Force timezone default to NZ
	$wgLocaltimezone = "Pacific/Auckland";
	putenv( "TZ=$wgLocaltimezone" );
	$wgLocalTZoffset = date( "Z" ) / 60;

	# Force HTTPS
	if ( !isset( $_SERVER['HTTPS'] ) ) {
		header( "Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		exit;
	}

	# Redirect main-page requests based on domain
	domainRedirect( array(
		'foo.bar$' => 'About Foo',
		'bar.baz$' => 'About Bar'
	) );

	# Example of adding codes for google analytics
	$wgGoogleTrackingCodes = array( 'UA-1234567-1' );
	$wgExtensionFunctions[] = 'wfGoogleAnalytics';

	# General extensions
	include( 'extensions/Nuke/SpecialNuke.php' );
	include( 'extensions/NewUserLog/Newuserlog.php' );
	include( 'extensions/Renameuser/SpecialRenameuser.php' );
	include( 'extensions/UserMerge/UserMerge.php' );
	include( 'extensions/ParserFunctions/ParserFunctions.php' );
	include( 'extensions/RegexParserFunctions/RegexParserFunctions.php' );
	include( 'extensions/StringFunctions/StringFunctions.php' );
	include( 'extensions/DynamicPageList/DynamicPageList2.php' );
	include( 'extensions/Cite/Cite.php' );
	include( 'extensions/Variables/Variables.php' );

	# OD extensions
	include( 'extensions/EventPipe/EventPipe.php' );
	include( 'extensions/InterWiki/InterWiki.php' );
	include( 'extensions/ExtraMagic/ExtraMagic.php' );
	include( 'extensions/SpecialNukeDPL.php' );
	include( 'extensions/WikidAdmin/SpecialWikidAdmin.php' );
	include( 'extensions/WikiaAdmin/SpecialWikiaAdmin.php' );
	include( 'extensions/JavaScript/JavaScript.php' );
	include( 'extensions/TransformChanges/TransformChanges.php' );
	include( 'extensions/TreeAndMenu/TreeAndMenu.php' );
	include( 'extensions/RecordAdmin/RecordAdmin.php' );
	include( 'extensions/RecordAdminCreateForm/RecordAdminCreateForm.php' );
	include( 'extensions/RecordAdminIntegratePerson/RecordAdminIntegratePerson.php' );
	include( 'extensions/SimpleSecurity/SimpleSecurity.php' );

	# Lock down to intranet-only
	$wgPageRestrictions['Namespace:Form']['edit'] = 'sysop';
	$wgSecurityExtraGroups                        = array( 'foo', 'bar' );
	$wgSecurityGroupsArticle                      = 'Groups';
	$wgGroupPermissions['*']['read']              = false;
	$wgGroupPermissions['*']['edit']              = false;
	$wgGroupPermissions['*']['createaccount']     = false;
	$wgGroupPermissions['user']['createaccount']  = true;
	$wgWhitelistRead = array( "Special:Userlogin", "-", "MediaWiki:Common.css" );

	# Allow articles in Category:Public to be publically accessible
	$wgHooks['UserGetRights'][] = 'wfPublicCat';

	# Bot jobs
	include( '/var/www/tools/jobs/ImportCSV.php' );
	include( '/var/www/tools/jobs/ModifyRecords.php' );

	# TreeView style
	$wgTreeViewShowLines = true;
	$wgTreeViewImages['folder']     = "Folder_mist.gif";
	$wgTreeViewImages['folderOpen'] = "Folder_opn_mist.gif";
}

function odAddWikiaCss( &$out, $skin = false ) {
	global $wgScriptPath;
	$out->addScript("<link rel=\"stylesheet\" type=\"text/css\" href=\"$wgScriptPath/extensions/wikia.css\" />");
	return true;
}

function wfForceSkin() {
	global $wgUser;
	$wgUser->setOption( 'skin', 'foo' );
}

function wfPublicCat() {
	global $wgTitle, $wgWhitelistRead;
	$id   = $wgTitle->getArticleID();
	$dbr  = wfGetDB( DB_SLAVE );
	$cat  = $dbr->addQuotes( 'Public' );
	$cl   = $dbr->tableName( 'categorylinks' );
	if ( $dbr->selectRow( $cl, '0', "cl_from = $id AND cl_to = $cat" ) )
		$wgWhitelistRead[] = $wgTitle->getText();
	return true;
}

function wfGoogleAnalytics() {
	global $wgOut, $wgGoogleTrackingCodes;
	foreach ( $wgGoogleTrackingCodes as $code ) $wgOut->addScript( '<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
		</script><script type="text/javascript">
		var pageTracker = _gat._getTracker("' . $code . '");
		pageTracker._trackPageview();</script>' );
}
