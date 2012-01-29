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
define( 'BOOKNAVIGATION_VERSION', "1.0.7, 2012-01-29" );

define( 'BOOKNAVIGATION_DEPTH',   1 );
define( 'BOOKNAVIGATION_TITLE',   2 );
define( 'BOOKNAVIGATION_PARENT',  3 );
define( 'BOOKNAVIGATION_NEXT',    4 );
define( 'BOOKNAVIGATION_PREV',    5 );
define( 'BOOKNAVIGATION_CHAPTER', 6 );
define( 'BOOKNAVIGATION_LINK',    7 );
define( 'BOOKNAVIGATION_URL',     8 );
define( 'BOOKNAVIGATION_INDEX',   9 );

$wgBookNavigationNavLinks         = array( 'Main Page' );
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

		// Create parser-functions
		$wgParser->setFunctionHook( $wgBookNavigationPrevNextMagic,    array( $this, 'expandPrevNext' ),    SFH_NO_HASH );
		$wgParser->setFunctionHook( $wgBookNavigationBreadCrumbsMagic, array( $this, 'expandBreadCrumbs' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( $wgBookNavigationBookTreeMagic,    array( $this, 'expandBookTree' ),    SFH_NO_HASH );
		$wgParser->setFunctionHook( 'booktreescript', array( $this, 'expandBookTreeScript' ) );

		$wgHooks['UnknownAction'][] = $this;
		$wgHooks['ParserAfterTidy'][] = $this;

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.booknav'] = array(
			'scripts'       => array( 'booknavigation.js' ),
			'styles'        => array( 'booknavigation.css' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.booknav' );
	}

	/**
	 * Return information for this page and the structure for debugging
	 */
	function onUnknownAction( $action, $article ) {
		if( $action == 'debug' ) {
			global $wgOut, $wgTitle;
			$wgOut->disable();
			print "<pre>\n\nPAGE INFORMATION:\n\n";
			$c = array();
			foreach( get_defined_constants() as $k => $v ) {
				if( strpos( $k, 'BOOKNAV' ) === 0 && is_numeric( $v ) ) $c[$v] = $k;
			}
			$info = $this->getPage( $wgTitle );
			if( $info === false ) print "\tTitle not in structure";
			elseif( $info === true ) print "\tTitle is a chapter heading";
			else {
				foreach( $info as $k => $v ) {
					print "\t[$c[$k]]    \t$v\n";
				}
				print "\n\n\t[Prev linkable]    \t\t" . $this->prevLinkable( $info );
				print "\n\t[Next linkable]    \t\t" . $this->nextLinkable( $info );
			}
			print "\n\n\n\nSTRUCTURE ARRAY:\n\n";
			print_r( $c );
			print_r( $this->getStructure() );
			print "</pre>";
		}
		return true;
	}

	/**
	 * Render the sidebar tree into a div ready to be positioned after page-load
	 */
	function onParserAfterTidy( &$parser, &$text ) {
		global $wgOut, $wgTitle, $wgUser;
		static $done = false;
		if( $done ) return true;
		$done = true;
		$this->sidebarTreeDone = true;
		$opt = ParserOptions::newFromUser( $wgUser );
		$html = $parser->parse( $this->renderTree( $wgTitle, 'sidebar' ), $wgTitle, $opt, true, true )->getText();
		$wgOut->addHTML( "<div style=\"display:none\"><div id=\"booknav-sidebar\">$html</div></div>" );
		return true;
	}

	/**
	 * Expand the PrevNext parser function
	 */
	function expandPrevNext( &$parser, $param ) {
		global $wgTitle;
		$title = $param ? $param : $wgTitle;
		if( is_object( $title ) ) $title = $title->getPrefixedText();
		$info = $this->getPage( $title );
		if( $info === false ) return wfMsg( 'booknav-notinbook' );

		// Special condition if this page is a chapter page
		if( $info === true ) {
			$prev = wfMsg( 'booknav-chapter-start' );
			$i = $this->getChapter( $title );
			$next = $i[1][0][BOOKNAVIGATION_TITLE];
			$i = $this->getPage( $next );
			$url = $i[BOOKNAVIGATION_URL];
			$next = "<a href=\"$url\">$next</a>";
		}

		// Otherwise this is a normal page in the structure
		else {
			if( $prev = $this->prevLinkable( $info ) ) {
				$i = $this->getPage( $prev );
				$url = $i[BOOKNAVIGATION_URL];
				$prev = "<a href=\"$url\">$prev</a>";
			}

			// At start, render chapter heading
			else {
				$chapter = $info[BOOKNAVIGATION_CHAPTER];
				$anchor = self::pageName( $chapter );
				$page = self::pageName( $chapter );
				$url = Title::newFromText( $page )->getLocalUrl();
				$prev = "<a href=\"$url\">$page</a>";
			}

			if( $next = $this->nextLinkable( $info ) ) {
				$i = $this->getPage( $next );
				$url = $i[BOOKNAVIGATION_URL];
				$next = "<a href=\"$url\">$next</a>";
			} else $next = wfMsg( 'booknav-chapter-end' );
		}

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

		$script = "window.tamOnload_tam$tree.push(function(){ $('#itam$tree$node').parent().children().last().attr('class','booknav-selected');";
		foreach( $params as $node ) {
			//if( $node ) $script .= "tam$tree.openTo($node);";
		}
		$script .= "});";
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
		global $wgBookNavigationStructureArticle, $wgBookNavigationNavLinks;
		$id = $id ? $id : 'booknav' . $this->treeID++ . 'tree';
		$tree = '';
		$info = $this->getPage( $title );

		// Is this page a chapter page?
		if( $info === true ) {
			$i = $this->getChapter( $title );
			$current_chapter = $i[0];
			$current_link = false;

		// Not in the structure at all?
		} elseif( $info === false ) {
			$current_chapter = false;
			$current_link = false;
		}

		// A normal structure item
		else {
			$current_chapter = $info[BOOKNAVIGATION_CHAPTER];
			$current_link = $info[BOOKNAVIGATION_LINK];
		}

		// Render the special nav links preceding the chapter headings in the tree
		foreach( $wgBookNavigationNavLinks as $link ) {
			$tree .= "<div class=\"booknav-chapter booknav-navlink\">[[$link]]</div>";
		}

		// Render each top-level chapter heading
		$structure = $this->getStructure();
		foreach( $structure as $chapter => $pages ) {

			// Render the chapter heading if it's the current chapter or there is no current chapter
			if( $current_chapter == false || $chapter == $current_chapter ) {
				$tree .= "<div class=\"booknav-chapter\">$chapter</div>";
			}

			// If current page is in this chapter (or is this chapter), render that chapter's tree only
			if( $chapter == $current_chapter ) {
				$tree .= "<div class=\"booknavtree\">{{#tree:id=$id||\n";
				$node = -1;
				$prev = '';
				$next = '';
				$i = 1;
				foreach( $structure[$chapter] as $page ) {
					$tree .= str_repeat( '*', $page[BOOKNAVIGATION_DEPTH] );
					$title = $page[BOOKNAVIGATION_TITLE];
					$link = $page[BOOKNAVIGATION_LINK];
					if( $link ) {
						$url = Title::newFromText( $link )->getFullUrl( self::qsChapter( $chapter ) );
						$tree .= "[$url $title]\n";

						// If this is the current page, mark as selected and note prev and next nodes
						if( $link == $current_link ) {
							$node = $i;
							$prev = $this->prevLinkable( $page, true );
							$next = $this->nextLinkable( $page, true );
						}

					} else $tree .= "$title\n";
					$i++;
				}
				$tree .= "}}{{#booktreescript:$id|$node|$prev|$next}}</div>";
			}
		}

		return $tree;
	}

	/**
	 * Render breadcrumbs for passed title
	 */
	function renderBreadcrumbs( $title ) {
		if( is_object( $title ) ) $title = $title->getPrefixedText();

		$info = $this->getPage( $title );
		if( $info === false ) return wfMsg( 'booknav-notinbook' );

		$links = array();
		if( is_array( $info ) ) {
			do {
				$info = $this->getPage( $title );
				$url = $info[BOOKNAVIGATION_URL];
				$anchor = $info[BOOKNAVIGATION_TITLE];
				$link = $url ? "<a href=\"$url\">$anchor</a>" : $anchor;
				array_unshift( $links, $link );
			} while( $title = $info[BOOKNAVIGATION_PARENT] );
			$chapter = $info[BOOKNAVIGATION_CHAPTER];
		} else $chapter = $title;

		$anchor = self::pageName( $chapter );
		$page = self::pageName( $chapter );
		$title = Title::newFromText( $page );
		if( is_object( $title ) ) {
			$url = $title->getLocalUrl(); 
			array_unshift( $links, $url ? "<a href=\"$url\">$anchor</a>" : $anchor );
		} else array_unshift( $links, $anchor );

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
						BOOKNAVIGATION_LINK  => self::pageLink( $m[2][$i] ),
						BOOKNAVIGATION_PREV  => false,
						BOOKNAVIGATION_NEXT  => false,
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
	 * - or return true if page is a chapter
	 * - or false if not in structure at all
	 */
	function getPage( $title ) {
		if( is_object( $title ) ) $page = $title->getPrefixedText();
		else $page = $title;

		// What chapter(s) does this page belong to?
		$structure = $this->getStructure();
		$chapters = array();
		foreach( $structure as $chapter => $pages ) {
			if( $page == self::pageLink( $chapter ) ) return true;
			foreach( $pages as $info ) {
				if( $info[BOOKNAVIGATION_TITLE] == $page ) $chapters[] = $chapter;
			}
		}

		// Bail if none
		if( count( $chapters ) == 0 ) return false;

		// If chapter ambigous, check query-string for chapter or otherwise select first
		if( count( $chapters ) > 1 ) {
			global $wgRequest;
			$chapter = $wgRequest->getText( 'chapter', '' );
			$i = $this->getChapter( $chapter );
			if( $i === false ) return false;
			$chapter = $i[0];
		} else $chapter = $chapters[0];
		$info[BOOKNAVIGATION_CHAPTER] = $chapter;

		// Get the page index in the structure array - bail if page not found in chapter
		$i = false;
		foreach( $structure[$chapter] as $k => $info ) {
			if( $info[BOOKNAVIGATION_TITLE] == $page ) $i = $k;
		}
		if( $i === false ) return false;
		$info = $structure[$chapter][$i];
		$info[BOOKNAVIGATION_INDEX] = $i;
		$info[BOOKNAVIGATION_CHAPTER] = $chapter;

		// Get next and previous page names
		if( array_key_exists( $i + 1, $structure[$chapter] ) ) $info[BOOKNAVIGATION_NEXT] = $structure[$chapter][$i + 1][BOOKNAVIGATION_TITLE];
		if( array_key_exists( $i - 1, $structure[$chapter] ) ) $info[BOOKNAVIGATION_PREV] = $structure[$chapter][$i - 1][BOOKNAVIGATION_TITLE];

		// Change parent from an index to a name
		if( array_key_exists( $info[BOOKNAVIGATION_PARENT], $structure[$chapter] ) ) {
			$info[BOOKNAVIGATION_PARENT] = $structure[$chapter][$info[BOOKNAVIGATION_PARENT]][BOOKNAVIGATION_TITLE];
		} else $info[BOOKNAVIGATION_PARENT] = false;

		// Add the URL if this is a linkable item
		if( !is_object( $title ) ) $title = Title::newFromText( $title );
		$info[BOOKNAVIGATION_URL] = $info[BOOKNAVIGATION_LINK] ? $title->getFullUrl( self::qsChapter( $chapter ) ) : false;

		return $info;
	}

	/**
	 * Return the chapter structure from the chapter array from the passed chapter name
	 */
	function getChapter( $title ) {
		if( is_object( $title ) ) $title = $title->getPrefixedText();
		$i = false;
		foreach( $this->getStructure() as $chapter => $pages ) {
			if( self::pageLink( $title, true ) == self::pageLink( $chapter, true ) ) $i = array( $chapter, $pages );
		}
		return $i;
	}

	/**
	 * Get the previous linkable item to the passed page info structure
	 */
	function prevLinkable( $info, $index = false ) {
		do {
			$prev = $info[BOOKNAVIGATION_PREV];
			if( $prev === false ) return false;
			$info = $this->getPage( $prev );
			$url = $info[BOOKNAVIGATION_URL];
		} while( $url === false );
		return $index ? $info[BOOKNAVIGATION_INDEX] : $info[BOOKNAVIGATION_TITLE];
	}

	/**
	 * Get the next linkable item to the passed page info structure
	 */
	function nextLinkable( $info, $index = false ) {
		do {
			$next = $info[BOOKNAVIGATION_NEXT];
			if( $next === false ) return false;
			$info = $this->getPage( $next );
			$url = $info[BOOKNAVIGATION_URL];
		} while( $url === false );
		return $index ? $info[BOOKNAVIGATION_INDEX] : $info[BOOKNAVIGATION_TITLE];
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
	 * - if $label is true, allow plain links to be returned as a link
	 */
	static function pageLink( $text, $label = false ) {
		if( preg_match( "#\[\[\s*(.+?)\s*\|.+?\]\]#", $text, $m ) ) return $m[1];
		elseif( preg_match( "#\[\[\s*(.+?)\s*\]\]#", $text, $m ) ) return $m[1];
		return $label ? $text : false;
	}

	/**
	 * Create a query-string parameter from the passed chapter
	 */
	static function qsChapter( $chapter ) {
		return 'chapter=' . urlencode( self::pageLink( $chapter ) );
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
