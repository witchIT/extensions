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

define( 'WEBSOCKET_VERSION', '1.1.0, 2015-04-25' );

require( __DIR__ . '/WebSocket.class.php' );
require( __DIR__ . '/WebSocketClient.class.php' );

WebSocket::$port = 1729;               # Port the WebSocket daemon will run on
WebSocket::$rewrite = false;           # Configure URL rewriting so that the WebSocket port doesn't need to be public
WebSocket::$perl = '/usr/bin/perl';    # Location of the Perl interpreter
WebSocket::$log = false;               # Set a file location to log WebSocket daemon events and errors
WebSocket::$ssl_cert = false;          # If the wiki uses SSL, then the WebSocket will need to know the certificate file,
WebSocket::$ssl_key = false;           # and the SSL key file

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'WebSocket',
	'author'         => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'url'            => 'http://www.organicdesign.co.nz/Extension:WebSocket',
	'descriptionmsg' => 'websocket-desc',
	'version'        => WEBSOCKET_VERSION,
);

$wgExtensionMessagesFiles['WebSocket'] = __DIR__ . '/WebSocket.i18n.php';

new WebSocket();
