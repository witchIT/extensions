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
define( 'OD_VERSION', "2.0.8, 2016-08-08" );

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
		global $wgExtensionFunctions, $wgHooks, $wgLanguageCode, $wgGroupPermissions;

		$wgGroupPermissions['family']['edit'] = true;

		$wgExtensionFunctions[] = array( $this, 'setup' );
		$wgHooks['AjaxCommentsCheckTitle'][] = $this;
		$wgHooks['jQueryUploadAddAttachLink'][] = $this;
		$wgHooks['OutputPageBodyAttributes'][]  = $this;
		$wgHooks['AfterFinalPageOutput'][] = $this;

		// Set language to pt if it's the pt domain
		if( preg_match( "/\.br$/", $_SERVER['HTTP_HOST'] ) ) $wgLanguageCode = 'pt-br';

	}

	function setup() {
		global $wgOut, $wgExtensionAssetsPath, $wgResourceModules, $wgUser, $wgCommandLineMode;
		self::$title = array_key_exists( 'title', $_REQUEST ) ? Title::newFromText( $_REQUEST['title'] ) : false;

		// Bounce to the https www (if they're not https or not www)
		if( !$wgCommandLineMode ) {
			$host = preg_match( "|^(.+):\d+$|", $_SERVER['HTTP_HOST'], $m ) ? $m[1] : $_SERVER['HTTP_HOST'];
			$uri = $_SERVER['REQUEST_URI'];
			$ssl = array_key_exists( 'HTTPS', $_SERVER ) && $_SERVER['HTTPS'] == 'on';
			$od = preg_match( "|^www\.organicdesign\.(.+)$|", $host, $m );
			$tld = $m[1] ? $m[1] : 'co.nz';
			if( !$od || !$ssl ) {
				header( "Location: https://www.organicdesign.$tld$uri", true, 301 );
				global $mediaWiki;
				if( is_object( $mediaWiki ) ) $mediaWiki->restInPeace();
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
	 * Return whether or not comments and uploads are allowed for the passed title
	 */
	function commentsAndUploads( $title ) {
		if( !is_object( $title ) ) return false;
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

		// Microdata
		$bodyAttrs['itemscope'] = '';
		$bodyAttrs['itemtype'] = 'http://schema.org/WebPage';

		return true;
	}

	/**
	 * Modify the page before output
	 */
	public static function onAfterFinalPageOutput( $output ) {
		$out = ob_get_clean();

		// Heading
		$out = str_replace(
			$match = '<h1 id="firstHeading"',
			$match . ' itemprop="name"',
			$out
		);

		// Sidebar
		$title = Title::newFromText( 'Od-sidebar', NS_MEDIAWIKI );
		$article = new Article( $title );
		$html = $output->parse( $article->getPage()->getContent()->getNativeData() );
		$out = str_replace(
			$match = '<div class="portlet" id="p-tb"',
			$html . $match,
			$out
		);

		// Footer
		$title = Title::newFromText( 'Footer', NS_MEDIAWIKI );
		$article = new Article( $title );
		$html = $output->parse( $article->getPage()->getContent()->getNativeData() );
		$out = preg_replace(
			'#<div( id="footer".*?>)#',
			"<div itemscope itemtype=\"http://www.schema.org/WPFooter\"$1\n<div id=\"od-footer\">$html</div>",
			$out
		);

		// Add the other items
		$out = preg_replace(
			'#(id="p-logo".+?</div>)#s',
			"$1\n" . self::avatar() . self::social() . self::donations(),
			$out
		);

		// Languages and content microdata
		$out = str_replace(
			$match = '<div id="column-content"',
			self::languages() . $match . ' itemprop="mainContentOfPage" itemscope itemtype="http://schema.org/WebPageElement"',
			$out
		);

		// Sidebar microdata
		$out = str_replace(
			$match = 'id="column-one"',
			$match . ' itemscope itemtype="http://www.schema.org/WPSideBar"',
			$out
		);

		ob_start();
		echo $out;
		return true;
	}

	public static function languages() {
		return '<div id="languages" itemscope itemtype="http://www.schema.org/SiteNavigationElement">
			<a href="https://www.organicdesign.co.nz' . $_SERVER['REQUEST_URI'] . '" title="English"><img src="/wiki/skins/organicdesign/uk.png" /></a>
			<a href="https://www.organicdesign.com.br' . $_SERVER['REQUEST_URI'] . '" title="Português brasileiro"><img src="/wiki/skins/organicdesign/br.png" /></a>
		</div>';
	}

	public static function social() {
		$social = '<div id="social">';
		$social .= '<a title="GNU Social" href="https://social.organicdesign.co.nz"><img src="/files/0/0e/Gnusocial_32.png" alt="GNU Social" /></a>';
		$social .= '<a title="RSS" href="https://www.organicdesign.co.nz/wiki/api.php?action=blikifeed"><img src="/files/6/6d/Rss_32.png" alt="RSS" /></a>';
		$social .= '<a title="Github" href="https://github.com/OrganicDesign"><img src="/files/c/c0/Github_32.png" alt="Github" /></a>';
		$social .= '<a title="Email" href="https://www.organicdesign.co.nz/contact"><img src="/files/e/e6/Email_32.png" alt="Email" /></a>';
		$social .= '<a title="Twitter" href="https://twitter.com/AranDunkley"><img src="/files/0/00/Twitter_32.png" alt="Twitter" /></a>';
		$social .= '<a title="Facebook" href="https://www.facebook.com/organicdesign.co.nz"><img src="/files/8/81/Facebook_32.png" alt="Facebook" /></a>';
		$social .= '</div>';
		return $social;
	}

	public static function donations() {
		global $wgOrganicDesignDonations, $wgOrganicDesignDonationsBTC;
		return '<div class="portlet" id="donations">
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
		<h5 id="btcbest">' . wfMessage( 'btc-awesome' )->parse() . '</h5>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/a/a0/Bitcoin-icon.png) no-repeat 5px 6px;">
			<input style="width:139px;margin-left:23px" readonly="1" value="' . $wgOrganicDesignDonationsBTC . '" onmouseover="this.select()" />
		</div>
		<h5 id="paymentopts">' . wfMessage( 'see-donations'  )->parse() . '</h5>
		</div>';
	}

	public static function avatar() {
		global $wgUploadDirectory, $wgUploadPath, $wgUser;
		if( $wgUser->isLoggedIn() ) {
			$out = '<div id="p-avatar">';
			$name  = $wgUser->getName();
			$img = wfLocalFile( "$name.png" );
			if( is_object( $img  ) && $img->exists() ) {
				$url = $img->transform( array( 'width' => 50 ) )->getUrl();
				$out .= "<a href=\"" . $wgUser->getUserPage()->getLocalUrl() . "\"><img src=\"$url\" alt=\"$name\"></a>";
			} else {
				$upload = Title::newFromText( 'Upload', NS_SPECIAL );
				$url = $upload->getLocalUrl( "wpDestFile=$name.png" );
				$out .= "<a href=\"$url\" class=\"new\"><br>user<br>icon</a>";
			}
			$out .= '</div>';
		} else $out = '';
		return $out;
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

