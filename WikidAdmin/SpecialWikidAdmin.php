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

if ( !defined('MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WIKIDADMIN_VERSION', '1.0.3, 2009-09-05' );

$wgExtensionFunctions[] = 'wfSetupWikidAdmin';
$wgAjaxExportList[] = 'wfWikidAdminRenderWork';
$wgAjaxExportList[] = 'wfWikidAdminRenderWorkHistory';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => 'Special:WikidAdmin',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Administer the local wiki daemon and its work',
	'url'         => 'http://www.organicdesign.co.nz/Extension:WikidAdmin',
	'version'     => WIKIDADMIN_VERSION
);

require_once( "$IP/includes/SpecialPage.php" );

/**
 * Define a new class based on the SpecialPage class
 */
class SpecialWikidAdmin extends SpecialPage {

	function SpecialWikidAdmin() {
		SpecialPage::SpecialPage( 'WikidAdmin', 'sysop', true, false, false, false );
	}

	function execute( $param ) {
		global $wgWikidWork, $wgWikidTypes, $wgParser, $wgOut, $wgRequest, $wgJsMimeType;
		$wgParser->disableCache();
		$this->setHeaders();
		wfWikidAdminLoadWork();

		# Add some script for auto updating the job info
		$wgOut->addScript("<script type='$wgJsMimeType'>
			function wikidAdminCurrentTimer() {
				setTimeout('wikidAdminCurrentTimer()',1000);
				sajax_do_call('wfWikidAdminRenderWork',[],document.getElementById('current-work'));
			}
			function wikidAdminHistoryTimer() {
				setTimeout('wikidAdminHistoryTimer()',10000);
				sajax_do_call('wfWikidAdminRenderWorkHistory',[],document.getElementById('work-history'));
			}
			</script>");

		# Start a new job instance if wpStart & wpType posted
		if ( $wgRequest->getText( 'wpStart' ) ) {
			$this->startJob( $wgRequest->getText( 'wpType' ) );
		}

		# Pause/continue a job
		if ( $wgRequest->getText( 'action' ) == 'pause' ) {
			$this->pauseJob( $wgRequest->getText( 'id' ) );
		}

		# Render as a table with pause/start/stop for each
		$wgOut->addWikiText( "== Currently executing work ==\n" );
		$wgOut->addHtml( '<div id="current-work">' . wfWikidAdminRenderWork() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminCurrentTimer();</script>" );

		# Render ability to start a new job and supply optional args
		$wgOut->addWikiText( "\n\n== Start a new job ==\n" );
		if ( count( $wgWikidTypes ) ) {
			$html = '<form method="POST">';
			$html .= 'Type: <select name="wpType">';
			foreach( $wgWikidTypes as $type ) $html .= "<option>$type</option>";
			$html .= '</select>&nbsp;<input name="wpStart" type="submit" value="Start" />';
			$html .= '</form>';
			$wgOut->addHtml( $html );
		} else $wgOut->addHtml( '<i>There are no job types defined</i>' );

		# Render a list of previously run jobs from the job log
		$wgOut->addWikiText( "\n\n== Work history ==\n" );
		$wgOut->addHtml( '<div id="work-history">' . wfWikidAdminRenderWorkHistory() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminHistoryTimer();</script>" );

	}

	/**
	 * Send a start job request to the local peer
	 */
	function startJob( $type ) {
		global $wgEventPipePort, $wgSitename, $wgServer, $wgScript;
		if ( $handle = fsockopen( '127.0.0.1', $wgEventPipePort ) ) {
			$data = serialize( array(
				'type'       => $type,
				'wgSitename' => $wgSitename,
				'wgScript'   => $wgServer . $wgScript
			) );
			fputs( $handle, "GET StartJob?$data HTTP/1.0\n\n\x00" );
			fclose( $handle ); 
		}
	}

	/**
	 * Send a pause job request to the local peer
	 */
	function pauseJob( $id ) {
		global $wgEventPipePort, $wgSitename, $wgServer, $wgScript;
		if ( $handle = fsockopen( '127.0.0.1', $wgEventPipePort ) ) {
			$data = serialize( array(
				'id'         => $id,
				'wgSitename' => $wgSitename,
				'wgScript'   => $wgServer . $wgScript
			) );
			fputs( $handle, "GET PauseJobToggle?$data HTTP/1.0\n\n\x00" );
			fclose( $handle ); 
		}
	}

}

/**
 * Return html table of the current work underway
 * - called from both special page and statically by ajax handler
 */
function wfWikidAdminRenderWork() {
	global $wgWikidWork;
	if ( !is_array( $wgWikidWork ) ) wfWikidAdminLoadWork();
	$unset = '<i>unset</i>';
	$title = Title::makeTitle( NS_SPECIAL, 'WikidAdmin' );
	if ( count( $wgWikidWork ) > 0 ) {
		$html = "<table><tr><th>ID</th><th>Type</th><th>Start</th><th>Progress</th><th>Status</th><th>Actions</th></tr>\n";
		foreach ( $wgWikidWork as $job ) {
			$id     = isset( $job['id'] )       ? $job['id']     : $unset;
			$type   = isset( $job['type'] )     ? $job['type']   : $unset;
			$start  = isset( $job['start'] )    ? $job['start']  : $unset;
			$len    = isset( $job['length'] )   ? $job['length'] : $unset;
			$wptr   = isset( $job['progress'] ) ? $job['wptr']   : $unset;
			$pause  = isset( $job['paused'] )   ? $job['paused'] : $unset;
			$status = isset( $job['status'] )   ? $job['status'] : $unset;
			$link   = Title::makeTitle( NS_SPECIAL, 'WikidAdmin' )->getLocalUrl( "id=$id&action=pause" );
			$link   = "<a href=\"$link\">" . ( $pause == $unset ? "Pause" : "Continue" ) . "</a>";
			$progress = ( $wptr == $unset || $len == $unset ) ? $unset : "$wptr of $len";
			$html .= "<tr><td><b>$id</b></td><td>$type</td><td>$start</td><td>$progress</td><td>$status</td><td>$link</td>\n";
		}
		$html .= "</table>\n";
	} else $html = "<i>There are currently no active jobs</i>\n";
	return $html;
}

/**
 * Return HTML table of the historical work
 */
function wfWikidAdminRenderWorkHistory() {
	$log = '/var/www/tools/wikid.work.log';
	if ( file_exists( $log ) && preg_match_all( "|^\[(.+?)\]\n(.+?)\n\n|sm", file_get_contents( $log ), $m ) ) {

		# Extract the matched work items into a hash by id ($tmp)
		$tmp = array();
		foreach ( $m[1] as $i => $id ) {
			if ( preg_match_all( "|^\s*(.+?)\s*:\s*(.*?)\s*$|sm", $m[2][$i], $n ) ) {
				foreach( $n[1] as $j => $k ) $tmp[$id][$k] = $n[2][$j];
			}
		}

		# Take most recent 100 items ($hist)
		$hist = array();
		$n = count( $tmp );
		if ( $n > 100 ) $n = 100;
		for ( $i = 0; $i < $n; $i++ ) $hist[] = array_pop( $tmp );

		# Render the table if any items
		if ( count( $hist ) > 0 ) {
			$html = "<table><tr><th>ID</th><th>Type</th><th>Start</th><th>Progress</th><th>Results</th></tr>\n";
			$contrib = Title::newFromText( 'Contributions', NS_SPECIAL );
			foreach ( $hist as $id => $job ) {
				$type      = $job['Type'];
				$user      = $job['User'];
				$start     = $job['Start'];
				$finish    = $job['Finish'];
				$progress  = $job['Progress'];
				$errors    = $job['Errors'];
				$revisions = $job['Revisions'];
				if ( $revisions > 0 ) {
					$offset = wfTimestamp( TS_MW, $finish );
					$url    = $contrib->getLocalUrl( "target=$user&offset=$offset&limit=$revisions" );
					$link   = "<a href=\"$url\">$revisions revisions</a>";
				} else $link = "<i>no changes</i>";
				if ( $errors ) {
					$results = '<ul>';
					foreach( explode( '|', $errors ) as $err ) $results .= "<li>$err</li>\n";
					$results .= '</ul>';
				} else $results = $link;
				$html .= "<tr><td>$id</td><td>$type</td><td>$start</td><td>$progress</td><td>$results</td>\n";
			}
			$html .= "</table>\n";
		} else $html = "<i>There are no jobs in the work log</i>\n";
	} else $html = "<i>No work log file found!</i>\n";
	return $html;
}

	/**
	 * Load the work array from the work file
	 */
	function wfWikidAdminLoadWork() {
		global $wgWikidWork, $wgWikidTypes;
		$wkfile = '/var/www/tools/wikid.work';
		if ( file_exists( $wkfile ) ) {
			$work = unserialize( file_get_contents( $wkfile ) );
			$wgWikidWork = $work[0];
			$wgWikidTypes = $work[2];
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
	$wgMessageCache->addMessages( array( 'wikidadmin' => 'Wiki Daemon Administration' ) );
	SpecialPage::addPage( new SpecialWikidAdmin() );
}

