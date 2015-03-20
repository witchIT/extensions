$(document).ready(function(){

	//$('.fancytree ul:first, .fancytree-persist ul:first').hide().attr('id','treeData');
	alert('foo');
	$('.fancytree').fancytree();
	$('.fancytree-persist').fancytree({ extensions: ["persist"] });

});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
