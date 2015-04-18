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

define( 'WEBSOCKET_VERSION', '1.0.1, 2015-04-18' );


WebSocket::$port = 1729;               # Port the WebSocket daemon will run on
WebSocket::$rewrite = false;           # Configure URL rewriting so that the WebSocket port doesn't need to be public
WebSocket::$perl = '/usr/bin/perl';    # Location of the Perl interpreter
WebSocket::$log = false;               # Set a file location to log WebSocket daemon events and errors


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
	public static function message( $type, $msg, $to = false ) {
	}


	$host = '10.9.8.173';  //where is the websocket server
	$port = 8575;
	$local = "http://mypc";  //url where this script run
	$data = "first message";  //data to be send

	$head = "GET / HTTP/1.1"."\r\n".
			"Upgrade: WebSocket"."\r\n".
			"Connection: Upgrade"."\r\n".
			"Origin: $local"."\r\n".
			"Host: $host"."\r\n".
			"Sec-WebSocket-Key: asdasdaas76da7sd6asd6as7d"."\r\n".
			"Content-Length: ".strlen($data)."\r\n"."\r\n";
	//WebSocket handshake
	$sock = fsockopen($host, $port, $errno, $errstr, 2);
	fwrite($sock, $head ) or die('error:'.$errno.':'.$errstr);
	$headers = fread($sock, 2000);
	echo $headers;
	fwrite($sock, hybi10Encode($data)) or die('error:'.$errno.':'.$errstr);
	$wsdata = fread($sock, 2000);
	var_dump(hybi10Decode($wsdata));
	fclose($sock);

	function hybi10Decode( $data ) {
		$bytes = $data;
		$dataLength = '';
		$mask = '';
		$coded_data = '';
		$decodedData = '';
		$secondByte = sprintf( '%08b', ord( $bytes[1] ) );
		$masked = ( $secondByte[0] == '1' ) ? true : false;
		$dataLength = ($masked === true) ? ord( $bytes[1] ) & 127 : ord( $bytes[1] );
		if( $masked === true ) {
			if( $dataLength === 126 ) {
			   $mask = substr( $bytes, 4, 4 );
			   $coded_data = substr( $bytes, 8 );
			}
			elseif( $dataLength === 127 ) {
				$mask = substr( $bytes, 10, 4 );
				$coded_data = substr( $bytes, 14 );
			} else {
				$mask = substr( $bytes, 2, 4 );       
				$coded_data = substr( $bytes, 6 );        
			}   
			for( $i = 0; $i < strlen( $coded_data ); $i++ ) $decodedData .= $coded_data[$i] ^ $mask[$i % 4];
		} else {
			if( $dataLength === 126 ) $decodedData = substr( $bytes, 4 );
			elseif( $dataLength === 127 ) $decodedData = substr( $bytes, 10 );
			else $decodedData = substr( $bytes, 2 );            
		}
		return $decodedData;
	}

	function hybi10Encode( $payload, $type = 'text', $masked = true ) {
		$frameHead = array();
		$frame = '';
		$payloadLength = strlen( $payload );
		switch( $type ) {
			case 'text':
				// first byte indicates FIN, Text-Frame (10000001):
				$frameHead[0] = 129;
				break;
			case 'close':
				// first byte indicates FIN, Close Frame(10001000):
				$frameHead[0] = 136;
				break;
			case 'ping':
				// first byte indicates FIN, Ping frame (10001001):
				$frameHead[0] = 137;
				break;
			case 'pong':
				// first byte indicates FIN, Pong frame (10001010):
				$frameHead[0] = 138;
				break;
		}

		// set mask and payload length (using 1, 3 or 9 bytes)
		if( $payloadLength > 65535 ) {
			$payloadLengthBin = str_split( sprintf( '%064b', $payloadLength ), 8 );
			$frameHead[1] = ( $masked === true ) ? 255 : 127;
			for( $i = 0; $i < 8; $i++ ) {
				$frameHead[$i + 2] = bindec( $payloadLengthBin[$i] );
			}

			// most significant bit MUST be 0 (close connection if frame too big)
			if( $frameHead[2] > 127 ) {
				$this->close( 1004 );
				return false;
			}
		} elseif( $payloadLength > 125 ) {
			$payloadLengthBin = str_split( sprintf( '%016b', $payloadLength ), 8 );
			$frameHead[1] = ( $masked === true ) ? 254 : 126;
			$frameHead[2] = bindec( $payloadLengthBin[0] );
			$frameHead[3] = bindec( $payloadLengthBin[1] );
		} else $frameHead[1] = ( $masked === true ) ? $payloadLength + 128 : $payloadLength;

		// convert frame-head to string:
		foreach( array_keys( $frameHead ) as $i ) $frameHead[$i] = chr( $frameHead[$i] );

		if( $masked === true ) {
			// generate a random mask:
			$mask = array();
			for( $i = 0; $i < 4; $i++ ) {
				$mask[$i] = chr( rand( 0, 255 ) );
			}
			$frameHead = array_merge( $frameHead, $mask );
		}
		$frame = implode('', $frameHead);

		// append payload to frame and return it
		for( $i = 0; $i < $payloadLength; $i++ ) $frame .= ( $masked === true ) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
		return $frame;
	}


}

new WebSocket();
