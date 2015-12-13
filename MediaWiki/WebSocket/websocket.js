window.webSocket = (function($, document, mw, undefined) {

	// Private vars
	var ws = false;
	var id = false;
	var active = false;
	var connected = false;
	var disconnectedCallback = false;
	var subscribers = {};

	function onOpen(e) {
		console.info('WebSocket connected');
		ws.send(JSON.stringify({type: 'Register', from: id}));
		connected = true;
	}

	function onClose() {
		console.info('WebSocket disconnected');
		connected = false;
		ws = false;
		if(disconnectedCallback) disconnectedCallback();
	}

	function onMessage(e) {
		var i, s, data = $.parseJSON(e.data);
		if( data.type in subscribers ) {
			s = subscribers[data.type].length;
			for( i = 0; i < s; i++ ) subscribers[data.type][i](data);
		} else s = 'none';
		console.info('WebSocket "' + data.type + '" message received (subscibers notified: ' + s + ')');
	}

	function onError(e) {
		console.log('WebSocket error');
		console.log(e);
	}

	function checkConnection() {
		if(!connected) webSocket.connect();
	}

	// Return public API
	return {

		active: function() { return active; },
		connected: function() { return connected; },

		connect: function() {
			if(connected) return;

			// Set this client's unique ID to be used in registration and the "from" field of sent messages
			id = mw.config.get('wsClientID');

			// Url depends on rewrite, port and SSL
			var server = mw.config.get('wgServer');
			var port = server.match(/https:/) ? mw.config.get('wsPort') + 1 : mw.config.get('wsPort');
			var rewrite = mw.config.get('wsRewrite');
			var url = server.replace(/^http/,'ws') + ( rewrite ? '/websocket' + ':' + port : ':' + port );
			console.info('Connecting to WebSocket server at ' + url);

			// Connect the WebSocket
			ws = new WebSocket(url);
			if(ws) active = true;
			console.info('WebSocket active');

			// Conect the WebSocket's events to our private handlers
			ws.onopen = onOpen;
			ws.onclose = onClose;
			ws.onmessage = onMessage;
			ws.onerror = onError;

			// Check the connection evert 5 seconds to see if a reconnection needs to be tried
			if(active) setInterval(ws.checkConnection, 5000);

			return active;
		},

		// Pass a callback to execute when the socket disconnects
		disconnected: function(callback) {
			disconnectedCallback = callback;
		},

		// Pass a callback to execute when messages of the specified type are received
		subscribe: function(type, callback) {
			if(type in subscribers) subscribers[type].push(callback);
			else subscribers[type] = [callback];
			console.info('Subscribed to "' + type + '"');
		},

		// Send a message of the specified type to the specified recipient(s) (or broadcast if none specified)
		send: function(type, msg, to) {
			if(msg === undefined) msg = '';
			if(to === undefined) to = '';
			ws.send(JSON.stringify({type: type, msg: msg, from: id, to: to}));
		},
	}
}(jQuery, document, mw));

