// Copyright (C) 2013 Aran Dunkley
// Licence: GPLv2+
const St = imports.gi.St;
const Main = imports.ui.main;
const Mainloop = imports.mainloop;
const Json = imports.gi.Json;
const Soup = imports.gi.Soup;

let label;
let update_price;
function init() {
	label = new St.Bin({ style_class: 'panel-bitcoin-price' });
	update_price = function() {
		let session = new Soup.SessionAsync();
		let message = Soup.Message.new('GET', 'http://mtgox.com/api/1/BTCUSD/ticker');
		session.queue_message(message, function(session, message) {
			let parser = new Json.Parser();
			parser.load_from_data(message.response_body.data, -1);
			let json = parser.get_root().get_object();
			if( json.get_string_member('result') == 'success' ) {
				let price = json.get_object_member('return').get_object_member('last').get_string_member('display');
				let text = new St.Label({ text: price });
				label.set_child(text);
			}
		});
		Mainloop.timeout_add(120000, update_price);
	};
	update_price();
}

function enable() {
    let children = Main.panel._rightBox.get_children();
    Main.panel._rightBox.insert_child_at_index(label, children.length-2);
}

function disable() {
    Main.panel._rightBox.remove_child(label);
}
