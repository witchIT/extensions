$(document).ready(function(){

	$('.fancytree').each(function() {

		// Get options passed to the parser-function from span
		var div = $('div.opts', $(this));
		var opts = $.parseJSON(div.text());
		div.remove();

		// Need to make links in nodes function normally
		opts['activate'] = function(event, data) {
			var node = data.node;
			if(node.data.href) window.open(node.data.href, '_self');
		};

		// Activate the tree
		$(this).fancytree(opts);

	});
});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
