<?php
/**
 * PageProperties - Creates a flexible interface to the page_props table which stores per-article named-properties
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'PAGEPROPS_VERSION', "0.1.0, 2012-03-08" );

$wgExtensionCredits['specialpage'][] = array(
	'name'        => "PageProperties",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Creates a flexible interface to the page_props table which stores per-article named-properties",
	'url'         => "http://www.mediawiki.org/wiki/ArticleProperties",
	'version'     => PAGEPROPS_VERSION
);

$dir = dirname( __FILE__ );
require_once( "$dir/PageProperties.class.php" );
