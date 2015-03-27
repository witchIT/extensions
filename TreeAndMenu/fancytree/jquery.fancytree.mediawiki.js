/*!
 * jquery.fancytree.mediawiki.js
 *
 * Add mediawiki-specific ajax loading and some mediawiki helper functions
 * (Extension module for jquery.fancytree.js: https://github.com/mar10/fancytree/)
 *
 * Copyright (c) 2015, Aran Dunkley (http://www.organicdesign.co.nz/aran)
 *
 * Released under the GNU General Public Licence 2.0 or later
 *
 */

;(function($, window, document, mw, undefined) {

"use strict";


/**
 * Private functions and variables
 */
var _assert = $.ui.fancytree.assert;


	/**
	 * Open the tree to the node containing the passed title, or current page if none supplied
	 */
	$.ui.fancytree._FancytreeClass.prototype.makeTitleVisible = function(title) {
		var local = this.ext.mediawiki;
		if(typeof(title) === 'undefined') title = mw.config.get('wgTitle');
		this.visit(function(node) {
			if(node.title == title) {
				node.makeVisible({ noAnimation: true, noEvents: true, scrollIntoView: false });
				node.setActive({ noEvents: true });
				return false;
			}
		});
	};

	$.ui.fancytree.registerExtension({

		name: "mediawiki",
		version: "0.0.1",

		// Default options for this extension.
		options: {
		},

		// When a tree is initialised, do some modifications appropriate to mediawiki trees
		treeInit: function(ctx) {
			var tree = ctx.tree,
				opts = ctx.options,
				local = this._local,
				instOpts = this.options.mediawiki;

			// Make nodes with hrefs back into normal links
			// - for samples of all events, see https://github.com/mar10/fancytree/blob/master/demo/sample-events.html
			opts.renderNode = function(event, data) {
				var node = data.node;
				if(node.data.href) {
					$('.fancytree-title',node.span).html('<a href="' + node.data.href + '" title="' + node.title + '">' + node.title + '</a>');
				}
			};

			// Execute the parent context to initialise the tree
			var ret = this._superApply(arguments);

			// Lazy load event to collect child data from the supplied URL via ajax
			opts.lazyLoad = function(event, data) {
				/*
				console.log('ajax: '+data.node.data.ajax);
				data.result = {
					type: 'GET',
					url: data.node.data.ajax,
					data: data.node.data.ajax.data,
					dataType: 'text',
					success: function(children) {
						if(children.substr(1) == '[') data.result = $.parseJSON( children );
						else {
							var $ul = $(children.match(/^.*?(<ul.+<\/ul>)/i)[1]);
							data.result = $.ui.fancytree.parseHtml($ul);
						}
					}
				});
				*/
			};

			// Set all nodes in the tree marked as ajax to lazy with null children (so they trigger the lazyLoad event when opened)
			tree.visit(function(node) {
				if('ajax' in node.data) {
					node.lazy = true;
					node.children = null;
				}
			});

			// Return the value from tree initialisation
			return ret;
		},

	});

}(jQuery, window, document, mw));
