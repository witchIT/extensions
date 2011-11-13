<?php
/* EmailToWiki extension - Allows emails to be sent to the wiki and added to an existing or new article
 * Started: 2007-05-25, version 2 started 2011-11-13
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 - 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'EMAILTOWIKI_VERSION', '2.0.0, 2011-11-13' );

$wgExtensionCredits['other'][] = array(
	'name'        => 'EmailToWiki',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Allows emails to be sent to the wiki and added to an existing or new article',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:EmailToWiki',
	'version'     => EMAILTOWIKI_VERSION
);

// Add a MediaWiki variable to get the page's email address
$wgETWCustomVariables = array('EMAILTOWIKI');
 
$wgHooks['MagicWordMagicWords'][]          = 'wfETWAddCustomVariable';
$wgHooks['MagicWordwgVariableIDs'][]       = 'wfETWAddCustomVariableID';
$wgHooks['LanguageGetMagic'][]             = 'wfETWAddCustomVariableLang';
$wgHooks['ParserGetVariableValueSwitch'][] = 'wfETWGetCustomVariable';
 
function wfETWAddCustomVariable( &$magicWords ) {
	global $wgETWCustomVariables;
	foreach( $wgETWCustomVariables as $var ) $magicWords[] = "MAG_$var";
	return true;
}
 
function wfETWAddCustomVariableID( &$variables ) {
	global $wgETWCustomVariables;
	foreach($GLOBALS['wgETWCustomVariables'] as $var) $variables[] = constant("MAG_$var");
	return true;
	}
 
function wfETWAddCustomVariableLang( &$langMagic, $langCode = 0 ) {
	global $wgETWCustomVariables;
	foreach( $wgETWCustomVariables as $var ) {
		$magic = "MAG_$var";
		$langMagic[ defined( $magic ) ? constant( $magic ) : $magic ] = array( 0, $var );
	}
	return true;
}
 
function wfETWGetCustomVariable( &$parser, &$cache, &$index, &$ret ) {
	if( $index == MAG_EMAILTOWIKI ) {
		global $wgTitle, $wgServer;
		$url  = parse_url( $wgServer );
		$host = ereg_replace( '^www.','',$url['host'] );
		$ret  = $wgTitle->getPrefixedURL();
		$ret  = str_replace( ':','&3A',$ret );
		$ret  = eregi_replace( '%([0-9a-z]{2})', '&$1', $ret );
		$ret  = "$ret@" . $url['host'];
		$ret  = "[mailto:$ret $ret]";
	}
	return true;
}
