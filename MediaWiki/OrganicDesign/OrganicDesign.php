<?php
/**
 * OrganicDesign extension - an extension to encapsulate all the functionality specific to the OD wiki
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2012-2015 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'OD_VERSION', "2.0.3, 2015-03-20" );

// Allow cookies to work for either so that login pages can be HTTPS but the rest of the site HTTP
$wgCookieSecure = false;

$wgExtensionCredits['other'][] = array(
	'name'        => "OrganicDesign",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "An extension to encapsulate all the functionality specific to the Organic Design wiki",
	'url'         => "http://www.organicdesign.co.nz",
	'version'     => OD_VERSION
);

class OrganicDesign {

	public static $title = false;

	function __construct() {
		global $wgExtensionFunctions, $wgHooks, $wgLanguageCode;

		$wgExtensionFunctions[] = array( $this, 'setup' );
		$wgHooks['AjaxCommentsCheckTitle'][] = $this;
		$wgHooks['jQueryUploadAddAttachLink'][] = $this;
		$wgHooks['OutputPageBodyAttributes'][]  = $this;
		$wgHooks['BeforePageDisplay'][] = $this;

		// Set language to pt if it's the pt domain
		if( preg_match( "/^pt\./", $_SERVER['HTTP_HOST'] ) ) $wgLanguageCode = 'pt';

	}

	function setup() {
		global $wgOut, $wgExtensionAssetsPath, $wgResourceModules, $wgUser, $wgCommandLineMode;
		self::$title = array_key_exists( 'title', $_REQUEST ) ? Title::newFromText( $_REQUEST['title'] ) : false;

		// Bounce to the https www (if they're not https or not www)
		if( !$wgCommandLineMode ) {
			$host = preg_match( "|^(.+):\d+$|", $_SERVER['HTTP_HOST'], $m ) ? $m[1] : $_SERVER['HTTP_HOST'];
			$uri = $_SERVER['REQUEST_URI'];
			$ssl = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on';
			$od = preg_match( "/^(www|pt)\.organicdesign\.co\.nz$/", $host, $m );
			$www = $m[1] ? $m[1] : 'www';
			if( !$od || !$ssl ) {
				header( "Location: https://$www.organicdesign.co.nz$uri", true, 301 );
				exit;
			}
		}

		// Add the OD monobook modification styles and js
		$path  = $wgExtensionAssetsPath . '/' . basename( __DIR__ );
		$wgResourceModules['ext.organicdesign'] = array(
			'scripts'        => array( 'organicdesign.js' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__,
		);
		$wgOut->addModules( 'ext.organicdesign' );
		$wgOut->addStyle( "$path/organicdesign.css" );

		// Force the recentchanges to the JS format
		$wgUser->setOption( 'usenewrc', 1 );		

	}

	/**
	 * Only use AjaxComments if the title's not in the "No files or comments" category
	 */
	function onAjaxCommentsCheckTitle( $title, &$ret ) {
		$ret = $this->commentsAndUploads( $title );
		return true;
	}

	/**
	 * Only use jQuery uploads if it's a loan page and the current user can edit the talk page
	 */
	function onjQueryUploadAddAttachLink( $title, &$ret ) {
		$ret = $this->commentsAndUploads( $title );
		return true;
	}

	/**
	 * Return whether or now comments and uploads are allowed for the passed title
	 */
	function commentsAndUploads( $title ) {
		$ns = $title->getNamespace();
		if( $ns == 2 || $ns == 8 || $ns == 10 ) return false;
		return !self::inCat( 'No files or comments', $title );
	}

	/**
	 * Add group info to body tag
	 */
	public static function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ) {
		global $wgUser;

		// Add user group information
		if( $wgUser->isAnon() ) $bodyAttrs['class'] .= ' anon';
		if( $wgUser->isLoggedIn() ) $bodyAttrs['class'] .= ' user';
		if( in_array( 'sysop', $wgUser->getEffectiveGroups() ) ) $bodyAttrs['class'] .= ' sysop';
		else $bodyAttrs['class'] .= ' notsysop';

		// Add hide-cats if in Category:Hide categories
		if( self::inCat( 'Hide categories' ) ) $bodyAttrs['class'] .= ' hide-cats';

		return true;
	}

	public static function onBeforePageDisplay( $out, $skin ) {
		global $wgUser, $wgParser;
		if( is_object( $wgParser ) ) { $psr = $wgParser; $opt = $wgParser->mOptions; }
		else { $psr = new Parser; $opt = NULL; }
		if( !is_object( $opt ) ) $opt = ParserOptions::newFromUser( $wgUser );

		// Add sidebar content
		$title = Title::newFromText( 'Od-sidebar', NS_MEDIAWIKI );
		$article = new Article( $title );
		$html = $psr->parse( $article->getContent(), $title, $opt, true, true )->getText();
		$out->addHTML( "<div id=\"wikitext-sidebar\" style=\"display:none\">$html</div>" );

		// Add footer content
		$title = Title::newFromText( 'Footer', NS_MEDIAWIKI );
		$article = new Article( $title );
		$html = $psr->parse( $article->getContent(), $title, $opt, true, true )->getText();
		$out->addHTML( "<div id=\"wikitext-footer\" style=\"display:none\"><div id=\"od-footer\">$html</div></div>" );

		// Add the other items
		self::donations( $out );
		self::languages( $out );
		self::avatar( $out, $wgUser );

		return true;
	}

	public static function languages( $out ) {
		$out->addHTML( '<div id="languages-wrapper" style="display:none"><div id="languages">
			<a href="http://www.organicdesign.co.nz' . $_SERVER['REQUEST_URI'] . '" title="English"><img src="/wiki/skins/organicdesign/uk.png" /></a>
			<a href="http://pt.organicdesign.co.nz' . $_SERVER['REQUEST_URI'] . '" title="Português brasileiro"><img src="/wiki/skins/organicdesign/br.png" /></a>
		</div></div>' );
	}

	public static function donations( $out ) {
		global $wgOrganicDesignDonations;
		$out->addHTML( '<div id="donations-wrapper" style="display:none"><div class="portlet" id="donations">
		<h2 style="white-space:nowrap">' . wfMessage('tips-welcome') . '</h2>
		<h5>' . wfMessage('paypal-or-cc') . '</h5>
		<div class="pBody">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="' . $wgOrganicDesignDonations . '" />
				<input type="hidden" name="item_name" value="Donation to Organic Design">
				<input type="hidden" name="currency_code" value="USD">
				$<input style="width:35px" type="text" name="amount" value="5.00" />&nbsp;<input type="submit" value="' . wfMessage('checkout') . '" />
			</form>
		</div>
		<h5 id="btcbest">' . wfMessage( 'btc-awesome', '<a href="/Bitcoin">Bitcoins</a>' )->plain() . '</h5>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/a/a0/Bitcoin-icon.png) no-repeat 5px 2px;">
			<input style="width:139px;margin-left:23px" readonly="1" value="1Aran5dJVJVz1UVU8mLAGdrxCjCpZgm1Mz" onmouseover="this.select()" />
		</div>
		<h5 id="nmccool">' . wfMessage( 'also' ) . ' <a href="/XCurrency">XC</a>, <a href="/Ripple">XRP</a> & <a href="/Stellar">STR</a></h5>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/1/12/XC-icon.png) no-repeat 0px 4px;">
			<input style="width:139px;margin-left:23px" readonly="1" value="XNDhYkgwvNXjiK6178r9i25U9hYCJvd43S" onmouseover="this.select()" />
		</div>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/2/23/Ripple.png) no-repeat 5px 2px;">
			<input style="width:139px;margin-left:23px" readonly="1" value="rBSVzXKvPiRVKa4aBpr3SNqSem1RBDdhqy" onmouseover="this.select()" />
		</div>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/thumb/8/86/StellarLogo.png/30px-StellarLogo.png) no-repeat -1px 1px;">
			<input style="width:139px;margin-left:23px" readonly="1" value="gHAcuAzTNXzq7wM74znnWsZ1N92mJTpNZ9" onmouseover="this.select()" />
		</div></div></div>' );
	}

	public static function avatar( $out, $user ) {
		global $wgUploadDirectory, $wgUploadPath;
		if( $user->isLoggedIn() ) {
			$out->addHTML( '<div id="avatar-wrapper" style="display:none"><div id="p-avatar">' );
			$name  = $user->getName();
			$img = wfLocalFile( "$name.png" );
			if( is_object( $img  ) && $img->exists() ) {
				$url = $img->transform( array( 'width' => 50 ) )->getUrl();
				$out->addHTML( "<a href=\"" . $user->getUserPage()->getLocalUrl() . "\"><img src=\"$url\" alt=\"$name\"></a>" );
			} else {
				$upload = Title::newFromText( 'Upload', NS_SPECIAL );
				$url = $upload->getLocalUrl( "wpDestFile=$name.png" );
				$out->addHTML( "<a href=\"$url\" class=\"new\"><br>user<br>icon</a>" );
			}
			$out->addHTML( '</div></div>' );
		}
	}

	/**
	 * Return whether or not the passed title is a member of the passed cat
	 */
	public static function inCat( $cat, $title = false ) {
		global $wgTitle;
		if( $title === false ) $title = $wgTitle;
		if( !is_object( $title ) ) $title = Title::newFromText( $title );
		$id   = $title->getArticleID();
		$dbr  = wfGetDB( DB_SLAVE );
		$cat  = $dbr->addQuotes( Title::newFromText( $cat, NS_CATEGORY )->getDBkey() );
		return $dbr->selectRow( 'categorylinks', '1', "cl_from = $id AND cl_to = $cat" );
	}

}
new OrganicDesign();

