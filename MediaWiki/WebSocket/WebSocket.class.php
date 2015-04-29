<?php
class WebSocket {

	public static $port;
	public static $rewrite;
	public static $perl;
	public static $log;
	public static $ssl_cert;
	public static $ssl_key;

	private static $clientID = false;
	private static $ssl = false;

	function __construct() {
		global $wgExtensionFunctions;
		
		// Extension setup hook
		$wgExtensionFunctions[] = 'WebSocket::setup';

		// Give this client an ID or use that supplied in request
		self::$clientID = array_key_exists( 'clientID', $_REQUEST ) ? $_REQUEST['clientID'] : uniqid( 'WS' );

		// Is this an SSL connection?
		self::$ssl = array_key_exists( 'HTTPS', $_SERVER ) && $_SERVER['HTTPS'];
	}

	public static function setup() {
		global $wgOut, $wgResourceModules, $wgExtensionAssetsPath, $wgDBname, $wgDBprefix;

		// If using SSL but no creds supplied, die
		if( self::$ssl && !(self::$ssl_cert && self::$ssl_key) ) die( wfMessage( 'websocket-nosslcreds' )->text() );

		// Ensure WebSocket daemon is running
		$processes = shell_exec( "ps ax|grep '[W]ebSocket.pl" );
		if( empty( $processes ) ) {
			$log = self::$log ? ' "' . self::$log . '"' : '';
			$rewrite = self::$rewrite ? ' 1' : '';
			$ssl = self::$ssl_cert ? ' "' . self::$ssl_cert . '" "' . self::$ssl_key . '"' : '';
			exec( self::$perl . ' "' . __DIR__ . '/WebSocket.pl" ' . self::$port . $log . $rewrite . $ssl );
		}

		// Add the JS, styles and messages for the special page
		$path = $wgExtensionAssetsPath . '/' . basename( __DIR__ );
		$wgResourceModules['ext.websocket'] = array(
			'scripts'        => array( 'websocket.js' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__,
			'messages'       => array(),
		);
		$wgOut->addModules( 'ext.websocket' );

		// Send some additional config info to the client side
		$wgOut->addJsConfigVars( 'wsPort', self::$port );
		$wgOut->addJsConfigVars( 'wsRewrite', self::$rewrite );
		$wgOut->addJsConfigVars( 'wsClientID', self::$clientID );
		$wgOut->addJsConfigVars( 'wsWikiID', "$wgDBprefix$wgDBname:" );
	}

	/**
	 * Send a message to WebSocket clients (use port + 1 for SSL connections)
	 */
	public static function send( $type, $msg, $to = false ) {
		$proto = self::$ssl ? 'wss' : 'ws';
		$port = self::$ssl ? self::$port + 1 : self::$port;
		$ws = new WebSocketClient( "$proto://localhost:" . self::$port );
		$ws->send( json_encode( array(
			'type' => $type,
			'from' => self::$clientID,
			'msg'  => $msg,
			'to'   => $to
		) ) );
		$ws->close();
	}
}
