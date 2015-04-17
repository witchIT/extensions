<?php
/**
 * WebSocket extension - Allows live connections between the server and other current clients
 *
 * See http://www.organicdesign.co.nz/Extension:WebSocket for details
 * 
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @copyright © 2015 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WEBSOCKET_VERSION','0.0.0, 2015-04-14' );


WebSocket::$port = 1729;               # Port the WebSocket daemon will run on
WebSocket::$rewrite = false;           # Configure URL rewriting so that the WebSocket port doesn't need to be public
WebSocket::$perl = '/usr/bin/perl';    # Location of the Perl interpreter


$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'WebSocket',
	'author'         => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'url'            => 'http://www.organicdesign.co.nz/Extension:WebSocket',
	'descriptionmsg' => 'websocket-desc',
	'version'        => WEBSOCKET_VERSION,
);

$wgExtensionMessagesFiles['WebSocket'] = __DIR__ . '/WebSocket.i18n.php';

class WebSocket {

	public static $port;
	public static $rewrite;
	public static $perl;

	private static $clientID = false;
	
	function __construct() {
		global $wgExtensionFunctions;
		
		// Extension setup hook
		$wgExtensionFunctions[] = 'WebSocket::setup';

		// Ensure WebSocket.py is running
		if( empty( shell_exec( "ps ax|grep '[W]ebSocket.pl'" ) ) ) {
			exec( self::$perl . ' "' . __DIR__ . '/WebSocket.pl" ' . self::$port );
		}

		// Give this client an ID or use that supplied in request
		self::$clientID = array_key_exists( 'clientID', $_REQUEST ) ? $_REQUEST['clientID'] : uniqid( 'WS' );
	}

	/**
	 * Add the JS, styles and messages for the special page
	 */
	public static function setup() {
		global $wgOut, $wgResourceModules, $wgExtensionAssetsPath;
		$path = $wgExtensionAssetsPath . '/' . basename( __DIR__ );
		$wgResourceModules['ext.websocket'] = array(
			'scripts'        => array( 'websocket.js' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__,
			'messages'       => array(),
		);
		$wgOut->addModules( 'ext.websocket' );
		$wgOut->addJsConfigVars( 'wsPort', self::$port );
		$wgOut->addJsConfigVars( 'wsRewrite', self::$rewrite );
		$wgOut->addJsConfigVars( 'wsClientID', self::$clientID );
	}
}

new WebSocket();
