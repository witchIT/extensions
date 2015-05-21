<?php
/**
 * CodeTidy extension - Simple wrapper around the Organic Design tidy.php util to make a web-based code tidier that matches the MediaWiki conventions
 * - see https://github.com/OrganicDesign/tools/blob/git-svn/tidy.php
 *       https://www.mediawiki.org/wiki/Manual:Coding_conventions
 *       https://www.mediawiki.org/wiki/Manual:Coding_conventions/PHP
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @copyright © 2015 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 * 
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'CODETIDY_VERSION', '0.0.1, 2015-05-21' );
$wgSpecialPages['CodeTidy'] = 'SpecialCodeTidy';
$wgExtensionCredits['specialpage'][] = array(
	'path'        => __FILE__,
	'name'        => 'CodeTidy',
	'author'      => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:CodeTidy',
	'description' => 'Simple wrapper around the Organic Design [https://github.com/OrganicDesign/tools/blob/git-svn/tidy.php tidy.php utility] to make a web-based code tidier that matches the [https://www.mediawiki.org/wiki/Manual:Coding_conventions/PHP MediaWiki conventions]',
	'version'     => CODETIDY_VERSION
);

// Include the Organic Design CodeTidy class
require( '/var/www/tools/tidy.php' );

$wgExtensionMessagesFiles['CodeTidy'] = __DIR__ . '/CodeTidy.i18n.php';

class SpecialCodeTidy extends SpecialPage {

	function __construct() {
		parent::__construct( 'CodeTidy', '' );
	}

	public function execute( $param ) {
		global $wgOut, $wgRequest;
		$this->setHeaders();
		if( $code = $wgRequest->getText( 'wpSource' ) ) $this->tidy( $code );
		else {
			$wgOut->addWikiText( '=== Enter your code here: ===' );
			$wgOut->addHTML( '<form method="POST"><textarea name="wpSource" cols="50" rows="30"></textarea><input type="submit" value="Tidy!" /></form>' );
		}
	}

	private function tidy( $code ) {
		global $wgOut;
		$tidy = CodeTidy::tidy( $code );
		$wgOut->addWikiText( '=== Tidied code ===' );
		$wgOut->addWikiText( "<php>$tidy</php>" );
	}
}
