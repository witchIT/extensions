<?php
/**
 * AnnotateRecipes extension - Adds RDFa annotations to articles in recipes categories
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad] for Copycat Recipe Guide (Elance job ID 25658298)
 * @licence GNU General Public Licence 2.0 or later
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

// The measurement units available (regular expressions)
$wgAnnotateRecipesUnits = array(
	"tb?sp\.?",
	"tablespoons?",
	"teaspoons?",
	"cups?",
	"pints?",
	"ounces?",
	"oz\.?",
	"pounds?",
	"lb\.?"
);

define( 'ANNOTATERECIPES_VERSION', '1.0.3, 2011-08-24' );

$wgExtensionFunctions[] = 'wfSetupAnnotateRecipes';
$wgExtensionCredits['other'][] = array(
	'name'        => 'AnnotateRecipes',
	'author'      => '[http://www.mediawiki.org/wiki/User:Nad User:Nad]',
	'description' => 'Adds RDFa annotations to articles in recipes categories',
	'url'         => 'http://www.elance.com/php/collab/main/collab.php?bidid=25658298',
	'version'     => ANNOTATERECIPES_VERSION
);

class AnnotateRecipes {

	function __construct() {
		global $wgHooks;
		$wgHooks['BeforePageDisplay'][] = $this;
	}

	function onBeforePageDisplay( $out, $skin = false ) {
		global $wgTitle;
		$text =& $out->mBodytext;

		// Bail if not in main namespace
		if( $wgTitle->getNamespace() != 0 ) return true;

		// Bail if the article isn't in a recipes category
		$dbr  = &wfGetDB( DB_SLAVE );
		$cl   = $dbr->tableName( 'categorylinks' );
		$id   = $wgTitle->getArticleID();
		if( !$dbr->selectRow( $cl, '0', "cl_from = $id AND cl_to LIKE '%ecipes'" ) ) return true;

		// Annotate list items in sections ending in "ingredients"
		$text = preg_replace_callback(
			"#class=\"mw-headline\"[^>]*>[^<]+ingredients</.+?<ul>.+?</ul>#is",
			array( $this, 'annotateIngredients' ),
			$text
		);

		// Make the first paragraph tag into the recipe summary
		$text = preg_replace(
			"#^(.*?<p)#s",
			"$1 class=\"summary\"",
			$text
		);

		// Annotate the section ending in the word "recipe" as the cooking instructions
		$text = preg_replace(
			"#(class=\"mw-headline\"[^>]*>[^<]+recipe</.+?)<ol>(.+?</ol>)#is",
			"$1<ol class=\"instructions\">\n$2",
			$text
		);

		// Annotate the author
		$text = preg_replace(
			"#(offered by</th>\s*<td[^>]+>)([^>]+)(?=<)#is",
			"$1<span class=\"author\">$2</span>",
			$text
		);

		// Wrap the whole bodytext in recipe annotation
		$recipe = preg_replace( "|how to make( a )?|i", "", $wgTitle->getText() );
		$title = "<h1 class=\"item\" style=\"display:none\"><span class=\"fn\">$recipe</span></h1>";
		$text = "<div class=\"hrecipe\">\n$title\n$text\n</div>";
 
		return true;
	}

	function annotateIngredients( $m ) {
		return preg_replace_callback(
			"#<li>\s*(.+?)\s*</li>#s",
			array( $this, 'annotateIngredient' ),
			$m[0]
		);
	}

	function annotateIngredient( $m ) {
		global $wgAnnotateRecipesUnits;
		$units = join( '|', $wgAnnotateRecipesUnits );
		$li = $m[1];
		$li = preg_replace(
			"#([0-9./]+( [0-9./]+)? )($units)?(.+?)(,|<|\(|$)#s",
			"<span class=\"amount\">$1$3</span><span class=\"name\">$4</span></span>$5",
			$li
		);
		return "<li class=\"ingredient\">$li</li>\n";
	}

}

function wfSetupAnnotateRecipes() {
	global $wgAnnotateRecipes;
	$wgAnnotateRecipes = new AnnotateRecipes();
}


