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

define( 'ADDLINKCLASSES_VERSION', '1.0.3, 2009-08-31' );

$wgExtensionCredits['other'][] = array(
	'name'        => 'AddLinkClasses',
	'author'      => '[http://www.mediawiki.org/wiki/User:Nad User:Nad]',
	'description' => 'Adds class attributes to links based on their category',
	'url'         => 'http://www.rentacoder.com/RentACoder/misc/BidRequests/ShowBidRequest.asp?lngBidRequestId=1239559',
	'version'     => ADDLINKCLASSES_VERSION
);

$wgHooks['BeforePageDisplay'][] = 'wfAddLinkClasses';
function wfAddLinkClasses( $out, $skin = false ) {
	$out->mBodytext = preg_replace_callback( '|<a[^>]+?title="(.+?)".*?>|s', 'wfAddLinkClassesCallback', $out->mBodytext );
	return true;
}

function wfAddLinkClassesCallback( $m ) {

	# Bail if title non-existent
	$title = Title::newFromText( $m[1] );
	if ( !is_object( $title ) || !$title->exists() ) return $m[0];

	# Extract current class or create empty
	$class = preg_match( '|class="(.+?)"|', $m[0], $n ) ? $n[1] : '';
	$link = preg_replace( '|class="(.+?)"|', '', $m[0] );

	# Get cats
	$cats = array( $class );
	$dbr  = &wfGetDB( DB_SLAVE );
	$cl   = $dbr->tableName( 'categorylinks' );
	$id   = $title->getArticleID();
	$res  = $dbr->select( $cl, 'cl_to', "cl_from = $id", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
	while ( $row = $dbr->fetchRow( $res ) ) $cats[] = 'cat-' . preg_replace( '|\W|', '', strtolower( $row[0] ) );
	$dbr->freeResult( $res );

	# Bail if no cats
	if ( count( $cats ) == 0 ) return $m[0];

	# Return the link with its new classes
	$class = join( ' ', $cats );
	if ( $class ) $class = " class=\"$class\"";
	return preg_replace( '|>|', "$class>", $link );
}

