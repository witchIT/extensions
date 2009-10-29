<?php
/**
 * MarketResearch extension
 *
 * Version 0.0.1 started 2008-07-12
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @licence GNU General Public Licence 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'MARKETRESEARCH_VERSION', '1.1.0, 2009-10-29' );

$wgMarketResearchPartner  = 'partnerid-not-set';
$wgMarketResearchSearch   = 'http://www.marketresearch.com/feed/search_results.asp?bquery=$2&partnerid=$1';
$wgMarketResearchCart     = 'http://www.marketresearch.com/feed/cart/addtocart.asp';
$wgMarketResearchView     = 'http://www.marketresearch.com/feed/cart/ViewCart.asp';
$wgMarketResearchExpiry   = 600; # Number of seconds a cached entry should live for
$wgMarketResearchDescSize = 500; # Max characters to render of a description
$wgMarketResearchLink     = 'http://www.govitwiki.com';
$wgMarketResearchName     = 'GovITwiki Research Report Store';
$wgMarketResearchImages   = preg_replace( '|^.+(?=[/\\\\]extensions)|', $wgScriptPath, dirname( __FILE__ ) . '/images' );

# Extension credits that show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'MarketResearch',
	'author'      => '[http://www.organicdesign.co.nz/nad Nad] for [http://www.wikiexpert.com WikiExpert]',
	'url'         => 'http://www.organicdesign.co.nz/Extension:MarketResearch',
	'description' => 'Display market research information',
	'version'     => MARKETRESEARCH_VERSION
);

$wgSpecialPages['MarketResearch'] = 'SpecialMarketResearch';
$wgExtensionFunctions[] = "wfSetupMarketResearch"; 

require_once "$IP/includes/SpecialPage.php";

/**
 * The callback function for converting the input text to HTML output
 */
function wfMarketResearchTag( $input ) {
	global $wgTitle, $wgMarketResearchPartner, $wgMarketResearchSearch, $wgMarketResearchDescSize,
		$wgMarketResearchTable, $wgMarketResearchExpiry, $wgMarketResearchImages;

	# Add keywords and product id into feed URL
	$keywords = preg_split( '/[\x00-\x1f+,]+/', strtolower( trim( $input ) ) );
	$search   = str_replace( '$1', $wgMarketResearchPartner, $wgMarketResearchSearch );
	$search   = str_replace( '$2', join( '+', $keywords ), $search );
	$keywords = join( ',', $keywords );
	$ts       = time();
	$db       = &wfGetDB( DB_MASTER );
	$return   = urlencode( $wgTitle->getFullURL() );
	$html     = "";
	$xml      = false;
	$doc      = false;

	# Remove expired items & attempt to read current item
	if ( $wgMarketResearchTable ) {

		# Auto-expire items which are really old
		$db->delete( $wgMarketResearchTable, array( "mrc_time < " . ( $ts - 10 * $wgMarketResearchExpiry ) ), __FUNCTION__ );

		# Read current cache entry for this item if one exists
		# - if this cache item is older than the expiry time, ensure an attempt will be made to renew it
		if ( $row = $db->selectRow( $wgMarketResearchTable, 'mrc_xml', "mrc_keywords = '$keywords'", __FUNCTION__ ) ) {
			$xml = $row->mrc_xml;
			$doc = new DOMDocument();
			$doc->loadXML( $xml );
			if ( $row->mrc_time < $ts - $wgMarketResearchExpiry ) $xml = false;
		}

	}

	# Load the item from the source (and cache if enabled)
	if ( $xml === false ) {

		# Attempt to load XML and convert to a DOM object
		$xml = file_get_contents( $search );
		$tmp = new DOMDocument();
		$tmp->loadXML( $xml );

		# Try again if it failed to load or convert
		if ( !is_object( $tmp ) ) {
			$xml = file_get_contents( $search );
			$tmp = new DOMDocument();
			$tmp->loadXML( $xml );
		}

		# If it loaded, update or create cache entry
		if ( is_object( $tmp ) ) {
			$doc = $tmp;
			if ( $wgMarketResearchTable ) {
				$db->delete( $wgMarketResearchTable, array( "mrc_keywords = '$keywords'" ), __FUNCTION__ );
				$row = array(
					'mrc_keywords' => $keywords,
					'mrc_time'     => $ts,
					'mrc_xml'      => $xml
				);
				$db->insert( $wgMarketResearchTable, $row, __FUNCTION__ );
			}
		}

	}

	# Render the item if a DOM object has been created
	if ( is_object( $doc ) ) {

		$jump = Title::makeTitle( NS_SPECIAL, 'MarketResearch' );
		$buy = "<img src=\"$wgMarketResearchImages/more-info.gif\" />";
		
		# Loop through products
		foreach ( $doc->getElementsByTagName( 'PRODUCT' ) as $product ) {
			$id = $product->getAttribute( 'id' );
			foreach ( $product->getElementsByTagName( 'TITLE' ) as $i)       $title   = $i->textContent;
			foreach ( $product->getElementsByTagName( 'VENDOR' ) as $i)      $vendor  = $i->textContent;
			foreach ( $product->getElementsByTagName( 'DESCRIPTION' ) as $i) $desc    = $i->textContent;
			if ( strlen( $desc ) > $wgMarketResearchDescSize ) $desc = substr( $desc, 0, $wgMarketResearchDescSize ) . '...';

			# Jump page link
			$link = "<a href=\"" . $jump->getLocalURL( "keywords=$keywords&productid=$id&returnURL=$return" ) . "\">$buy</a>\n";

			# render this item
			$html .= "<div class=\"marketresearch-item\">\n<h3>$title by $vendor</h3>\n<p>$desc</p>\n$link\n</div>";
		}
	} else $html = "no valid XML returned or found in cache!<br /><br />The following content was returned:<br /><pre>$xml</pre>";

	return "<div class=\"marketresearch\">$html</div>";
}

/**
 * An unlisted special page used as the "jump page" to a product
 */
class SpecialMarketResearch extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'MarketResearch', '', false, false, false, false );
	}
 
	public function execute( $param ) {
		global $wgOut, $wgParser, $wgRequest,
			$wgMarketResearchTable, $wgMarketResearchPartner, $wgMarketResearchCart, $wgMarketResearchView,
			$wgMarketResearchLink, $wgMarketResearchName, $wgMarketResearchImages;
		
		$this->setHeaders();
		$title    = Title::makeTitle( NS_SPECIAL, 'MarketResearch' );
		$keywords = $wgRequest->getText( 'keywords' );
		$product  = $wgRequest->getText( 'productid' );
		$return   = $wgRequest->getText( 'returnURL' );

		# Read query XML from cache
		$db = &wfGetDB( DB_SLAVE );
		if ( $row = $db->selectRow( $wgMarketResearchTable, 'mrc_xml', "mrc_keywords = '$keywords'", __FUNCTION__ ) ) {
			$doc = new DOMDocument();
			$xml = $row->mrc_xml;
			$doc->loadXML( $xml );
		} else $doc = false;

		# Parse XML and render
		if ( is_object( $doc ) ) {
			$wgOut->addHTML( "<form name=\"mr-form\" action=\"$wgMarketResearchCart\" method=\"GET\">\n" );
			foreach ( $doc->getElementsByTagName( 'PRODUCT' ) as $p ) if ( $p->getAttribute( 'id' ) == $product ) {
				foreach ( $p->getElementsByTagName( 'TITLE' ) as $i )       $title   = $i->textContent;
				foreach ( $p->getElementsByTagName( 'VENDOR' ) as $i )      $vendor  = $i->textContent;
				foreach ( $p->getElementsByTagName( 'DESCRIPTION' ) as $i ) $desc    = $i->textContent;
				$wgOut->addWikiText( "== $title by $vendor ==\n" );
				$wgOut->addHTML( $desc );
				$options = "";
				foreach ( $p->getElementsByTagName( 'BUY' ) as $i ) {
					$id       = $i->getAttribute( 'id' );
					$name     = $i->textContent;
					$price    = '$'.$i->getAttribute( 'price' ) . '.00';
					$options .= "<option value=\"$id\">$price ($name)</option>\n";
				}
				$wgOut->addHTML( "<br><select name=\"productid\">$options</select>" );
				$wgOut->addHTML( "&nbsp;<input type=\"image\" src=\"$wgMarketResearchImages/add-to-cart.gif\" />\n" );
				$wgOut->addHTML( "<br><a href=\"$wgMarketResearchView?partnerid=$wgMarketResearchPartner&returnURL=$return\">View cart</a>" );
				$wgOut->addHTML( " | <a href=\"$wgMarketResearchLink\">$wgMarketResearchName</a>" );
				$wgOut->addHTML( " | <a href=\"javascript:history.go(-1)\">Back</a>" );
			}
			$wgOut->addHTML( "<input type=\"hidden\" name=\"partnerid\" value=\"$wgMarketResearchPartner\" />\n" );
			$wgOut->addHTML( "<input type=\"hidden\" name=\"returnURL\" value=\"$return\" />\n" );
			$wgOut->addHTML( "</form>\n" );
		} else $html = "no valid XML returned or found in cache!<br /><br />The following content was returned:<br /><pre>$xml</pre>";
	}
}

/**
 * Install the hook and special page
 */
function wfSetupMarketResearch() {
	global $wgParser, $wgRequest, $wgOut, $egSelenium, $wgLanguageCode, $wgMessageCache, $wgMarketResearchTable;
	$wgParser->setHook( "marketresearch", "wfMarketResearchTag" );

	# Create cache DB table if it doesn't exist
	$db = &wfGetDB( DB_MASTER );
	$wgMarketResearchTable = $db->tableName( 'MarketResearchCache' );
	if ( !$db->tableExists( $wgMarketResearchTable ) ) {
		$query = "CREATE TABLE $wgMarketResearchTable (mrc_keywords TINYBLOB, mrc_time INTEGER NOT NULL, mrc_xml MEDIUMBLOB);";
		$result = $db->query( $query );
	}

	# If the table couldn't be created, disable IPN and add error to site notice
	if ( !$db->tableExists( $wgMarketResearchTable ) ) {
		global $wgSiteNotice;
		$wgMarketResearchTable = false;
		$wgSiteNotice = "<div class=\"errorbox\"><b>MarketResearch extension problem! Could not create database table, caching functionality is disabled.</b><br>Please ensure the wiki database user has CREATE permission, or add the table manually with the following query:<br><tt>$query</tt></div>";
	}

	# Add any messages
	if ( $wgLanguageCode == 'en' ) {
		$wgMessageCache->addMessages( array(
			'marketresearch' => "Details on This Report"
		) );
	}

	SpecialPage::addPage( new SpecialMarketResearch() );
}

