/* Move and show additional content items such as sidebar, footer, avatar etc */
$(document).ready( function() {

	var item = $('#wikitext-sidebar');
	$('#p-search').after( item.html() );
	item.html('');

	var item = $('#wikitext-footer');
	$('#footer').after( item.html() );
	item.html('');

	var item = $('#donations-wrapper');
	$('#p-logo').after( item.html() );
	item.html('');

	var item = $('#social-wrapper');
	$('#p-logo').after( item.html() );
	item.html('');

	var item = $('#avatar-wrapper');
	$('#p-logo').after( item.html() );
	item.html('');

	var item = $('#languages-wrapper');
	$('#column-content').before( item.html() );
	item.html('');

	/* Make recent changes look nicer - replaces old TransformChanges extension */
	$('table.mw-enhanced-rc').each(function() {
		var row = $(this);
		row.css('width','100%');
		if((row.index()&1)==0) row.css('background-color','#F2F2F9');
		$('td:last-child',row).css('width','100%');
		$('span.mw-title',row).css({width:'200px',display:'block',float:'left',overflow:'hidden'});
		$('.mw-usertoollinks,.mw-changeslist-separator,.mw-rollback-link',row).hide();
		$('.mw-userlink',row).css('padding','0 10px');
		$('span.changedby .mw-userlink',row).css('padding','0');
	});
});
