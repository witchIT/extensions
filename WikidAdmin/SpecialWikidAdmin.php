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

define( 'WIKIDADMIN_VERSION', '1.0.0, 2009-08-26' );

$wgExtensionFunctions[] = 'wfSetupWikidAdmin';

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
		global $wgParser, $wgOut, $wgRequest;
		$wgParser->disableCache();
		$this->setHeaders();
				
		# Render specific information on a specific job run ( start time, duration, status, revisions )
		if ( $wgRequest->getVal( 'job' ) > 0 ) {
		}
		
		else {
		
			# Render as a table with pause/start/stop for each
			$wgOut->addHtml( $this->renderWork() );
			
			# Render ability to start a new job and supply optional args
			
			# Render a list of previously run jobs from the job log
			#foreach ( $log as $line ) {
			#	$url = $wgTitle->getLocalUrl( "job=$id" );
			#}
		}
		
	}

	/*
	 * Return html table of the current work underway
	 * - called from both special page and statically by ajax handler
	 */
	function renderWork() {
		$wkfile = '/var/www/tools/wikid.work';
		if ( file_exists( $wkfile ) ) {
			$work = unserialize( file_get_contents( $wkfile ) );
			if ( count( $work ) > 0 ) {
				$html = "<table class=\"wikidwork\"><tr><th>Type</th><th>Start</th><th>Progress</th><th>Status</th></tr>\n";
				foreach ( $work as $id => $job ) {
					$type  = $job['type'];
					$start = $job['start'];
					$len   = $job['length'];
					$wptr  = $job['wptr'];
					$pause = $job['paused'];
					$data  = $job['data'];
					$html .= "<tr><td>$type</td><td>$start</td><td>$wptr of $len</td><td>$pause</td>\n";
				}
				$html .= "</table>\n";
			} else $html =  "<i>There are currently no active jobs</i>\n";
		} else $html = "<i>No work file found!</i>\n";
		return $html;
	}

}

/*
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWikidAdmin() {
	global $wgLanguageCode, $wgMessageCache;
	$wgMessageCache->addMessages( array( 'wikidadmin' => 'Wikid Administration' ) );
	SpecialPage::addPage( new SpecialWikidAdmin() );
}

