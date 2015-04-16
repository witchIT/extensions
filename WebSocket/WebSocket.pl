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
use Net::Async::WebSocket::Server;
use File::Basename;
use Cwd qw(realpath);
$::basedir = realpath( dirname( __FILE__ ) );
$::log = "/var/www/dcs/wiki-settings/jobs/jobs.log";

# Fork off and die
defined ( my $pid = fork ) or die "Can't fork: $!";
exit if $pid;

open STDIN, '/dev/null';
open STDOUT, '>>', $::log;
binmode STDOUT, ':utf-8';
open STDERR, '>>', $::log;
binmode STDERR, ':utf-8';


 
my $server = Net::Async::WebSocket::Server->new(
   on_client => sub {
      my( undef, $client ) = @_;
      $client->configure(
         on_frame => sub {
            my( $self, $frame ) = @_;
            #$self->send_frame( $frame );
            open LOG, '>>', $::log;
            print LOG "ws recv: $frame";
            close LOG;
         },
      );
   }
);
 
my $loop = IO::Async::Loop->new;
$loop->add( $server );
$server->listen( service => $ARGV[0] )->get;
$loop->run;

