window.webSocket = (function($, mw, undefined) {

	// Private vars
	var ws = false;
	var active = false;
	var connected = false;

	function onOpen(e) {
		console.info('WebSocket connected');
		connected = true;
	}

	function onClose() {
		console.info('WebSocket disconnected');
		connected = false;
	}

	function onMessage(e) {
		console.info('WebSocket message received');
		var data = $.parseJSON(e.data);
		$.event.trigger({type: 'ws' + data.type, args: {msg: data.msg, to: data.to}});
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

			// url depends on rewrite and port
			var port = mw.config.get('wsPort');
			var rewrite = mw.config.get('wsRewrite');
			var id = mw.config.get('wsClientID');
			var url = rewrite
				? mw.config.get('wgServer') + '/websocket/' + id
				: mw.config.get('wgServer').replace(/^https?/,'ws') + ':' + port + '/' + id;

			ws = new WebSocket(url);
			if(ws) active = true;
			console.info('WebSocket active');

			ws.onopen = onOpen;
			ws.onclose = onClose;
			ws.onmessage = onMessage;
			ws.onerror = onError;
		},

		send: function(type, msg, to) {
			if(to === undefined) to = [0];
			ws.send(JSON.stringify({type: type, msg: msg, to: to}));
		},
	}
}(jQuery, mw));

// Initialise the WebSocket when document is ready
$(document).ready(webSocket.connect);

