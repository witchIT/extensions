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
		
		
		# Read in wikid.work
		$wkfile = '/var/www/tools/wikid.work';
		if ( !file_exists( $wkfile ) ) {
			$wgOut->addWikiText( "''Error: No work file found!''", true );
			return;
		}
		
		$work = unserialize( file_get_contents( $wkfile ) );
		
		# Render specific information on a specific job run ( start time, duration, status, revisions )
		if ( $wgRequest->getVal( 'job' ) > 0 ) {
		}
		
		else {
		
			# Render as a table with pause/start/stop for each
			if ( count( $work ) > 0 ) {
				$wgOut->addWikiText( "{|\n!Type!!Start!!Progress!!Status\n", true );
				foreach ( $work as $id => $job ) {
					$type  = $job['type'];
					$start = $job['start'];
					$len   = $job['length'];
					$wptr  = $job['wptr'];
					$pause = $job['paused'];
					$data  = $job['data'];
					$wgOut->addWikiText( "|-\n$type||$start||$wptr of $len||$pause\n", true );
				}
				$wgOut->addWikiText( "|}\n", true );
			} else $wgOut->addWikiText( "''There are currently no active jobs''", true );
			
			# Render a list of previously run jobs from the job log
			#foreach ( $log as $line ) {
			#	$url = $wgTitle->getLocalUrl( "job=$id" );
			#}
		}
		
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

