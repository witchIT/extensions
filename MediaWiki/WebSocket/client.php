<?php
require( __DIR__ . '/WebSocketClient.class.php' );

// Connect to the WebSocket server
$ws = new WebSocketClient( 'wss://localhost:1729' );

// Send some messages
$ws->send('Foo');
$ws->send('Bar');
$ws->send('Baz');

// Close the connection
$ws->close();

