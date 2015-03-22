$(document).ready(function(){

	var opts = {
		focusOnSelect: false,
	}
	$('.fancytree').fancytree(opts);

	opts['extensions'] = ["persist"];
	$('.fancytree-persist').fancytree(opts);
});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
