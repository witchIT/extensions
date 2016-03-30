<?php
class Bliki {

	// Register our ajax handler
	public static function onRegistration() {
		global $wgAPIModules;
		$wgAPIModules['blikifeed'] = 'ApiBlikiFeed';
	}

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
		$parser->setFunctionHook( 'blogroll', __CLASS__ . '::expandBlogroll' );
		return true;
	}

	/**
	 * Tags parser-function returns list of tags the passed post is in
	 */
	public static function expandTags( $parser, $item ) {
		global $wgBlikiDefaultBlogPage;
		$tags = array();
		$dbr  = wfGetDB( DB_SLAVE );
		$id   = Title::newFromText( $item )->getArticleID();
		$res  = $dbr->select( 'categorylinks', 'cl_to', "cl_from = $id", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
		foreach( $res as $row ) {
			$title = Title::newFromText( $row->cl_to );
			if( self::inCat( 'Tags', $title ) ) {
				$text = $title->getPrefixedText();
				$url = Title::newFromText( $wgBlikiDefaultBlogPage )->getLocalUrl( "q=$text" );
				$tags[] = "<a href=\"$url\" title=\"" . wfMessage( 'bliki-taglinktitle', $text )->escaped() . "\">$text</a>";
			}
		}
		return array( implode( ' | ', $tags ), 'isHTML' => true, 'noparse' => true );
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
		global $wgBlikiDefaultCat, $wgBlikiDefaultBlogPage;
		if( $cat == false ) $cat = $wgBlikiDefaultCat;
		return array( $html, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Blogroll parser-function
	 */
	public static function expandBlogroll( $parser ) {
		global $wgRequest, $wgLang, $wgBlikiDefaultCat;
		$dbr = wfGetDB( DB_SLAVE );
		$roll = '';

		// ZSet up parser options
		$opt = $parser->getOptions();
		$opt->setEditSection( false );
		$opt->enableLimitReport( false );

		// Get parser-function args
		$args = array();
		foreach( func_get_args() as $arg ) {
			if( !is_object( $arg ) ) {
				if( preg_match( '/^(\\w+?)\\s*=\\s*(.+)$/s', $arg, $m ) ) {
					$args[$m[1]] = $m[2];
				} else $args[$arg] = true;
			}
		}

		// Allow query-string params to override arg values
		$limit = $wgRequest->getVal( 'limit', array_key_exists( 'limit', $args ) ? $args['limit'] : false );
		$offset = $wgRequest->getVal( 'offset', array_key_exists( 'offset', $args ) ? $args['offset'] : false );
		$tag = $wgRequest->getVal( 'q', array_key_exists( 'tag', $args ) ? $args['template'] : $wgBlikiDefaultCat );
		$desc = $wgRequest->getBool( 'reverse', array_key_exists( 'reverse', $args ) );

		// Convert args to SQL options
		$options = array( 'ORDER BY' => "cl_timestamp$desc" );

		// First get the total count
		$cat = Title::newFromText( $tag )->getDBkey();
		$total = $dbr->selectRow( 'categorylinks', 'count(*) as total', array( 'cl_to' => $cat ), __METHOD__, $options )->total;

		// Do the query
		if( $limit ) {
			$options['LIMIT'] = $limit;
			if( $offset ) $options['OFFSET'] = $offset;
		}
		$res = $dbr->select( 'categorylinks', 'cl_from', array( 'cl_to' => $cat ), __METHOD__, $options );

		// Subscribe link
		$roll .= self::feedLink( $tag ) . '<div style="clear:both"></div>';

		// Render each item
		foreach( $res as $row ) {

			// Get the title, article and first revision objects
			$title = Title::newFromID( $row->cl_from );
			$id = $title->getArticleID();
			$article = new Article( $title );
			$rev = $dbr->selectRow( 'revision', '*', array( 'rev_page' => $id ), __METHOD__, array( 'ORDER BY' => 'rev_timestamp' ) );

			// Get the article content
			$content = $article->getPage()->getContent( Revision::RAW );
			$content = is_object( $content ) ? ContentHandler::getContentText( $content ) : $content;

			// Remove any noincludes
			$content = preg_replace( "|<noinclude>.*?</noinclude>|s", '', $content );

			// Remove includeonly tags (not content)
			$content = preg_replace( "|<\/?includeonly>|", '', $content );

			// Remove all but onlyinclude if it exists
			$content = preg_match( "|<onlyinclude>(.+?)</onlyinclude>|s", $content, $m ) ? $m[1] : $content;

			// Make the tag line
			$tags = array();
			foreach( self::getTags( $title ) as $tag ) $tags[] = '[' . self::blogLink( $tag ) . ' ' . $tag . ']';
			$tags = '<div class="taglist">' . wfMessage( 'bliki-tags' )->text() . ': ' . implode( ' | ', $tags ) . '</div>';

			// Build the item
			$page = $title->getPrefixedText();
			$user = User::newFromID( $rev->rev_user )->getName();
			$link = self::blogLink( wfMessage( 'bliki-cat', $user )->text() );
			$sig = wfMessage( 'bliki-sig', $link, $user, $wgLang->date( $rev->rev_timestamp, true ), $wgLang->time( $rev->rev_timestamp, true ) )->text();
			$content = "{|class=blog\n|\n== [[$page]] ==\n|-\n!$sig\n|-\n|$tags\n|-\n|$content\n|}";

			// Parse the item and add to the roll
			$roll .= $parser->parse( $content, $parser->getTitle(), $parser->getOptions(), true, false )->getText();
		}

		// Add the pager after the roll
		$roll .= self::pager( $offset, $limit, $total );

		return array( $roll, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Return an URL to the blog with the passed query
	 */
	private static function blogLink( $q ) {
		global $wgTitle;
		return $wgTitle->getFullUrl( 'q=' . urlencode( $q ) );
	}

	/**
	 * Return HTML for feed links
	 */
	private static function feedLink( $q = false ) {
		global $wgScriptPath, $wgBlikiDefaultCat;
		$feedUrl = $wgScriptPath . '/api.php?action=blikifeed';
		if( $q && $q != $wgBlikiDefaultCat ) $feedUrl .= '&q=' . urlencode( $q );
		return '<div class="subscribe" style="float:right"><b>' . wfMessage( 'bliki-subscribe' )->text()
			. ':&nbsp;</b><a id="feed-rss" class="feedlink" title="' . wfMessage( 'bliki-pagefeed', 'RSS' )->text() . '" '
			. 'type="application/rss+xml" rel="alternate" href="' . $feedUrl. '">RSS</a>&nbsp;'
			. '<a id="feed-atom" class="feedlink" title="' . wfMessage( 'bliki-pagefeed', 'RSS' )->text() . '" '
			. 'type="application/atom+xml" rel="alternate" href="' . $feedUrl . '&feed=atom">Atom</a>'
			. '</div>';
	}

	/**
	 * Return HTML for the pager after the blog roll
	 */
	private static function pager( $offset, $limit, $total ) {
		$pagesf = $total / $limit;
		$pages = (int)$pagesf;
		if( $pagesf > $pages ) $pages++;
		$html = '<div class="bliki-pager">';
		for( $n = 0; $n < $pages; $n++ ) {
			$selected = ( $offset >= $n * $limit && $offset < ($n+1) * $limit ) ? ' class="selected"' : '';
			$html .= "<a$selected href=\"/\">" . ($n + 1) . '</a>';
		}
		$html .= '</div>';
		return $html;
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

	/**
	 * Return a list of titles that are members of the passed category
	 */
	public static function getMembers( $cat ) {
		$list = array();
		$dbr  = wfGetDB( DB_SLAVE );
		$res  = $dbr->select( 'categorylinks', 'cl_from', array( 'cl_to' => $cat ), __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
		foreach( $res as $row ) {
			$list[] = Title::newFromID( $row->cl_from )->getText();
		}
		return $list;
	}

	/**
	 * Return a list of categories the passed article belongs to
	 */
	public static function getCats( $title ) {
		if( !is_object( $title ) ) $title = Title::newFromText( $title );
		$list = array();
		$dbr  = wfGetDB( DB_SLAVE );
		$id   = $title->getArticleID();
		$res  = $dbr->select( 'categorylinks', 'cl_to', "cl_from = $id", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
		foreach( $res as $row ) {
			$list[] = Title::newFromDBkey( $row->cl_to )->getText();
		}
		return $list;
	}

	/**
	 * Return a list of tags the passed title is categorised in
	 */
	public static function getTags( $title ) {
		global $wgBlikiTagCat;
		$allTags = self::getMembers( $wgBlikiTagCat );
		$tags = array();
		foreach( self::getCats( $title ) as $tag ) {
			if( in_array( $tag, $allTags ) ) $tags[] = $tag;
		}
		return $tags;
	}
}
