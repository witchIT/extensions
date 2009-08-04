<?php
/**
 * ParseAfter extension - A parser function allowing contained content to be parsed after parent content is parsed
 * 
 * See http://www.mediawiki.org/wiki/Extension:ParseAfter for installation and usage details
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 * @copyright © 2007 [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'PARSEAFTER_VERSION', '0.0.0, 2009-07-04' );

$egParseAfterMagic             = "after";
$wgExtensionFunctions[]        = 'efSetupParseAfter';
$wgHooks['LanguageGetMagic'][] = 'efParseAfterLanguageGetMagic';

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'ParseAfter',
	'author'      => '[http://www.mediawiki.org/wiki/User:Nad User:Nad]',
	'description' => 'A parser function allowing contained content to be parsed after parent content is parsed',
	'url'         => 'http://www.organicdesign.co.nz/Extension:ParseAfter',
	'version'     => PARSEAFTER_VERSION
);

class ParseAfter {

	function __construct() {
		global $wgHooks, $wgParser, $egParseAfterMagic, $egParseAfterTag;
 
		# Add the parser-function
		$wgParser->setFunctionHook( $egParseAfterMagic, array( $this, '' ) );
 
		# Add the tagHook
		$wgParser->setHook( $egParseAfterTag, array( $this, '' ) );

	}

	function ( &$parser ) {
		global $egParseAfterMagic;

		# Populate $argv with both named and numeric parameters
		$argv = array();
		foreach ( func_get_args() as $arg ) if ( !is_object( $arg ) ) {
			if ( preg_match( '/^(.+?)\\s*=\\s*(.+)$/', $arg, $match ) ) $argv[$match[1]] = $match[2]; else $argv[] = $arg;
		}

		# Return result with available parser flags
		return array(
			$text,
			'found'   => true,
			'nowiki'  => false,
			'noparse' => false,
			'noargs'  => false,
			'isHTML'  => false
		);

	}
}

function efSetupParseAfter() {
	global $egParseAfter;
	$egParseAfter = new ParseAfter();
}

function efParseAfterLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $egParseAfterMagic;
	$magicWords[$egParseAfterMagic] = array($langCode, $egParseAfterMagic);
	return true;
}
