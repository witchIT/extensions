<?php
class Bliki {

	public static function onUnknownAction( $action, $article ) {
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

	private static function preview( $heading, $title, $wikitext ) {
		global $wgOut, $wgParser;
		$wgOut->addWikitext( '<div class="previewnote">' . wfMsg( 'previewnote' ) . '</div>' );
		$wgOut->addWikitext( "== $heading ==" );
		$wgOut->addHTML( $wgParser->parse( $wikitext, $title, new ParserOptions(), true, true )->getText() );
		$wgOut->addHTML( "<br /><hr /><br />" );
	}

	/**
	 * Add a "blog-item" attribute to the body element of blog post pages
	 */
	public static function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ) {
		global $wgBlikiDefaultCat, $wgBlikiAddBodyClass;
		if( $wgBlikiAddBodyClass && self::inCat( $wgBlikiDefaultCat ) ) $bodyAttrs['class'] .= ' blog-item';
		return true;
	}

	/**
	 * Register parser-functions
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'tags', __CLASS__ . '::expandTags' );
		$parser->setFunctionHook( 'nextpost', __CLASS__ . '::expandNext' );
		$parser->setFunctionHook( 'prevpost', __CLASS__ . '::expandPrev' );
		return true;
	}

	/**
	 * Tags parser-function returns list of tags the passed post is in
	 */
	public static function expandTags( $parser, $item ) {
		$tags = array();
		$dbr  = wfGetDB( DB_SLAVE );
		$id   = Title::newFromText( $item )->getArticleID();
		$res  = $dbr->select( 'categorylinks', 'cl_to', "cl_from = $id", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
		foreach( $res as $row ) {
			$title = Title::newFromText( $row->cl_to );
			if( self::inCat( 'Tags', $title ) ) $tags[] = $title;
		}
		return array( $html, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Nextpost parser-function returns the next post in the passed cat or blog-items cat
	 */
	public static function expandNext( $parser, $item, $cat = false ) {
		global $wgBlikiDefaultCat;
		if( $cat == false ) $cat = $wgBlikiDefaultCat;
		return array( $html, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Prevpost parser-function returns the previous post in the passed cat or blog-items cat
	 */
	public static function expandPrev( $parser, $item, $cat = false ) {
		global $wgBlikiDefaultCat;
		if( $cat == false ) $cat = $wgBlikiDefaultCat;
		return array( $html, 'isHTML' => true, 'noparse' => true );
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
