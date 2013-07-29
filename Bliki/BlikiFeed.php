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

/**
 * Add a new special page for blog feeds based on Special:RecentChanges
 */
$wgSpecialPages['BlikiFeed'] = 'SpecialBlikiFeed';
class SpecialBlikiFeed extends SpecialRecentChanges {

	// Construct special page with our new name, and force to feed type
	public function __construct() {
		global $wgHooks;
		$wgHooks['SpecialRecentChangesQuery'][] = $this;
		if( !$this->getRequest()->getVal( 'feed' ) ) $this->getRequest()->setVal( 'feed', 'rss' );
		parent::__construct( 'BlikiFeed' );
	}

	// Inject a value into opts so we can know on the hook function that its a bliki feed
	public function doMainQuery( $conds, $opts ) {
		$opts->add( 'bliki', false );
		$opts['bliki'] = $this->getRequest()->getVal( 'q', 'Blog items' );
		return parent::doMainQuery( $conds, $opts );
	}

	// If it's a bliki list, filter the list to onlynew items and to the tag cat if q supplied
	public static function onSpecialRecentChangesQuery( &$conds, &$tables, &$join_conds, $opts, &$query_options, &$fields ) {
		if( $opts->validateName( 'bliki' ) ) {
		print_r($opts);
			$tables[] = 'categorylinks';
			$conds[] = 'rc_new=1';
			$cat = Title::newFromText( $opts['bliki'] )->getDBkey();
			$join_conds['categorylinks'] = array( 'RIGHT JOIN', 'cl_from=page_id AND cl_to=\'$cat\'' );
		}
		return true;
	}

	public function getFeedObject( $feedFormat ) {
			global $wgRequest, $wgSitename;

			// Blog title & description
			$q = $wgRequest->getVal( 'q', false );
			$cat = $q ? Title::newFromText( $q )->getText() : false;
			$tag = $cat ? Bliki::inCat( 'Tags', $cat ) : false;
			$title = str_replace( ' wiki', '', $wgSitename ) . ' blog';
			$desc = $cat ? ( $tag ? "$cat posts" : lcfirst( $cat ) ) : 'posts';
			$desc = "Use this feed to track the most recent $desc in the $wgSitename.";

			// Blog URL
			$blog = Title::newFromText( 'Blog' );
			$url = $cat ? $blog->getFullURL( "q=$cat" ) : $blog->getFullURL();

			// Instantiate our custom ChangesFeed class
			$changesFeed = new BlikiChangesFeed( $feedFormat, 'rcfeed' );
			$formatter = $changesFeed->getFeedObject( $title, $desc, $url );

			return array( $changesFeed, $formatter );
		}

}

/**
 * Our BlikiChanges special page uses this custom ChangesFeed
 * which has the item description changed to the plain-text blog item summary instead of the usual diff/wikitext
 */
class BlikiChangesFeed extends ChangesFeed {

	// Much more compact version than parent because only new items by known authors will be in the list
	public static function generateFeed( $rows, &$feed ) {
		$feed->outHeader();
		foreach( $rows as $obj ) {
			$title = Title::makeTitle( $obj->rc_namespace, $obj->rc_title );
			$url = $title->getFullURL();
			$item = new FeedItem( $title->getPrefixedText(), self::desc( $title ), $url, $obj->rc_timestamp, $obj->rc_user_text, $url );
			$feed->outItem( $item );
		}
		$feed->outFooter();
	}

	// Use the plain-text of the summary for the item description
	static function desc( $title ) {
		global $wgParser;
		$article = new Article( $title );
		$content = $article->getContent();
		$desc = preg_match( "/^.+?1=(.+?)\|2=/", $content, $m ) ? $m[1] : $title->getText();
		$desc = strip_tags( $wgParser->parse( $desc, $title, new ParserOptions(), true, true )->getText() );
		return $desc;
	}

}
