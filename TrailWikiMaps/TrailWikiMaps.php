<?php
/**
 * TrailWikiMaps extension - Create custom maps using SemanticMaps data over AJAX (for Elance job 28036402)
 *
 * See http://www.mediawiki.org/wiki/Extension:TreeAndMenu for installation and usage details
 * See http://www.organicdesign.co.nz/Extension_talk:TreeAndMenu.php for development notes and disucssion
 * 
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'TRAILWIKIMAP_VERSION','0.0.1, 2012-01-15' );

$wgTrailWikiMagic              = "ajaxmap";
$wgExtensionFunctions[]        = 'wfSetupTrailWikiMaps';
$wgHooks['LanguageGetMagic'][] = 'wfTrailWikiMapsLanguageGetMagic';

$wgExtensionCredits['parserhook'][] = array(
	'path'        => __FILE__,
	'name'        => 'TrailWikiMaps',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad Nad]',
	'url'         => 'https://www.elance.com/php/collab/main/collab.php?bidid=28036402',
	'description' => 'Create custom maps using SemanticMaps data over AJAX (for Elance job 28036402)',
	'version'     => TRAILWIKIMAP_VERSION
);

class TrailWikiMaps {

	function __construct() {
		global $wgOut, $wgHooks, $wgParser, $wgScriptPath, $wgJsMimeType, $wgTrailWikiMagic;

		// Add hooks
		$wgParser->setFunctionHook( $wgTrailWikiMagic, array( $this,'expandAjaxMap' ) );

		$wgResourceModules['ext.trailwikimaps'] = array(
			'scripts' => array( 'trailwikimaps.js' ),
			'styles' => array(),
			'dependencies' => array( 'ext.maps.googlemaps3' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.trailwikimaps' );

	}

	/**
	 * Expand #ajaxmap parser-functions
	 */
	public function expandAjaxMap() {
		$options = func_get_args();
		array_shift( $options );
		return 'ajaxmap';
	}

}

function wfSetupTrailWikiMaps() {
	global $wgTrailWikiMaps;
	$wgTrailWikiMaps = new TrailWikiMaps();
}

function wfTrailWikiMapsLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $wgTrailWikiMagic;
	$magicWords[$wgTrailWikiMagic] = array( $langCode, $wgTrailWikiMagic );
	return true;
}
