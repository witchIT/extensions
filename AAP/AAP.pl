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
	my @index = ( 0 );
	if ( open INPUT, '<', $file ) {
		push @index, tell INPUT while <INPUT>;
    }
    
	# Couldn't open file
    else {
		workLogError( "Couldn't open input file \"$file\", job aborted!" );
		workStopJob();
	}	

	$$::job{'index'} = \@index;
	$$::job{'length'} = 1 + $#index;

	1;
}

sub mainAAPImport {
	my $wiki   = $$::job{'wiki'};
	my $file   = $$::job{'file'};
	my $ptr    = $$::job{'wptr'};
	my $offset = $$::job{'index'}[$ptr];
	my $length = $$::job{'index'}[$ptr + 1] - $offset;
	$$::job{'status'} = "Processing record $ptr";
	my $cur = wikiRawPage( $wiki, $title );
	$$::job{'revisions'}++ if wikiEdit( $wiki, $title, $text, "Content imported from \"$file\"" ) && $cur ne $text;
	1;
}

sub stopAAPImport {
	1;
}
