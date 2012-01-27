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

define( 'TRAILWIKIMAP_VERSION','2.1.0, 2012-01-27' );

$wgTrailWikiMagic           = "ajaxmap";
$wgTrailWikiIdMagic         = "articleid";
$wgTrailWikiRatingMagic     = "trailwikirating";
$wgTrailWikiRatingTable     = 'cv_ratings_votes';
$wgTrailWikiDifficultyTable = 'tw_difficulty_votes';
$wgTrailWikiRatingCats      = array(
	$wgTrailWikiDifficultyTable => array( 'Unknown difficulty', 'Easy', 'Easy/Moderate', 'Moderate', 'Moderate/Difficult', 'Difficult' ),
	$wgTrailWikiRatingTable     => array( 'Unrated', '1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars' )
);


/**
 * Marker clustering information
 * - title: lat, lon, radius, max-active-zoom
 * - the clustering will not take effect at greater zoom levels than max-active-zoom
 * - clusters with lower max-active-zoom levels take precedence
 */
$wgTrailWikiClusters = array(
	'Test cluster' => array( 46.832167, -121.525918, 1, 8 )
);

$wgExtensionFunctions[] = 'wfSetupTrailWikiMaps';
$wgExtensionCredits['parserhook'][] = array(
	'path'        => __FILE__,
	'name'        => 'TrailWikiMaps',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad Nad]',
	'url'         => 'https://www.elance.com/php/collab/main/collab.php?bidid=28036402',
	'description' => 'Create custom maps using SemanticMaps data over AJAX (for Elance job 28036402)',
	'version'     => TRAILWIKIMAP_VERSION
);

define( 'TRAILWIKIMAP_NAME', 1 );
define( 'TRAILWIKIMAP_OFFSET', 2 );
define( 'TRAILWIKIMAP_LENGTH', 3 );
define( 'TRAILWIKIMAP_DEPTH', 4 );

class TrailWikiMaps {

	var $mapid = 1;
	var $opts = array();

	function __construct() {
		global $wgOut, $wgJsMimeType, $wgExtensionAssetsPath, $wgHooks, $wgParser, $wgResourceModules,
			$wgTrailWikiMagic, $wgTrailWikiRatingMagic;

		// Set up magic words
		$wgHooks['UnknownAction'][]                = $this;
		$wgHooks['ParserGetVariableValueSwitch'][] = $this;
		$wgHooks['MagicWordwgVariableIDs'][]       = $this;
		$wgHooks['LanguageGetMagic'][]             = $this;

		// Set up parser-functions
		$wgParser->setFunctionHook( $wgTrailWikiMagic, array( $this,'expandAjaxMap' ) );
		$wgParser->setFunctionHook( $wgTrailWikiRatingMagic, array( $this,'expandRating' ) );
		$wgParser->setFunctionHook( 'ajaxmapinternal', array( $this,'expandAjaxMapInternal' ) );

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.trailwikimaps'] = array(
			'scripts'       => array( 'trailwikimaps.js' ),
			'styles'        => array( 'trailwikimaps.css' ),
			'dependencies'  => array( 'mediawiki.util' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.trailwikimaps' );
	}

	/**
	 * Return the trail data in JSON format when the ajaxmap action is requested
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut, $wgJsMimeType, $wgTrailWikiClusters;

		// Returns the list of trails in each location in JSON format
		if( $action == 'traillocations' ) {
			$wgOut->disable();
			header( 'Content-Type: application/json' );

			// Send clusters
			$c = '';
			print "{\"clusters\":[\n";
			foreach( $wgTrailWikiClusters as $cluster => $data ) {
				print $c . "[\"$cluster\"," . implode( ',', $data ) . "]";
				$c = ",\n";
			}

			// Send trails
			if( array_key_exists( 'query', $_REQUEST ) ) $query = explode( '!', $_REQUEST['query'] );
			else $query = false;
			$c = '';
			print "\n],\n\"locations\":[\n";
			foreach( self::getTrailLocations( $query ) as $trails ) {
				print $c . "[\"" . implode( '","', $trails ) . "\"]";
				$c = ",\n";
			}
			print "\n]}\n";
		}

		// Returns the rendered HTML of a popup box for the specified trail
		elseif( $action == 'trailinfo' ) {
			global $wgTitle, $wgRequest;
			$wgOut->disable();
			header( 'Content-Type: application/json' );
			if( is_object( $wgTitle ) && array_key_exists( 'title', $_REQUEST ) ) print $this->renderTrailInfo( $wgTitle );
			else {
				if( array_key_exists( 'query', $_REQUEST ) ) $query = explode( '!', $_REQUEST['query'] );
				else $query = false;
				$c = '';
				print "{\n";
				foreach( self::getTrailList( $query ) as $trail => $title ) {
					print $c . $this->renderTrailInfo( $title );
					$c = ',';
				}
				print "}\n";
			}
		}

		// Returns information about the data stored in the ratings table (internal maintenance action)
		elseif( $action == 'updateratingtables' ) {
			global $wgTrailWikiRatingTable, $wgTrailWikiDifficultyTable;
			$wgOut->disable();
			$dbw = wfGetDB( DB_MASTER );
			$dbr = wfGetDB( DB_SLAVE );
			foreach( array( $wgTrailWikiRatingTable, $wgTrailWikiDifficultyTable ) as $table ) {
				$table = $dbr->tableName( $table );
				$res   = $dbr->select( $table, '*', array() );
				while( $row = $dbr->fetchRow( $res ) ) {
					if( !is_numeric( $row['vot_title'] ) ) {
						$title = Title::newFromText( $row['vot_title'] );
						$trail = $title->getPrefixedText();
						$id = $title->getArticleID();
						$row['vot_title'] = $id;
						unset( $row[0], $row[1], $row[2], $row[3] );
						$dbw->insert( $table, $row, __METHOD__, 'IGNORE' );
						print "$trail ($id) (" . $dbw->affectedRows() . ")<br />\n";
					}
				}
				$dbr->freeResult( $res );
			}
		}

		elseif( $action == 'updatetrailcats' ) {
			$wgOut->disable();
			foreach( self::getTrailList() as $trail => $title ) {
				$article = new Article( $title );
				$article->doEdit( $article->fetchContent(), 'Updating catlinks', EDIT_UPDATE );
				print "$trail<br />\n";
			}
		}

		return true;
	}

	/**
	 * Register all magic words
	 */
	function onLanguageGetMagic( &$magicWords, $langCode = 0 ) {
		global $wgTrailWikiMagic, $wgTrailWikiIdMagic, $wgTrailWikiRatingMagic;
		$magicWords[$wgTrailWikiIdMagic]     = array( 0, $wgTrailWikiIdMagic );
		$magicWords[$wgTrailWikiRatingMagic] = array( 0, $wgTrailWikiRatingMagic );
		$magicWords[$wgTrailWikiMagic]       = array( 0, $wgTrailWikiMagic );
		$magicWords['ajaxmapinternal']       = array( 0, 'ajaxmapinternal' );
		return true;
	}

	/**
	 * Register variable magic word for Artcile ID
	 */
	function onMagicWordwgVariableIDs( &$variables ) {
		global $wgTrailWikiIdMagic;
		$variables[] = $wgTrailWikiIdMagic;
		return true;
	}

	/**
	 * Expand Article ID magic word
	 */
	function onParserGetVariableValueSwitch( &$parser, &$cache, &$index, &$ret ) {
		global $wgTrailWikiIdMagic;
		if( $index == $wgTrailWikiIdMagic ) {
			global $wgTitle;
			if( is_object( $wgTitle ) ) $ret = $wgTitle->getArticleID();
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

		// Set default map options for this map ID
		$id = 'ajaxmap' . $this->mapid++;
		$this->opts[$id] = array(
			'type' => '"TERRAIN"',
			'zoom' => 8
		);

		// Update options from parameters specified in parser-function
		foreach( func_get_args() as $opt ) {
			if( !is_object( $opt ) && preg_match( "/^(\w+?)\s*=\s*(.*)$/s", $opt, $m ) ) {
				if( $m[1] == 'width' || $m[1] == 'height' ) {
					if( is_numeric( $m[2] ) ) $m[2] .= 'px';
				}
				if( $m[1] == 'query' ) {
					preg_match_all( "|\|(.+?)\]\]|", $m[2], $n );
					$v = '"' . join( '!', $n[1] ) . '"';
				} else {
					$v = is_numeric( $m[2] ) ? $m[2] : '"' . str_replace( '"', '', $m[2] ) . '"';
				}
				$this->opts[$id][$m[1]] = $v;
			}
		}

		// Return a div element for this map making use of the current Maps extensions for its JS resources
		$wikitext = '<div id="' . $id . '">{{#display_map:0,0}}{{#ajaxmapinternal:' . $id . '}}</div>';
		return array(
			$wikitext,
			'found'   => true,
			'nowiki'  => false,
			'noparse' => false,
			'noargs'  => false,
			'isHTML'  => false
		);
	}

	/**
	 * Expand #trailwikirating parser-function
	 */
	public function expandRating( &$parser, $param ) {
		global $wgTrailWikiRatingTable, $wgTrailWikiDifficultyTable, $wgTrailWikiRatingCats;
		$wikitext = '';
		foreach( array( $wgTrailWikiRatingTable, $wgTrailWikiDifficultyTable ) as $table ) {
			$rating = self::getCommunityValue( $table, $param );
			$cat = $wgTrailWikiRatingCats[$table][number_format( $rating, 0 )];
			$wikitext .= "[[Category:$cat]]";
		}
		return array(
			$wikitext,
			'found'   => true,
			'nowiki'  => false,
			'noparse' => false,
			'noargs'  => false,
			'isHTML'  => false
		);
	}

	/**
	 * Return a rating value from the selected CommunityVoice table
	 */
	static function getCommunityValue( $table, $title ) {
		$dbr = wfGetDB( DB_SLAVE );
		$table = $dbr->tableName( $table );
		$val = $dbr->selectField( $table, 'AVG(vot_rating)', array( 'vot_title' => $title ) );
		return $val;
	}

	public function expandAjaxMapInternal( $parser, $id ) {
		global $wgOut, $wgJsMimeType, $wgExtensionAssetsPath;

		// First time running? declare opts array and make the images path available
		if( $id == 'ajaxmap1' ) {
			$img = $wgExtensionAssetsPath . '/' . basename( dirname( __FILE__ ) ) . '/images/';
			$script = "window.tw_img='$img';window.ajaxmap_opt = {};";
		} else $script = '';

		$script .= "window.ajaxmap_opt.$id = {";
		$c = '';
		foreach( $this->opts[$id] as $k => $v ) {
			$script .= "$c\"$k\":$v";
			$c = ',';
		}
		$script .= "};document.getElementById('$id').innerHTML = '';";

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
	 * Get a list of all trails, or from a query list
	 */
	static function getTrailList( $query = false ) {
		$titles = array();
		if( is_array( $query ) ) {
			foreach( $query as $trail ) if( !empty( $trail ) ) $titles[$trail] = Title::newFromText( $trail );
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
		return $titles;
	}

	/**
	 * Build a list of trails at each location
	 */
	static function getTrailLocations( $query = false ) {
		$list  = array();
		foreach( self::getTrailList( $query ) as $trail => $title ) {
			$data = self::getTrailInfo( $title );
			if( array_key_exists( 'Latitude', $data ) && array_key_exists( 'Longitude', $data ) ) {
				if( is_numeric( $data['Latitude'] ) && is_numeric( $data['Longitude'] ) ) {
					$pos = $data['Latitude'] . ',' . $data['Longitude'];
					if( !array_key_exists( $pos, $list ) ) $list[$pos] = array( $data['Latitude'], $data['Longitude'], $trail );
					else $list[$pos][] = $trail;
				}
			}
		}
		return $list;
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
	 * Return info in JSON format for the passed trail title
	 */
	function renderTrailInfo( $title ) {
		$data = self::getTrailInfo( $title );

		$difficulty = is_numeric( $data['Difficulty'] ) ? number_format( $data['Difficulty'], 0 ) : '';
		$rating     = is_numeric( $data['Rating'] ) ? number_format( $data['Rating'], 0 ) : '';
		$distance   = $data['Distance'] ? $data['Distance'] :'';
		$elevation  = is_numeric( $data['Elevation Gain'] ) ? number_format( $data['Elevation Gain'], 0 ) : '';
		$high       = is_numeric( $data['High Point'] ) ? number_format( $data['High Point'], 0 ) : '';

		// Get a thumbnail image if the image field is set
		$img = '';
		if( !empty( $data['Image Name'] ) ) {
			if( $img = wfLocalFile( $data['Image Name'] ) ) $img = $img->transform( array( 'width' => 140 ) )->getUrl();
		}
		if( preg_match( '|placeholder|i', $img ) ) $img = ''; else $img = str_replace( '/w/images/thumb/', '', $img );

		// Convert the trail uses to a list of images
		$uses = '[';
		$c = '';
		if( !empty( $data['Trail Use'] ) ) {
			global $wgTrailWikiIcons;
			$u = ucwords( preg_replace( "|[^a-z ]|i", "", strtolower( $data['Trail Use'] ) ) );
			foreach( preg_split( "|\s+|", $u ) as $i ) {
				$uses .= "$c\"$i\"";
				$c = ',';
			}
		}
		$uses .= ']';

		if( is_object( $title ) ) $title = $title->getText();
		$info = "\"$title\":{\"u\":$uses";
		if( $distance )   $info .= ",\"d\":\"$distance\"";
		if( $elevation )  $info .= ",\"e\":\"$elevation\"";
		if( $high )       $info .= ",\"h\":\"$high\"";
		if( $difficulty ) $info .= ",\"s\":\"$difficulty\"";
		if( $rating )     $info .= ",\"r\":\"$rating\"";
		if( $img )        $info .= ",\"i\":\"$img\"";
		$info .= "}\n";

		return $info;
	}
}

function wfSetupTrailWikiMaps() {
	global $wgTrailWikiMaps;
	$wgTrailWikiMaps = new TrailWikiMaps();
}

