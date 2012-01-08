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

$wgBookNavigationStructureArticle = 'MediaWiki:BookStructure';
$wgBookNavigationPrevNextMagic = 'PrevNext';
$wgBookNavigationTreeMagic = 'BookTree';

$dir = dirname( __FILE__ );
//$wgExtensionMessagesFiles['BookNavigation'] = "$dir/BookNavigation.i18n.php";
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
	function renderTree() {
		global $wgBookNavigationStructureArticle;
		$tree = '';

		return $tree;
	}

	/**
	 * Render breadcrumbs for current title in structure
	 */
	function renderBreadcrumbs() {
	}

	/**
	 * Get an array of the article structure from the structure article content
	 */
	function getStructure() {
		global $wgBookNavigationStructureArticle;

		// Get the structure-article content
		$title = Title::newFromText( $wgBookNavigationStructureArticle );
		$article = new Article( $title );
		$content = $article->getContent();

		// Extract all the chapters and their following structure data
		$structure = array();
		foreach( preg_split( "|^=|m", $content ) as $chapter ) {
			if( preg_match( "|^=\s*(.+?)\s*==$(.*)|sm", $chapter, $m ) ) {
				preg_match_all( "|^(\*+\s*.+?)\s*$|m", $m[2], $n );
				$structure[$m[1]] = $n[1];
			}
		}

print_r( $structure );
		
		return $structure;
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
