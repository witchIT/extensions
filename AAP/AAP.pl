#!/usr/bin/perl
#
# Subroutines for AAP import called by wikid.pl (Organic Design wiki daemon)
#
# @author Aran Dunkley http://www.organicdesign.co.nz/nad
# @copyright © 2009 Debt Compliance Services
#

sub initAAPImport {
	$$::job{'titles'} = {};
	$$::job{'work'} = [];
	my $file = $$::job{'file'};

	# Index the byte offsets of each line in the source file
	my @index = ();
	open INPUT, '<', $file
	
	$offset = tell($data_file);
	


	# Preprocess the workload to check for errors
	my $line = 1;
	my $errors = 0;
	$$::job{'status'} = "Preprocessing \"$file\"";
	if ( open INPUT, '<', $file ) {
		$$::job{'length'} = 1 + $#{$$::job{'work'}};
	}

	1;
}

sub mainDcsImport {
	my $wiki  = $$::job{'wiki'};
	my $title = $$::job{'work'}[$$::job{'wptr'}];
	my $text  = readFile( '/tmp/' . $$job{'id'} . '-' . encode_base64( $title ) );
	my $file  = $$::job{'file'};
	$$::job{'status'} = "Processing \"$title\"";
	my $cur = wikiRawPage( $wiki, $title );
	$$::job{'revisions'}++ if wikiEdit( $wiki, $title, $text, "Content imported from \"$file\"" ) && $cur ne $text;
	1;
}

sub stopDcsImport {
	1;
}


#---------------------------------------------------------------------------------------------------------#
# DEBUGGING

# Include this for stand alone testing
if ( 0 ) {
	use DBI;
	require( 'wiki.pl' );
	$wiki = 'http://114.localhost/wiki/index.php';
	wikiLogin( $::wiki, 'Nad', '*******' );
	$::dbname = 'svn';
	$::dbuser = 'root';
	$::dbpass = '*******';
	$::dbpre = '';
	$::db = DBI->connect( "DBI:mysql:$::dbname", $::dbuser, $::dbpass );
}

1;






