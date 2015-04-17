#!/usr/bin/perl
use IO::Async::Loop;
use Net::Async::WebSocket::Client;
use Data::Dumper;

my $client = Net::Async::WebSocket::Client->new();

#my $poll = IO::Poll->new;
#my $loop = IO::Async::Loop::Poll->new( poll => $poll );
IO::Async::Loop->new->add( $client );

$client->connect(url => "ws://localhost:1729/")->then()->get;

sleep 1;

$client->send_frame( "Pong\n" )->then()->get;


$client->send_frame( "Pung\n" )->then()->get;
#while( not $::wsReady ) {
   #my $ret = $poll->poll( 1 );
  # $loop->post_poll;
#}
