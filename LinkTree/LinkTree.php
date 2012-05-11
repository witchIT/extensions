<?php
/**
 * LinkTree extension - Adds a #linktree parser functions for creating a bullet tree of links in the page and links in those pages etc
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'LINKTREE_VERSION','1.0.2, 2012-05-11' );

$wgLinkTreeMaxChr = false;
$wgExtensionFunctions[] = 'wfSetupLinkTree';
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'LinkTree',
	'author'         => '[http://www.organicdesign.co.nz/User:Nad Nad]',
	'url'            => 'http://www.organicdesign.co.nz/Extension:LinkTree',
	'descriptionmsg' => 'treeandmenu-desc',
	'version'        => LINKTREE_VERSION,
);

$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['LinkTree'] = $dir . 'LinkTree.i18n.php';
$wgExtensionMessagesFiles['LinkTreeMagic'] = $dir . 'LinkTree.i18n.magic.php';

class LinkTree {

	var $exclusions = array();

	function __construct() {
		global $wgOut, $wgHooks, $wgParser;
		$wgParser->setFunctionHook( 'linktree', array( $this, 'expandLinkTree' ) );
	}

	public function expandLinkTree( &$parser, $limit ) {
		$parser->disableCache();

		// Build exclusions list (if category, add all members to list)
		$exclusions = array();
		$dbr  = &wfGetDB( DB_SLAVE );
		$cl   = $dbr->tableName( 'categorylinks' );
		$args = func_get_args();
		array_shift( $args );
		array_shift( $args );
		foreach( $args as $arg ) {
			$title = Title::newFromText( $arg );
			if( $title->getNamespace() == NS_CATEGORY ) {
				$cat  = $dbr->addQuotes( $title->getDBkey() );
				$res  = $dbr->select( $cl, 'cl_from', "cl_to = $cat" );
				while( $row = $dbr->fetchRow( $res ) ) $exclusions[Title::newFromID( $row[0] )->getPrefixedText()] = 1;
				$dbr->freeResult( $res );
			} else $exclusions[$title->getText()] = 1;
		}
		$this->exclusions = array_keys( $exclusions );

		$tree = $this->linkTree( $parser->getTitle(), $limit );
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
	 * Recursive link tree
	 */
	function linkTree( $title, $limit, $level = 1 ) {
		global $wgLinkTreeMaxChr;
		$tree = '';
		$links = $this->getLinksFrom( $title );
		foreach( $links as $title ) {
			$text = $title->getPrefixedText();
			if( !in_array( $text, $this->exclusions ) ) {
				if( $wgLinkTreeMaxChr && strlen( $text ) > $wgLinkTreeMaxChr ) $text = substr( $text, 0, $wgLinkTreeMaxChr ) . '...';
				$url = $title->getFullURL();
				$tree .= str_repeat( "*", $level ) . "[$url $text]\n";
				if( $level < $limit ) $tree .= $this->linkTree( $title, $limit, $level + 1 );
			}
		}
		return $tree;
	}

	/**
	 * Return a list of titles for links out from the passed title
	 */
	function getLinksFrom( $title ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'page', 'pagelinks' ),
			array( 'pl_namespace', 'pl_title' ),
			array( 'pl_from' => $title->getArticleId() ),
			__METHOD__,
			array(),
			array(
				'page' => array(
					'LEFT JOIN',
					array( 'pl_namespace=page_namespace', 'pl_title=page_title' )
				)
			)
		);
		$links = array();
		foreach( $res as $row ) $links[] = Title::makeTitle( $row->pl_namespace, $row->pl_title );
		return $links;
	}

}

function wfSetupLinkTree() {
	global $wgLinkTree;
	$wgLinkTree = new LinkTree();
}
