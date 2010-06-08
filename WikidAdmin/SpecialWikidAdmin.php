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
if ( !defined('MEDIAWIKI' ) )          die( 'Not an entry point.' );
if ( !defined( 'EVENTPIPE_VERSION' ) ) die( 'The WikidAdmin special page extension depends on the EventPipe extension' );

define( 'WIKIDADMIN_VERSION', '1.2.0, 2010-06-09' );

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
		global $wgWikidWork, $wgWikidTypes, $wgParser, $wgOut, $wgHooks, $wgRequest, $wgJsMimeType;
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
			}</script>");

		# Start a new job instance if wpStart & wpType posted
		if ( $wgRequest->getText( 'wpStart' ) ) {
			$type = $wgRequest->getText( 'wpType' );
			$start = true;
			$args  = array();
			wfRunHooks( "WikidAdminTypeFormProcess_$type", array( &$args, &$start ) );
			if ( $start ) $this->startJob( $type, $args );
		}

		# Cancel a job
		if ( $wgRequest->getText( 'action' ) == 'stop' ) {
			$this->stopJob( $wgRequest->getText( 'id' ) );
		}

		# Pause/continue a job
		if ( $wgRequest->getText( 'action' ) == 'pause' ) {
			$this->pauseJob( $wgRequest->getText( 'id' ) );
		}

		# Render ability to start a new job and supply optional args
		$wgOut->addWikiText( "== Start a new job ==\n" );
		if ( count( $wgWikidTypes ) ) {
			$url = Title::newFromText( 'WikidAdmin', NS_SPECIAL )->getLocalUrl();
			$html = "<form action=\"$url\" method=\"POST\" enctype=\"multipart/form-data\">";
			$html .= "<table><tr valign=\"top\">\n";
			$html .= '<td>Type: <select name="wpType" id="wpType" onchange="wikidAdminShowTypeForm()" ><option />';
			foreach( $wgWikidTypes as $type ) $html .= "<option>$type</option>";
			$html .= "</select></td><td>";

			# Render forms for types
			$forms = array();
			foreach( $wgWikidTypes as $type ) {
				$hook = "WikidAdminTypeFormRender_$type";
				if ( isset( $wgHooks[$hook] ) ) {
					$form = '';
					wfRunHooks( $hook, array( &$form ) );
					$html .= "<div id=\"form-$type\" style=\"display:none\" >$form</div>";
					$forms[] = "'$type'";
				}
			}

			# and the script to switch the visible one
			$forms = join( ',', $forms );
			$wgOut->addScript("<script type='$wgJsMimeType'>
			function wikidAdminShowTypeForm() {
				var type = document.getElementById('wpType').value;
				var forms = [$forms];
				for( i in forms ) document.getElementById('form-'+forms[i]).style.display = forms[i] == type ? '' : 'none';
			}</script>");

			$html .= '</td><td><input name="wpStart" type="submit" value="Start" /></td>';
			$html .= '</tr></table></form><br />';
			$wgOut->addHtml( $html );
		} else $wgOut->addHtml( '<i>There are no job types defined</i><br />' );

		# Render as a table with pause/continue/cancel for each
		$wgOut->addWikiText( "\n== Currently executing work ==\n" );
		$wgOut->addHtml( '<div id="current-work">' . wfWikidAdminRenderWork() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminCurrentTimer();</script><br />" );

		# Render a list of previously run jobs from the job log
		$wgOut->addWikiText( "== Work history ==\n" );
		$wgOut->addHtml( '<div id="work-history">' . wfWikidAdminRenderWorkHistory() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminHistoryTimer();</script>" );

	}

	/**
	 * Send a start job request to the local peer
	 */
	function startJob( $type, &$args ) {
		$args['type'] = $type;
		wfEventPipeSend( 'StartJob', $args );
	}

	/**
	 * Send a stop job request to the local peer
	 */
	function stopJob( $id ) {
		wfEventPipeSend( 'StopJob', $id );
	}

	/**
	 * Send a pause job request to the local peer
	 */
	function pauseJob( $id ) {
		wfEventPipeSend( 'PauseJobToggle', $id );
	}

}

/**
 * Return html table of the current work underway
 * - called from both special page and statically by ajax handler
 */
function wfWikidAdminRenderWork() {
	global $wgWikidWork;
	if ( !is_array( $wgWikidWork ) ) wfWikidAdminLoadWork();
	if ( count( $wgWikidWork ) > 0 ) {
		$unset = '<i>unset</i>';
		$title = Title::makeTitle( NS_SPECIAL, 'WikidAdmin' );
		$class = '';
		$cols  = array( 'ID', 'Type', 'Start', 'Progress', 'State', 'Status' );
		$html  = "<table class=\"changes\"><tr>\n";
		foreach( $cols as $col ) $html .= "<th id=\"wa-work-" . strtolower( $col ) . "\">$col</th>\n";
		$html .= "</tr>\n";
		foreach ( $wgWikidWork as $job ) {
			$class  = $class == 'odd' ? 'even' : 'odd';
			$id     = isset( $job['id'] )     ? $job['id']     : $unset;
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
			$html .= "<td>$id</td><td>$type</td><td>$start</td><td>$progress</td><td>$state</td><td>$status</td>";
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
	$log = '/var/www/tools/wikid.work.log';
	$max = 16384;
	if ( file_exists( $log ) ) {

		if ( preg_match_all( "|^\[(.+?)\]\n(.+?)\n\n|sm", file_get_contents( $log, false, NULL, filesize( $log ) - $max, $max ), $m ) ) {

			# Extract the matched work items into a hash by id ($tmp)
			$tmp = array();
			$n = 0;
			foreach ( $m[1] as $i => $id ) {
				if ( preg_match_all( "|^\s*(.+?)\s*: (.*?)\s*?$|sm", $m[2][$i], $m2 ) ) {
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
			if ( $n > 100 ) $n = 100;
			for ( $i = 0; $i < $n; $i++ ) $hist[] = array_pop( $tmp );
		} else $hist = array();

		# Render the table if any items
		if ( count( $hist ) > 0 ) {
			$cols  = array( 'ID', 'Type', 'Start', 'Finish', 'Results' );
			$html  = "<table class=\"changes\"><tr>\n";
			foreach( $cols as $col ) $html .= "<th id=\"wa-hist-" . strtolower( $col ) . "\">$col</th>\n";
			$html .= "</tr>\n";
			$contrib = Title::newFromText( 'Contributions', NS_SPECIAL );
			foreach ( $hist as $job ) {
				$id        = $job['id'];
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
				$html .= "<tr><td>$id</td><td>$type</td><td>$start</td><td>$finish</td><td><ul>$results</ul></td>\n";
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
		$wkfile = '/var/www/tools/wikid.work';
		if ( file_exists( $wkfile ) ) {
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
	$wgMessageCache->addMessages( array( 'wikidadmin' => 'Wiki Daemon Administration' ) );
	SpecialPage::addPage( new SpecialWikidAdmin() );
}

