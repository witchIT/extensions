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

define( 'TRAILWIKIMAP_VERSION','1.0.5, 2012-01-16' );
define( 'TRAILWIKIMAP_NAME', 1 );
define( 'TRAILWIKIMAP_OFFSET', 2 );
define( 'TRAILWIKIMAP_LENGTH', 3 );
define( 'TRAILWIKIMAP_DEPTH', 4 );

// Note - these images should really be made into consistent naming such as "icon-dog.png"
//        that way a mapping from name to image wouldn't be needed,
//        and the sub-templates in Template:Infobox Trail wouldn't be needed either
$wgTrailWikiIcons = array(
	'dog' => 'Dog.gif',
	'tent' => 'Tent.gif',
	'hike' => 'Hike.png',
	'bike' => 'Bike.gif',
	'walk' => 'Walk.gif',
	'horse' => 'Horse.png',
	'skiing' => 'Cross_Country_Skiing.gif',
	'snowshoe' => 'Snowshoeing.jpg',
	'motorbike' => 'Motorbike.gif',
	'wheelchair' => 'Wheelchair.png'
);

$wgTrailWikiRatingTable = 'cv_ratings_votes';
$wgTrailWikiDifficultyTable = 'tw_difficulty_votes';

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

	var $opts = array(
		'"type":"TERRAIN"',
		'"zoom":8'
	);

	function __construct() {
		global $wgOut, $wgHooks, $wgParser, $wgResourceModules, $wgTrailWikiMagic;

		$wgHooks['UnknownAction'][] = $this;

		$wgParser->setFunctionHook( $wgTrailWikiMagic, array( $this,'expandAjaxMap' ) );
		$wgParser->setFunctionHook( 'ajaxmapinternal', array( $this,'expandAjaxMapInternal' ) );

		$wgResourceModules['ext.trailwikimaps'] = array(
			'scripts' => array( 'trailwikimaps.js' ),
			'styles' => array( 'trailwikimaps.css' ),
			'dependencies' => array( 'mediawiki.util' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.trailwikimaps' );
	}

	/**
	 * Return the trail data in JSON format when the ajaxmap action is requested
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut, $wgJsMimeType;

		// Update the information for the specified ISBN (or oldest book in the wiki if none supplied)
		// - if the book doesn't exist and the "create" query-string item is set, then create the book article
		if( $action == 'traillocations' ) {
			global $wgRequest;
			$wgOut->disable();
			header( 'Content-Type: application/json' );
			$comma = '';
			print "{\n";
			foreach( self::getTrailLocations( $wgRequest->getText( 'query', false ) ) as $pos => $trails ) {
				print "$comma\"$pos\":[\"" . implode( '","', $trails ) . "\"]\n";
				$comma = ',';
			}
			print "}\n";
		}

		if( $action == 'trailinfo' ) {
			$wgOut->disable();
			global $wgTitle;
			$data = self::getTrailInfo( $wgTitle );

			// Convert the trail uses to a list of images
			$icons = '';
			if( !empty( $data['Trail Use'] ) ) {
				global $wgTrailWikiIcons;
				$uses = preg_replace( "|[^a-z ]|", "", strtolower( $data['Trail Use'] ) );
				foreach( preg_split( "|\s+|", $uses ) as $i ) {
					if( array_key_exists( $i, $wgTrailWikiIcons ) ) {
						if( $icon = wfLocalFile( $wgTrailWikiIcons[$i] ) ) {
							$icon = $icon->transform( array( 'width' => 20 ) )->toHtml();
							$icons .= "<span class=\"ajaxmap-info-icon\">$icon</span>";
						}
					}
				}
			}

			$unknown    = '<i>unknown</i>';
			$difficulty = is_numeric( $data['Difficulty'] ) ? number_format( $data['Difficulty'], 2 ) : $unknown;
			$rating     = is_numeric( $data['Rating'] ) ? number_format( $data['Rating'], 2 ) : $unknown;
			$distance   = ( $data['Distance'] != '*' ) ? $data['Distance'] . ' Miles' : $unknown;
			$elevation  = is_numeric( $data['Elevation Gain'] ) ? number_format( $data['Elevation Gain'], 0 ) . ' Feet' : $unknown;
			$high       = is_numeric( $data['High Point'] ) ? number_format( $data['High Point'], 0 ) . ' Feet' : $unknown;

			// Render the info
			$info = "<b>Distance: </b>$distance<br />";
			$info .= "<b>Difficulty: </b>$difficulty<br />";
			$info .= "<b>Rating: </b>$rating<br />";
			$info .= "<b>Elevation Gain: </b>$elevation<br />";
			$info .= "<b>High Point: </b><i>$high</i><br />";
			$info .= "<b>Trail Uses: $icons<br />";

			// Get a thumbnail image if the image field is set
			$img = '';
			if( !empty( $data['Image Name'] ) ) {
				if( $img = wfLocalFile( $data['Image Name'] ) ) $img = $img->transform( array( 'width' => 140 ) )->toHtml();
			}

			// Return the data in a table
			print "<table><tr><td>$info</td><th>$img</th></tr></table>";
		}

		return true;
	}


	/**
	 * Expand #ajaxmap parser-functions
	 * - pretends to render a map from Extension:Maps to load the necessary resources
	 * - adds our internal parser-function to remove the dummy map and to define our map options in script
	 */
	public function expandAjaxMap() {
		global $wgOut, $wgJsMimeType;
		foreach( func_get_args() as $opt ) {
			if( !is_object( $opt ) && preg_match( "/^(\w+?)\s*=\s*(.*)$/s", $opt, $m ) ) {
				if( $m[1] == 'query' ) {
					preg_match_all( "|>(.+?)</a>|", $m[2], $n );
					$v = '"' . join( '!', $n[1] ) . '"';
				} else {
					$v = is_numeric( $m[2] ) ? $m[2] : '"' . str_replace( '"', '', $m[2] ) . '"';
				}
				$this->opts[] = "\"$m[1]\":$v";
			}
		}
		return array(
			'<div id="ajaxmap">{{#display_map:0,0}}{{#ajaxmapinternal:}}</div>',
			'found'   => true,
			'nowiki'  => false,
			'noparse' => false,
			'noargs'  => false,
			'isHTML'  => false
		);
	}

	public function expandAjaxMapInternal() {
		global $wgOut, $wgJsMimeType;
		$script = "window.ajaxmap_opt = {" . implode( ',', $this->opts ) . "};";
		$script .= "document.getElementById('ajaxmap').innerHTML = '';";
		return array(
			"<script type=\"$wgJsMimeType\">$script</script>",
			'found'   => true,
			'nowiki'  => false,
			'noparse' => true,
			'noargs'  => false,
			'isHTML'  => true
		);
	}

	/**
	 * Return array of args from the trail infobox for passed trail
	 */
	static function getTrailInfo( $title ) {
		global $wgTrailWikiRatingTable, $wgTrailWikiDifficultyTable;
		$article = new Article( $title );
		$trail = $title->getText();
		preg_match( "|\{\{Infobox Trail(.+?)^\}\}|sm", $article->fetchContent(), $m );
		$template = preg_replace( "/(?<=\S)( +\| )/s", "\n$1", $m[1] ); // fix malformed template syntax
		preg_match_all( "|^\s*\|\s*(.+?)\s*= *(.*?) *(?=^\s*[\|\}])|sm", $template, $m );
		$data = array(
			'Rating' => self::getCommunityValue( $wgTrailWikiRatingTable, $trail ),
			'Difficulty' => self::getCommunityValue( $wgTrailWikiDifficultyTable, $trail )
		);
		foreach( $m[1] as $i => $k ) {
			$k = trim( $k );
			$v = trim( $m[2][$i] );
			if( $k == 'Elevation Gain' || $k == 'High Point' ) $v = str_replace( ',', '', $v );
			$data[$k] = $v;
		}
		return $data;
	}

	/**
	 * Return a 2dp formatted rating from the selected CommunityVoice table
	 */
	static function getCommunityValue( $table, $title ) {
		$dbr = wfGetDB( DB_SLAVE );
		$table = $dbr->tableName( $table );
		$val = $dbr->selectField( $table, 'AVG(vot_rating)', array( 'vot_title' => $title ) );
		return $val;
	}

	/**
	 * Build a list of trails at each location
	 */
	static function getTrailLocations( $query = false ) {

		// Get all the trail articles that will be involved
		$titles = array();
		if( $query ) {
			foreach( explode( '!', $query ) as $trail ) $titles[$trail] = Title::newFromText( $trail );
		} else {			
			$dbr   = &wfGetDB( DB_SLAVE );
			$tmpl  = $dbr->addQuotes( Title::newFromText( 'Infobox Trail' )->getDBkey() );
			$table = $dbr->tableName( 'templatelinks' );
			$res   = $dbr->select( $table, 'tl_from', "tl_namespace = 10 AND tl_title = $tmpl" );
			while( $row = $dbr->fetchRow( $res ) ) {
				$title = Title::newFromId( $row[0] );
				$trail = $title->getText();
				$titles[$trail] = $title;
			}
			$dbr->freeResult( $res );
		}

		// Build the location list
		$list  = array();
		foreach( $titles as $trail => $title ) {
			$data = self::getTrailInfo( $title );
			if( array_key_exists( 'Latitude', $data ) && array_key_exists( 'Longitude', $data ) ) {
				if( is_numeric( $data['Latitude'] ) && is_numeric( $data['Longitude'] ) ) {
					$pos = $data['Latitude'] . ',' . $data['Longitude'];
					if( !array_key_exists( $pos, $list ) ) $list[$pos] = array( $trail );
					else $list[$pos][] = $trail;
				}
			}
		}

		return $list;
	}

}

function wfSetupTrailWikiMaps() {
	global $wgTrailWikiMaps;
	$wgTrailWikiMaps = new TrailWikiMaps();
}

function wfTrailWikiMapsLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $wgTrailWikiMagic;
	$magicWords[$wgTrailWikiMagic] = array( $langCode, $wgTrailWikiMagic );
	$magicWords['ajaxmapinternal'] = array( $langCode, 'ajaxmapinternal' );
	return true;
}
