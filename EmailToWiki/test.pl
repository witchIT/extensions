#!/usr/bin/perl

open FH, '<', "./test.eml";
sysread FH, $body, -s "./test.eml";
close FH;

my @addrs = $body =~ /[>:]([-a-z0-9_.]+\@[-a-z0-9_.]+)["<]/gi;

print "$_\n" for @addrs;
