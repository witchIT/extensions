<?php
# To be included in LocalSettings.php
# Adds a blog item in response to the post-item form being submitted
# See http://www.organicdesign.co.nz/Bliki_2.0 for details

$wgHooks['UnknownAction'][] = 'wfBlogPOst';
function wfBlogPost( $action, $article ) {
	global $wgOut, $wgRequest, $wgUser, $wgParser;
	if( $action == 'blog' ) {
		$newtitle = $wgRequest->getText( 'newtitle' );
		$title = Title::newFromText( $newtitle );
		$error = false;
		if( !is_object( $title ) ) {
			$wgOut->addWikitext( '<div class="previewnote">Error: Bad title!</div>' );
			$error = true;
		}
		elseif( $title->exists() ) {
			$wgOut->addWikitext( '<div class="previewnote">Error: Title already exists!</div>' );
			$error = true;
		}
		if( !$error ) {
			$summary = $wgRequest->getText( 'summary' );
			$content = $wgRequest->getText( 'content' );
			$user = $wgUser->getName();
			$date = date( 'd F, Y' );
			$sig = "Posted by [[User:$user|$user]] on $date";
			$type = $wgRequest->getText( 'type' );
			switch( $type ) {

				// Preview the item
				case "Full preview":
					$wikitext = "<div class=\"blog-sig\">$sig</div>\n$summary\n\n$content";
					wfBlogPreview( $type, $title, $wikitext );
					$article->view();
				break;

				// Preview the item in news/blog format
				case "Summary preview":
					$wikitext = "{|class=\"blog\"\n|\n== [[Post a blog item|$newtitle]] ==\n|-\n!$sig\n|-\n|$summary\n|}";
					$title = Title::newFromText( 'Blog' );
					wfBlogPreview( $type, $title, $wikitext );
					$article->view();
				break;

				// Create the item with tags as category links
				case "Post":
					$wikitext = '{{' . "Blog|1=$summary|2=$content" . '}}';
					$wikitext .= "<noinclude>[[Category:Blog item]][[Category:Posts by $user]]";
					foreach( array_keys( $_POST ) as $k ) {
						if( preg_match( "|^tag(.+)$|", $k, $m ) ) {
							$wikitext .= '[[Category:' . str_replace( '_', ' ', $m[1] ) . ']]';
						}
					}
					$wikitext .= "</noinclude>";
					$article = new Article( $title );
					$article->doEdit( $wikitext, 'Blog item created via post form', EDIT_NEW );
					$wgOut->redirect( $title->getFullURL() );
				break;
			}
		} else $article->view();
		return false;
	}
	return true;
}

function wfBlogPreview( $heading, $title, $wikitext ) {
	global $wgOut, $wgParser;
	$wgOut->addWikitext( '<div class="previewnote">' . wfMsg( 'previewnote' ) . '</div>' );
	$wgOut->addWikitext( "== $heading ==" );
	$wgOut->addHTML( $wgParser->parse( $wikitext, $title, new ParserOptions(), true, true )->getText() );
	$wgOut->addHTML( "<br /><hr /><br />" );
}
