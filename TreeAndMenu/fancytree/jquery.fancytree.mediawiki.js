/*!
 * jquery.fancytree.mediawiki.js
 *
 * Add mediawiki-specific ajax loading and some mediawiki helper functions
 * (Extension module for jquery.fancytree.js: https://github.com/mar10/fancytree/)
 * - for samples of all events, see https://github.com/mar10/fancytree/blob/master/demo/sample-events.html
 *
 * Copyright (c) 2015, Aran Dunkley (http://www.organicdesign.co.nz/aran)
 *
 * Released under the GNU General Public Licence 2.0 or later
 *
 */
(function($, window, document, mw, undefined) {

	"use strict";

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

	/**
	 * Register the extension and set the lazy-loading up in a MediaWiki-friendly way
	 */
	$.ui.fancytree.registerExtension({

		name: "mediawiki",
		version: "1.0.0",
		options: {},

		// When a tree is initialised, do some modifications appropriate to mediawiki trees
		treeInit: function(ctx) {
			var tree = ctx.tree, opts = ctx.options;

			// Put the full HTML content of the node back by referring to it's original LI element
			opts.renderNode = function(event, data) {
				var li = $('#' + data.node.data.li).clone();
				$('ul', li).remove();
				$('.fancytree-title', data.node.span).html(li.html());
			};

			// Lazy load event to collect child data from the supplied URL via ajax
			opts.lazyLoad = function(event, data) {
				var url = data.node.data.ajax;

				// Set result to a jQuery ajax options object
				data.result = {
					type: 'GET',
					dataType: 'text',
				};

				// If the ajax option is an URL, split it into main part and query-string
				if(url.match(/^(https?:\/\/|\/)/)) {
					var parts = url.split('?');
					data.result.url = parts[0];
					data.result.data = parts[1];
				}

				// Otherwise treat it as an article title to be read with action=render
				else {
					data.result.url = mw.util.wikiScript();
					data.result.data = { title: url, action: 'render' };
				}
			};

			// Parse the data collected from the Ajax response and make it into child nodes
			opts.postProcess = function(event, data) {
				var response = data.response, m;

				// If there's a UL section in it, parse it into nodes
				if(m = response.match(/^.*?(<ul[\s\S]+<\/ul>)/i)) data.result = $.ui.fancytree.parseHtml($(m[1]));

				// Otherwise see if it's as a JSON list of node data (need to extract as MediaWiki adds parser info)
				else if(m = response.match(/^.*?(\[[\s\S]+\])/i)) data.result = $.parseJSON(m[1]);

				// Otherwise just return an empty node set (should raise an error)
				else data.result = [];
			};

			// Execute the parent render the tree (this must be after adding renderNode hook, but before tree.visit is called
			var ret = this._superApply(arguments);

			// Set all nodes in the tree marked as ajax to lazy with null children (so they trigger the lazyLoad event when opened)
			tree.visit(function(node) {
				if('ajax' in node.data) {
					node.lazy = true;
					node.children = null;
				}
			});

			// Return the value from tree parent initialisation
			return ret;
		},
	});

}(jQuery, window, document, mw));
