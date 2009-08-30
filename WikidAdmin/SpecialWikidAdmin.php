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

define( 'WIKIDADMIN_VERSION', '1.0.1, 2009-08-30' );

$wgExtensionFunctions[] = 'wfSetupWikidAdmin';
$wgAjaxExportList[] = 'wfWikidAdminRenderWork';
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
		global $wgParser, $wgOut, $wgRequest, $wgJsMimeType;
		$wgParser->disableCache();
		$this->setHeaders();

		# Add some script for auto updating the job info
		$wgOut->addScript("<script type='$wgJsMimeType'>
			function wikidAdminTimer() {
				setTimeout('wikidAdminTimer()',1000);
				sajax_do_call('wfWikidAdminRenderWork',[],document.getElementById('current-work'));
			}
			</script>");

		# Start a new job instance if wpStart & wpType posted
		if ( $wgRequest->getText( 'wpStart' ) ) {
			$this->startJob( $wgRequest->getText( 'wpType' ) );
			$wgOut->addWikiText( "<div class=\"successbox\">New job</div>\n" );
		}

		# Render specific information on a specific job run ( start time, duration, status, revisions )
		if ( $param ) {
			$wgOut->addWikiText( "== Job $param ==\n" );
			$wgOut->addWikiText( "bla bla bla...\n" );
		}

		# Render as a table with pause/start/stop for each
		$wgOut->addWikiText( "== Currently executing work ==\n" );
		$wgOut->addHtml( '<div id="current-work">' . wfWikidAdminRenderWork() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminTimer();</script>" );

		# Render ability to start a new job and supply optional args
		$wgOut->addWikiText( "\n\n== Start a new job ==\n" );

		# Render a list of previously run jobs from the job log
		$wgOut->addWikiText( "\n\n== Job history ==\n" );
		#foreach ( $log as $line ) {
		#	$url = $wgTpecialPagesitle->getLocalUrl( "job=$id" );
		#}

	}

	/**
	 * Send a start jon request to the local peer
	 */
	function startJob( $type ) {
		global $wgEventPipePort;
		if ( $handle = fsockopen( '127.0.0.1', $wgEventPipePort ) ) {
			$guid = strftime( '%Y%m%d', time() ) . '-' . substr( strtoupper( uniqid('', true) ), -5 );
			$data = serialize( array( 'id' => $guid, 'type' => $type ) );
			fputs( $handle, "GET StartJob?$data HTTP/1.0\n\n\x00" );
			fclose( $handle ); 
		}
	}
}

/**
 * Return html table of the current work underway
 * - called from both special page and statically by ajax handler
 */
function wfWikidAdminRenderWork() {
	$wkfile = '/var/www/tools/wikid.work';
	$unset = '<i>unset</i>';
	if ( file_exists( $wkfile ) ) {
		$work = unserialize( file_get_contents( $wkfile ) );
		$work = $work[0];
		if ( count( $work ) > 0 ) {
			$html = "<table class=\"wikidwork\"><tr><th>ID</th><th>Type</th><th>Start</th><th>Progress</th><th>Status</th></tr>\n";
			foreach ( $work as $job ) {
				$id    = isset( $job['id'] )     ? $job['id']     : $unset;
				$type  = isset( $job['type'] )   ? $job['type']   : $unset;
				$start = isset( $job['start'] )  ? $job['start']  : $unset;
				$len   = isset( $job['length'] ) ? $job['length'] : $unset;
				$wptr  = isset( $job['wptr'] )   ? $job['wptr']   : $unset;
				$pause = isset( $job['paused'] ) ? $job['paused'] : $unset;
				$data  = isset( $job['data'] )   ? $job['data']   : $unset;
				$url   = Title::makeTitle( NS_SPECIAL, "WikidAdmin/$id" )->getLocalUrl();
				$link = "<a href=\"$url\">$id</a>";
				$progress = ( $wptr == $unset || $len == $unset ) ? $unset : "$wptr of $len";
				$html .= "<tr><td>$link</td><td>$type</td><td>$start</td><td>$progress</td><td>$pause</td>\n";
			}
			$html .= "</table>\n";
		} else $html =  "<i>There are currently no active jobs</i>\n";
	} else $html = "<i>No work file found!</i>\n";
	return $html;
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWikidAdmin() {
	global $wgLanguageCode, $wgMessageCache;
	$wgMessageCache->addMessages( array( 'wikidadmin' => 'Wiki Daemon Administration' ) );
	SpecialPage::addPage( new SpecialWikidAdmin() );
}

