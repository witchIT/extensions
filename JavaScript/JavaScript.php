<?php
/**
 * JavaScript extension - Includes all *.js files in the directory containing this script
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'JAVASCRIPT_VERSION', '1.0.0, 2009-11-11' );

$wgExtensionCredits['other'][] = array(
	'name'        => 'JavaScript',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Includes all *.js files in the directory containing this script',
	'url'         => 'http://www.organicdesign.co.nz/Extension:JavaScript',
	'version'     => JAVASCRIPT_VERSION
);

$wgHooks['BeforePageDisplay'][] = 'wfJavaScriptAddScripts';

function wfJavaScriptAddScripts( &$out, $skin = false ) {
	global $wgJsMimeType, $wgScriptPath;
	foreach ( glob( dirname( __FILE__ ) . "/*.js" ) as $file ) {
		$file = ereg_replace( '^.*/extensions/', $wgScriptPath . '/extensions/' . basename( $path ), $file );
		$out->addScript( "<script type='$wgJsMimeType'>$file</script>" );
	}
	return true;
}
