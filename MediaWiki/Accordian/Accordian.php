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

define( 'ACCORDIAN_VERSION', '1.0.2, 2010-06-24' );

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
 		$wgParser->setFunctionHook( $egAccordianMagic, array( $this, 'magicAccordian' ) ); 
		$wgHooks['BeforePageDisplay'][] = $this;
	}

	/**
	 * Add the accordian js
	 */
	function onBeforePageDisplay( &$out, $skin = false ) {
		global $wgScriptPath, $wgJsMimeType;
		$url = preg_replace( '|^.+(?=/ext)|', $wgScriptPath, dirname( __FILE__ ) );
		$out->addScript( "<script type=\"$wgJsMimeType\" src=\"$url/menu.js\"><!-- Accordian --></script>\n" );
		return true;
	}

	/**
	 * Expand the accordian-magic
	 */
	function magicAccordian( &$parser, $tree ) {
		static $id = 0;
		$id++;
		$p = clone $parser;
		$o = clone $parser->mOptions;
		$o->mTidy = $o->mEnableLimitReport = false;
		$html = $p->parse( $tree, $parser->mTitle, $o, true, true )->getText();
		$html = preg_replace( '|<ul>|', "<ul id=\"menu$id\" class=\"menu\">", $html, 1 );
		return array( $html, 'isHTML' => true );
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
