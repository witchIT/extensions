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
	function magicAccordian( &$parser, $tree ) {

		# <script src="jquery-1.2.1.min.js" type="text/javascript"></script>
		# <script src="menu.js" type="text/javascript"></script>

		#	<ul id="menu">
		#		<li>
		#			<a href="#">Weblog Tools</a>
		#			<ul>
		#				<li><a href="http://www.pivotx.net/">PivotX</a></li>
		#				<li><a href="http://www.wordpress.org/">WordPress</a></li>
		#				<li><a href="http://www.textpattern.com/">Textpattern</a></li>
		#				<li><a href="http://typosphere.org/">Typo</a></li>
		#			</ul>
		#		</li>
		#	</ul>

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
