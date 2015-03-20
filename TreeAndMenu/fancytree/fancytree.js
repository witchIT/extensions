$(document).ready(function(){

	$('.fancytree').fancytree();
	$('.fancytree-persist').fancytree({ extensions: ["persist"] });

});

// Preload the tree icons and loader
(new Image()).src = 'loading.gif';
(new Image()).src = 'icons.gif';
