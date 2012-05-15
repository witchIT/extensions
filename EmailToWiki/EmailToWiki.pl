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
use utf8;
use Encode;
use strict;
$::ver   =  '2.2.8, 2012-05-15';

# Determine log file, tmp file and program directory
$0 =~ /^(.+)\..+?$/;
$::log  = "$1.log";
$0 =~ /^(.+)\/.+?$/;
$::dir = $1;
logAdd( "EmailToWiki.pl $::ver started" );

# Loop through all the *.conf files found in the programs directory
opendir( CONF, $::dir ) or die $!;
while( $::config = readdir( CONF ) ) {
	next if( $::config !~ /([^\/]+)\.conf$/ );
	$::prefix = $1;
	$::tmp = "$::dir/$1.tmp";
	logAdd( "Processing configuration file \"$1.conf\"" );

	# Set default configuration options
	$::limit = 1000000;
	$::format = "Email:\$id (\$subject)";
	$::owner = "www-data";
	$::remove = 0;
	$::template = "Email";
	$::emailonly = 1;
	$::html_only = 0;
	$::fromfilter = 0;

	# Set the globals from the config file
	require "$::dir/$::config";

	# Create a tmp directory to store the collected email and attachment data for this configuration
	mkdir $::tmp unless -e $::tmp;
	my( $login, $pass, $uid, $gid ) = getpwnam( $::owner );
	chown $uid, $gid, $::tmp;

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
	$email =~ s/\n\n/\n/g if $email =~ /Message-ID: \S+\n\n/s;

	# Extract the useful header portion of the message
	my $id      = $1 if $email =~ /^message-id:\s*<(.+?)>\s*$/mi;
	my $date    = $1 if $email =~ /^date:\s*(.+?)\s*$/mi;
	my $to      = $1 if $email =~ /^to:\s*(.+?)\s*$/mi;
	my $from    = $1 if $email =~ /^from:\s*(.+?)\s*$/mi;
	my $subject = $1 if $email =~ /^subject:\s*(.+?)\s*$/im;

	# Support for MIME encoded
	$from    = decode( "MIME-Header", $from );
	$to      = decode( "MIME-Header", $to );
	$subject = decode( "MIME-Header", $subject );

	# Ensure the utf8 encoding
	# FIXME: guess the original encoding!
	Encode::from_to( $from,    "iso-8859-2", "utf8" ) if !utf8::is_utf8( $from );
	Encode::from_to( $to,      "iso-8859-2", "utf8" ) if !utf8::is_utf8( $to );
	Encode::from_to( $subject, "iso-8859-2", "utf8" ) if !utf8::is_utf8( $subject );

	# Extract only real email address portion
	if( $::emailonly ) {
		$from = $1 if $from =~ /<(.+?)>$/;
		$to = $1 if $to =~ /<(.+?)>$/;
	}

	# Create unique title according to $::format
	my $title = friendlyTitle( eval "\"$::format\"" );

	# Create directory of the title name for any attachments (bail if exists already)
	my $dir = "$::tmp/$title";
	return if -e $dir;
	mkdir $dir;
	my( $login, $pass, $uid, $gid ) = getpwnam( $::owner );
	chown $uid, $gid, $dir;

	# Loop through attachments uploading each
	# - Separate the email body to 3 parts
	my $plain_body = "";
	my $html_body = "";
	my $other_body = "";
	Email::MIME->new( $email )->walk_parts( sub {
		my( $part ) = @_;
		if( $part->content_type =~ /\bname="([^\"]+)"/ ) {
			my $name = friendlyTitle( $1 );
			my $file = $dir . '/__' . $id . '_' . $name;

			# Extract attachments from message and save in $::tmp
			logAdd( "Extracting attachment $file" );
			open my $fh, ">", $file or return logAdd( "Failed to open attachment $file: $!" );
			print $fh $part->content_type =~ m!^text/! ? $part->body_str : $part->body
				or return logAdd( "Failed to write attachment $file: $!" );
			close $fh or return logAdd( "Failed to close attachment $file: $!" );
			chown $uid, $gid, $file;
		} else {
			if( $part->content_type =~ m!^text/html! ) {
				$html_body .= $part->body_str;
			} elsif( $part->content_type =~ m!^text/! ) {
				$plain_body .= $part->body_str;
			} else {
				$other_body .= $part->body unless $part->body =~ /This is a multi-part message in MIME format/i;
			}
		}
	} );

	my $body = "";
	# Create the article content
	$html_body =~ s/\s*<!DOCTYPE[^>]+>\s*//si;
	$html_body =~ s/\s*<head>.+?<\/head>\s*//si;
	$html_body =~ s/\s*<title>.+?<\/title>\s*//si;
	$html_body =~ s/\s*<style[^>]*>.+?<\/style>\s*//sgi; # Disable css
	$html_body =~ s/\s*<\/?body[^>]*>\s*//sgi;
	$html_body =~ s/\s*<\/?html[^>]*>\s*//sgi; # Strip html tags too

	# This feature needs $wgRawHtml = true setting!
	if( $html_body =~ /^\s*$/ ) { $::html_only = 0 } else { $body .= "<div name=\"html_part\"><html>\n$html_body</html></div>\n" }
	unless( $::html_only ) {
		$body .= "<div name=\"plain_part\"><pre>\n$plain_body</pre></div>\n" unless $plain_body =~ /^\s*$/;
		$body .= "<div name=\"other_part\"><pre>\n$other_body</pre></div>\n" unless $other_body =~ /^\s*$/;
	}
	$body =~ s/\r//g;

	# Add original address if this is a forwarded message
	my $forward = '';
	if( $subject =~ /^\s*fwd?:/i ) {
		my @addrs = $body =~ /[>:]([-a-z0-9_.]+@[-a-z0-9_.]["<]/gi;
		if( $#addrs >= 0 ) {
			if( $::emailonly ) {
				for( @addrs ) {
					$_ = $1 if /<(.+?)>$/;
				}
			}
			$forward = "\n | forward = " . join( ',', @addrs );
		}
	}

	my $filter = $::fromfilter ? "\n | filter = yes" : '';
	my $text = "{{$::template
 | id      = $id
 | date    = $date
 | to      = $to
 | from    = $from$forward$filter
 | subject = $subject
}}
$body";

	# Save the content for importing with attachments
	my $file = "$dir/_BODYTEXT_";
	open FH, ">", $file or return logAdd( "Failed to open attachment $file: $!" );
	binmode FH, ":utf8";
	print FH $text or return logAdd( "Failed to write attachment $file: $!" );
	close FH or return logAdd( "Failed to close attachment $file: $!" );
	chown $uid, $gid, $file;
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
	$title =~ tr/<{[/(/;
	$title =~ tr/>}]/)/;
	$title =~ tr/#|\\+/-/;
	return $title;
}

