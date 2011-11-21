#!/usr/bin/perl
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
# - Usage:   This script should be called periodically (eg. from crond)
# - Docs:	 http://www.mediawiki.org/wiki/Extension:EmailToWiki
# - Author:  http://www.organicdesign.co.nz/nad
# - Started: 2007-05-25, version 2 started 2011-11-13
use Net::POP3;
use Email::MIME;
use HTTP::Request;
use LWP::UserAgent;
use strict;
$ver   =  '2.0.0 (2011-11-13)';

# Determine log and config file
$0 =~ /^(.+)\..+?$/;
$log  = "$1.log";
require "$1.conf";

# Location to store emails to be processed by wiki (create if nonexistent)
$::tmp = "$1.tmp";
mkdir $::tmp unless -e $::tmp;

# Process messages in a POP3 mailbox
if ( $type eq 'POP3' ) {
	if ( my $server = Net::POP3->new( $host ) ) {
		logAdd( "$t Connected to $args{proto} server \"$args{host}\"" );
		if ( $server->login( $user, $pass ) > 0 ) {
			logAdd( "$t Logged \"$args{user}\" into $args{proto} server \"$args{host}\"" );
			for ( keys %{ $server->list() } ) {
				my $content = join "\n", @{ $server->top( $_, $limit ) };
				processEmail( $content );
			}
		} else { emalogAddilLog( "$t Couldn't log \"$args{user}\" into $args{proto} server \"$args{host}\"" ) }
		$server->quit();
	} else { logAdd( "$t Couldn't connect to $args{proto} server \"$args{host}\"" ) }
}

# Process messages in an IMAP mailbox
elsif ( $type eq 'IMAP' ) {
	if ( my $server = new Net::IMAP::Simple::SSL( $host ) ) {
		if ( $server->login( $user, $pass ) > 0 ) {
			logAdd( "$t Logged \"$args{user}\" into IMAP server \"$args{host}\"" );
			my $i = $server->select( $path or 'Inbox' );
			while ( $i > 0 ) {
				my $fh = $server->getfh( $i );
				sysread $fh, ( my $content ), $limit;
				close $fh;
				processEmail( $content );
				$i--;
			}
		} else { logAdd( "$t Couldn't log \"$args{user}\" into $args{proto} server \"$args{host}\"" ) }
		$server->quit();
	} else { logAdd( "$t Couldn't connect to $args{proto} server \"$args{host}\"" ) }
}

# Tell wiki to import any unprocessed messages
LWP::UserAgent->new( agent => 'Mozilla/5.0' )->get( "$wiki?action=emailtowiki" );

# Finished
exit(0);


# Parse content from a single message
# - upload attachments to wiki
# - create article in wiki with attachments linked
sub processEmail {
	my $email = shift;

	# Extract useful header information
	my %message = ();
	$message{id}      = $1 if $email =~ /^message-id:\s*(.+?)\s*$/mi;
	$message{date}    = $1 if $email =~ /^date:\s*(.+?)\s*$/mi;
	$message{to}      = $1 if $email =~ /^to:\s*(.+?)\s*$/mi;
	$message{from}    = $1 if $email =~ /^from:\s*(.+?)\s*$/mi;
	$message{subject} = $1 if $email =~ /^subject:\s*(.+?)\s*$/im;

	# Create unique title according to title-format
	return if title exists in wiki;

	# Create directory of the title name for any attachments
	mkdir "$::tmp/$title";

	# Loop through attachments uploading each
	my $body = "";
	Email::MIME->new( $email )->walk_parts( sub {
		my( $part ) = @_;
		if( $part->content_type =~ /\bname="([^"]+)"/ ) {
			my $file = "$::tmp/$title/$1";

			# Extract attachments from message and save in $::tmp
			logAdd( "Extracting attachment $file" );
			open my $fh, ">", $file or return logAdd( "Failed to open attachment $file: $!" );
			print $fh $part->content_type =~ m!^text/! ? $part->body_str : $part->body
				or return logAdd( "Failed to write attachment $file: $!" );
			close $fh or return logAdd( "Failed to close attachment $file: $!" );
			chown $file, 'www-data';
		} else {
			$body .= $part->content_type =~ m!^text/! ? $part->body_str : $part->body;
		}
	} );
	
	# Save the body-text and header info
	my $file = "$::tmp/$title/__BODYTEXT";
	open my $fh, ">", $file or return logAdd( "Failed to open attachment $file: $!" );
	print $fh $body or return logAdd( "Failed to write attachment $file: $!" );
	close $fh or return logAdd( "Failed to close attachment $file: $!" );
}


# Output an item to the email log file with timestamp
sub logAdd {
	my $entry = shift;
	open LOGH, '>>', $::log or die "Can't open $::log for writing!";
	print LOGH localtime() . " : $entry\n";
	close LOGH;
	return $entry;
}
