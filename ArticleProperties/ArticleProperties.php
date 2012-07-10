<?php
/**
 * ArticleProperties - Creates a flexible interface to the page_props table which stores per-article named-properties
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'ARTICLEPROPS_VERSION', "2.1.7, 2012-07-10" );
define( 'AP_VOID', "\x07AP_VOID" );

$wgExtensionCredits['other'][] = array(
	'name'        => "ArticleProperties",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Creates a flexible programming interface for dealing with specialised page classes having defined sets of properties in their own database tables",
	'url'         => "http://www.mediawiki.org/wiki/ArticleProperties",
	'version'     => ARTICLEPROPS_VERSION
);

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['ArticleProperties'] = "$dir/ArticleProperties.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );
require_once( "$dir/ArticleProperties.class.php" );
require_once( "$dir/SpecialArticleProperties.php" );
$wgSpecialPages['ArticleProperties'] = 'SpecialArticleProperties';

// This hook allows us to change the class of article to one of our classes
$wgHooks['ArticleFromTitle'][] = 'ArticleProperties::onArticleFromTitle';
