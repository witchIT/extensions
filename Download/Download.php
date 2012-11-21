<?php
/**
 * Download extension - Provides a tag which renders a list of image titles as downloadable links
 *
 * See http://www.organicdesign.co.nz/Extension:Download for installation and usage details
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined('MEDIAWIKI') ) die( 'Not an entry point.' );

define( 'DOWNLOAD_VERSION', '1.0.4, 2012-11-21' );

$egDownloadTag          = "download";
$egDownloadImages       = dirname(__FILE__)."/images";
$wgExtensionFunctions[] = 'efSetupDownload';

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'Download',
	'author'      => '[http://www.mediawiki.org/wiki/User:Nad User:Nad]',
	'description' => 'Provides a tag which renders a list of image titles as downloadable links',
	'url'         => 'http://www.organicdesign.co.nz/Extension:Download',
	'version'     => DOWNLOAD_VERSION
	);

class Download {

	function __construct() {
		global $wgParser, $egDownloadTag;
		$wgParser->setHook( $egDownloadTag, array( $this, 'tagDownload' ) );
	}

	function tagDownload( $text, $argv, &$parser ) {
		global $wgScriptPath, $egDownloadImages;
		$dir = preg_replace( '|^.+(?=[/\\\\]extensions)|', $wgScriptPath, $egDownloadImages );
		preg_match_all( '|^\s*(.+?)\s*(\|(.+?))?\s*$|m', $text, $links );
		$text = "<table class=\"gallery download-gallery\" cellspacing=\"0\" cellpadding=\"0\">\n";
		$cols = isset( $argv['cols'] ) && is_numeric( $argv['cols'] ) ? $argv['cols'] : 4;
		$row = "";
		foreach( $links[3] as $i => $link ) {
			$page = $links[1][$i];
			$icon = glob( "$egDownloadImages/default.*" );
			$img = wfLocalFile( Title::newFromText( $page )->getText() . '.jpg' );
			if( $src = $img && $img->exists() ? $img->getUrl() : false ) {
				$ext = preg_match( '|^.+\.(.+?)$|', $src, $m ) ? $m[1] : 'default';
				if( count( $j = glob( "$egDownloadImages/$ext.*" ) ) > 0 ) $icon = $j; 
				$item = "<a href=\"$src\">$link</a>";
			} else $item = "No file associated with <b>$page</b>";
			$icon = "<img src=\"$dir/" . basename( $icon[0] )."\" width=\"128\" height=\"128\" alt=\"\" />";
			$icon = "<a class=\"image\" title=\"$page\" href=\"$src\">$icon</a>";
			$row .= "<td><div class=\"gallerybox\" style=\"width: 158px;\">\n";
			$row .= "<div class=\"thumb\" style=\"padding: 13px 0pt; width: 158px;\">\n";
			$row .= "<div style=\"margin-left: auto; margin-right: auto; width: 128px;\">\n";
			$row .= "$icon\n</div>\n</div>\n";
			$row .= "<div class=\"gallerytext\">$item</div></div></td>\n";
			if( $i%$cols == 3 ) {
				$text .= "<tr>\n$row</tr>\n";
				$row = "";
			}
		}
		$row = $row ? "<tr>\n$row</tr>\n" : "";
		$text .= "$row</table>\n";
		return $text;
	}
}

function efSetupDownload() {
	global $egDownload;
	$egDownload = new Download();
}
