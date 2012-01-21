$('#p-logo').after( '<div id="booknav-sidebar" class="portal"></div>' );

$.ajax({
	type: 'GET',
	url: mw.config.get( 'wgScript' ),
	data: { title: mw.config.get( 'wgTitle' ), action: 'booknavtree' },
	dataType: 'html',
	success: function( data ) { $('#booknav-sidebar').html(data); }
});
