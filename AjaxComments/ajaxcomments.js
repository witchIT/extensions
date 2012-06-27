$(document).ready( function() {

	$('#ca-talk').hide();

	// If there's a discussion tab, normal view action and not on talk page, render the discussion below the article
	if( $('#ca-talk').length > 0 && mw.config.get('wgAction') == 'view' && !mw.config.get('wgNamespaceNumber')&1 ) {
		$('.printfooter').after('<div id="ajaxcomments"><div class="ajaxcomments-loader"></div></div>');
		$.ajax({
			type: 'GET',
			url: mw.util.wikiScript(),
			data: { action: 'ajaxcomments', title: mw.config.get('wgTitle') },
			dataType: 'html',
			success: function(html) {
				$('#ajaxcomments').html(html);
			}
		});
	}

});

/**
 * An add link has been clicked
 */
window.ajaxcomment_add = function() {
	ajaxcomment_textinput($('#ajaxcomment-add').parent(), 'add');
	$('#ajaxcomments-none').remove();
};

/**
 * An edit link has been clicked
 */
window.ajaxcomment_edit = function(id) {
	var e = $('#ajaxcomments-' + id + ' .ajaxcomment-text').first();
	ajaxcomment_textinput(e, 'edit');
	ajaxcomment_source( id, $('textarea', e.parent()).first() );
	e.hide();
};

/**
 * A reply link has been clicked
 */
window.ajaxcomment_reply = function(id) {
	ajaxcomment_textinput($('#ajaxcomments-' + id + ' .ajaxcomment-links').first(), 'reply');
};

/**
 * An delete link has been clicked
 */
window.ajaxcomment_del = function(id) {
	var target = $('#ajaxcomments-' + id);
	if(confirm('Are you sure you want to remove this comment?')) {
		target.html('<div class="ajaxcomments-loader"></div>');
		$.ajax({
			type: 'GET',
			url: mw.util.wikiScript(),
			data: {
				action: 'ajaxcomments',
				title: mw.config.get('wgTitle'),
				cmd: 'del',
				id: id,
			},
			context: target,
			dataType: 'html',
			success: function(html) {
				this.replaceWith(html);
			}
		});
	}
};

/**
 * Disable the passed input box, retrieve the wikitext source via ajax, then populate and enable the input
 */
window.ajaxcomment_source = function(id, target) {
	target.attr('disabled',true);
	$.ajax({
		type: 'GET',
		url: mw.util.wikiScript(),
		data: {
			action: 'ajaxcomments',
			title: mw.config.get('wgTitle'),
			cmd: 'src',
			id: id,
		},
		context: target,
		dataType: 'json',
		success: function(json) {
			this.val(json.text);
			this.attr('disabled',false);
		}
	});
};

/**
 * Open a comment input box at the passed element location
 */
window.ajaxcomment_textinput = function(e, cmd) {
	ajaxcomment_cancel();
	var html = '<div id="ajaxcomment-input" class="ajaxcomment-input-' + cmd + '"><textarea></textarea><br />';
	html += '<input type="button" onclick="ajaxcomment_submit(this,\'' + cmd + '\')" value="Post" />';
	html += '<input type="button" onclick="ajaxcomment_cancel()" value="Cancel" />';
	html += '</div>';
	e.after(html);
};

/**
 * Remove any current comment input box
 */
window.ajaxcomment_cancel = function() {
	$('#ajaxcomment-input').remove();
	$('.ajaxcomment-text').show();
};

/**
 * Submit a comment command to the server
 * - e is the button element that was clicked
 * - cmd will be add, reply or edit
 */
window.ajaxcomment_submit = function(e, cmd) {
	e = $(e);
	var target;
	var id = 0;
	var text = '';

	// If it's an add, create the target at the end
	if( cmd == 'add' ) {
		$('#ajaxcomment-add').parent().after('<div id="ajaxcomments-new"></div>');
		target = $('#ajaxcomments-new');
		text = $('#ajaxcomment-input textarea').val();
	}

	// If it's a reply, create the target within the current comment
	if( cmd == 'reply' ) {
		e.parent().before('<div id="ajaxcomments-new"></div>');
		target = $('#ajaxcomments-new');
		text = $('#ajaxcomment-input textarea').val();
		id = target.parent().attr('id').substr(13);
	}

	// If it's an edit, create the target as the current comment
	if( cmd == 'edit' ) {
		text = $('#ajaxcomment-input textarea').val();
		target = e.parent().parent();
		target.html('<div id="ajaxcomments-new"></div>');
		id = target.attr('id').substr(13);
	}

	// Put a loader into the target
	target.html('<div class="ajaxcomments-loader"></div>');

	// Send the command and replace the loader with the new post
	$.ajax({
		type: 'GET',
		url: mw.util.wikiScript(),
		data: {
			action: 'ajaxcomments',
			title: mw.config.get('wgTitle'),
			cmd: cmd,
			id: id,
			text: text
		},
		context: target,
		dataType: 'html',
		success: function(html) {
			this.replaceWith(html);
			ajaxcomment_cancel();
		}
	});

};
