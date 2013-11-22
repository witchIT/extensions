// Copyright (C) 2013 Aran Dunkley
// Licence: GPLv2+
const Main = imports.ui.main;
const Mainloop = imports.mainloop;
const St = imports.gi.St;
const Soup = imports.gi.Soup;
const Local = imports.misc.extensionUtils.getCurrentExtension();
const Convenience = Local.imports.convenience;
const Settings = Local.imports.settings;

let label;
let update_price;
let settings;
let currencies = ["USD","AUD","CHF","NOK","RUB","DKK","JPY","CAD","NZD","PLN","CNY","SEK","SGD","HKD","EUR"];
function init() {
	settings = Convenience.getSettings();
	label = new St.Bin({ style_class: 'panel-bitcoin-price' });
	let text = new St.Label({ text: '...' });
	label.set_child(text);
	update_price = function() {

		// Get the currency setting and make the url
		let settings_data = Settings.getSettings(settings);
		let currency = currencies[parseInt(settings_data.currency)];
		let url = 'http://mtgox.com/api/1/BTC' + currency + '/ticker';

		// Request the MtGox data
		let session = new Soup.SessionAsync();
		let message = Soup.Message.new('GET', url);
		session.queue_message(message, function(session, message) {
			let json = JSON.parse(message.response_body.data);
			if(json.result == 'success') {
				let text = new St.Label({ text: json['return']['last']['display'] });
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
