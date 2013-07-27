<?php
# Add a "blog-item"	class to the page body element if the article is categorised in Category:Blog items
# See http://www.organicdesign.co.nz/bliki

$wgHooks['OutputPageBodyAttributes'][] = 'wfBlikiAddBodyClasses';
function wfBlikiAddBodyClasses( $out, $sk, &$bodyAttrs ) {
	global $wgTitle;
	$id  = $wgTitle->getArticleID();
	$dbr = wfGetDB( DB_SLAVE );
	if( $dbr->selectRow( 'categorylinks', '1', "cl_from = $id AND cl_to = 'Blog_items'" ) ) $bodyAttrs['class'] .= ' blog-item';
	return true;
}
