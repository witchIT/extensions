$(document).ready(function(){

	$('.fancytree').each(function() {

		// Todo: get parser-function opts
		var opts = {
			activate: function(event, data) {
				var node = data.node;
				if(node.data.href) window.open(node.data.href);
			}
		}

		// Add persist extension if opt sent
		if( $(this).hasClass('persist') ) opts['extensions'] = ["persist"];

		// Activate the tree
		$(this).fancytree(opts);

	});
});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
