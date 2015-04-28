window.webSocket = (function($, document, mw, undefined) {

	// Private vars
	var ws = false;
	var id = false;
	var active = false;
	var connected = false;

	function onOpen(e) {
		console.info('WebSocket connected');
		ws.send(JSON.stringify({type: 'Register', from: id}));
		connected = true;
	}

	function onClose() {
		console.info('WebSocket disconnected');
		connected = false;
	}

	function onMessage(e) {
		console.info('WebSocket message received');
		var data = $.parseJSON(e.data);
		$.event.trigger({type: 'ws_' + data.type, args: {msg: data.msg, to: data.to}});
	}

	function onError(e) {
		console.log('WebSocket error: ' + e);
	}

	// Return public API
	return {

		active: function() { return active; },
		connected: function() { return connected; },

		connect: function() {
			if(ws) return;

			// url depends on rewrite, port and SSL
			var server = mw.config.get('wgServer');
			var port = server.match(/https:/) ? mw.config.get('wsPort') + 1 : mw.config.get('wsPort');
			var rewrite = mw.config.get('wsRewrite');
			var url = server.replace(/^http/,'ws') + ( rewrite ? '/websocket' + ':' + port : ':' + port );
			console.info('Connecting to WebSocket server at ' + url);

			ws = new WebSocket(url);
			if(ws) active = true;
			id = mw.config.get('wsClientID');
			console.info('WebSocket active');

			ws.onopen = onOpen;
			ws.onclose = onClose;
			ws.onmessage = onMessage;
			ws.onerror = onError;

			return active;
		},

		subscribe: function(type, callback) {
			$(document).on( 'ws_' + type, callback );
		},

		send: function(type, msg, to) {
			if(msg === undefined) msg = '';
			if(to === undefined) to = [0];
			ws.send(JSON.stringify({type: type, msg: msg, from: id, to: to}));
		},
	}
}(jQuery, document, mw));

