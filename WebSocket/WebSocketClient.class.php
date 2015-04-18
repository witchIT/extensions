<?php
class WebSocketClient {

	private $sock;
	private $host;
	private $port;

	function __construct( $host, $port ) {
		$this->host = $host;
		$this->port = $port;

		# Create a socket
		if( !( $this->sock = socket_create( AF_INET, SOCK_STREAM, 0 ) ) ) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror( $errorcode );
			die( "Couldn't create socket: [$errorcode] $errormsg \n" );
		}

		# Connect the socket to the WebSocket daemon
		if( !socket_connect( $this->sock , $this->host , $this->port ) ) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror( $errorcode );
			die( "Could not connect: [$errorcode] $errormsg \n" );
		}

		# Construct a WebSocket message header
		$head = "GET / HTTP/1.1\r\n" 
			. "Upgrade: WebSocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Host: {$this->host}\r\n"
			. "Sec-WebSocket-Key: " . base64_encode( uniqid() ) . "\r\n"
			. "Sec-WebSocket-Version: 10\r\n"
			. "Content-Length: 0\r\n\r\n";

		# Connect to the daemon
		if( !socket_send( $this->sock , $head, strlen( $head ) , 0 ) ) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			die( "Could not send data: [$errorcode] $errormsg \n" );
		}

		# Receive the header
		$res = socket_read( $this->sock, 2000 );

		# Close and die if not accepted
		if( !preg_match( '/Sec-WebSocket-Accept:/', $res ) ) {
			$this->close();
			die( "Connection was not accepted!\n" );
		}
	}

	function close() {
		return socket_close( $this->sock );
	}

	function send( $data ) {
		$data = $this->encode( $data );
		if( !socket_send( $this->sock , $data, strlen( $data ) , 0 ) ) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			die( "Could not send data: [$errorcode] $errormsg \n" );
		}
	}

	function decode( $data ) {
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

	function encode( $payload, $type = 'text', $masked = true ) {
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
