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

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['AjaxComments'] = "$dir/AjaxComments.i18n.php";

class AjaxComments {

	var $comments = array();
	var $changed = false;

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

				// Get the talk page content
				if( $talk = $article->getTitle()->getTalkPage() && $talk->exists() ) {
					$article = new Article( $talk );
					$content = $talk->fetchContent();
					$this->comments = self::textToData( $content );
				}

				switch( $command = $wgRequest->getText( 'command' ) ) {

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

			// If any comment data has been changed write it back to the talk article
			if( $this->changed ) {
				$article->doEdit( $self::dataToText( $this->comments ), wfMsg( "ajaxcomments-$command-summary" ), EDIT_UPDATE );
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
	 * Render the comment data structure as HTML
	 */
	function renderComments() {
		$html = '';
		foreach( $this->comments as $comment ) {
			$html .= $this->renderComment( $comment );
		}
		return $html;
	}

	/**
	 * Render a single comment and any of it's replies
	 * - this is recursive - it will render any replies which could in turn contain replies etc
	 * - renders edit/delete link if ysop, or noreplies and current user is owner
	 */
	function renderComment( $comment ) {
		if( array_key_exists( 'replies', $comment ) ) $replies = $this->renderComment( $comment['replies'] );
		return "<div class=\"ajaxcomment\" id=\"" . $comment['id'] . "\">\n" .
			"<div class=\"ajaxcomment-sig\">" . wfMsg( 'ajaxcomments-sig', $comment['user'], wfTimestamp( TS_MW, $comment['time'] ) ) . "</div>\n" .
			"<div class=\"ajaxcomment-text\">" . $wgParser->parse( $comment['text'], $talk, new ParserOptions(), true, true )->getText() . "</div>\n" .
			"<ul class=\"ajaxcomment-links\">" .
				"<li id=\"ajaxcomment-reply\"><a href=\"javascript:ajaxcomment_reply(this)\">" .wfMsg( 'ajaxcomments-reply' ) . "</a></li>\n" .
				"<li id=\"ajaxcomment-edit\"><a href=\"javascript:ajaxcomment_edit(this)\">" .wfMsg( 'ajaxcomments-edit' ) . "</a></li>\n" .
				"<li id=\"ajaxcomment-del\"><a href=\"javascript:ajaxcomment_del(this)\">" .wfMsg( 'ajaxcomments-del' ) . "</a></li>\n" .
			"</ul>$replies</div>\n";
	}

	/**
	 * Return the passed talk text as a data structure of comments
	 */
	static function textToData( $text ) {
		return unserialize( $text );
	}

	/**
	 * Return the passed data structure of comments as text for a talk page
	 */
	static function textToData( $data ) {
		return serialize( $data );
	}

}

function wfSetupAjaxComments() {
	global $wgAjaxComments;
	$wgAjaxComments = new AjaxComments();
}

