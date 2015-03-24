/**
 * This code pre-processes trees and menus to integrate the third-party code into mediawiki without changing it
 * - I added script after each tree to call its prepare function rather than iterating over the elements so that ones that load later work too
 */


// Prepare the passed tree element
window.prepareTree = function(s) {
	var tree = $(s);

	// Get options passed to the parser-function from span
	var div = $('div.opts', tree);
	var opts = {};
	if(div.length > 0) {
		opts = $.parseJSON(div.text());
		div.remove();
	}

	// Add the mediawiki extension
	if('extensions' in opts) opts['extensions'].push('mediawiki');
	else opts['extensions'] = ['mediawiki'];

	// Activate the tree
	tree.fancytree(opts);
};


// Prepare the passed menu element (add even and odd classes)
window.prepareMenu = function(s) {
	var li = $(s + ' li');
	li.addClass( (li.index()&1) ? 'odd' : 'even' );
};


// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
