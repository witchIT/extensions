$(document).ready(function(){

	$('.fancytree ul:first, .fancytree-persist ul:first').hide().setAttr('id','treeData');
	$('.fancytree').fancytree();
	$('.fancytree-persist').fancytree({ extensions: ["persist"] });

});

// Preload the tree icons and loader
var path = mw.config.get('fancytree_path');
(new Image()).src = path + '/loading.gif';
(new Image()).src = path + '/icons.gif';
