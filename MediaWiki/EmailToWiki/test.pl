#!/usr/bin/perl

open FH, '<', "./test.eml";
sysread FH, $body, -s "./test.eml";
close FH;

		my @tmp = $body =~ /[>:]([-a-z0-9_.]+\@[-a-z0-9_.]+)["<]/gi;
		my %addrs = ();
		$addrs{$_} = 1 for @tmp;
		my @addrs = keys %addrs;
		
print "$_\n" for @addrs;
