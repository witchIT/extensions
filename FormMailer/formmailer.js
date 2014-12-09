$(document).ready(function() {
	$('input[name=' + mw.config.get('wgFormMailerVarName') + ']').each(function() {
		$(this).attr('name', $(this).attr('name') + '-' + mw.config.get('wgFormMailerAP') );
	});
});
