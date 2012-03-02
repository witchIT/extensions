<?php

class NukeDPL extends SpecialPage {

	function __construct() {
		parent::__construct( 'NukeDPL', 'nuke' );
	}
 
	function execute( $par ) {
		global $wgUser, $wgRequest;

		if( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();
		$this->outputHeader();

		$target = $wgRequest->getText( 'target' );
		$reason = $wgRequest->getText( 'wpReason', wfMsgForContent( 'nuke-defaultreason', $target ) );
		$posted = $wgRequest->wasPosted() && $wgUser->matchEditToken( $wgRequest->getVal( 'wpEditToken' ) );

		if( $posted ) {
			if( $pages = $wgRequest->getArray( 'pages' ) ) return $this->doDelete( $pages, $reason );
		}

		if( $target ) $this->listForm( $target, $reason ); else $this->promptForm();
	}

	function promptForm() {
		global $wgUser, $wgOut, $wgNukeDPLDefaultText;

		$sk =& $wgUser->getSkin();
		$nuke = Title::makeTitle( NS_SPECIAL, 'NukeDPL' );
		$submit = Xml::element( 'input', array( 'type' => 'submit', 'value' => 'View candidate list' ) );

		$wgOut->addWikiText( "This tool allows for mass deletions of pages selected by a DPL query.<br>" );
		$wgOut->addWikiText( "Enter a query below to generate a list of titles to delete." );
		$wgOut->addWikiText( "*Titles can be individually removed before deleting." );
		$wgOut->addWikiText( "*Remember, article titles are case-sensitive." );
		$wgOut->addWikiText( "*Queries shouldn't be surrounded by any DPL tags or braces." );
		$wgOut->addWikiText( "*For information about the parameter meanings, see the [http://semeb.com/dpldemo/index.php?title=DPL:Manual DPL Manual]." );
		$wgOut->addHTML( Xml::element( 'form', array( 'action' => $nuke->getLocalURL( 'action=submit' ), 'method' => 'post' ), null )
			. "<textarea name=\"target\" cols=\"25\" rows=\"30\">$wgNukeDPLDefaultText</textarea>"
			. "\n$submit\n" );
		$wgOut->addHTML( "</form>" );
	}

	function listForm( $query, $reason ) {
		global $wgUser, $wgOut, $wgLang;

		$pages = $this->getPages( $query );
		if( count( $pages ) == 0 ) {
			$wgOut->addWikiText( wfMsg( 'nuke-nopages', $query ) );
			return $this->promptForm();
		}

		$wgOut->addWikiText( wfMsg( 'nuke-list',$query ) );

		$nuke = Title::makeTitle( NS_SPECIAL, 'NukeDPL' );
		$submit = Xml::element( 'input', array( 'type' => 'submit', 'value' => 'Nuke!' ) );
		$wgOut->addHTML(
			Xml::element( 'form', array( 'action' => $nuke->getLocalURL( 'action=delete' ), 'method' => 'post' ), null )
			."\n<div>".wfMsgHtml( 'deletecomment' ) . ': '
			.Xml::element( 'input', array( 'name' => 'wpReason', 'value' => $reason, 'size' => 60 ) ) . "</div>\n$submit"
			.Xml::element( 'input', array( 'type' => 'hidden', 'name' => 'wpEditToken', 'value' => $wgUser->editToken() ) ) . "\n<br/>"
		);

		$sk =& $wgUser->getSkin();
		foreach( $pages as $title ) {
			$wgOut->addHTML(
				Xml::element( 'input', array( 'type' => 'checkbox', 'name' => "pages[]", 'value' => $title, 'checked' => 'checked' ) )
				.'&nbsp;' . $sk->makeKnownLinkObj( Title::newFromText( $title ) ) . "<br />\n"
			);
		}
		$wgOut->addHTML( "\n$submit</form>" );
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
		foreach( $pages as $page ) {
			if( $title = Title::newFromText( $page ) ) {
				$article = new Article( $title );
				$article->doDelete( $reason );
			} else die( "Bad title: \"$page\"" );
		}
	}
}

