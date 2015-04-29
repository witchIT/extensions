#!/usr/bin/perl
use AnyEvent::WebSocket::Client;

my $host = $ARGV[0] || '127.0.0.1';
my $port = $ARGV[1] || 1729;

# Connect to the WebSocket server
my $client = AnyEvent::WebSocket::Client->new();
my $ready = AnyEvent->condvar;
$client->connect( "ws://$host:$port" )->cb(sub {
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

# Now thw same for SSL
$port++;
my $client = AnyEvent::WebSocket::Client->new( ssl_no_verify => 1 );
my $ready = AnyEvent->condvar;
$client->connect( "wss://$host:$port" )->cb(sub {
	our $ws = eval { shift->recv };
	die $@ if $@;
	$ready->send;
});
$ready->recv();
$ws->send('foo');
$ws->send('bar');
$ws->send('baz');
$ws->close;
