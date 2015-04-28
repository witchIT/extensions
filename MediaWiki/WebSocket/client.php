<?php
require( __DIR__ . '/WebSocketClient.class.php' );

// Connect to the WebSocket server
$ws = new WebSocketClient( 'ws://localhost:1729' );

// Send some messages
$ws->send('Foo');
$ws->send('Bar');
$ws->send('Baz');

// Close the connection
$ws->close();

// Now do the same for SSL
$ws = new WebSocketClient( 'wss://localhost:1730' );
$ws->send('Foo');
$ws->send('Bar');
$ws->send('Baz');
$ws->close();

