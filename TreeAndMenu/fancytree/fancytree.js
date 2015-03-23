$(document).ready(function(){

	$('.fancytree').each(function() {

		// Get options passed to the parser-function from span
		var div = $('div.opts', $(this));
		var opts = {};
		if(div.length > 0) {
			opts = $.parseJSON(div.text());
			div.remove();
		}

		// Add the mediawiki extension
		opts['extensions'].push('mediawiki');

		// Mustn't store active state because it triggers links to open again
		opts['persist'] = { types: "expanded focus selected" };

		// Activate the tree
		$(this).fancytree(opts);

	});
});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
