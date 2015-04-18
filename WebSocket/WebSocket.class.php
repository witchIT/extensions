<?php
class WebSocket {

	public static $port;
	public static $rewrite;
	public static $perl;
	public static $log;

	private static $clientID = false;
	
	function __construct() {
		global $wgExtensionFunctions;
		
		// Extension setup hook
		$wgExtensionFunctions[] = 'WebSocket::setup';

		// Ensure WebSocket.py is running
		if( empty( shell_exec( "ps ax|grep '[W]ebSocket.pl'" ) ) ) {
			exec( self::$perl . ' "' . __DIR__ . '/WebSocket.pl" ' . self::$port . ( self::$log ? ' ' . self::$log : '' ) );
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

	/**
	 * TODO: Send a message to WebSocket clients
	 */
	public static function send( $type, $msg, $to = false ) {
		$ws = new WebSocketClient( '127.0.0.1', self::$port );
		$ws->send( json_encode( array(
			'type' => $type,
			'from' => self::$clientID,
			'msg'  => $msg,
			'to'   => $to
		) ) );
		$ws->close();		
	}
}
