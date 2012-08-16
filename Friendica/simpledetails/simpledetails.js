/**
 * Simplify the personal details screen
 */
$(document).ready( function() {
	var elems = $('#profile-edit-form').children().get();
	var j = 0;
	for( i = 0; i < elems.length; i++ ) {
		var e = $(elems[i]);
		var id = e.attr('id');
		if( id == 'profile-edit-marital-wrapper' || id = 'dislikes-jot-end' || id = 'music-jot-wrapper' || 'profile-edit-submit-end' ) j++;
		if( j == 1 || j == 3 ) e.hide();
	}
});
