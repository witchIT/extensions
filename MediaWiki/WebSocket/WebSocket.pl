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
use IO::Socket::SSL;
use IO::Socket::INET;
use Net::WebSocket::Server;   # See https://metacpan.org/pod/Net::WebSocket::Server
use Data::Dumper;

# Set upglobal config
our( $port, $log, $rewrite, $ssl_cert, $ssl_key ) = @ARGV;

# Fork off and die
defined ( my $pid = fork ) or die "Can't fork: $!";
exit if $pid;
open STDIN, '/dev/null';
open STDOUT, '>>', $log;
binmode STDOUT, ':utf-8';
autoflush STDOUT 1;
open STDERR, '>>', $log;
binmode STDERR, ':utf-8';
autoflush STDERR 1;
setsid or die "Can't start a new session: $!";
umask 0;

# Set up the non-SSL WebSocket listener on $port
my $sock = IO::Socket::INET->new(
	Listen    => 5,
	LocalPort => $port,
	Proto     => 'tcp',
	Domain    => AF_INET,
	ReuseAddr => 1,
) or die "failed to set up TCP listener: $!";

my $tcp = Net::WebSocket::Server->new(
	listen => $sock,
	on_connect => sub {
		my( $serv, $conn ) = @_;
		$conn->on( utf8 => sub{ processMessage( @_, 0 ) } );
	},
);
$tcp->{select_readable}->add($sock);
$tcp->{conns} = {};

# Use SSL info supplied, set up an SSL listener on $port + 1
my $ssl = 0;
if( $ssl_cert ) {

	my $ssl_sock = IO::Socket::SSL->new(
		Listen        => 5,
		LocalPort     => $port + 1,
		Proto         => 'tcp',
		Domain        => AF_INET,
		SSL_cert_file => $ssl_cert,
		SSL_key_file  => $ssl_key,
		ReuseAddr => 1,
	) or die "failed to set up SSL listener: $!";

	$ssl = Net::WebSocket::Server->new(
		listen => $ssl_sock,
		on_connect => sub {
			my( $serv, $conn ) = @_;
			$conn->on( utf8 => sub{ processMessage( @_, 1 ) } );
		},
	);

	$ssl->{select_readable}->add($ssl_sock);
	$ssl->{conns} = {};
}

# Log startup
$sslmsg = $ssl_cert ? ' (and SSL on ' . ($port + 1) . ')' : '';
print "WebSocket daemon starting with PID $$ on port $port$sslmsg\n" if $log;

# Keep a record of client instances associated with their IDs
our %clients = {};

# Set up a loop to listen on both sockets
while(1) {
	serverLoop( $tcp );
	serverLoop( $ssl ) if $ssl;
}



# Do one iteration of the server loop for the passed socket (code based on Net::WebSocket::Server start method)
sub serverLoop {
	my $server = shift;
	$server->{'silence_nextcheck'} = ( $server->{silence_max} ? (time + $server->{silence_checkinterval}) : 0 ) unless defined $server->{'silence_nextcheck'};
	$silence_nextcheck = $server->{'silence_nextcheck'};
	if( %{$server->{conns}} || $server->{listen}->opened ) {
		my $silence_checktimeout = $server->{silence_max} ? ( $silence_nextcheck - time ) / 2 : undef;
		my( $ready_read, $ready_write, undef ) = IO::Select->select( $server->{select_readable}, $server->{select_writable}, undef, 0.1 );
		foreach my $fh ( $ready_read ? @$ready_read : () ) {
			if( $fh == $server->{listen} ) {
				my $sock = $server->{listen}->accept;
				next unless $sock;
				my $conn = new Net::WebSocket::Server::Connection( socket => $sock, server => $server );
				$server->{conns}{$sock} = {conn=>$conn, lastrecv=>time};
				$server->{select_readable}->add( $sock );
				$server->{on_connect}( $server, $conn );
			} elsif( $server->{watch_readable}{$fh} ) {
				$server->{watch_readable}{$fh}{cb}( $server, $fh );
			} elsif( $server->{conns}{$fh} ) {
				my $connmeta = $server->{conns}{$fh};
				$connmeta->{lastrecv} = time;
				$connmeta->{conn}->recv();
			} else {
				warn "filehandle $fh became readable, but no handler took responsibility for it; removing it";
				$server->{select_readable}->remove( $fh );
			}
		}

		foreach my $fh ( $ready_write ? @$ready_write : () ) {
			if( $server->{watch_writable}{$fh} ) {
				$server->{watch_writable}{$fh}{cb}( $server, $fh );
			} else {
				warn "filehandle $fh became writable, but no handler took responsibility for it; removing it";
				$server->{select_writable}->remove( $fh );
			}
		}

		if( $server->{silence_max} ) {
			my $now = time;
			if( $silence_nextcheck < $now ) {
				my $lastcheck = $silence_nextcheck - $server->{silence_checkinterval};
				$_->{conn}->send('ping') for grep { $_->{lastrecv} < $lastcheck } values %{$server->{conns}};
				$silence_nextcheck = $now + $server->{silence_checkinterval};
			}
		}
	}
	$server->{'silence_nextcheck'} = $silence_nextcheck;
}

# Process an incoming message
sub processMessage {
	my( $conn, $msg, $ssl ) = @_;
	$ssl = $ssl ? ',SSL' : '';
	my $from = 0;
	my $peeraddr = join( '.', unpack( 'C4', $conn->{socket}->peeraddr() ) );

	# Disconnect the client if in rewrite mode and this is not local
	if( $rewrite and $peeraddr ne '127.0.0.1' ) {
		print "Disconnecting non-local$ssl client ($peeraddr)\n" if $log;
		$conn->disconnect();
	}

	else {
		my $type = $msg =~ /"type"\s*:\s*"(.+?)"/s ? $1 : '';
		my $typemsg = $type ? "$type," : '';
		print "Message received ($typemsg$peeraddr$ssl)\n" if $log;

		# If this client sent an ID, store them in the client hash
		if( $msg =~ /"from"\s*:\s*"(.+?)"/s ) {
			$from = $1;
			$clients{$from} = $conn;
			print "\tFrom: $from\n" if $log;
		}

		# Only forward message if it's not a registration
		if( $type ne 'Register' ) {

			# Extract recipients
			my $to = $msg =~ /"to"\s*:\s*"(.*?)"/s ? $1 : '';
			my @recipients = ();
			if( $to ) {
				$to =~ s/[^0-9,]//;
				@recipients = split /,/, $to;
			}

			# Send to recipients, or roadcast message if none specified
			foreach my $k ( keys %clients ) {
				if( $k ne $from and ( $to eq '' or grep { $_ eq $k } @recipients ) ) {
					$client = $clients{$k};
					if( defined $client and $client->{socket}->connected ) {
						print "\tForwarding to $k\n" if $log;
						$client->send_utf8( $msg )
					} else { delete $clients{$k} }
				}
			}
		}
	}
}
