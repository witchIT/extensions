<?php
/**
 * AjaxComments extension - Add comments to the end of the page that can be edited, deleted or replied to instead of using the talk pages
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'AJAXCOMMENTS_VERSION','0.0.1, 2012-06-26' );

$wgExtensionFunctions[] = 'wfSetupAjaxComments';
$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'        => 'AjaxComments',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad Aran Dunkley]',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:AjaxComments',
	'description' => 'Add comments to the end of the page that can be edited, deleted or replied to instead of using the talk pages',
	'version'     => AJAXCOMMENTS_VERSION
);

class AjaxComments {

	var $mapid = 1;
	var $opts = array();

	function __construct() {

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.ajaxcomments'] = array(
			'scripts'       => array( 'ajaxcomments.js' ),
			'styles'        => array( 'ajaxcomments.css' ),
			'dependencies'  => array( 'mediawiki.util' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.ajaxcomments' );
	}

	/**
	 * Process the Ajax requests
	 * - we're bypassing the Ajax handler because we need the title and parser to be established
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut;

			// Returns the list of trails in each location in JSON format
			if( $action == 'ajaxcomments' ) {
				$wgOut->disable();
				$id = $wgRequest->getText( 'id', false );
				$text = $wgRequest->getText( 'text', false );
				switch( $wgRequest->getText( 'command' ) ) {

					case 'add':
						print $this->add( $text );
					break;

					case 'reply':
						print $this->reply( $id, $text );
					break;

					case 'edit':
						print $this->edit( $id, $text );
					break;

					case 'delete':
						print $this->delete( $id );
					break;

					default: print $this->renderComments();
			}

		return true;
	}

	function add( $text ) {
	}

	function reply( $id, $text ) {
	}

	function edit( $id, $text ) {
	}

	function delete( $id ) {
	}

	/**
	 * Get the data from the talk page and render as HTML
	 * - in the context of the current title and user
	 * - nested as a hierarchy of div elements
	 */
	function renderComments() {
		global $wgTitle;

		// Get the talk page content
		$talk = $wgTitle->getTalkPage();

		// Scan through each section
		

	}

	/**
	 * Render a single comment (this could be nested within a parent comment)
	 * - renders edit/delete link if ysop, or noreplies and current user is owner
	 */
	function renderComment( $user, $date, $text, $replies = false ) {
		global $wgUser;
	}

}

function wfSetupAjaxComments() {
	global $wgAjaxComments;
	$wgAjaxComments = new AjaxComments();
}

