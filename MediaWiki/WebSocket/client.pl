#!/usr/bin/perl
use AnyEvent::WebSocket::Client;
 
my $client = AnyEvent::WebSocket::Client->new(ssl_no_verify => 1);
my $connected = AnyEvent->condvar;
$client->connect("wss://localhost:1729")->cb(sub {
	our $connection = eval { shift->recv };
	die $@ if $@;
	$connected->send;
});
$connected->recv();

$connection->send('foo');
$connection->send('bar');
$connection->send('baz');

$connection->close;
