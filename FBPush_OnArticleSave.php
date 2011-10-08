<?php
/**
 * @author Sean Colombo, adjusted by aran@organicdesign.co.nz
 *
 * Pushes an item to Facebook wall on ArticleSave event
 */

global $wgExtensionMessagesFiles, $wgFbPushEventClasses, $wgFbTimeBetweenPosts;
$wgExtensionMessagesFiles['FBPush_OnArticleSave'] = dirname( __FILE__ ) . '/FBPush_OnArticleSave.i18n.php';
$wgFbPushEventClasses[] = 'FBPush_OnArticleSave';
$wgFbTimeBetweenPosts = 0;

class FBPush_OnArticleSave extends FacebookPushEvent {
	protected $isAllowedUserPreferenceName = 'facebook-push-allow-OnEdit';
	static $messageName = 'facebook-msg-OnEdit';
	static $eventImage = 'edits.png';

	public function init() {
		global $wgHooks;
		$wgHooks['ArticleSaveComplete'][] = $this;
	}

	function onArticleSaveComplete( &$article, &$user, $text, $summary, $minor, $watch, $sec, &$flags, $rev, &$status, $baseId ) {
		global $wgSitename, $wgFbTimeBetweenPosts;

		if( $rev ) {

			$ts = $rev->getTimestamp();
			$tsPrev = $user->getOption( 'fb-last-edit-timestamp' );
			$timediff = $ts - $tsPrev;
$summary = "time since last: $timediff : $ts : $tsPrev";
			// Post to wall if greater then specified period
			if( $timediff >= $wgFbTimeBetweenPosts ) {
				$params = array(
					'$ARTICLENAME' => $article->getTitle()->getPrefixedText(),
					'$WIKINAME' => $wgSitename,
					'$ARTICLE_URL' => $article->getTitle()->getFullURL( 'ref=fbfeed&fbtype=articlesave' ),
					'$EVENTIMG' => self::$eventImage,
					'$SUMMARY' => $summary,
					'$TEXT' => self::shortenText( self::parseArticle( $article, $text ) )
				);
				self::pushEvent( self::$messageName, $params, __CLASS__ );
				$user->setOption( 'fb-last-edit-timestamp', $ts );
				$user->saveSettings();
			}

		}

		return true;
	}
}

