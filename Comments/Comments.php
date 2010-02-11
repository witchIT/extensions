<?php
/**
 * Comments extension
 * - Changes discussion page behaviour to a more forum-like format below the article
 *
 * See http://www.organicdesign.co.nz/Extension:Comments for development notes and disucssion
 *
 * Started: 2010-02-11
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2010 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'COMMENTS_VERSION', '0.0.1, 2010-02-11' );

$wgExtensionFunctions[]        = 'wfSetupComments';

$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'	      => 'Comments',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Changes discussion page behaviour to a more forum-like format below the article',
	'url'	      => 'http://www.organicdesign.co.nz/Extension:Comments',
	'version'     => COMMENTS_VERSION
);

class Comments {

	function Comments() {
		global $wgHooks, $wgParser, $wgPdfBookMagic;
		$wgHooks['UnknownAction'][] = $this;

	# change the discussion link to go to bottom of page
	
	# render the bottom of page
	# - <a name> for link to go to
	# - loop using stripes classes and auto-sign
	# - reply and new-thread buttons

	# - Ajaxly update the talk article

	}

}

function wfSetupComments() {
	global $wgComments;
	$wgComments = new Comments();
}
