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

define( 'LINKTREE_VERSION','0.0.0, 2012-01-31' );

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

	function __construct() {
		global $wgOut, $wgHooks, $wgParser;
		$wgParser->setFunctionHook( 'linktree', array( $this, 'expandLinkTree' ) );
	}

	public function expandLinkTree( &$parser, $levels ) {
		$parser->disableCache();
		$tree = "*[[foosh]]\n**[[bar]]\n*[[baz]]\n";
		return array(
			$tree,
			'found'   => true,
			'nowiki'  => false,
			'noparse' => false,
			'noargs'  => false,
			'isHTML'  => false
		);
	}

}

function wfSetupLinkTree() {
	global $wgLinkTree;
	$wgLinkTree = new LinkTree();
}
