<?php
/**
 * WikiObjects extension
 *
 * Allows special "object-namespaces" which have a corresponding DB table
 * - All the articles have a corresponding row in the table
 * - Each namespace has a class (subclass of WikiObject) used to access the article's "object aspect"
 * - Columns in the row are the object properties
 * - The properties are not part of the article textual content or wiki history
 * - The WikiObject base-class provides common functionality for article-DB synchronisation etc
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley http://www.organicdesign.co.nz/nad
 * @copyright © 2012 Aran Dunkley
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WIKIOBJECT_VERSION','0.0.0, 2012-02-16' );

// The first namespace index for the WikiObject classes
$wgWikiObjectsBaseNamespace = 1725676;

// WikiObject class definitions
$wgWikiObjectsClasses = array(
	'Example' => array(
		'Col1' => 'TEXT',
		'Col2' => 'INT(11)'
	)
);

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'WikiObjects',
	'author'         => '[http://www.organicdesign.co.nz/nad Aran Dunkley]',
	'url'            => 'http://www.organicdesign.co.nz/Extension:WikiObjects',
	'description'    => 'Allows special "object-namespaces" which have a corresponding DB table',
	'version'        => WIKIOBJECT_VERSION,
);

/**
 * The main extension class and also the base-class for WikiObject instances
 */
class WikiObject {

	function __construct() {
		global $wgHooks, $wgWikiObjectsBaseNamespace, $wgWikiObjectsClasses;

		// Add hooks
		$wgHooks['ArticleSaveComplete'][] = $this;
		$wgHooks['ArticleDeleteComplete'][] = $this;

		// Create the namespaces for the WikiObject classes and ensure the DB tables exist
		foreach( $wgWikiObjectsClasses as $name => $properties ) {
		}
	}

	/**
	 * When an article in one of the WikiObject namespaces is created, create a corresponding DB entry
	 */
	function onArticleSaveComplete( &$article, &$user, $text, $summary, $minor, $watch, $section, &$flags, $rev, &$status, $baseRevId ) {
		return true;
	}

	/**
	 * When an article in one of the WikiObject namespaces is deleted, remove the corresponding DB entry
	 */
	function onArticleDeleteComplete( &$article, &$user, $reason, $id ) {
		return true;
	}


}

// Create a wrapper class for each WikiObject type for creation and updating
// - create new - ExampleType::new()
// - create from title - e.g. $ex = ExampleType::new( Title )
