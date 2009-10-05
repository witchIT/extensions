#!/usr/bin/perl

# TITLE
# currently the first english term is used as the primary title
# all the other synonyms both en and gr are created as redirects to the primary title

# SYNONYMS
# these are terms which include ;; seprators
# currently these are converted to a pipe list but are not treated specially in any way

# INITIALISMS
# these are matched as all items that are all caps, in brackets, following a term
# when an initialism is encountered, it is created as a redirect to the main article for the term
# i.e. "term (ITEM)" becomes an article called "term (ITEM)" containing the term's definition, and also:
#                    an article called "ITEM" containing "#REDIRECT [[term (ITEM)]]"
#                    an article called "term" containing "#REDIRECT [[term (ITEM)]]"
# "term (Item)" where the item is not all caps, is treated as just a single term

# DUPLICATE ENTRIES
# currently if the title for a newly imported row already exists it will be overwritten
# the original content will still be available from the title's history as usual

require('/var/www/tools/wiki.pl');
require('/var/www/tools/wikid.conf');

# Log into the target wiki
wikiLogin( $wiki, $wikiuser, $wikipass ) or die "Couldn't log into wiki!";

# Loop through the lines of the input file
$file = $ARGV[0];
open CSV, '<', $file or die "Could not open CSV file '$file'!";
for ( <CSV> ) {

	# Extract the data from the row
	s/^"//; s/"\s*$//;
	( $en, $gr, $url, $cats, $src ) = split /";"/;
	$url    = "[$1 $2]" if $url =~ /<a href='*(.+?)'*>(.+?)<\/a>/;
	@enlist = split /\s*;;\s*/, $en;
	@grlist = split /\s*;;\s*/, $gr;
	$en     =~ s/\s*;;\s*/ &#0124; /g;
	$gr     =~ s/\s*;;\s*/ &#0124; /g;
	$title  = $enlist[0];

	# Create definition content including category links
	$text  = "{{Glossary\n | en  = $en\n | gr  = $gr\n | url = $url\n | src = $src\n}}\n\n";
	$text .= "[[Category:$_]]" for split '\s*;;\s*', $cats;

	# Create/overwrite the article
	wikiEdit( $wiki, $en, $text, "Glossary entry imported from row " . ++$i . " of $file" );

}

