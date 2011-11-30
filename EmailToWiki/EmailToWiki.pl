#!/usr/bin/perl
#
# EmailToWiki extension - allows emails to be sent to the wiki and imported as an article
# Started: 2007-05-25, version 2 started 2011-11-13
# Contact: neill@prescientsoftware.co.uk
#
# Copyright (C) 2008-2010 Aran Dunkley
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html
#
# - dependencies: libnet-imap-simple-ssl-perl, libemail-mime-perl
#
use Net::POP3;
use Net::IMAP::Simple;
use Net::IMAP::Simple::SSL;
use Email::MIME;
use HTTP::Request;
use LWP::UserAgent;
use strict;
$::ver   =  '2.1.0 (2011-11-30)';

# Determine log file, tmp file and program directory
$0 =~ /^(.+)\..+?$/;
$::log  = "$1.log";
$0 =~ /^(.+)\/.+?$/;
$::dir = $1;
logAdd( "EmailToWiki.pl $::ver started" );

# Loop through all the *.conf files found in the programs directory
opendir( CONF, $::dir ) or die $!;
while ( $::config = readdir( CONF ) ) {
	next if( $::config !~ /([^\/]+)\.conf$/ );
	$::prefix = $1;
	logAdd( "Processing configuration file \"$1.conf\"" );

	# Create a tmp directory to store the collected email and attachment data for this configuration
	$::tmp = "$::dir/$1.tmp";
	mkdir $::tmp unless -e $::tmp;

	# Set the globals from the config file
	require $::config;

	# Process messages in a POP3 mailbox
	if( $::type eq 'POP3' ) {
		if( my $server = Net::POP3->new( $::host ) ) {
			logAdd( "Connected to $::type server \"$::host\"" );
			my $login = $server->login( $::user, $::pass );
			if( defined $login ) {
				logAdd( "Logged \"$::user\" into $::type server \"$::host\" ($login)" );
				if( $login eq '0E0' ) { logAdd( "No messages" ) }
				else {
					for my $msg ( keys %{ $server->list() } ) {
						my $content = join "\n", @{ $server->top( $msg, $::limit ) };
						processEmail( $content );
						$server->delete( $msg ) if $::remove;
					}
				}
			} else { logAdd( "Couldn't log \"$::user\" into $::type server \"$::host\"" ) }
			$server->quit();
		} else { logAdd( "Couldn't connect to $::type server \"$::host\"" ) }
	}

	# Process messages in an IMAP mailbox
	elsif( $::type eq 'IMAP' ) {
		if( my $server = new Net::IMAP::Simple::SSL( $::host ) ) {
			if( $server->login( $::user, $::pass ) > 0 ) {
				logAdd( "Logged \"$::user\" into $::type server \"$::host\"" );
				my $msg = $server->select( 'Inbox' );
				while( $msg > 0 ) {
					my $fh = $server->getfh( $msg );
					sysread $fh, ( my $content ), $::limit;
					close $fh;
					processEmail( $content );
					$server->delete( $msg ) if $::remove;
					$msg--;
				}
			} else { logAdd( "Couldn't log \"$::user\" into $::type server \"$::host\"" ) }
			$server->quit();
		} else { logAdd( "Couldn't connect to $::type server \"$::host\"" ) }
	}

	# Tell wiki to import any unprocessed messages
	my $ua = LWP::UserAgent->new( agent => 'Mozilla/5.0', max_size => 1000 );
	my $res = $ua->get( "$::wiki?action=emailtowiki&prefix=$::prefix" );
	logAdd( "PHP returned output: " . $res->content . "..." ) if $res->content;
}

# Finished
closedir( CONF );
exit( 0 );


# Parse content from a single message
# - upload attachments to wiki
# - create article in wiki with attachments linked
sub processEmail {
	my $email = shift;

	# Test if lines are doubled up and fix if so
	$email =~ s/\n\n/\n/g if $email =~ /Delivered-To: \S+\n\n/s;

	# Extract the useful header portion of the message
	my $id      = $1 if $email =~ /^message-id:\s*<(.+?)>\s*$/mi;
	my $date    = $1 if $email =~ /^date:\s*(.+?)\s*$/mi;
	my $to      = $1 if $email =~ /^to:\s*(.+?)\s*$/mi;
	my $from    = $1 if $email =~ /^from:\s*(.+?)\s*$/mi;
	my $subject = $1 if $email =~ /^subject:\s*(.+?)\s*$/im;

	# Create unique title according to $::format
	my $title = friendlyTitle( eval "\"$::format\"" );

	# Create directory of the title name for any attachments (bail if exists already)
	my $dir = "$::tmp/$title";
	return if -e $dir;
	mkdir $dir;
	qx( chown $::owner:$::owner "$dir" );

	# Loop through attachments uploading each
	my $body = "";
	Email::MIME->new( $email )->walk_parts( sub {
		my( $part ) = @_;
		if( $part->content_type =~ /\bname="([^"]+)"/ ) {
			my $name = friendlyTitle( $1 );
			my $file = $dir . '/__' . $id . '_' . $name;

			# Extract attachments from message and save in $::tmp
			logAdd( "Extracting attachment $file" );
			open my $fh, ">", $file or return logAdd( "Failed to open attachment $file: $!" );
			print $fh $part->content_type =~ m!^text/! ? $part->body_str : $part->body
				or return logAdd( "Failed to write attachment $file: $!" );
			close $fh or return logAdd( "Failed to close attachment $file: $!" );
			qx( chown $::owner:$::owner "$file" );
		} else {
			my $text = $part->content_type =~ m!^text/! ? $part->body_str : $part->body;
			$body .= $text unless $text =~ /This is a multi-part message in MIME format/i;
		}
	} );

	# Create the article content
	$body =~ s/\s*<!DOCTYPE[^>]+>\s*//s;
	$body =~ s/\s*<head>.+?<\/head>\s*//s;
	$body =~ s/\s*<\/?body>\s*//sg;
	$body =~ s/\r//g;
	my $text = "{{$::template
 | id      = $id
 | date    = $date
 | to      = $to
 | from    = $from
 | subject = $subject
}}
$body";
	
	# Save the content for importing with attachments
	my $file = "$dir/_BODYTEXT_";
	open FH, ">", $file or return logAdd( "Failed to open attachment $file: $!" );
	binmode FH, ":utf8";
	print FH $text or return logAdd( "Failed to write attachment $file: $!" );
	close FH or return logAdd( "Failed to close attachment $file: $!" );
	qx( chown $::owner:$::owner "$file" );
}


# Output an item to the email log file with timestamp
sub logAdd {
	my $entry = shift;
	open LOGH, '>>', $::log or die "Can't open $::log for writing!";
	binmode LOGH, ":utf8";
	print LOGH "PERL (" . localtime() . ") : $entry\n";
	close LOGH;
	return $entry;
}


# Make the passed string ok for a wiki article title
sub friendlyTitle {
	my $title = shift;
	$title =~ s/[^-_ \(\)\[\]:;'"?.,%$@!+a-zA-Z0-9]+/-/g;
	return $title;
}
