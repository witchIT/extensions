$(document).ready( function() {

	// If there's a discussion area on this page, populate it with the comments from the server
	if( $('#ajaxcomments').length > 0 ) {
		$('#ajaxcomments').html('<div class=\"ajaxcomments-loader\"></div>');
		$.ajax({
			type: 'GET',
			url: mw.util.wikiScript(),
			data: { action: 'ajaxcomments', title: mw.config.get('wgTitle') },
			dataType: 'html',
			success: function(html) { $('#ajaxcomments').html(html); }
		});
	}

};
