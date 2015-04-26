#!/usr/bin/perl
use IO::Async::Loop;
use Net::Async::WebSocket::Client;

# Connect to the WebSocket
my $client = Net::Async::WebSocket::Client->new();
IO::Async::Loop->new->add( $client );

$client->connect(url => "ws://localhost:1729/" )->then()->get;

# Send some messages
$client->send_frame( "Foo" )->then()->get;
$client->send_frame( "Bar" )->then()->get;
$client->send_frame( "Baz" )->then()->get;
