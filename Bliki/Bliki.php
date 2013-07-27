<?php
# Adds "Bliki" (blog in a wiki) functionality
# See http://www.organicdesign.co.nz/bliki for details

if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'BLIKI_VERSION','2.0.0, 2013-07-27' );

$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'        => 'ChildrenAreWelcome',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad Aran Dunkley]',
	'url'         => 'http://www.childrenarewelcome.co.uk',
	'description' => 'Adds [[Bliki]] (blog in a wiki) functionality',
	'version'     => BLIKI_VERSION
);

$dir = dirname( __FILE__ );
include( "$dir/post-form-processor.php" );
include( "$dir/add-body-class.php" );
