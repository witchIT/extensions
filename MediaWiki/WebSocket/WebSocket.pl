#!/usr/bin/perl
#
# Server daemon for the MediaWiki WebSocket extension
#
# See http://www.organicdesign.co.nz/Extension:WebSocket for details
# 
# Author Aran Dunkley (http://www.organicdesign.co.nz/aran Aran Dunkley)
# Copyright © 2015 Aran Dunkley
# Licence: GNU General Public Licence 2.0 or later
#
use IO::Async::Loop;
use Net::WebSocket::Server;   # See https://metacpan.org/pod/Net::WebSocket::Server
use Data::Dumper;
use File::Basename;
use Cwd qw(realpath);
$::basedir  = realpath( dirname( __FILE__ ) );
$::port     = $ARGV[0];
$::log      = $ARGV[1];
$::rewrite  = $ARGV[2];
$::ssl_cert = $ARGV[3];
$::ssl_key  = $ARGV[4];

# Fork off and die
defined ( my $pid = fork ) or die "Can't fork: $!";
exit if $pid;
open STDIN, '/dev/null';
open STDOUT, '>>', $::log;
binmode STDOUT, ':utf-8';
open STDERR, '>>', $::log;
binmode STDERR, ':utf-8';
setsid or die "Can't start a new session: $!";
umask 0;
$0 = "WebSocket.pl:$::port";

# Open log file if supplied
open LOG, '>>', $::log if $::log;
binmode LOG, ':utf-8';
autoflush LOG 1;
$sslmsg = $::ssl_cert ? ' (using SSL)' : '';
print LOG "WebSocket daemon starting on port $::port$sslmsg\n" if $::log;

# Keep a record of client instances associated with their IDs
%::clients = {};

# Use an SSL socket instead of port for listen parameter if SSL enabled
my $listen = $::port;
if( $::ssl_cert ) {
	$listen = IO::Socket::SSL->new(
	  Listen        => 5,
	  LocalPort     => $::port,
	  Proto         => 'tcp',
	  SSL_cert_file => $::ssl_cert,
	  SSL_key_file  => $::ssl_key,
	) or die "failed to listen: $!";
}

# Set up the WebSocket listerner
Net::WebSocket::Server->new(
    listen => $listen,
    on_connect => sub {
        my ($serv, $conn) = @_;
        $conn->on(
            utf8 => sub {
                my ($conn, $msg) = @_;
				my $from = 0;
				my $peeraddr = join( '.', unpack( 'C4', $conn->{socket}->peeraddr() ) );

				# Disconnect the client if in rewrite mode and this is not local
				if( $::rewrite and $peeraddr ne '127.0.0.1' ) {
					print LOG "Disconnecting non-local client\n" if $::log;
					$conn->disconnect();
				}

				else {
					print LOG "Message received ($peeraddr): $msg\n" if $::log;

					# If this client sent an ID, store them in the client hash
					if( $msg =~ /"from"\s*:\s*"(.+?)"/s ) {
						$from = $1;
						$::clients{$from} = $conn;
						print LOG "From: $from\n" if $::log;
					}

					# TODO: If recipients were listed, forward message to each
					
					# No recipients, broadcast message to all clients (except sender)
					foreach( keys %::clients ) {
						if( $_ ne $from ) {
							$c = $::clients{$_};
							if( defined $c and $c->{socket}->connected ) { $c->send_utf8( $msg ) }
							else { delete $::clients{$_} }
						}
					}
				}
            },
        );
    },
)->start;
