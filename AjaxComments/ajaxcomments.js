$(document).ready( function() {

	// If there's a discussion area on this page, populate it with the comments from the server
	if( $('#ajax-comments').length > 0 ) {
		$('#ajax-comments').html('<div class=\"ajax-comments-loader\"></div>');
		$.ajax({
			type: 'GET',
			url: mw.util.wikiScript(),
			data: { action: 'ajaxcomments', title: mw.config.get('wgTitle') },
			dataType: 'html',
			success: function(html) { $('#ajax-comments').html(html); }
		});
	}

};
