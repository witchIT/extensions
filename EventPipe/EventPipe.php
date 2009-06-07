<?php
/**
 * EventPipe extension - Allows selected events (hooks) to be forwarded to a local pipe
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 * @copyright © 2009 [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'EVENTPIPE_VERSION', '1.0.4, 2009-05-30' );

$wgEventPipePort = '1729';
$wgEventPipeList = array( 'RevisionInsertComplete', 'UserLoginComplete', 'PrefsPasswordAudit', 'AddNewAccount' );

$wgExtensionCredits['other'][] = array(
	'name'        => 'EventPipe',
	'author'      => '[http://www.mediawiki.org/wiki/User:Nad User:Nad]',
	'description' => 'Allows selected events (hooks) to be forwarded to a local pipe',
	'url'         => 'http://www.organicdesign.co.nz/Extension:EventPipe',
	'version'     => EVENTPIPE_VERSION
);

$wgExtensionFunctions[] = 'wfSetupEventPipe';
function wfSetupEventPipe() {
	global $wgHooks, $wgEventPipeList;
	foreach ( $wgEventPipeList as $hook ) {
		$callback = $wgHooks[$hook][] = "wfEventPipeOn$hook";
		eval( "function $callback() { \$args=func_get_args();return wfEventPipeSend('$hook',\$args); }" );
	}
}

/**
 * Forward the hooks name, args and the request global to the pipe
 */
function wfEventPipeSend( $hook, $args ) {
	global $wgEventPipePort, $wgSitename, $wgSerevr, $wgScript;
	if ( $handle = fsockopen( '127.0.0.1', $wgEventPipePort ) ) {
		$data = var_export( array(
			'wgSitename' => $wgSitename,
			'wgScript'   => $wgServer.$wgScript,
			$args,
			$_REQUEST
		), true );
		fputs( $handle, "GET $hook?$data HTTP/1.0\n\n\x00" );
		fclose( $handle ); 
	}
	return true;
}


