<?php
/**
 * BookNavigation extension - creates a treeview, breadcrumbs and prev/next links from book-structure article
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'BOOKNAVIGATION_VERSION', "0.0.1, 2012-01-07" );

define( 'BOOKNAVIGATION_DEPTH',  1 );
define( 'BOOKNAVIGATION_TITLE',  2 );
define( 'BOOKNAVIGATION_PARENT', 3 );
define( 'BOOKNAVIGATION_NEXT',   4 );
define( 'BOOKNAVIGATION_PREV',   5 );

$wgBookNavigationStructureArticle = 'MediaWiki:BookStructure';
$wgBookNavigationPrevNextMagic = 'PrevNext';
$wgBookNavigationTreeMagic = 'BookTree';

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['BookNavigation'] = "$dir/BookNavigation.i18n.php";
$wgExtensionFunctions[] = 'wfSetupBookNavigation';
$wgHooks['LanguageGetMagic'][] = 'wfBookNavigationLanguageGetMagic';

$wgExtensionCredits['other'][] = array(
	'name'        => "BookNavigation",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Creates a treeview, breadcrumbs and prev/next links from book-structure article (for Elance job 27932975)",
	'url'         => "https://www.elance.com/php/collab/main/collab.php?bidid=27932975",
	'version'     => BOOKNAVIGATION_VERSION
);

/**
 * Main BookNavigation class definition
 */
class BookNavigation {

	// Cache the structure because it's calculation is expensive
	var $structure = false;

	function __construct() {
		global $wgHooks, $wgParser, $wgBookNavigationPrevNextMagic, $wgBookNavigationBookTreeMagic;

		// Create parser-functions
		$wgParser->setFunctionHook( $wgBookNavigationPrevNextMagic, array( $this, 'expandPrevNext' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( $wgBookNavigationBookTreeMagic, array( $this, 'expandBookTree' ), SFH_NO_HASH );
		
		// TODO: add hook to put tree into sidebar
		$this->getStructure();

	}

	/**
	 * Expand the PrevNext parser function
	 */
	function expandPrevNext( &$parser ) {
		$title = $parser->getTitle();
		$info = $this->getPageInfo( $title );
		return array( '< prev | next >', 'isHTML' => true, 'noparse' => true);
	}

	/**
	 * Expand the BookTree parser function
	 */
	function expandBookTree( &$parser ) {
		return array( '< prev | next >', 'isHTML' => true, 'noparse' => true);
	}

	/**
	 * Render the tree structure from the structure defined in the book-nav article
	 */
	function renderTree( $title ) {
		global $wgBookNavigationStructureArticle;
		$tree = '';

		$structure = $this->getStructure();

		// JS to open only the selected node
		// $(function() {
		//		tamtesttree.closeAll();
		//		tamtesttree.openTo(13);
		// });

		return $tree;
	}

	/**
	 * Render breadcrumbs for current title in structure
	 */
	function renderBreadcrumbs( $title ) {
		$links = array();
		do {
			$info = $this->getPageInfo( $title );
			array_unshift( $links, $info['link'] );
		} while( $title = $info['parent'] );
		return '<div class="booknav-breadcrumbs">' . join( ' > ', $links ) . '</div>';
	}

	/**
	 * Get an array of the book structure from the structure article content
	 */
	function getStructure() {
		global $wgBookNavigationStructureArticle;
		if( $this->structure ) return $this->structure;

		// Get the structure-article content
		$title = Title::newFromText( $wgBookNavigationStructureArticle );
		$article = new Article( $title );
		$content = $article->fetchContent();

		// Extract all the chapters and their page structure
		$structure = array();
		foreach( preg_split( "|^=|m", $content ) as $chapter ) {
			if( preg_match( "|^=\s*(.+?)\s*==$(.*)|sm", $chapter, $m ) ) {
				$chapter = $m[1];
				preg_match_all( "|^(\*+)\s*(.+?)\s*$|m", $m[2], $m );
				$lastdepth = 0;
				$pages = array();
				foreach( $m[1] as $i => $depth ) {
					$info = array( BOOKNAVIGATION_TITLE => $m[2][$i] );
					$depth = $info[BOOKNAVIGATION_DEPTH] = strlen( $depth );
					if( $lastdepth ) {
						if( $depth == $lastdepth ) $info[BOOKNAVIGATION_PARENT] = $pages[$i - 1][BOOKNAVIGATION_PARENT];
						elseif( $depth > $lastdepth ) $info[BOOKNAVIGATION_PARENT] = $i - 1;
						else {
							do { $i--; } while( $i >= 0 && $pages[$i][BOOKNAVIGATION_DEPTH] > $depth );
							$info[BOOKNAVIGATION_PARENT] = $pages[$i][BOOKNAVIGATION_PARENT];
						}
					} else $info[BOOKNAVIGATION_PARENT] = -1;
					$pages[] = $info;
					$lastdepth = $depth;
				}
				$structure[$chapter] = $pages;
			}
		}
		return $this->structure = $structure;
	}

	/**
	 * Return structure information about the current page
	 */
	function getPage( $title ) {
		global $wgRequest;
		if( is_object( $title ) ) $page = $title->getPrefixedText();
		else {
			$page = $title;
			$title = Title::newFromText( $page );

		}
		// What chapter(s) does this page belong to?
		$structure = $this->getStructure();
		$chapters = array();
		foreach( $structure as $chapter => $pages ) {
			foreach( $pages as $page ) {
				if( $page[BOOKNAVIGATION_TITLE] == $page ) $chapters[] = $chapter;
			}
		}

		// Bail if none
		if( count( $chapters ) == 0 ) return false;

		// If ambigous (more than one chapter), check query-string for chapter
		if( count( $chapters ) > 1 ) {
			$chapter = $wgRequest->getText( 'chapter', '' );
			if( !in_array( $chapter, $chapters ) ) return false;
			$url = $title->getLocalUrl( "chapter=$chapter" );
		} else {
			$chapter = $chapters[0];
			$url = $title->getLocalUrl();
		}

		// Get the page index in the structure array - bail if page not found in chapter
		$i = array_search( $page, $structure[$chapter] );
		if( $i === false ) ) return false;

		// Get next and previous page names
		$info = $structure[$chapter][$i];
		if( array_key_exists( $i + 1, $structure[$chapter] ) ) $info[BOOKNAVIGATION_NEXT] = $structure[$chapter][$i + 1][BOOKNAVIGATION_TITLE];
		if( array_key_exists( $i - 1, $structure[$chapter] ) ) $info[BOOKNAVIGATION_PREV] = $structure[$chapter][$i - 1][BOOKNAVIGATION_TITLE];

		// Change parent from an index to a name
		if( array_key_exists( $info[BOOKNAVIGATION_PARENT], $structure[$chapter] ) ) {
			$info[BOOKNAVIGATION_PARENT] = $structure[$chapter][$info[BOOKNAVIGATION_PARENT]][BOOKNAVIGATION_TITLE];
		} else $info[BOOKNAVIGATION_PARENT] = false;

		return $info;
	}
}


/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupBookNavigation() {
	global $wgBookNavigation;
	if( !defined( 'TREEANDMENU_VERSION' ) )
		die( "The BookNavigation extension requires the <a href=\"http://www.mediawiki.org/wiki/Extension:TreeAndMenu\">TreeAndMenu</a> extension." );
	$wgBookNavigation = new BookNavigation();
	print_r( $wgBookNavigation->getStructure() );
}

/**
 * Set up magic words for parser-functions
 */
function wfBookNavigationLanguageGetMagic( &$langMagic, $langCode = 0 ) {
	global $wgBookNavigationPrevNextMagic, $wgBookNavigationBookTreeMagic;
	$langMagic[$wgBookNavigationPrevNextMagic] = array( $langCode, $wgBookNavigationPrevNextMagic );
	$langMagic[$wgBookNavigationBookTreeMagic] = array( $langCode, $wgBookNavigationBookTreeMagic );
	return true;
}
