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
define( 'AJAXCOMMENTS_USER', 1 );
define( 'AJAXCOMMENTS_DATE', 2 );
define( 'AJAXCOMMENTS_TEXT', 3 );
define( 'AJAXCOMMENTS_PARENT', 4 );
define( 'AJAXCOMMENTS_REPLIES', 5 );

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
	var $talk = false;

	function __construct() {
		global $wgHooks, $wgOut, $wgResourceModules;

		$wgHooks['UnknownAction'][] = $this;

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.ajaxcomments'] = array(
			'scripts'       => array( 'ajaxcomments.js' ),
			'styles'        => array( 'ajaxcomments.css' ),
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
		if( $action == 'ajaxcomments' ) {
			global $wgOut, $wgRequest;
			$wgOut->disable();
			$id = $wgRequest->getText( 'id', false );
			$text = $wgRequest->getText( 'text', false );
			$talk = $article->getTitle()->getTalkPage();
			if( is_object( $talk ) ) {
				$this->talk = $talk;
				$article = new Article( $talk );

				// Get the talk page content
				if( $talk->exists() ) $this->comments = self::textToData( $article->fetchContent() );
//print '<pre>';
//print_r($this->comments);
//print '</pre>';

				// Perform the command on the talk content
				switch( $command = $wgRequest->getText( 'cmd' ) ) {

					case 'add':
						print $this->add( $text );
					break;

					case 'reply':
						print $this->reply( $id, $text );
					break;

					case 'edit':
						print $this->edit( $id, $text );
					break;

					case 'del':
						print $this->delete( $id );
						print count( $this->comments ) > 0 ? '' : "<i id=\"ajaxcomments-none\">" . wfMsg( 'ajaxcomments-none' ) . "</i>";
					break;

					default:
						print "<h2>" . wfMsg( 'ajaxcomments-heading' ) . "</h2>\n";
						print $this->renderComments();
				}

				// If any comment data has been changed write it back to the talk article
				if( $this->changed ) {
					$flag = $talk->exists() ? EDIT_UPDATE : EDIT_NEW;
					$article->doEdit( self::dataToText( $this->comments ), wfMsg( "ajaxcomments-$command-summary" ), $flag );
				}
			}
		}

		return true;
	}

	/**
	 * Add a new comment to the data structure
	 */
	function add( $text ) {
		global $wgUser;
		$id = uniqid();
		$this->comments[$id] = array(
			AJAXCOMMENTS_PARENT => false,
			AJAXCOMMENTS_USER => $wgUser->getName(),
			AJAXCOMMENTS_DATE => time(),
			AJAXCOMMENTS_TEXT => $text,
			AJAXCOMMENTS_REPLIES => array()
		);
		$this->changed = true;
		return $this->renderComment( $id );
	}

	/**
	 * Edit an existing comment in the data structure
	 */
	function edit( $id, $text ) {
		$this->comments[$id][AJAXCOMMENTS_TEXT] = $text;
		$this->changed = true;
		return $this->renderComment( $id );
	}

	/**
	 * Add a new comment as a reply to an existing comment in the data structure
	 */
	function reply( $parent, $text ) {
		global $wgUser;
		$id = uniqid();
		$this->comments[$parent][AJAXCOMMENTS_REPLIES][] = $id;
		$this->comments[$id] = array(
			AJAXCOMMENTS_PARENT => $parent,
			AJAXCOMMENTS_USER => $wgUser->getName(),
			AJAXCOMMENTS_DATE => time(),
			AJAXCOMMENTS_TEXT => $text,
			AJAXCOMMENTS_REPLIES => array()
		);
		$this->changed = true;
		return $this->renderComment( $id );
	}

	/**
	 * Delete a comment amd all its replies from the data structure
	 */
	function delete( $id ) {
		if( array_key_exists( $id, $this->comments ) ) {

			// Call delete for all the replies of this comment
			foreach( $this->comments[$id][AJAXCOMMENTS_REPLIES] as $child ) $this->delete( $child );

			// Remove this item from the parents replies list (unless root level)
			if( $parent = $this->comments[$id][AJAXCOMMENTS_PARENT] ) {
				$i = array_search( $id, $this->comments[$parent][AJAXCOMMENTS_REPLIES] );
				if( $i !== false ) unset( $this->comments[$parent][AJAXCOMMENTS_REPLIES][$i] );
			}

			// Remove this comment from the data
			unset( $this->comments[$id] );

			$this->changed = true;
		}
	}

	/**
	 * Render the comment data structure as HTML
	 */
	function renderComments() {
		$html = '';
		foreach( $this->comments as $id => $comment ) {
			if( $comment[AJAXCOMMENTS_PARENT] === false ) $html .= $this->renderComment( $id );
		}
		if( $html == '' ) $html = "<i id=\"ajaxcomments-none\">" . wfMsg( 'ajaxcomments-none' ) . "</i>";
		$html .= "<ul class=\"ajaxcomment-links\">" .
			"<li id=\"ajaxcomment-add\"><a href=\"javascript:ajaxcomment_add()\">" . wfMsg( 'ajaxcomments-add' ) . "</a></li>\n" .
			"</ul>\n";
		return $html;
	}

	/**
	 * Render a single comment and any of it's replies
	 * - this is recursive - it will render any replies which could in turn contain replies etc
	 * - renders edit/delete link if ysop, or noreplies and current user is owner
	 */
	function renderComment( $id ) {
		global $wgParser;
		$c = $this->comments[$id];
		$r = '';
		foreach( $c[AJAXCOMMENTS_REPLIES] as $child ) $r .= $this->renderComment( $child );
		return "<div class=\"ajaxcomment\" id=\"ajaxcomments-$id\">\n" .
			"<div class=\"ajaxcomment-sig\">" .
				wfMsg( 'ajaxcomments-sig', $c[AJAXCOMMENTS_USER], wfTimestamp( TS_DB, $c[AJAXCOMMENTS_DATE] ) ) .
			"</div>\n" .
			"<div class=\"ajaxcomment-text\">" .
				$wgParser->parse( $c[AJAXCOMMENTS_TEXT], $this->talk, new ParserOptions(), true, true )->getText() .
			"</div>\n" .
			"<ul class=\"ajaxcomment-links\">" .
				"<li id=\"ajaxcomment-reply\"><a href=\"javascript:ajaxcomment_reply('$id')\">" . wfMsg( 'ajaxcomments-reply' ) . "</a></li>\n" .
				"<li id=\"ajaxcomment-edit\"><a href=\"javascript:ajaxcomment_edit('$id')\">" . wfMsg( 'ajaxcomments-edit' ) . "</a></li>\n" .
				"<li id=\"ajaxcomment-del\"><a href=\"javascript:ajaxcomment_del('$id')\">" . wfMsg( 'ajaxcomments-del' ) . "</a></li>\n" .
			"</ul>$r</div>\n";
	}

	/**
	 * Return the passed talk text as a data structure of comments
	 */
	static function textToData( $text ) {
		return $text ? unserialize( $text ) : array();
	}

	/**
	 * Return the passed data structure of comments as text for a talk page
	 */
	static function dataToText( $data ) {
		return count( $data ) ? serialize( $data ) : '';
	}

}

function wfSetupAjaxComments() {
	global $wgAjaxComments;
	$wgAjaxComments = new AjaxComments();
}

