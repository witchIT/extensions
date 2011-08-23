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

define( 'ANNOTATERECIPES_VERSION', '1.0.0, 2011-08-23' );

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

		// Bail if not recipe section
		#if( !preg_match( "|Recipe|", $text ) ) return true;

		// Bail if the article isn't in a recipes category
		$dbr  = &wfGetDB( DB_SLAVE );
		$cl   = $dbr->tableName( 'categorylinks' );
		$id   = $wgTitle->getArticleID();
		if( !$dbr->selectRow( $cl, '0', "cl_from = $id AND cl_to LIKE '%ecipes'" ) ) return true;

		// Annotate list items in sections ending in "ingredients"
		$text = preg_replace_callback(
			"#(class=\"mw-headline\"[^>]*>[^<]+ingredients</.+?<ul>)(.+?)(</ul>)#is",
			array( $this, 'annotateIngredients' ),
			$text
		);

		// Annotate the section ending in the word "recipe" as the cooking instructions
		$text = preg_replace(
			"#(class=\"mw-headline\"[^>]*>[^<]+recipe</.+?)(<ol>.+?</ol>)#is",
			"$1<div property=\"v:instructions\">\n$2\n</div>",
			$text
		);

		// Wrap the whole bodytext in recipe annotation
		$recipe = preg_replace( "|how to make( a )?|i", "", $wgTitle->getText() );
		$xmlns = "<div xmlns:v=\"http://rdf.data-vocabulary.org/#\" typeof=\"v:Recipe\">";
		$title = "<h1 style=\"display:none\" property=\"v:name\">$recipe</h1>";
		$text = "$xmlns\n$title\n$text\n</div>";

		return true;
	}

	function annotateIngredients( $m ) {
		$ul = preg_replace_callback( "#<li>\s*(.+?)\s*</li>#s", array( $this, 'annotateIngredient' ), $m[2] );
		return "$m[1]$ul$m[3]";
	}

	function annotateIngredient( $m ) {
		$li = $m[1];
		$li = preg_replace(
			"#([0-9./]+( [0-9./]+)? )(tb?sp\.?|tablespoons?|teaspoons?|cups?|pints?|ounces?|oz\.?|pounds?|lb\.?)?(.+?)(,|<|\(|$)#s",
			"<span rel=\"v:ingredient\"><span typeof=\"v:Ingredient\"><span property=\"v:amount\">$1$3</span><span property=\"v:name\">$4</span></span></span>$5",
			$li
		);
		return "<li>$li</li>\n";
	}

}

function wfSetupAnnotateRecipes() {
	global $wgAnnotateRecipes;
	$wgAnnotateRecipes = new AnnotateRecipes();
}


