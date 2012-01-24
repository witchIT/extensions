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
define( 'BOOKNAVIGATION_VERSION', "1.0.1, 2012-01-23" );

define( 'BOOKNAVIGATION_DEPTH',   1 );
define( 'BOOKNAVIGATION_TITLE',   2 );
define( 'BOOKNAVIGATION_PARENT',  3 );
define( 'BOOKNAVIGATION_NEXT',    4 );
define( 'BOOKNAVIGATION_PREV',    5 );
define( 'BOOKNAVIGATION_CHAPTER', 6 );
define( 'BOOKNAVIGATION_LINK',    7 );
define( 'BOOKNAVIGATION_URL',     8 );

$wgBookNavigationStructureArticle = 'MediaWiki:BookStructure';
$wgBookNavigationPrevNextMagic    = 'PrevNext';
$wgBookNavigationBreadCrumbsMagic = 'BreadCrumbs';
$wgBookNavigationBookTreeMagic    = 'BookTree';

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
	var $mStructure = false;

	// Keep track of ids for multiple trees
	var $treeID = 0;

	function __construct() {
		global $wgOut, $wgRequest, $wgResourceModules, $wgHooks, $wgParser, $wgGroupPermissions,
			$wgBookNavigationPrevNextMagic, $wgBookNavigationBookTreeMagic, $wgBookNavigationBreadCrumbsMagic;

		// Make sure that the AJAX request can always get its data
		if( $wgRequest->getText( 'action', false ) == 'booknavtree' ) $wgGroupPermissions['*']['read'] = true;

		// Create parser-functions
		$wgParser->setFunctionHook( $wgBookNavigationPrevNextMagic,    array( $this, 'expandPrevNext' ),    SFH_NO_HASH );
		$wgParser->setFunctionHook( $wgBookNavigationBreadCrumbsMagic, array( $this, 'expandBreadCrumbs' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( $wgBookNavigationBookTreeMagic,    array( $this, 'expandBookTree' ),    SFH_NO_HASH );
		$wgParser->setFunctionHook( 'booktreescript', array( $this, 'expandBookTreeScript' ) );

		$wgHooks['UnknownAction'][] = $this;

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.booknav'] = array(
			'scripts'       => array( 'booknavigation.js' ),
			'styles'        => array( 'booknavigation.css' ),
			'dependencies'  => array( 'mediawiki.util' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.booknav' );
	}

	/**
	 * Return sidebar BookTree
	 */
	function onUnknownAction( $action, $article ) {
		if( $action == 'booknavtree' ) {
			global $wgOut, $wgTitle, $wgParser, $wgUser;
			$wgOut->disable();
			$opt = ParserOptions::newFromUser( $wgUser );
			print $wgParser->parse( $this->renderTree( $wgTitle, 'sidebar' ), $wgTitle, $opt, true, true )->getText();
		}
		return true;
	}

	/**
	 * Expand the PrevNext parser function
	 */
	function expandPrevNext( &$parser, $param ) {
		global $wgTitle;
		$title = $param ? $param : $wgTitle;
		$info = $this->getPage( $title );

		if( $prev = $info[BOOKNAVIGATION_PREV] ) {
			$i = $this->getPage( $prev );
			$url = $i[BOOKNAVIGATION_URL];
			$prev = "<a href=\"$url\">$prev</a>";
		} else $prev = wfMsg( 'chapter-start' );

		if( $next = $info[BOOKNAVIGATION_NEXT] ) {
			$i = $this->getPage( $next );
			$url = $i[BOOKNAVIGATION_URL];
			$next = "<a href=\"$url\">$next</a>";
		} else $next = wfMsg( 'chapter-end' );

		$html = "|<div class=\"booknav-prev\">$prev</div><div class=\"booknav-next\">$next</div>";
		return array( "<div class=\"booknav-prevnext\">$html</div>", 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Expand the PrevNext parser function
	 */
	function expandBreadCrumbs( &$parser, $param ) {
		global $wgTitle;
		$title = $param ? $param : $wgTitle;
		$html = $this->renderBreadcrumbs( $title );
		return array( $html, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Expand the BookTree parser function
	 */
	function expandBookTree( &$parser, $param ) {
		global $wgTitle;
		$title = $param ? $param : $wgTitle;
		$tree = $this->renderTree( $title );
		return array(
			$tree,
			'found'   => true,
			'nowiki'  => false,
			'noparse' => false,
			'noargs'  => false,
			'isHTML'  => false
		);
	}

	/**
	 * Expand the BookTreeScript parser function
	 * - internal parser-function to add script after the tree that ensures only the selected nodes are visible
	 */
	function expandBookTreeScript( &$parser ) {
		global $wgJsMimeType;

		$params = func_get_args();
		array_shift( $params );
		$tree = array_shift( $params );
		$node = $params[0];

		$script = "tam$tree.closeAll();";
		$script .= "document.getElementById('itam$tree$node').parentNode.lastChild.setAttribute('class','booknav-selected');";
		foreach( $params as $id ) $script .= "tam$tree.openTo($id);";
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
	 * Render the tree structure from the structure defined in the book-nav article
	 */
	function renderTree( $title, $id = false ) {
		global $wgBookNavigationStructureArticle;
		$id = $id ? $id : 'booknav' . $this->treeID++ . 'tree';
		$tree = '';
		$info = $this->getPage( $title );
		$current_chapter = $info[BOOKNAVIGATION_CHAPTER];
		$current_link = $info[BOOKNAVIGATION_LINK];

		// Render each top-level chapter heading
		$structure = $this->getStructure();
		foreach( $structure as $chapter => $pages ) {

			// Render the chapter heading if it's the current chapter or there is no current chapter
			if( $current_chapter == false || $chapter == $current_chapter ) {
				$tree .= "<div class=\"booknav-chapter\">$chapter</div>";
			}

			// If current page is in this chapter, render that chapter's heading and tree only
			if( $chapter == $current_chapter ) {
				$tree .= "<div class=\"booknavtree\">{{#tree:id=$id||\n";
				$node = -1;
				$i = 1;
				foreach( $structure[$chapter] as $page ) {
					$title = $page[BOOKNAVIGATION_TITLE];
					$link = $page[BOOKNAVIGATION_LINK];
					if( $link == $current_link ) $node = $i;
					$tree .= str_repeat( '*', $page[BOOKNAVIGATION_DEPTH] );
					if( $link ) {
						$url = Title::newFromText( $link )->getFullUrl( 'chapter=' . urlencode( $chapter ) );
						$tree .= "[$url $title]\n";
					} else $tree .= "$title\n";
					$i++;
				}
				$tree .= "}}{{#booktreescript:$id|$node}}</div>";
			}
		}

		return $tree;
	}

	/**
	 * Render breadcrumbs for passed title
	 */
	function renderBreadcrumbs( $title ) {
		$links = array();
		do {
			$info = $this->getPage( $title );
			$url = $info[BOOKNAVIGATION_URL];
			$anchor = $info[BOOKNAVIGATION_TITLE];
			$link = "<a href=\"$url\">$anchor</a>";
			array_unshift( $links, $link );
		} while( $title = $info[BOOKNAVIGATION_PARENT] );
		array_unshift( $links, $info[BOOKNAVIGATION_CHAPTER] );
		return '<div class="booknav-breadcrumbs">' . join( ' <span class="booknav-separator">&gt;</span> ', $links ) . '</div>';
	}

	/**
	 * Get an array of the book structure from the structure article content
	 */
	function getStructure() {
		global $wgBookNavigationStructureArticle;
		if( $this->mStructure ) return $this->mStructure;

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
					$info = array(
						BOOKNAVIGATION_TITLE => self::pageName( $m[2][$i] ),
						BOOKNAVIGATION_LINK  => self::pageLink( $m[2][$i] )
					);
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
		return $this->mStructure = $structure;
	}

	/**
	 * Return structure information about the current page
	 */
	function getPage( $title ) {
		if( is_object( $title ) ) $page = $title->getPrefixedText();
		else {
			$page = $title;
			$title = Title::newFromText( $page );
		}

		// What chapter(s) does this page belong to?
		$structure = $this->getStructure();
		$chapters = array();
		foreach( $structure as $chapter => $pages ) {
			foreach( $pages as $info ) {
				if( $info[BOOKNAVIGATION_LINK] == $page ) $chapters[] = $chapter;
			}
		}

		// Bail if none
		if( count( $chapters ) == 0 ) return false;

		// If ambigous (more than one chapter), check query-string for chapter
		if( count( $chapters ) > 1 ) {
			global $wgRequest;
			$chapter = $wgRequest->getText( 'chapter', '' );
			if( !in_array( $chapter, $chapters ) ) return false;
		}

		// if ambiguous and no query-string indicator, we just tak ethe first		
		else $chapter = $chapters[0];

		// Get the page index in the structure array - bail if page not found in chapter
		$i = false;
		foreach( $structure[$chapter] as $k => $info ) {
			if( $info[BOOKNAVIGATION_LINK] == $page ) $i = $k;
		}
		if( $i === false ) return false;

		// Get next and previous page names
		$info = $structure[$chapter][$i];
		if( array_key_exists( $i + 1, $structure[$chapter] ) ) $info[BOOKNAVIGATION_NEXT] = $structure[$chapter][$i + 1][BOOKNAVIGATION_TITLE];
		if( array_key_exists( $i - 1, $structure[$chapter] ) ) $info[BOOKNAVIGATION_PREV] = $structure[$chapter][$i - 1][BOOKNAVIGATION_TITLE];

		// Change parent from an index to a name
		if( array_key_exists( $info[BOOKNAVIGATION_PARENT], $structure[$chapter] ) ) {
			$info[BOOKNAVIGATION_PARENT] = $structure[$chapter][$info[BOOKNAVIGATION_PARENT]][BOOKNAVIGATION_TITLE];
		} else $info[BOOKNAVIGATION_PARENT] = false;

		// Add other missing info to the array
		$info[BOOKNAVIGATION_CHAPTER] = $chapter;
		$info[BOOKNAVIGATION_URL] = $title->getFullUrl( "chapter=$chapter" );

		return $info;
	}

	/**
	 * Extract the text label of a page from passed text which might be: title, [[title]] or [[title|anchor]]
	 */
	static function pageName( $text ) {
		if( preg_match( "#\[\[\s*.+?\s*\|(.+?)\]\]#", $text, $m ) ) return $m[1];
		elseif( preg_match( "#\[\[\s*(.+?)\s*\]\]#", $text, $m ) ) return $m[1];
		return $text;
	}

	/**
	 * Extract the link information of a page from passed text which might be: title, [[title]] or [[title|anchor]]
	 * - returns false or a target article title
	 */
	static function pageLink( $text ) {
		if( preg_match( "#\[\[\s*(.+?)\s*\|.+?\]\]#", $text, $m ) ) return $m[1];
		elseif( preg_match( "#\[\[\s*(.+?)\s*\]\]#", $text, $m ) ) return $m[1];
		return false;
	}
}


/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupBookNavigation() {
	global $wgBookNavigation, $wgTreeViewImages, $wgExtensionAssetsPath;
	$wgBookNavigation = new BookNavigation();

	// Must have TreeAndMenu settings
	if( !defined( 'TREEANDMENU_VERSION' ) )
		die( "The BookNavigation extension requires the <a href=\"http://www.mediawiki.org/wiki/Extension:TreeAndMenu\">TreeAndMenu</a> extension." );
	$img = $wgExtensionAssetsPath . '/' . basename( dirname( __FILE__ ) ) . '/img';
	$wgTreeViewImages['root'] = '';
	$wgTreeViewImages['folder'] = '';
	$wgTreeViewImages['folderOpen'] = '';
	$wgTreeViewImages['node'] = $img . '/bullet.gif';
	$wgTreeViewImages['empty'] = $img . '/empty.gif';
	$wgTreeViewImages['nlPlus'] = $img . '/right-arrow.gif';
	$wgTreeViewImages['nlMinus'] = $img . '/down-arrow.gif';
}

/**
 * Set up magic words for parser-functions
 */
function wfBookNavigationLanguageGetMagic( &$langMagic, $langCode = 0 ) {
	global $wgBookNavigationPrevNextMagic, $wgBookNavigationBookTreeMagic, $wgBookNavigationBreadCrumbsMagic;
	$langMagic[$wgBookNavigationPrevNextMagic]    = array( $langCode, $wgBookNavigationPrevNextMagic );
	$langMagic[$wgBookNavigationBreadCrumbsMagic] = array( $langCode, $wgBookNavigationBreadCrumbsMagic );
	$langMagic[$wgBookNavigationBookTreeMagic]    = array( $langCode, $wgBookNavigationBookTreeMagic );
	$langMagic['booktreescript'] = array( $langCode, 'booktreescript' );
	return true;
}
