<?php
/**
 * AddLinkClasses extension - Adds class attributes to links based on their category
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad] for Dave Booker (RAC bid 1239559)
 * @licence GNU General Public Licence 2.0 or later
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'ADDLINKCLASSES_VERSION', '2.1.0, 2012-06-14' );

$wgAddLinkClassesPattern = false;

$wgExtensionFunctions[] = 'efSetupAddLinkClasses';
$wgExtensionCredits['other'][] = array(
	'name'        => 'AddLinkClasses',
	'author'      => '[http://www.mediawiki.org/wiki/User:Nad User:Nad]',
	'description' => 'Adds class attributes to links based on their category',
	'url'         => 'http://www.rentacoder.com/RentACoder/misc/BidRequests/ShowBidRequest.asp?lngBidRequestId=1239559',
	'version'     => ADDLINKCLASSES_VERSION
);

class AddLinkClasses {

	function __construct() {
		global $wgHooks;
		$wgHooks['LinkEnd'][] = $this;
	}

	function onLinkEnd( $skin, $target, $options, &$text, &$attribs, &$ret ) {
		global $wgAddLinkClassesPattern;
		if( $target->exists() ) {

			// Get cats
			$cats = array();
			$dbr  = &wfGetDB( DB_SLAVE );
			$cl   = $dbr->tableName( 'categorylinks' );
	 )		$id   = $target->getArticleID();
			$res  = $dbr->select( $cl, 'cl_to', "cl_from = $id", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
			while( $row = $dbr->fetchRow( $res ) ) $cats[] = 'cat-' . preg_replace( '|\W|', '', strtolower( $row[0] ) );
			$dbr->freeResult( $res );

			// Add cat classes if any
			if( count( $cats ) > 0 ) {
				$classes = join( ' ', $cats );
				$attribs['class'] = array_key_exists( 'class', $attribs ) ? $attribs['class'] . " $classes" : $classes;
			}

			// If a pattern has been set apply it to the text
			if( is_array( $wgAddLinkClassesPattern ) ) {
				$text = preg_replace( $wgAddLinkClassesPattern[0], $wgAddLinkClassesPattern[1], $text );
			}
		}

		return true;
	}

}

function efSetupAddLinkClasses() {
	global $egAddLinkClasses;
	$egAddLinkClasses = new AddLinkClasses();
}


