<?php
/**
 * Extension:SpecialWikidAdmin
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2009 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) )         die( "Not an entry point." );
if( !defined( 'EVENTPIPE_VERSION' ) ) die( "The WikidAdmin special page extension depends on the EventPipe extension" );

define( 'WIKIDADMIN_VERSION', "1.2.5, 2010-10-28" );

if( !isset( $wgWikidAdminWikiPattern ) ) $wgWikidAdminWikiPattern = "|^.+/(.+?)/.+$|";

$wgExtensionFunctions[] = 'wfSetupWikidAdmin';
$wgAjaxExportList[] = 'wfWikidAdminRenderWork';
$wgAjaxExportList[] = 'wfWikidAdminRenderWorkHistory';
$wgSpecialPages['WikidAdmin'] = 'WikidAdmin';
$wgSpecialPageGroups['WikidAdmin'] = 'od';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "Special:WikidAdmin",
	'author'      => "[http://www.organicdesign.co.nz/nad User:Nad]",
	'description' => "Administer the local wiki daemon and its work",
	'url'         => "http://www.organicdesign.co.nz/Extension:WikidAdmin",
	'version'     => WIKIDADMIN_VERSION
);

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['WikidAdmin'] = "$dir/WikidAdmin.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );
require_once( "$dir/WikidAdmin.class.php" );

/**
 * Return html table of the current work underway
 * - called from both special page and statically by ajax handler
 */
function wfWikidAdminRenderWork() {
	global $wgWikidWork, $wgWikidAdminWikiPattern;
	if( !is_array( $wgWikidWork ) ) wfWikidAdminLoadWork();
	if( count( $wgWikidWork ) > 0 ) {
		$unset = "<i>unset</i>";
		$title = Title::makeTitle( NS_SPECIAL, 'WikidAdmin' );
		$class = '';
		$cols  = array( 'Job ID', 'Wiki', 'Type', 'Start', 'Progress', 'State', 'Status' );
		$html  = "<table class=\"changes\"><tr>\n";
		foreach( $cols as $col ) $html .= "<th id=\"wa-work-" . strtolower( $col ) . "\">$col</th>\n";
		$html .= "</tr>\n";
		foreach ( $wgWikidWork as $job ) {
			$class  = $class == 'odd' ? 'even' : 'odd';
			$id     = isset( $job['id'] )     ? $job['id']     : $unset;
			$wiki   = preg_replace( $wgWikidAdminWikiPattern, "$1", $job['wiki'] );
			$type   = isset( $job['type'] )   ? $job['type']   : $unset;
			$start  = isset( $job['start'] )  ? wfTimestamp( TS_DB, $job['start'] ) : $unset;
			$len    = isset( $job['length'] ) ? $job['length'] : $unset;
			$wptr   = isset( $job['wptr'] )   ? $job['wptr']   : $unset;
			$paused = isset( $job['paused'] ) ? $job['paused'] : false;
			$status = isset( $job['status'] ) ? $job['status'] : $unset;
			$plink  = $title->getLocalUrl( "id=$id&action=pause" );
			$plink  = "<a href=\"$plink\">" . ( $paused ? "continue" : "pause" ) . "</a>";
			$slink  = $title->getLocalUrl( "id=$id&action=stop" );
			$slink  = "<a href=\"$slink\">cancel</a>";
			$state  = ( $paused ? "Paused" : "Running" ) . "&nbsp;<small>($plink|$slink)</small>";
			$progress = ( $wptr == $unset || $len == $unset ) ? $unset : "$wptr of $len";
			$html .= "<tr class=\"mw-line-$class\">";
			$html .= "<td>$id</td><td>$wiki</td><td>$type</td><td>$start</td><td>$progress</td><td>$state</td><td>$status</td>";
			$html .= "</tr>\n";
		}
		$html .= "</table>\n";
	} else $html = "<i>There are currently no active jobs</i>\n";
	return $html;
}

/**
 * Return HTML table of the historical work
 */
function wfWikidAdminRenderWorkHistory() {
	global $wgWikidAdminWikiPattern;
	$log = '/var/www/tools/wikid.work.log';
	$max = 4096;
	if( file_exists( $log ) ) {

		if( preg_match_all( "|^\[(.+?)\]\n(.+?)\n\n|sm", file_get_contents( $log, false, NULL, filesize( $log ) - $max, $max ), $m ) ) {

			# Extract the matched work items into a hash by id ($tmp)
			$tmp = array();
			$n = 0;
			foreach( $m[1] as $i => $id ) {
				if( preg_match_all( "|^\s*(.+?)\s*: (.*?)\s*?$|sm", $m[2][$i], $m2 ) ) {
					$m2[1][] = 'id';
					$m2[2][] = $id;
					$tmp[$n] = array();
					foreach( $m2[1] as $j => $k ) $tmp[$n][$k] = $m2[2][$j];
					$n++;
				}
			}

			# Take most recent 100 items ($hist)
			$hist = array();
			$n = count( $tmp );
			if( $n > 100 ) $n = 100;
			for( $i = 0; $i < $n; $i++ ) $hist[] = array_pop( $tmp );
		} else $hist = array();

		# Render the table if any items
		if( count( $hist ) > 0 ) {
			$cols  = array( 'Job ID', 'Wiki', 'Type', 'Start', 'Finish', 'Results' );
			$html  = "<table class=\"changes\"><tr>\n";
			foreach( $cols as $col ) $html .= "<th id=\"wa-hist-" . strtolower( $col ) . "\">$col</th>\n";
			$html .= "</tr>\n";
			$contrib = Title::newFromText( 'Contributions', NS_SPECIAL );
			foreach( $hist as $job ) {
				$id        = $job['id'];
				$wiki      = preg_replace( $wgWikidAdminWikiPattern, "$1", $job['Wiki'] );
				$type      = $job['Type'];
				$user      = $job['User'];
				$start     = wfTimestamp( TS_DB, $job['Start'] );
				$finish    = wfTimestamp( TS_DB, $job['Finish'] );
				$progress  = $job['Progress'];
				$errors    = $job['Errors'];
				$revisions = $job['Revisions'];
				$results   = '';
				if ( $progress > 0 ) $results .= "<li>$progress completed</li>";
				elseif ( $progress ) $results .= "<li>$progress</li>";
				if ( $revisions > 0 ) $results .= "<li>$revisions items changed</li>";
				else $results .= "<li><i>no changes</i></li>";
				if ( $errors ) {
					foreach( explode( '|', $errors ) as $err ) $results .= "<li>$err</li>\n";
				}
				$html .= "<tr><td>$id</td><td>$wiki</td><td>$type</td><td>$start</td><td>$finish</td><td><ul>$results</ul></td>\n";
			}
			$html .= "</table>\n";
		} else $html = "<i>There are no jobs in the work log</i>\n";
	} else $html = "<i>No work log file found!</i>\n";
	return $html;
}

	/**
	 * Load the work array from the work file
	 * - removes "RpcSendAction" from the list
	 */
	function wfWikidAdminLoadWork() {
		global $wgWikidWork, $wgWikidTypes;
		$wkfile = "/var/www/tools/wikid.work";
		if( file_exists( $wkfile ) ) {
			$work = unserialize( file_get_contents( $wkfile ) );
			$wgWikidWork = $work[0];
			$wgWikidTypes = $work[2];
			if ( false !== $offset = array_search( 'RpcSendAction', $wgWikidTypes ) ) array_splice( $wgWikidTypes, $offset, 1 );
		} else {
			$wgWikidWork = array();
			$wgWikidTypes = array();
		}
	}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWikidAdmin() {
	global $wgLanguageCode, $wgMessageCache;
	SpecialPage::addPage( new WikidAdmin() );
}

