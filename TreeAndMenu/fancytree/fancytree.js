/**
 * This code pre-processes trees and menus to integrate the third-party code into mediawiki without changing it
 */

$(document).ready(function(){

	/**
	 * Initialise trees
	 */
	$('.fancytree').each(function() {

		// Get options passed to the parser-function from span
		var div = $('div.opts', $(this));
		var opts = {};
		if(div.length > 0) {
			opts = $.parseJSON(div.text());
			div.remove();
		}

		// Add the mediawiki extension
		if('extensions' in opts) opts['extensions'].push('mediawiki');
		else opts['extensions'] = ['mediawiki'];

		// Activate the tree
		$(this).fancytree(opts);
	});

	/**
	 * Initialise menus
	 */
	$('.suckerfish li').each(function() {
		var li = $(this);
		li.addClass( (li.index()&1) ? 'odd' : 'even' );
	});

});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
