<?php
# Extension:CurrentPages{{Category:Extensions|CurrentUsers}}{{php}}
# - Licenced under LGPL (http://www.gnu.org/copyleft/lesser.html)
# - Author: [http://www.organicdesign.co.nz/nad User:Nad]
# - Started: 2008-05-24

if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'CURRENTPAGES_VERSION', '1.0.10, 2008-06-07' );

$egCurrentPagesMagic               = 'currentpages';
$wgExtensionFunctions[]            = 'efCurrentPagesSetup';
$wgHooks['LanguageGetMagic'][]     = 'efCurrentPagesLanguageGetMagic';

if ( !isset( $_REQUEST['action'] ) && !isset( $_REQUEST['diff'] ) && !isset( $_REQUEST['oldid'] ) )
	$wgHooks['OutputPageBeforeHTML'][] = 'efCurrentPagesCount';

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'CurrentPages',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Adds a parser function to return a bullet list of most viewed pages within the last 24 hours.',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:CurrentPages',
	'version'     => CURRENTPAGES_VERSION
);

/**
 * Add the parser-function hook and create the counter table if non-existent
 */
function efCurrentPagesSetup() {
	global $wgParser, $egCurrentPagesMagic;

	# Add parser-function hook
	$wgParser->setFunctionHook( $egCurrentPagesMagic, 'efCurrentPagesMagic' );

	# Create the DB table if it doesn't exist
	$db = &wfGetDB( DB_MASTER );
	$table = $db->tableName( 'currentpages_hourlyviews' );
	if ( !$db->tableExists( $table ) ) {
		$query = "CREATE TABLE $table (hour INTEGER, page INTEGER, views INTEGER);";
		$res = $db->query( $query );
		$db->freeResult( $res );
	}
}

/**
 * Called for a normal page view request to update the counter table
 */
function efCurrentPagesCount() {

	# Only call this function once
	static $done = 0;
	if ( $done++ ) return true;

	# Get article ID corresponding to title request or return if unattainable
	global $wgTitle;
	if ( !is_object( $wgTitle ) ) return true;
	$page = $wgTitle->getArticleID();
	if ( $page < 1 ) return true;

	# If the hour has changed, clear any existing data out
	# - current hour is stored in a special row where hour is -1
	$db    = &wfGetDB( DB_MASTER );
	$table = $db->tableName( 'currentpages_hourlyviews' );
	$hour  = strftime( '%H' );
	$cond  = array('hour' => -1);
	if ( $row = $db->selectRow( $table, 'views', $cond ) ) {
		if ( $row->views != $hour ) {
			$db->update( $table, array( 'hour' => -1, 'page' => -1, 'views' => $hour ), $cond );
			$db->delete( $table, array( 'hour' => $hour ) );
		}
	} else $db->insert( $table, array( 'hour' => -1, 'views' => $hour ) );

	# Increment the view count for the current title and hour
	$cond = array( 'hour' => $hour, 'page' => $page );
	if ( $row = $db->selectRow( $table, 'views', $cond ) )
		$db->update( $table, array( 'hour' => $hour, 'page' => $page, 'views' => 1+$row->views ), $cond );
	else $db->insert( $table, array( 'hour' => $hour, 'page' => $page, 'views' => 1 ) );

	return true;
}

/**
 * Expand parser function
 */
function efCurrentPagesMagic( &$parser, $n = 0, $format = "*\$2: \$1\n" ) {
	$parser->disableCache();
	if ( $n < 1 ) $n = 10;
	
	# Query DB to get total views for each title over all hours
	$dbr   = &wfGetDB( DB_SLAVE );
	$table = $dbr->tableName( 'currentpages_hourlyviews' );
	$res   = $dbr->select(
		$table, 'page, SUM(views) AS totals', 'hour >= 0', '',
		array( 'GROUP BY' => 'page', 'ORDER BY' => 'totals DESC', 'LIMIT' => $n )
	);

	# Render the title list
	$list = '';
	while ( $row = $dbr->fetchRow( $res ) ) {
		$title = Title::newFromID( $row[0] );
		if ( is_object( $title ) ) {
			$page  = $title->getPrefixedText();
			$link  = "[[:$page|" . $title->getText() . "]]";
			$list .= str_replace( '$1', $link, str_replace( '$2', $row[1], $format ) ) . "\n";
		}
	}
	$dbr->freeResult( $res );
	
	return $list;
}

/**
 * Needed in MediaWiki >1.8.0 for magic word hooks to work properly
 */
function efCurrentPagesLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $egCurrentPagesMagic;
	$magicWords[$egCurrentPagesMagic] = array( $langCode, $egCurrentPagesMagic );
	return true;
}

