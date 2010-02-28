<?php
/**
 * ExtraMagic extension - Adds useful variables and parser functions
 *
 * See http://www.organicdesign.co.nz/Extension:ExtraMagic.php
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/User:Nad User:Nad]
 * @copyright © 2007 [http://www.organicdesign.co.nz/User:Nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if (!defined('MEDIAWIKI')) die('Not an entry point.');
 
define('EXTRAMAGIC_VERSION', '2.0.5, 2010-02-28');
 
$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'ExtraMagic',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Adds useful variables and parser functions',
	'url'         => 'http://www.organicdesign.co.nz/Extension:ExtraMagic.php',
	'version'     => EXTRAMAGIC_VERSION
);
 
 
$wgCustomVariables = array( 'CURRENTUSER', 'CURRENTPERSON', 'CURRENTLANG', 'CURRENTSKIN', 'IPADDRESS', 'DOMAIN', 'NUMBERINGOFF', 'GUID' );
 
$wgExtensionFunctions[]                    = 'efSetupExtraMagic';
$wgHooks['MagicWordMagicWords'][]          = 'efAddCustomVariable';
$wgHooks['MagicWordwgVariableIDs'][]       = 'efAddCustomVariableID';
$wgHooks['LanguageGetMagic'][]             = 'efAddCustomVariableLang';
$wgHooks['ParserGetVariableValueSwitch'][] = 'efGetCustomVariable';
 
/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function efSetupExtraMagic() {
	global $wgParser;
	$wgParser->setFunctionHook( 'REQUEST', 'efExtraMagicExpandRequest', SFH_NO_HASH );
	$wgParser->setFunctionHook( 'USERID',  'efExtraMagicExpandUserID',  SFH_NO_HASH );
}
 
/**
 * Register magic words
 */
function efAddCustomVariable( &$magicWords ) {
	global $wgCustomVariables;
	foreach( $wgCustomVariables as $var ) $magicWords[] = "MAG_$var";
	return true;
}
 
function efAddCustomVariableID( &$variables ) {
	global $wgCustomVariables;
	foreach( $wgCustomVariables as $var ) $variables[] = constant( "MAG_$var" );
	return true;
}
 
function efAddCustomVariableLang( &$langMagic, $langCode = 0 ) {
	global $wgCustomVariables;
 
	# Magic words
	foreach( $wgCustomVariables as $var ) {
		$magic = "MAG_$var";
		$langMagic[defined( $magic ) ? constant( $magic ) : $magic] = array( $langCode, $var );
	}
 
	# Parser functions
	$langMagic['REQUEST'] = array( $langCode, 'REQUEST' );
	$langMagic['USERID']  = array( $langCode, 'USERID' );
 
	return true;
}
 
/**
 * Expand parser functions
 */
function efExtraMagicExpandRequest( &$parser, $param ) {
	global $wgRequest;
	$parser->disableCache();
	return $wgRequest->getText( $param );
}
 
function efExtraMagicExpandUserID( &$parser, $param ) {
	$col = strpos( $param, ' ' ) ? 'user_real_name' : 'user_name';
	$dbr = wfGetDB( DB_SLAVE );
	if ( $row = $dbr->selectRow( 'user', array( 'user_id' ), array( $col => $param ), __METHOD__ ) )
		return $row->user_id;
	return '';
}
 
/**
 * Process variable values
 */
function efGetCustomVariable( &$parser, &$cache, &$index, &$ret ) {
	switch ( $index ) {
 
		case MAG_CURRENTUSER:
			global $wgUser;
			$parser->disableCache();
			$ret = $wgUser->mName;
		break;
 
		case MAG_CURRENTPERSON:
			global $wgUser;
			$parser->disableCache();
			$ret = $wgUser->getRealName();
		break;

		case MAG_CURRENTLANG:
			global $wgUser;
			$parser->disableCache();
		break;
			$ret = $wgUser->getOption( 'language' );
 
		case MAG_CURRENTSKIN:
			global $wgUser;
			$parser->disableCache();
			$ret = $wgUser->getOption( 'skin' );
		break;
 
		case MAG_IPADDRESS:
			$parser->disableCache();
			$ret = $_SERVER['REMOTE_ADDR'];
		break;
 
		case MAG_DOMAIN:
			$parser->disableCache();
			$ret = $_SERVER['SERVER_NAME'];
		break;
 
		case MAG_NUMBERINGOFF:
			global $wgUser;
			$wgUser->setOption( 'numberheadings', false );
			$ret = '';
		break;
 
		case MAG_GUID:
			$parser->disableCache();
			$ret = strftime( '%Y%m%d', time() ) . '-' . substr( strtoupper( uniqid('', true) ), -5 );
		break;
 
	}
	return true;
}
