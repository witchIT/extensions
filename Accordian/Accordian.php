<?php
/**
 * Accordian extension - An example extension made with [http://www.organicdesign.co.nz/Template:Extension Template:Extension]
 * 
 * See http://www.mediawiki.org/wiki/Extension:Accordian for installation and usage details
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/nad Nad]
 * @copyright © 2007 [http://www.organicdesign.co.nz/nad Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'ACCORDIAN_VERSION', '1.0.0, 2010-03-09' );

$egAccordianMagic              = "accordian";
$wgExtensionFunctions[]        = 'efSetupAccordian';
$wgHooks['LanguageGetMagic'][] = 'efAccordianLanguageGetMagic';

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'Accordian',
	'author'      => '[http://www.organicdesign.co.nz/nad Nad]',
	'description' => 'An example extension made with [http://www.organicdesign.co.nz/Template:Extension Template:Extension]',
	'url'         => 'http://www.organicdesign.co.nz/Extension:Example',
	'version'     => ACCORDIAN_VERSION
);

class Accordian {

	function __construct() {
		global $wgHooks, $wgParser, $egAccordianMagic;
 
		# Add the parser-function
		$wgParser->setFunctionHook( $egAccordianMagic, array( $this, 'magicAccordian' ) );
  
	}

	/**
	 * Expand the accordian-magic
	 */
	function magicAccordian( &$parser ) {

		# Populate $argv with both named and numeric parameters
		$argv = array();
		foreach ( func_get_args() as $arg ) if ( !is_object( $arg ) ) {
			if ( preg_match( '/^(.+?)\\s*=\\s*(.+)$/', $arg, $match ) ) $argv[$match[1]] = $match[2]; else $argv[] = $arg;
		}
 
		return $text;
 
	}

}

function efSetupAccordian() {
	global $egAccordian;
	$egAccordian = new Accordian();
}

function efAccordianLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $egAccordianMagic;
	$magicWords[$egAccordianMagic] = array( $langCode, $egAccordianMagic );
	return true;
}
