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
		global $wgHooks, $wgParser, $wgBookNavigationPrevNextMagic;

		// Create a parser-function to render login & account creations forms
		$wgParser->setFunctionHook( $wgBookNavigationPrevNextMagic, array( $this, 'expandPrevNext' ), SFH_NO_HASH );
		
		// TODO: add hook to put tree into sidebar

	}

	/**
	 * Expand the PrevNextMagic parser function
	 */
	function expandPrevNext( &$parser ) {
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
	global $wgBookNavigationPrevNextMagic;
	$langMagic[$wgBookNavigationPrevNextMagic] = array( $langCode, $wgBookNavigationPrevNextMagic );
	return true;
}
