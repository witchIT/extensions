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

	$.ui.fancytree.registerExtension({

		name: "mediawiki",
		version: "0.0.1",

		// Default options for this extension.
		options: {
		},

		// When a tree is initialised, do some modifications appropriate to mediawiki trees
		treeInit: function(ctx) {
			var tree = ctx.tree, opts = ctx.options;

			// Execute the parent context to initialise the tree first
			var ret = this._superApply(arguments);

			// Make nodes with hrefs back into normal links
			// - for samples of all events, see https://github.com/mar10/fancytree/blob/master/demo/sample-events.html
			opts.renderNode = function(event, data) {
				var node = data.node;
				if(node.data.href) {
					$('.fancytree-title',node.span).html('<a href="' + node.data.href + '" title="' + node.title + '">' + node.title + '</a>');
				}
			};

			// Lazy load event to collect child data from the supplied URL via ajax
			opts.lazyLoad = function(event, data) {
				var url = data.node.data.ajax;

				// Set result to a jQuery ajax options object
				data.result = {
					type: 'GET',
					//dataType: 'text',
					dataFilter: function(data) { return ''; } // Hack to prevent FancyTree from raising an exception due to it being a string
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
				console.log('dataType2:'+data.dataType);

				// Returned data was put in an array by $.ajax's dataFilter callback above
				//var response = data.response[0];
				var response = data.response;

				// If the returned data starts with a square bracket, treat it as a JSON list of node data
				if(response.substr(1) == '[') data.result = $.parseJSON(response);

				// Otherwise treat it as HTML and parse the UL section
				else {
					var m = response.match(/^.*?(<ul[\s\S]+<\/ul>)/i);
					data.result = m ? $.ui.fancytree.parseHtml($(m[1])) : [];
				}
			};

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
