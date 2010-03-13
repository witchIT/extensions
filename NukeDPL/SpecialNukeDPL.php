<?php
/**
 * NukeDPL extension - Mass delete by DPL query
 * {{Category:Extensions|NukeDPL}}{{php}}
 * See http://www.mediawiki.org/wiki/Extension:NukeDPL for installation and usage details
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) die( 'Not a valid entry point.' );

define( 'NUKEDPL_VERSION', '1.2.1, 2009-03-20' );

$wgGroupPermissions['sysop']['nuke'] = true;
$wgAvailableRights[]                 = 'nuke';
$wgExtensionFunctions[]              = 'wfSetupNukeDPL';
$wgSpecialPages['NukeDPL']           = 'SpecialNukeDPL';
$wgSpecialPageGroups['NukeDPL']      = 'pagetools';

# Text to be added into textbox by default
$wgNukeDPLDefaultText = '
distinct          = true | false
ignorecase        = true | false
title             = Article
nottitle          = Article
titlematch        = %fragment%
nottitlematch     = %fragment%
titleregexp       = ^.+$
nottitleregexp    = ^.+$
category          = Category1 | Category2
notcategory       = Category1 | Category2
categorymatch     = %fragment%
notcategorymatch  = %fragment%
categoryregexp    = ^.+$
notcategoryregexp = ^.+$
namespace         = Namespace1 | Namespace2
notnamespace      = Namespace1 | Namespace2
linksfrom         = Foo | Bar
notlinksfrom      = Foo | Bar
linksto           = Foo|Bar
notlinksto        = Foo|Bar
imageused         = Foo.jpg
imagecontainer    = Article1 | Article2
uses              = Template1 | Template2
notuses           = Template1 | Template2
redirects         = exclude | include | only
createdby         = User
notcreatedby      = User
modifiedby        = User
notmodifiedby     = User
lastmodifiedby    = User
notlastmodifiedby = User
';

$wgExtensionCredits['specialpage'][] = array(
	'name'        => 'Special:NukeDPL',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Mass delete by DPL query',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:NukeDPL',
	'version'     => NUKEDPL_VERSION
);

function wfSetupNukeDPL() {
	global $wgMessageCache;
	$wgMessageCache->addMessages( array(
		'nukedpl'            => 'Mass delete by DPL query',
		'nuke-nopages'       => "No pages to delete using DPL-query: <tt>$1</tt>",
		'nuke-list'          => "The following pages were selected by DPL-query: <tt>$1</tt> hit the button to delete them.",
		'nuke-defaultreason' => "Mass removal of pages selected by DPL-query: ($1)",
	) );
}

/**
 * Define a new class based on the SpecialPage class
 */
class SpecialNukeDPL extends SpecialPage {

	function __construct() {
		parent::__construct( 'NukeDPL', 'nuke' );
	}
 
	function execute( $par ) {
		global $wgUser, $wgRequest;

		if ( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();
		$this->outputHeader();

		$target = $wgRequest->getText( 'target' );
		$reason = $wgRequest->getText( 'wpReason', wfMsgForContent( 'nuke-defaultreason', $target ) );
		$posted = $wgRequest->wasPosted() && $wgUser->matchEditToken( $wgRequest->getVal( 'wpEditToken' ) );

		if ( $posted ) {
			if ( $pages = $wgRequest->getArray( 'pages' ) ) return $this->doDelete( $pages, $reason );
		}

		if ( $target ) $this->listForm( $target, $reason ); else $this->promptForm();
	}

	function promptForm() {
		global $wgUser, $wgOut, $wgNukeDPLDefaultText;

		$sk =& $wgUser->getSkin();
		$nuke = Title::makeTitle( NS_SPECIAL, 'NukeDPL' );
		$submit = wfElement( 'input', array( 'type' => 'submit', 'value' => 'View candidate list' ) );

		$wgOut->addWikiText( "This tool allows for mass deletions of pages selected by a DPL query.<br>" );
		$wgOut->addWikiText( "Enter a query below to generate a list of titles to delete." );
		$wgOut->addWikiText( "*Titles can be individually removed before deleting." );
		$wgOut->addWikiText( "*Remember, article titles are case-sensitive." );
		$wgOut->addWikiText( "*Queries shouldn't be surrounded by any DPL tags or braces." );
		$wgOut->addWikiText( "*For information about the parameter meanings, see the [http://semeb.com/dpldemo/index.php?title=DPL:Manual DPL Manual]." );
		$wgOut->addHTML( wfElement( 'form', array( 'action' => $nuke->getLocalURL( 'action=submit' ), 'method' => 'post' ), null )
			. "<textarea name=\"target\" cols=\"25\" rows=\"30\">$wgNukeDPLDefaultText</textarea>"
			. "\n$submit\n" );
		$wgOut->addHTML( "</form>" );
	}

	function listForm( $query, $reason ) {
		global $wgUser, $wgOut, $wgLang;

		$pages = $this->getPages( $query );
		if ( count( $pages ) == 0 ) {
			$wgOut->addWikiText( wfMsg( 'nuke-nopages', $query ) );
			return $this->promptForm();
		}

		$wgOut->addWikiText( wfMsg( 'nuke-list',$query ) );

		$nuke = Title::makeTitle( NS_SPECIAL, 'NukeDPL' );
		$submit = wfElement( 'input', array( 'type' => 'submit', 'value' => 'Nuke!' ) );
		$wgOut->addHTML(
			wfElement( 'form', array( 'action' => $nuke->getLocalURL( 'action=delete' ), 'method' => 'post' ), null )
			."\n<div>".wfMsgHtml( 'deletecomment' ) . ': '
			.wfElement( 'input', array( 'name' => 'wpReason', 'value' => $reason, 'size' => 60 ) ) . "</div>\n$submit"
			.wfElement( 'input', array( 'type' => 'hidden', 'name' => 'wpEditToken', 'value' => $wgUser->editToken() ) ) . "\n<ul>\n"
		);

		$sk =& $wgUser->getSkin();
		foreach ( $pages as $title ) {
			$wgOut->addHTML( '<li>'
				.wfElement( 'input', array( 'type' => 'checkbox', 'name' => "pages[]", 'value' => $title, 'checked' => 'checked' ) )
				.'&nbsp;' . $sk->makeKnownLinkObj( Title::newFromText( $title ) ) . "</li>\n"
			);
		}
		$wgOut->addHTML( "</ul>\n$submit</form>" );
	}

	function getPages( $query ) {
		global $wgTitle, $wgParser, $wgUser;
		$fname = 'NukeDPLForm::getNewPages';
		$query = trim( $query ) . "\nmode=userformat\nlistseparators=,\\n$$$%PAGE%$$$,,\n";
		$opt = ParserOptions::newFromUser( $wgUser );
		$out = $wgParser->parse( "<dpl>$query</dpl>", $wgTitle, $opt, false, true );
		preg_match_all( '|\\${3}(.+?)\\${3}|m', $out->getText(), $matches );
		return str_replace( array( '&nbsp;', '&amp;' ), array( ' ', '&' ), $matches[1] );
	}

	function doDelete( $pages, $reason ) {
		foreach ( $pages as $page ) {
			$title = Title::newFromUrl( $page );
			$article = new Article( $title );
			$article->doDelete( $reason );
		}
	}
}

