/**
 * Change any profile links to the new shortened format
 */
$(document).ready( function() {

	function  shortprofileurl(url) {
		url = url.replace( /\/profile(\/[^\/\?]+)$/, '$1/?tab=status' );
		return url.replace( /\/profile(\/[^\/\?]+)(\/\?tab=profile)?/, '$1' );
	}

	$('a[href]').each( function() {
		$(this).attr('href', shortprofileurl($(this).attr('href')));
	});

	$('a[title]').each( function() {
		$(this).attr('title', shortprofileurl($(this).attr('title')));
	});
});
