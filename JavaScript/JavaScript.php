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

define( 'JAVASCRIPT_VERSION', '2.1.3, 2011-01-14' );

$wgExtensionCredits['other'][] = array(
	'name'        => "JavaScript",
	'author'      => "[http://www.organicdesign.co.nz/nad User:Nad]",
	'description' => "Includes all *.js files in the directory containing this script",
	'url'         => "http://www.organicdesign.co.nz/Extension:JavaScript",
	'version'     => JAVASCRIPT_VERSION
);

$wgHooks['BeforePageDisplay'][] = 'wfJavaScriptAddScripts';
function wfJavaScriptAddScripts( &$out, $skin = false ) {
	global $wgJsMimeType, $wgScriptPath;

	# Load JavaScript files
	foreach ( glob( dirname( __FILE__ ) . "/*.js" ) as $file ) {
		if ( is_callable( array( $out, 'includeJQuery' ) ) && preg_match( "|/jquery-\d|", $file ) ) {
			$out->includeJQuery();
			$out->addScript( "<script type='$wgJsMimeType'>$=jQuery</script>" );
		} else {
			$file = preg_replace( "|^.*/extensions/|", "$wgScriptPath/extensions/", $file );
			$out->addScript( "<script src='$file' type='$wgJsMimeType'></script>" );
		}
	}

	# Load CSS files
	foreach ( glob( dirname( __FILE__ ) . "/*.css" ) as $file ) {
		$file = preg_replace( "|^.*/extensions/|", "$wgScriptPath/extensions/", $file );
		$out->addStyle( $file, 'screen', '', 'ltr' );
	}
	return true;
}
