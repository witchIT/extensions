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
define( 'TRAILWIKIMAP_NAME', 1 );
define( 'TRAILWIKIMAP_OFFSET', 2 );
define( 'TRAILWIKIMAP_LENGTH', 3 );
define( 'TRAILWIKIMAP_DEPTH', 4 );

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

		$wgHooks['UnknownAction'][] = $this;

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
	 * Return the trail data in JSON format when the ajaxmap action is requested
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut;

		// Update the information for the specified ISBN (or oldest book in the wiki if none supplied)
		// - if the book doesn't exist and the "create" query-string item is set, then create the book article
		if( $action == 'ajaxmap' ) {
			$wgOut->disable();
			header( 'Content-Type: application/json' );
			print "{\n";
			$dbr    = &wfGetDB(DB_SLAVE);
			$tmpl   = $dbr->addQuotes( Title::newFromText( 'Infobox Trail' )->getDBkey() );
			$table  = $dbr->tableName( 'templatelinks' );
			$res    = $dbr->select( $table, 'tl_from', "tl_namespace = 10 AND tl_title = $tmpl LIMIT 5" );
			$comma  = '';
			while( $row = $dbr->fetchRow( $res ) ) {
				$data = self::getTrailData( $row[0] );
				print $comma . $data;
				$comma = ',';
			}
			$dbr->freeResult( $res );
			print "}";
		}
		return true;
	}


	/**
	 * Expand #ajaxmap parser-functions
	 */
	public function expandAjaxMap() {
		$options = func_get_args();
		array_shift( $options );
		return 'ajaxmap';
	}

	/**
	 * Return array of args from the trail infobox
	 */
	static function getTrailData( $id ) {
		$title = Title::newFromId( $id );
		$trail = $title->getText();
		$article = new Article( $title );
		preg_match( "|\{\{Infobox Trail(.+?)^\}\}|sm", $article->fetchContent(), $m );
		$template = preg_replace( "/(?<=\S)( +\| )/s", "\n$1", $m[1] ); // fix malformed template syntax
		preg_match_all( "|^\s*\|\s*(.+?)\s*= *(.*?) *(?=^\s*[\|\}])|sm", $template, $m );
		$data = array();
		foreach( $m[1] as $i => $k ) $data[] = '"' . trim( $k ) . '":"' . trim( $m[2][$i] ) . '"';
		return "\"$trail\":\n{\n" . implode( ',', $data ) . "\n}\n";
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
