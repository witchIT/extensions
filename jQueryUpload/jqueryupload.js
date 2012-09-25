/*
 * jQuery File Upload Plugin JS Example 6.7
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 * 
 * Note: This file has been modified for use in the jQueryUpload MediaWiki extension
 *       - a path based on the article ID is added so that files can be attached to pages
 *       - the PHP server code called is a MediaWiki Ajax handler
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */

$(function() {
	'use strict';

	// Initialize the jQuery File Upload widget:
	$('#fileupload').fileupload();

    // Enable iframe cross-domain access via redirect option:
	$('#fileupload').fileupload(
		'option',
		'redirect',
		window.location.href.replace( /\/[^\/]*$/, '/cors/result.html?%s' )
	);

	// Load existing files using a path set to the current article ID if non-zero
	var path = mw.config.get('wgArticleId');
	path = path ? '&path=' + path : '';
	$('#fileupload').each(function () {
		var that = this;
		$.getJSON('?action=ajax' + path + '&rs=jQueryUpload::server', function(result) {
			if(result && result.length) {
				$(that).fileupload('option', 'done').call(that, null, {result: result});
			}
		});
	});
});
