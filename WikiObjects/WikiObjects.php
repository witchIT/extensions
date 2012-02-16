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

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'WikiObjects',
	'author'         => '[http://www.organicdesign.co.nz/nad Aran Dunkley]',
	'url'            => 'http://www.organicdesign.co.nz/Extension:WikiObjects',
	'description'    => 'Allows special "object-namespaces" which have a corresponding DB table',
	'version'        => WIKIOBJECT_VERSION,
);

class WikiObject {

	function __construct() {
	}

}
