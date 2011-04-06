<?php
/**
 * WhatLeadership extension - special page for whatleadership.com functionality
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2011 whatleadership.com
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );

define( 'WHATLEADERSHIP_VERSION', "1.0.0, 2011-04-06" );

$wgWhatLeadershipTemplate = 'Book';

$wgExtensionFunctions[] = 'wfSetupWhatLeadership';
$wgSpecialPages['WhatLeadership'] = 'WhatLeadership';
$wgSpecialPageGroups['WikidAdmin'] = 'od';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "WhatLeadership",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "whatleadership.com functionality",
	'url'         => "http://www.whatleadership.com",
	'version'     => WHATLEADERSHIP_VERSION
);

require_once( "$IP/includes/SpecialPage.php" );

/**
 * Define a new class based on the SpecialPage class
 */
class WhatLeadership extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'WhatLeadership', 'sysop', true, false, false, false );
	}

	function execute( $param ) {
		global $wgParser, $wgOut;
		$wgParser->disableCache();
		$this->setHeaders();


	}

	/*
	 * Get a list of books
	 */
	function books( $limit = false, $rev = false ) {
		global $wgWhatLeadershipTemplate;
		$limit = $limit ? " LIMIT $count" : "";
		$desc = $rev ? " DESC" : "";
		$dbr = wfGetDB( DB_SLAVE );
		$books = array();

		# Get page id list of all books (articles using the Book template)
		$table = $dbr->tableName( 'templatelinks' );
		$book = $dbr->addQuotes( $wgWhatLeadershipTemplate );
		$res = $dbr->select( $table, 'tl_from', "tl_namespace = 10 AND tl_title = $book" );
		while( $row = $dbr->fetchRow( $res ) ) $books[$row[0]] = true;
		$dbr->freeResult( $res );

		# Get the data age of each book
		$table = $dbr->tableName( 'revision' );
		$cond = "rev_comment LIKE '%whatleadership.pl' ORDER BY rev_timestamp DESC LIMIT 1";
		foreach( $books as $page => $data ) {
			if( $row = $dbr->selectRow( $table, 'tl_from', "rev_page = $page AND $cond" ) )
				$books[Title::newFromID( $page )->getPrefixedText()] = $row[0];
		}

		# Sort books into newest first
		arsort( $books );

		# Chop if limit supplied
		if( $limit > 0 && $limit < count( $books ) ) $books = array_splice( $books, 0, $limit );

		return $books;
	}

}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWhatLeadership() {
	global $wgLanguageCode, $wgMessageCache;
	$wgMessageCache->addMessages( array( 'whatleadership' => "WhatLeadership book list" ) );
	SpecialPage::addPage( new WhatLeadership() );
}

