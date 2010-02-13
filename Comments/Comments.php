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

$wgAjaxExportList[] = 'Comments::ajaxHandler';

$wgExtensionFunctions[] = 'wfSetupComments';

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

		$wgHooks['OutputPageBeforeHTML'][] = $this;

		# change the discussion link to go to bottom of page
		
		# render the bottom of page
		# - <a name> for link to go to
		# - loop using stripes classes and auto-sign
		# - reply and new-thread buttons

		# - Ajaxly update the talk article



		# Add an "edit with form" action link
		# - it should link to the <a name...> rendered at the bottom of the content
		# - it should be a link to the main content#talk if not on main content
		$wgHooks['SkinTemplateTabs'][] = $this;
		$qs = "wpType={$this->type}&wpRecord=" . $title->getPrefixedText();
		$this->acturl = Title::makeTitle( NS_SPECIAL, 'RecordAdmin' )->getLocalURL( $qs );

	}




	/**
	 * Render the comments at the end of rendered page
	 */
	function onOutputPageBeforeHTML( ) {
		
		# render an <a name...>
		
		# get the talk page content
		
		# extract structured thread/user based info
		# - official items are a group of colon-indented paragraphs following by a colon-indented sig
		# - if there is other content not in the correct format, prepend a warning and link to the actual talk article
		
		# build the output
		# - ajax: sajax_do_call( "Comments::ajaxHandler", [a, b] , callback );
		# - http://www.mediawiki.org/wiki/Manual:Ajax
		
		# add to page output
		
		return true;
	}

	/**
	 * Return the content for an AJAX request from the comments area
	 */
	function ajaxHandler() {
	}

}

function wfSetupComments() {
	global $wgComments;
	$wgComments = new Comments();
}
