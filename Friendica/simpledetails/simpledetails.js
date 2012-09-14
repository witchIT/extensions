/**
 * Simplify the personal details screen
 */
$(document).ready( function() {
	var elems = $('#profile-edit-form').children().get();
	var j = 0;
	for( i = 0; i < elems.length; i++ ) {
		var e = $(elems[i]);
		var id = e.attr('id');
		if( id == 'profile-edit-marital-wrapper' || id == 'dislikes-jot-end' || id == 'music-jot-wrapper' || id == 'profile-edit-submit-end' ) j++;
		if( ( j == 1 || j == 3 ) && e.attr('id') != 'about-jot-wrapper' ) e.hide();
	}
	gender = '<option selected="selected" value=""></option>';
	gender += '<option value="Male">Male</option>';
	gender += '<option value="Female">Female</option>';
	gender += '<option value="Private">Prefer not to say</option>';
	$('#gender-select').html(gender);
});
