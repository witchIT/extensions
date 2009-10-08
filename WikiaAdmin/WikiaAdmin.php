<?php
/**
 * Extension:WikiaInfo
 * {{Category:Extensions}}{{php}}{{Category:Extensions created with Template:SpecialPage}}
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if ( !defined('MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WIKIAADMIN_VERSION', '0.0.1, 2009-10-08' );

$wgExtensionFunctions[] = 'wfSetupWikiaAdmin';

$wgExtensionCredits['specialpage'][] = array(
	'name'        => 'Special:WikiaAdmin',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Manage the wikis in this wikia',
	'url'         => 'http://www.organicdesign.co.nz/Extension:WikiaAdmin.php',
	'version'     => WIKIAADMIN_VERSION
);

require_once( "$IP/includes/SpecialPage.php" );

/**
 * Define a new class based on the SpecialPage class
 */
class SpecialWikiaAdmin extends SpecialPage {

	function SpecialWikiaAdmin() {
		SpecialPage::SpecialPage( 'WikiaAdmin', 'sysop', true, false, false, false );
	}

	/**
	 * Return passed size as Bytes, KB, MB, GB
	 */
	function friendlySize( $size ) {
		$suffix = 'Bytes';
		if ( $size >> 30 )     { $size >>= 30; $suffix = 'GB'; }
		elseif ( $size >> 20 ) { $size >>= 20; $suffix = 'MB'; }
		elseif ( $size >> 10 ) { $size >>= 10; $suffix = 'KB'; }
		return "$size $suffix";
	}

	/**
	 * Override SpecialPage::execute()
	 */
	function execute() {
		global $wgOut, $domains, $settings, $egWikiaInfoDataDir;
		$this->setHeaders();
		$wgOut->addWikiText( "\n\n" );

		# Render tree of databases
		if ( $egWikiaInfoDataDir ) {
			$dbcount   = 0;
			$alltotal  = 0;
			$alltable  = 0;
			$tree      = "*'''Databases'''\n";
			$databases = array();
			foreach ( glob( "$egWikiaInfoDataDir/*" ) as $db ) if ( is_dir( $db ) ) {
				$dbcount++;
				$tables = array();
				$total  = 0;
				foreach ( glob( "$db/*.???" ) as $file ) {
					$stat   = stat( $file );
					$size   = $stat[7];
					$total += $size;
					$table  = preg_replace( "|^(.*/)?(.+?)\\....$|", "$2", $file );
					$tables[$table] = isset( $tables[$table] ) ? $tables[$table]+$size : $size;
				}
				$alltotal += $total;
				$ntable = count( $tables );
				$alltable += $ntable;
				$total = $this->friendlySize( $total );
				$tree .= "**" . basename( $db ) . " ($ntable tables, $total)\n";
			}
			$alltotal = $this->friendlySize( $alltotal );
			$tree  = "{"."{#tree:root=<big><b>$dbcount Databases ($alltable tables, $alltotal)</b></big>|id=databasetree|\n$tree";
			$tree .= "}"."}\n";
			$wgOut->addWikiText( $tree );
		}


		$codebases = array();
		foreach ( glob( '/var/www/mediawiki-*' ) as $codebase ) {
			$stat = stat($codebase);
			$codebases[$stat[1]] = basename($codebase);
		}

		# Get list of domain symlinks by inode
		$dlist = array();
		foreach ( glob( "$domains/*" ) as $link ) {
			$stat  = stat($link);
			$inode = $stat[1];
			if ( is_array( $dlist[$inode] ) ) $dlist[$inode][] = basename( $link );
			else $dlist[$inode] = array( basename( $link ) );
		}

		# Loop through settings files associating with domains
		$wikia  = array();
		foreach ( glob( "$settings/*" ) as $file ) {
			$stat = stat( $file );
			$wikia[basename($file)] = $dlist[$stat[1]];
		}
		ksort( $wikia );

		# Render the list (don't show naked domains if a www also exists)
		$tree = "{"."{#tree:root=<big><b>Currently Installed Wikia</b></big>|id=wikiatree|\n";
		foreach ( $wikia as $file => $dlist ) if ( is_array( $dlist ) ) {
			$stat  = stat( "$settings/$file/wiki" );
			$ver   = $codebases[$stat[1]];
			$tree .= "*'''$file''' ($ver)\n";
			$tree .= "**[[Config:$file/LocalSettings.php|LocalSettings.php]]\n";
			sort( $dlist );
			foreach ( $dlist as $domain ) {
				$wiki  = "http://$domain/wiki/index.php?title";
				$tree .= "**[http://$domain $domain]\n";
				$tree .= "***[$wiki=Special:Recentchanges Recent changes]\n";
				$tree .= "***[$wiki=Special:Version Version]\n";
				$tree .= "***[$wiki=Special:Statistics Statistics]\n";
			}
		}
		$tree .= "}"."}\n";
		$wgOut->addWikiText( $tree );

	}

}

/*
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWikiaAdmin() {
	global $wgLanguageCode, $wgMessageCache;
	$wgMessageCache->addMessages( array( 'wikiaadmin' => 'Wikia Administration' ) );
	SpecialPage::addPage( new SpecialWikiaAdmin() );
}

