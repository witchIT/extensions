#!/usr/bin/perl
use AnyEvent::WebSocket::Client;
 
# Connect to the WebSocket server
my $client = AnyEvent::WebSocket::Client->new( ssl_no_verify => 1 );
my $ready = AnyEvent->condvar;
$client->connect( 'wss://localhost:1729' )->cb(sub {
	our $ws = eval { shift->recv };
	die $@ if $@;
	$ready->send;
});
$ready->recv();

# Send some messages
$ws->send('foo');
$ws->send('bar');
$ws->send('baz');

# Close the connection
$ws->close;
