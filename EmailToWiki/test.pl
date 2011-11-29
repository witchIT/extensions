#!/usr/bin/perl
use HTTP::Request;
use LWP::UserAgent;

$dir = '/var/www/extensions/EmailToWiki';
$::tmp = "$1.tmp";

# Remove the current data
qx( rm -r $dir/EmailToWiki.tmp );

# Restore some test email data from test.tmp
qx( cp -pR $dir/test.tmp $dir/EmailToWiki.tmp );

# Restore the wiki to its initial empty state
qx( /var/www/tools/add-db /var/www/empty-1.17.sql dev.dev_ );

# Call the PHP to process the test email data
my $ua = LWP::UserAgent->new( agent => 'Mozilla/5.0', max_size => 100 );
my $res = $ua->get( "http://localhost/wiki/index.php?action=emailtowiki" );
print "PHP returned output: " . $res->content . "..." if $res->content;
