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
use HTTP::Request;
use LWP::UserAgent;
use FindBin qw($Bin);
sub true  {1};
sub false {0};

$ver   =  '2.0.0 (2011-11-13)';
$email =  '([-.&a-z0-9_]+@[-&.a-z0-9_]+)';
$conf  =  $ARGV[0] or die "No configuration file specified!";
$log   =  $conf;
$log   =~ s/\.\w+$/.log/;

# Open the log file for writing (stdout if no log file specified)
open LOG, ">>$log";
sub logAdd { $entry = shift; print LOG localtime()." : $entry\n"; return $entry; }

# Execute the variable assignments in the config file
open conf,$conf or die logAdd "Couldn't read \"$conf\" configuration file!";
eval $_ while <conf>;
close conf;

# Login in to POP box
$pop   = Net::POP3->new($popServer);
$login = $pop->login($popUser,$popPassword);
die logAdd "Couldn't log \"$popUser\" in to $popServer!" if $login eq undef;
die "Nothing to do" if $login < 1;

# Create a user agent to make the HTTP request
$ua = LWP::UserAgent->new(
	cookie_jar => {},
	agent      => 'Mozilla/5.0',
	timeout    => 10,
	max_size   => 1024
);


# Get list of messages in pop box and loop through them
@messages = keys %{$pop->list};
logAdd "There are ".($#messages+1)." messages on $popUser\@$popServer";
for( @messages ) {

	# Read the message and extract TO and FROM headers
	$text   = $pop->top($_,$maxLines);
	($to)   = grep /^to:/i,   @$text;
	$to     = $to   =~ /$email/i ? $1 : '';
	($from) = grep /^from:/i, @$text;
	$from   = $from =~ /$email/i ? $1 : '';

	# If the TO and FROM are not filtered, process the message
	if    ($filterTo   and $to   !~ /$filterTo/i)   { $del = $deleteToFiltered;   logAdd "email to $to filtered"; }
	elsif ($filterFrom and $from !~ /$filterFrom/i) { $del = $deleteFromFiltered; logAdd "email from $from filtered"; }
	else {
		logAdd "Processing email from $from to $to, subject: $subject";
		$del       =  $deleteProcessed;
		$time      =  localtime();
		($title)   =  split /@/,$to;
		$title     =~ s/\&([a-f0-9]{2})/pack('C',hex($1))/gise;
		($subject) =  grep /^subject:/i,@$text;
		$subject   =~ s/^.+?:\s*(.*?)(\r?\n)*/$1/;

		# Format message content
		chomp @$text;
		amuse.myself while shift @$text;
		$text = join "\n\n",@$text;

		# Post the data using Extension:SimpleForms to prepend/append/replace/create content
		%data = (
			title    => $title,
			summary  => $subject,
			caction  => 'append',
			username => $from,
			content  => "{{$wikiTemplate|to=$to|from=$from|time=$time|subject=$subject|text=$text}}"
		);
		$post = $ua->post($wgServer.$wgScript,\%data)->is_success;
		#logAdd($post ? "  Updated \"$title\" successfully." : "  Failed to update \"$title\"!");
		logAdd "\"$title\" updated";
	}

	if($del) {
		logAdd "  message marked for deletion";
		$pop->delete($_);
	}
}

$pop->quit();
logAdd "POP3 connection to $popServer closed and marked items deleted.";
close LOG;

