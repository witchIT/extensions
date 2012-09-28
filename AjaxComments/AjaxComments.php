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

define( 'AJAXCOMMENTS_VERSION','1.0.9, 2012-09-06' );
define( 'AJAXCOMMENTS_USER', 1 );
define( 'AJAXCOMMENTS_DATE', 2 );
define( 'AJAXCOMMENTS_TEXT', 3 );
define( 'AJAXCOMMENTS_PARENT', 4 );
define( 'AJAXCOMMENTS_REPLIES', 5 );
define( 'AJAXCOMMENTS_LIKE', 6 );

$wgAjaxCommentsLikeDislike = true;        // add a like/dislike link to each comment
$wgAjaxCommentsAvatars = true;            // use the gravatar service for users icons
$wgAjaxCommentsPollServer = 0;            // poll the server to see if any changes to comments have been made and update if so

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
		global $wgHooks, $wgOut, $wgResourceModules, $wgAjaxCommentsPollServer, $wgTitle, $wgExtensionAssetsPath;

		$wgHooks['UnknownAction'][] = $this;

		// Create a hook to allow external condition for whether there should be comments
		$ret = true;
		$title = array_key_exists( 'title', $_GET ) ? Title::newFromText( $_GET['title'] ) : false;
		if( (!is_object( $title )) || ($title->getArticleID() == 0) || $title->isRedirect() || ($title->getNamespace()&1) || array_key_exists( 'action', $_REQUEST ) )
			$ret = false;
		if( $ret ) wfRunHooks( 'AjaxCommentsCheckTitle', array( $title, &$ret ) );
		if( $ret ) {
			$wgHooks['MediaWikiPerformAction'][] = $this;
			$wgHooks['BeforePageDisplay'][] = $this;
		}

		// Set up JavaScript and CSS resources
		$wgResourceModules['ext.ajaxcomments'] = array(
			'scripts'       => array( 'ajaxcomments.js' ),
			'styles'        => array( 'ajaxcomments.css' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => $wgExtensionAssetsPath . '/' . basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.ajaxcomments' );

		// Set polling to -1 if checkTitle says comments are disabled
		$wgOut->addJsConfigVars( 'wgAjaxCommentsPollServer', $wgAjaxCommentsPollServer );
	}

	/**
	 * If the page is viewing a talk page, go to the comments instead
	 */
	function onMediaWikiPerformAction( $output, $article, $title, $user, $request, $wiki ) {
		global $wgRequest;
		$action = $wgRequest->getVal( 'action', 'view' );
		$ns = $title->getNamespace();
		if( is_object( $title ) && $action == 'view' && $ns > 0 && $ns & 1 && $this->checkTitle( $title ) ) {
			$output->disable();
			wfResetOutputBuffers();
			$url = Title::newFromText( $title->getText(), $ns - 1 )->getLocalUrl();
			header( "Location: $url#ajaxcomments" );
			exit;
		}
		return true;
	}

	/**
	 * Render a name at the end of the page so redirected talk pages can go there before ajax loads the content
	 */
	function onBeforePageDisplay( $out, $skin ) {
		$out->addHtml( "<a id=\"ajaxcomments-name\" name=\"ajaxcomments\"></a>" );
		return true;
	}


	/**
	 * Process the Ajax requests
	 * - we're bypassing the Ajax handler because we need the title and parser to be established
	 */
	function onUnknownAction( $action, $article ) {
		if( $action == 'ajaxcomments' ) {
			global $wgOut, $wgRequest;
			$wgOut->disable();
			$talk = $article->getTitle()->getTalkPage();
			if( is_object( $talk ) ) {
				$id = $wgRequest->getText( 'id', false );
				$text = $wgRequest->getText( 'text', false );
				$ts = $wgRequest->getText( 'ts', '' );
				$command = $wgRequest->getText( 'cmd' );
				$this->talk = $talk;
				$article = new Article( $talk );
				$summary = wfMsg( "ajaxcomments-$command-summary" );

				// If the talk page exists, get its content and the timestamp of the latest revision
				$content = '';
				if( $talk->exists() ) {
					$content = $article->fetchContent();
					$this->comments = self::textToData( $content );
					$latest = Revision::newFromTitle( $talk )->getTimestamp();
				} else $latest = 0;

				// If a timestamp is provided in the request, bail if nothings happened to the talk content since that time
				if( is_numeric( $ts ) && ( $ts == $latest || $latest == 0 ) ) return '';

				// Perform the command on the talk content
				switch( $command ) {

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

					case 'like':
						if( $summary = $this->like( $id, $text ) ) {
							print $this->renderComment( $id, true );
						}
					break;

					case 'src':
						header( 'Content-Type: application/json' );
						$comment = $this->comments[$id];
						print '{';
						print '"user":' . json_encode( $comment[AJAXCOMMENTS_USER] );
						print ',"date":' . json_encode( $comment[AJAXCOMMENTS_DATE] );
						print ',"text":' . json_encode( $comment[AJAXCOMMENTS_TEXT] );
						print '}';
					break;

					// By default return the whole rendered comments area
					default:
						$n = count( $this->comments );
						$tsdiv = "<div id=\"ajaxcomment-timestamp\" style=\"display:none\">$latest</div>";
						print "<h2>" . wfMsg( 'ajaxcomments-heading' ) . "</h2><a name=\"ajaxcomments\"></a>$tsdiv\n";
						$cc = "<h3 id=\"ajaxcomments-count\">";
						if( $n == 1 ) print $cc . wfMsg( 'ajaxcomments-comment', $n ) . "</h3>\n";
						else if( $n > 1 ) print $cc . wfMsg( 'ajaxcomments-comments', $n ) . "</h3>\n";
						print $this->renderComments();
				}

				// If any comment data has been changed write it back to the talk article
				if( $this->changed ) {
					$flag = $talk->exists() ? EDIT_UPDATE : EDIT_NEW;
					$article->doEdit( self::dataToText( $this->comments, $content ), $summary, $flag|EDIT_MINOR );
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
		global $wgParser;
		$this->comments[$id][AJAXCOMMENTS_TEXT] = $text;
		$html = $wgParser->parse( $text, $this->talk, new ParserOptions(), true, true )->getText();
		$this->changed = true;
		return "<div class=\"ajaxcomment-text\">$html</div>";
	}

	/**
	 * Add a new comment as a reply to an existing comment in the data structure
	 */
	function reply( $parent, $text ) {
		global $wgUser;
		$id = uniqid();
		array_unshift( $this->comments[$parent][AJAXCOMMENTS_REPLIES], $id );
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
	 * Like/unlike a comment returning a message describing the change
	 * - if val isn't passed, then the current like state of the current user and the total likes/dislikes are returned
	 */
	function like( $id, $val = false ) {
		global $wgUser;
		$name = $wgUser->getName();
		$cname = $this->comments[$id][AJAXCOMMENTS_USER];
		if( !array_key_exists( AJAXCOMMENTS_LIKE, $this->comments[$id] ) ) $this->comments[$id][AJAXCOMMENTS_LIKE] = array();
		$like = array_key_exists( $name, $this->comments[$id][AJAXCOMMENTS_LIKE] ) ? $this->comments[$id][AJAXCOMMENTS_LIKE][$name] : 0;

		// If a +1/-1 values was passed, update the like state now returing a description message
		if( $val ) {
			$this->changed = true;

			// Remove the user if they now nolonger like or dislike, otherwise update their value
			if( $like + $val == 0 ) unset( $this->comments[$id][AJAXCOMMENTS_LIKE][$name] );
			else $this->comments[$id][AJAXCOMMENTS_LIKE][$name] = $like + $val;

			if( $val > 0 ) {
				if( $like < 0 ) return wfMsg( 'ajaxcomments-undislike', $name, $cname );
				else return wfMsg( 'ajaxcomments-like', $name, $cname );
			}
			else {
				if( $like > 0 ) return wfMsg( 'ajaxcomments-unlike', $name, $cname );
				else return wfMsg( 'ajaxcomments-dislike', $name, $cname );
			}
		}

		// No value was passed, add up the likes and dislikes
		$likes = $dislikes = array();
		foreach( $this->comments[$id][AJAXCOMMENTS_LIKE] as $k => $v ) if( $v > 0 ) $likes[] = $k; else $dislikes[] = $k;
		return array( $like, $likes, $dislikes );
	}

	/**
	 * Render the comment data structure as HTML
	 * - also render a no comments message if none
	 * - and an add comments link at the top
	 */
	function renderComments() {
		global $wgUser;
		$html = '';
		foreach( $this->comments as $id => $comment ) {
			if( $comment[AJAXCOMMENTS_PARENT] === false ) $html = $this->renderComment( $id ) . $html;
		}
		if( $html == '' ) $html = "<i id=\"ajaxcomments-none\">" . wfMsg( 'ajaxcomments-none' ) . "</i><br />";

		// If logged in, allow replies and editing etc
		if( $wgUser->isLoggedIn() ) {
			$html = "<ul class=\"ajaxcomment-links\">" .
				"<li id=\"ajaxcomment-add\"><a href=\"javascript:ajaxcomment_add()\">" . wfMsg( 'ajaxcomments-add' ) . "</a></li>\n" .
				"</ul>\n$html";
		} else $html = "<i id=\"ajaxcomments-none\">" . wfMsg( 'ajaxcomments-anon' ) . "</i><br />$html";
		return $html;
	}

	/**
	 * Render a single comment and any of it's replies
	 * - this is recursive - it will render any replies which could in turn contain replies etc
	 * - renders edit/delete link if sysop, or no replies and current user is owner
	 * - if likeonly is set, return only the like/dislike links
	 */
	function renderComment( $id, $likeonly = false ) {
		global $wgParser, $wgUser, $wgLang, $wgAjaxCommentsAvatars, $wgAjaxCommentsLikeDislike;
		$curName = $wgUser->getName();
		$c = $this->comments[$id];
		$html = '';

		// Render replies
		$r = '';
		foreach( $c[AJAXCOMMENTS_REPLIES] as $child ) $r .= $this->renderComment( $child );

		// Get total likes and unlikes
		$likelink = $dislikelink = '';
		list( $like, $likes, $dislikes ) = $this->like( $id );

		// Render user name as link
		$name = $c[AJAXCOMMENTS_USER];
		$user = User::newFromName( $name );
		$url = $user->getUserPage()->getLocalUrl();
		$ulink = "<a href=\"$url\">$name</a>";

		// Get the user's gravitar url
		if( $wgAjaxCommentsAvatars && $user->isEmailConfirmed() ) {
			$email = $user->getEmail();
			$grav = "http://www.gravatar.com/avatar/" . md5( strtolower( $email ) ) . "?s=50&d=wavatar";
			$grav = "<img src=\"$grav\" alt=\"$name\" />";
		} else $grav = '';

		if( !$likeonly ) $html .= "<div class=\"ajaxcomment\" id=\"ajaxcomments-$id\">\n" .
			"<div class=\"ajaxcomment-sig\">" .
				wfMsg( 'ajaxcomments-sig', $ulink, $wgLang->timeanddate( $c[AJAXCOMMENTS_DATE], true ) ) .
			"</div>\n<div class=\"ajaxcomment-icon\">$grav</div><div class=\"ajaxcomment-text\">" .
				$wgParser->parse( $c[AJAXCOMMENTS_TEXT], $this->talk, new ParserOptions(), true, true )->getText() .
			"</div>\n<ul class=\"ajaxcomment-links\">";

		// If logged in, allow replies and editing etc
		if( $wgUser->isLoggedIn() ) {

			if( !$likeonly ) {

				// Reply link
				$html .= "<li id=\"ajaxcomment-reply\"><a href=\"javascript:ajaxcomment_reply('$id')\">" . wfMsg( 'ajaxcomments-reply' ) . "</a></li>\n";

				// If sysop, or no replies and current user is owner, add edit/del links
				if( in_array( 'sysop', $wgUser->getEffectiveGroups() ) || ( $curName == $c[AJAXCOMMENTS_USER] && $r == '' ) ) {
					$html .= "<li id=\"ajaxcomment-edit\"><a href=\"javascript:ajaxcomment_edit('$id')\">" . wfMsg( 'ajaxcomments-edit' ) . "</a></li>\n";
					$html .= "<li id=\"ajaxcomment-del\"><a href=\"javascript:ajaxcomment_del('$id')\">" . wfMsg( 'ajaxcomments-del' ) . "</a></li>\n";
				}
			}

			// Make the like/dislike links
			if( $wgAjaxCommentsLikeDislike ) {
				if( $curName != $name ) {
					if( $like <= 0 ) $likelink = " onclick=\"javascript:ajaxcomment_like('$id',1)\" class=\"ajaxcomment-active\"";
					if( $like >= 0 ) $dislikelink = " onclick=\"javascript:ajaxcomment_like('$id',-1)\" class=\"ajaxcomment-active\"";
				}

				// Add the likes and dislikes links
				$clikes = count( $likes );
				$cdislikes = count( $dislikes );
				$likes = $this->formatNameList( $likes, 'like' );
				$dislikes = $this->formatNameList( $dislikes, 'dislike' );
				$html .= "<li title=\"$likes\" id=\"ajaxcomment-like\"$likelink>$clikes</li>\n";
				$html .= "<li title=\"$dislikes\" id=\"ajaxcomment-dislike\"$dislikelink>$cdislikes</li>\n";
			}
		}

		if( !$likeonly ) $html .= "</ul>$r</div>\n";
		return $html;
	}

	/**
	 * Return the passed list of names as a list of "a,b,c and d"
	 */
	function formatNameList( $list, $msg ) {
		$len = count( $list );
		if( $len < 1 ) return wfMsg( "ajaxcomments-no$msg" );
		if( $len == 1 ) return wfMsg( "ajaxcomments-one$msg", $list[0] );
		$last = array_pop( $list );
		return wfMsg( "ajaxcomments-many$msg", join( ', ', $list ), $last );
	}

	/**
	 * Return the passed talk text as a data structure of comments
	 */
	static function textToData( $text ) {
		if( preg_match( "|== AjaxComments:DataStart ==\s*(.+)\s*== AjaxComments:DataEnd ==|s", $text, $m ) ) return unserialize( $m[1] );
		return array();
	}

	/**
	 * Return the passed data structure of comments as text for a talk page
	 * - $content is the current talk page text to integrate with
	 */
	static function dataToText( $data, $content ) {
		$text = serialize( $data );
		$text = "\n== AjaxComments:DataStart ==\n$text\n== AjaxComments:DataEnd ==";
		$content = preg_replace( "|== AjaxComments:DataStart ==\s*(.+)\s*== AjaxComments:DataEnd ==|s", $text, $content, 1, $count );
		if( $count == 0 ) $content .= $text;
		return $content;
	}

}

// $wgAjaxComments can be set to false prior to extension setup to disable comments on this page
function wfSetupAjaxComments() {
	global $wgAjaxComments;
	$wgAjaxComments = new AjaxComments();
}

