/**
 * Simplify the personal details screen
 */
$(document).ready( function() {
	$('#profile-edit-marital-wrapper').replace('<div id="simplifydetails1">');
	$('#dislikes-jot-end').replace('</div>');
	$('#simplifydetails1').hide();
	$('#music-jot-wrapper').prepend('<div id="simplifydetails2">');
	$('#profile-edit-submit-end').append('</div>');
	$('#simplifydetails2').hide();
	
});
