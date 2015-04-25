<?php
class WebSocket {

	public static $port;
	public static $rewrite;
	public static $perl;
	public static $log;
	public static $ssl_cert;
	public static $ssl_key;

	private static $clientID = false;
	
	function __construct() {
		global $wgExtensionFunctions;
		
		// Extension setup hook
		$wgExtensionFunctions[] = 'WebSocket::setup';

		// Give this client an ID or use that supplied in request
		self::$clientID = array_key_exists( 'clientID', $_REQUEST ) ? $_REQUEST['clientID'] : uniqid( 'WS' );
	}

	public static function setup() {
		global $wgOut, $wgResourceModules, $wgExtensionAssetsPath;

		// Ensure WebSocket daemon is running
		if( empty( shell_exec( "ps ax|grep '[W]ebSocket.pl'" ) ) ) {
			$log = self::$log ? ' ' . self::$log : '';
			$rewrite = self::$rewrite ? ' 1' : '';
			$ssl = ( $_SERVER['HTTPS'] && self::$ssl_cert && self::$ssl_key ) ? " \"{self::$ssl_cert}\" \"{self::$ssl_key}\"" : '';
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
		$wgOut->addJsConfigVars( 'wsPort', self::$port );
		$wgOut->addJsConfigVars( 'wsRewrite', self::$rewrite );
		$wgOut->addJsConfigVars( 'wsClientID', self::$clientID );
	}

	/**
	 * Send a message to WebSocket clients
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
