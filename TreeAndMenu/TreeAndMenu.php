<?php
/**
 * TreeAndMenu extension - Adds #tree and #menu parser functions for collapsible treeview's and dropdown menus
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

define( 'TREEANDMENU_VERSION','3.0.0, 2012-09-27' );

// Tree defaults
if( !isset( $wgTreeViewImages ) || !is_array( $wgTreeViewImages ) ) $wgTreeViewImages = array();
$wgTreeViewShowLines = false;  // whether to render the dotted lines joining nodes

$wgExtensionFunctions[] = 'wfSetupTreeAndMenu';
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'TreeAndMenu',
	'author'         => array( '[http://www.organicdesign.co.nz/User:Nad Nad]', '[http://www.organicdesign.co.nz/User:Sven Sven]' ),
	'url'            => 'http://www.mediawiki.org/wiki/Extension:TreeAndMenu',
	'descriptionmsg' => 'treeandmenu-desc',
	'version'        => TREEANDMENU_VERSION,
);

$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['TreeAndMenu'] = $dir . 'TreeAndMenu.i18n.php';
$wgExtensionMessagesFiles['TreeAndMenuMagic'] = $dir . 'TreeAndMenu.i18n.magic.php';

// @todo FIXME: Move classes out of init file.

class TreeAndMenu {

	var $version    = TREEANDMENU_VERSION;
	var $uniq       = '';      // uniq part of all tree id's
	var $uniqname   = 'tam';   // input name for uniqid
	var $id         = '';      // id for specific tree
	var $baseDir    = '';      // internal absolute path to treeview directory
	var $baseUrl    = '';      // external URL to treeview directory (relative to domain)
	var $images     = '';      // internal JS to update dTree images
	var $useLines   = true;    // internal variable determining whether to render connector lines
	var $args       = array(); // args for each tree

	/**
	 * Constructor
	 */
	function __construct() {
		global $wgOut, $wgHooks, $wgParser, $wgJsMimeType, $wgExtensionAssetsPath, $wgResourceModules, $wgTreeViewImages, $wgTreeViewShowLines;

		// Add hooks
		$wgParser->setFunctionHook( 'tree', array( $this, 'expandTreeAndMenu' ) );

		// Update general tree paths and properties
		$this->baseDir  = dirname( __FILE__ );
		$this->baseUrl  = $wgExtensionAssetsPath . '/' . basename( dirname( __FILE__ ) );
		$this->useLines = $wgTreeViewShowLines ? 'true' : 'false';
		$this->uniq     = uniqid( $this->uniqname );

		// Convert image titles to file paths and store as JS to update dTree
		foreach( $wgTreeViewImages as $k => $v ) {
			$image = wfLocalFile( $v );
			$v = ( is_object( $image ) && $image->exists() ) ? $image->getURL() : $wgTreeViewImages[$k];
			$this->images .= "tree.icon['$k'] = '$v';";
		}

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.treeandmenu'] = array(
			'styles'        => array( 'treeandmenu.css' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => $this->baseUrl,
		);
		$wgOut->addModules( 'ext.treeandmenu' );
		$wgOut->addHeadItem( 'treeBaseUrl', "<script type=\"$wgJsMimeType\">window.tamBaseUrl='{$this->baseUrl}';</script>\n" );
		$wgOut->addHeadItem( 'dTree', "<script type=\"$wgJsMimeType\" src=\"{$this->baseUrl}/dtree.js\"></script>\n" );
	}

	/**
	 * Expand either kind of parser-function (reformats tree rows for matching later) and store args
	 */
	public function expandTreeAndMenu( &$parser ) {
		global $wgUser;
		$args = func_get_args();
		array_shift( $args );
	
		// Store args for this tree for later use
		$text = '';
		foreach( $args as $arg ) if ( preg_match( '/^(\\w+?)\\s*=\\s*(.+)$/s', $arg, $m ) ) $args[$m[1]] = $m[2]; else $text = $arg;

		// If root, parse as wikitext
		if( isset( $args['root'] ) ) {
			$p = clone $parser;
			$o = clone $parser->mOptions;
			$o->mTidy = $o->mEnableLimitReport = false;
			$html = $p->parse( $args['root'], $parser->mTitle, $o, false, true )->getText();
			$args['root'] = addslashes( $html );
		}

		// Create a unique id for this tree or use id supplied in args and store args wrt id
		$this->id = isset($args['id']) ? $args['id'] : uniqid( '' ) .'t';
		$this->args[$this->id] = $args;

		// Reformat tree rows
		$text = preg_replace( '/(?<=\\*)\\s*\\[\\[Image:(.+?)\\]\\]/', "{$this->uniq}3$1{$this->uniq}4", $text );
		$text = preg_replace_callback( '/^(\\*+)(.*?)$/m', array( $this, 'formatRow' ), $text );

		// Parse the structure
		$psr = new Parser;
		$opt = ParserOptions::newFromUser( $wgUser );
		$html = $psr->parse( $text, $parser->mTitle, $opt, false, true )->getText();

		// Do the final rendering
		$html = $this->renderTreeAndMenu( $html );

		// Parser adds <p>'s all over the place :-(
		$html = preg_replace( "|<[/]?p>|m", "", $html );

file_put_contents( '/var/www/wikis/od/files/test.txt', $html );

		return array( $html, 'isHTML' => true, 'noparse' => true );
	}


	/**
	 * Reformat tree bullet structure recording row, depth and id in a format which is not altered by wiki-parsing
	 * - format is: 1{uniq}-{id}-{depth}-{item}-2{uniq}
	 * - sequences of this format will be matched in ParserAfterTidy and converted into dTree JavaScript
	 * - NOTE: we can't encode a unique row-id because if the same tree instranscluded twice a cached version
	 *         may be used (even if parser-cache disabled) this also means that tree id's may be repeated
	 */
	private function formatRow( $m ) {
		return "~x7f1{$this->uniq}~x7f{$this->id}~x7f" . ( strlen( $m[1] )-1 ) . "~x7f$m[2]~x7f2{$this->uniq}";
	}


	/**
	 * Called after parser has finished (ParserAfterTidy) so all transcluded parts can be assembled into final trees
	 */
	public function renderTreeAndMenu( $text ) {
		global $wgJsMimeType, $wgOut;
		$u = $this->uniq;


		// Extract all the formatted tree rows in the page and if any, replace with dTree JavaScript
		if( preg_match_all( "/~x7f1$u~x7f(.+?)~x7f([0-9]+)~x7f({$u}3(.+?){$u}4)?(.*?)(?=~x7f[12]$u)/", $text, $matches, PREG_SET_ORDER ) ) {

			// PASS-1: build $rows array containing depth, and tree start/end information
			$subs   = array( 1 => array() );
			$rows   = array();
			$depths = array( '' => 0 ); // depth of each tree root
			$rootId = '';               // the id of the current root-tree (used as tree id in PASS2)
			$lastId = '';
			$lastDepth = 0;
			foreach( $matches as $match ) {
				list( , $id, $depth,, $icon, $item ) = $match;
				$start = false;
				if( $id != $lastId ) {
					if( !isset( $depths[$id] ) ) $depths[$id] = $depths[$lastId]+$lastDepth;
					if( $start = $rootId != $id && !in_array( $id, $subs[1] ) ) $depths[$rootId = $id] = 0;
				}
				if( $item ) $rows[] = array( $rootId, $depth + $depths[$id], $icon, addslashes( $item ), $start );
				$lastId    = $id;
				$lastDepth = $depth;
			}

			// PASS-2: build the JavaScript and replace into $text
			$parents   = array(); // parent node for each depth
			$parity    = array(); // keep track of odd/even rows for each depth
			$node      = 0;
			$last      = -1;
			$nodes     = '';
			$opennodes = array();
			foreach( $rows as $i => $info ) {
				$node++;
				list( $id, $depth, $icon, $item, $start ) = $info;
				$objid = $this->uniqname . preg_replace( '/\W/', '', $id );
				$args  = $this->args[$id];
				$end   = $i == count( $rows )-1 || $rows[$i+1][4];
				if( !isset( $args['root'] ) ) $args['root'] = ''; // tmp - need to handle rootless trees
				$openlevels = isset( $args['openlevels'] ) ? $args['openlevels']+1 : 0;
				if( $start ) $node = 1;

				// Append node script for this row
				if( $depth > $last ) $parents[$depth] = $node-1;
				$parent = $parents[$depth];
				$nodes .= "$objid.add($node, $parent, '$item');\n";
				if( $depth > 0 && $openlevels > $depth ) $opennodes[$parent] = true;
				$last = $depth;

				// Last row of current root, surround nodes dtree or menu script and div etc
				if( $end ) {
					$class = isset( $args['class'] ) ? $args['class'] : "dtree";
					$add = isset( $args['root'] ) ? "tree.add(0,-1,'" . $args['root'] . "');" : '';
					$top = $bottom = $root = $opennodesjs = '';
					foreach( array_keys( $opennodes ) as $i ) $opennodesjs .= "$objid.o($i);";
					foreach( $args as $arg => $pos )
						if( ( $pos == 'top' || $pos == 'bottom' || $pos == 'root' ) && ( $arg == 'open' || $arg == 'close' ) )
							$$pos .= "<a href=\"javascript: $objid.{$arg}All();\">&#160;{$arg} all</a>&#160;";
					if( $top ) $top = "<p>&#160;$top</p>";
					if( $bottom ) $bottom = "<p>&#160;$bottom</p>";

					// Define the script to build this tree
					$script = "// TreeAndMenu-{$this->version}\ntree = new dTree('$objid');
						for (i in tree.icon) tree.icon[i] = '{$this->baseUrl}/'+tree.icon[i];{$this->images}
						tree.config.useLines = {$this->useLines};
						$add
						$objid = tree;
						$nodes
						document.getElementById('$id').innerHTML = $objid.toString();
						$opennodesjs
						for(i in window.tamOnload_$objid) { window.tamOnload_{$objid}[i](); }";
					$html = "<script type=\"$wgJsMimeType\">$script</script>";
					$html .= "$top<div class='$class' id='$id'></div>$bottom";
					$html .= "<script type=\"$wgJsMimeType\">window.tamOnload_$objid=[]</script>";
					$text  = preg_replace( "/~x7f1$u~x7f$id~x7f.+?$/m", $html, $text, 1 ); // replace first occurrence of this trees root-id
					$nodes = '';
					$last  = -1;
				}
			}
		}

		// Remove all unreplaced row information
		$html = preg_replace( "/~x7f1$u~x7f.+?[\\r\\n]+/m", '', $text );

		return $html;
	}
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupTreeAndMenu() {
	global $wgTreeAndMenu;
	$wgTreeAndMenu = new TreeAndMenu();
}
