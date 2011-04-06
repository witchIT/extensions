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
		global $wgHooks;
		SpecialPage::SpecialPage( 'WhatLeadership', 'sysop', true, false, false, false );
		$wgHooks['UnknownAction'][] = $this;
	}

	function execute( $param ) {
		global $wgParser, $wgOut, $wgLang;
		$wgParser->disableCache();
		$this->setHeaders();
		$wgOut->addHTML( "<table class='sortable changes'>" );
		$wgOut->addHTML( "<tr><th>Book</th><th>Last updated</th><th>Last checked</th></tr>" );
		foreach( $this->books() as $book => $time ) {
			if( $time == 0 ) $time = "<i>Never updated</i>";
			else {
				#preg_match( "|^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)|", $time, $m );
				#$time = "$m[1]-$m[2]-$m[3], $m[4]:$m[5]";
				$time = $wgLang->timeanddate($time, true );
			}
			$url = Title::newFromText( $book )->getLocalURL();
			$wgOut->addHTML( "<tr><td><a href='$url'>$book</a></td><td>$time</td></tr>" );
		}
		$wgOut->addHTML( "</table>" );
	}

	/*
	 * Return the data for the last updated book
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut;
		if( $action == 'oldestbook' ) {
			$wgOut->disable();
			$book = $this->books( 1 );
			print_r($book);
		}
		return true;
	}


	/*
	 * Get a list of books
	 */
	function books( $limit = false, $rev = false ) {
		global $wgWhatLeadershipTemplate;
		$dbr = wfGetDB( DB_SLAVE );
		$books = array();
		$titles = array();

		# Get page id list of all books (articles using the Book template)
		$table = $dbr->tableName( 'templatelinks' );
		$book = $dbr->addQuotes( $wgWhatLeadershipTemplate );
		$res = $dbr->select( $table, 'tl_from', "tl_namespace = 10 AND tl_title = $book" );
		while( $row = $dbr->fetchRow( $res ) ) $books[$row[0]] = true;
		$dbr->freeResult( $res );

		# Get the data age of each book
		$table = $dbr->tableName( 'revision' );
		$cond = "rev_comment LIKE '%whatleadership.pl' ORDER BY rev_timestamp DESC";
		foreach( $books as $page => $data ) {
			$title = Title::newFromID( $page )->getPrefixedText();
			if( $row = $dbr->selectRow( $table, 'rev_timestamp', "rev_page = $page AND $cond" ) ) $titles[$title] = $row->rev_timestamp;
			else $titles[$title] = 0;
		}

		# Sort books;
		$rev ? arsort( $titles ) : asort( $titles );

		# Chop if limit supplied
		if( $limit > 0 && $limit < count( $titles ) ) $titles = array_slice( $titles, 0, $limit, true );

		return $titles;
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

