<?php
/**
 * Bliki extension - Adds "Bliki" (blog in a wiki) functionality
 * 
 * See http://www.organicdesign.co.nz/bliki for more detail
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/nad Nad]
 * @copyright © 2013 [http://www.organicdesign.co.nz/nad Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'BLIKI_VERSION','2.0.5, 2014-07-03' );

$wgBlikiAddBodyClass = false;
$wgBlikiPostGroup = 'sysop';

$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'        => 'Bliki',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad Aran Dunkley]',
	'url'         => 'http://www.organicdesign.co.nz/bliki',
	'description' => 'Adds [[Bliki]] (blog in a wiki) functionality',
	'version'     => BLIKI_VERSION
);

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['Bliki'] = $wgExtensionMessagesFiles['BlikiFeed'] = "$dir/Bliki.i18n.php";
include( "$dir/BlikiFeed.php" );

class Bliki {
	
	function __construct() {
		global $wgHooks, $wgBlikiAddBodyClass;
		$wgHooks['UnknownAction'][] = $this;
		if( $wgBlikiAddBodyClass ) $wgHooks['OutputPageBodyAttributes'][] = $this;
	}

	function onUnknownAction( $action, $article ) {
		global $wgOut, $wgRequest, $wgUser, $wgParser, $wgBlikiPostGroup;
		if( $action == 'blog' && in_array( $wgBlikiPostGroup, $wgUser->getEffectiveGroups() ) ) {
			$newtitle = $wgRequest->getText( 'newtitle' );
			$title = Title::newFromText( $newtitle );
			$error = false;
			if( !is_object( $title ) ) {
				$wgOut->addWikitext( '<div class="previewnote">Error: Bad title!</div>' );
				$error = true;
			}
			elseif( $title->exists() ) {
				$wgOut->addWikitext( '<div class="previewnote">Error: Title already exists!</div>' );
				$error = true;
			}
			if( !$error ) {
				$summary = $wgRequest->getText( 'summary' );
				$content = $wgRequest->getText( 'content' );
				$user = $wgUser->getName();
				$date = date('U');
				$sig = '<div class="blog-sig">{{BlogSig|' . "$user|@$date" . '}}</div>';
				$type = $wgRequest->getText( 'type' );
				switch( $type ) {

					// Preview the item
					case "Full preview":
						$wikitext = "$sig\n$summary\n\n$content";
						self::preview( $type, $title, $wikitext );
						$article->view();
					break;

					// Preview the item in news/blog format
					case "Summary preview":
						$wikitext = "{|class=\"blog\"\n|\n== [[Post a blog item|$newtitle]] ==\n|-\n!$sig\n|-\n|$summary\n|}__NOEDITSECTION__";
						$title = Title::newFromText( 'Blog' );
						self::preview( $type, $title, $wikitext );
						$article->view();
					break;

					// Create the item with tags as category links
					case "Post":
						$wikitext = '{{' . "Blog|1=$summary|2=$content" . '}}';
						$wikitext .= "<noinclude>[[Category:Blog items]][[Category:Posts by $user]]";
						foreach( array_keys( $_POST ) as $k ) {
							if( preg_match( "|^tag(.+)$|", $k, $m ) ) {
								$wikitext .= '[[Category:' . str_replace( '_', ' ', $m[1] ) . ']]';
							}
						}
						$wikitext .= "</noinclude>";
						$article = new Article( $title );
						$article->doEdit( $wikitext, 'Blog item created via post form', EDIT_NEW );
						$wgOut->redirect( $title->getFullURL() );
					break;
				}
			} else $article->view();
			return false;
		}
		return true;
	}

	function preview( $heading, $title, $wikitext ) {
		global $wgOut, $wgParser;
		$wgOut->addWikitext( '<div class="previewnote">' . wfMsg( 'previewnote' ) . '</div>' );
		$wgOut->addWikitext( "== $heading ==" );
		$wgOut->addHTML( $wgParser->parse( $wikitext, $title, new ParserOptions(), true, true )->getText() );
		$wgOut->addHTML( "<br /><hr /><br />" );
	}

	function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ) {
		if( self::inCat( 'Blog_items' ) ) $bodyAttrs['class'] .= ' blog-item';
		return true;
	}

	/**
	 * Return whether or not the passed title is a member of the passed cat
	 */
	public static function inCat( $cat, $title = false ) {
		global $wgTitle;
		if( $title === false ) $title = $wgTitle;
		if( !is_object( $title ) ) $title = Title::newFromText( $title );
		$id  = $title->getArticleID();
		$dbr = wfGetDB( DB_SLAVE );
		$cat = $dbr->addQuotes( Title::newFromText( $cat, NS_CATEGORY )->getDBkey() );
		return $dbr->selectRow( 'categorylinks', '1', "cl_from = $id AND cl_to = $cat" );
	}
}

new Bliki();
