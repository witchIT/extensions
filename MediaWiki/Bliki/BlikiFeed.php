<?php
/**
 * Our BlikiChanges feed uses this custom ChangesFeed and custom selection query
 * which has the item description changed to the plain-text blog item summary instead of the usual diff/wikitext\
 *
 * See http://www.organicdesign.co.nz/bliki for more detail
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @copyright © 2013-2015 [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @licence GNU General Public Licence 2.0 or later
 */

class BlikiChangesFeed extends ChangesFeed {

	// This is just an exact copy of the parent, we had to override because it calls self::generateFeed
	public function execute( $feed, $rows, $lastmod, $opts ) {
		global $wgLang, $wgRenderHashAppend;

		if ( !FeedUtils::checkFeedOutput( $this->format ) ) {
			return null;
		}

		$optionsHash = md5( serialize( $opts->getAllValues() ) ) . $wgRenderHashAppend;
		$timekey = wfMemcKey( $this->type, $this->format, $wgLang->getCode(), $optionsHash, 'timestamp' );
		$key = wfMemcKey( $this->type, $this->format, $wgLang->getCode(), $optionsHash );

		FeedUtils::checkPurge( $timekey, $key );

		$cachedFeed = $this->loadFromCache( $lastmod, $timekey, $key );
		if( is_string( $cachedFeed ) ) {
			$feed->httpHeaders();
			echo $cachedFeed;
		} else {
			ob_start();
			self::generateFeed( $rows, $feed );
			$cachedFeed = ob_get_contents();
			ob_end_flush();
			$this->saveToCache( $cachedFeed, $timekey, $key );
		}
		return true;
	}

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
	public static function desc( $title ) {
		global $wgParser;
		$article = new Article( $title );
		$content = $article->getContent();
		$text = preg_match( "/^.+?1=(.+?)\|2=/s", $content, $m ) ? $m[1] : $title->getText();
		$html = $wgParser->parse( trim( $text ), $title, new ParserOptions(), true, true )->getText();
		$html = preg_replace( '|<a[^<]+<img .+?</a>|', '', $html );
		$desc = strip_tags( $html, '<p><a><i><b><u><s>' );
		$desc = preg_replace( "/[\r\n]+/", "", $desc );
		$desc = preg_replace( "|<p></p>|", "", $desc );
		$desc = trim( preg_replace( "|<p>|", "\n<p>", $desc ) );
		return $desc;
	}
}
