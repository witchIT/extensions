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
$::basedir = realpath( dirname( __FILE__ ) );
$::log = "/var/www/dcs/wiki-settings/jobs/jobs.log";
$::port = $ARGV[0];

# Fork off and die
defined ( my $pid = fork ) or die "Can't fork: $!";
exit if $pid;
open STDIN, '/dev/null';
open STDOUT, '>>', $::log;
binmode STDOUT, ':utf-8';
open STDERR, '>>', $::log;
binmode STDERR, ':utf-8';

# Keep a record of client instances associated with their IDs
%::clients = {};

# Set up the WebSocket listerner
Net::WebSocket::Server->new(
    listen => $::port,
    on_connect => sub {
        my ($serv, $conn) = @_;
        $conn->on(
            utf8 => sub {
                my ($conn, $msg) = @_;
				my $from = 0;

				# If this client sent an ID, store them in the client hash
				if( $msg =~ /"from"\s*:\s*"(.+?)"/s ) {
					$from = $1;
					$::clients{$from} = $conn;
				}

				# TODO: How do we detect a client closing?

				# TODO: If recipients were listed, forward message to each
				
				# No recipients, broadcast message to all clients
				foreach( keys %::clients ) {
					$c = $::clients{$_};
					if( defined $c ) { $c->send_utf8( $msg ) }
					else { delete $::clients{$_} }
				}
            },
        );
    },
)->start;

