#!/usr/bin/perl
require('/var/www/tools/wiki.pl');

wikiLogin( $wiki, $user, $pass ) or die "Couldn't log into wiki!";
open CSV, '<', $file             or die "Could not open CSV file '$file'!";
for (<CSV>) {
	s/^"//; s/"\s*$//;
	( $en, $gr, $url, $cats, $src ) = split /";"/;
	$url   = "[$1 $2]" if $url =~ /<a href='*(.+?)'*>(.+?)<\/a>/;
	$gr    =~ s/\s*;;\s*/ &#0124; /g;
	$text  = "{{Glossary\n | en  = $en\n | gr  = $gr | url = $url\n | src = $src\n}}\n\n";
	$text .= "[[Category:$_]]" for split '\s*;;\s*', $cats;
	wikiEdit( $wiki, $en, $text, "Glossary entry imported from row ".++$i." of $file" );
}

