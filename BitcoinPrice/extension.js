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
let check_settings;
let update_price;
let update_price_regular;
let settings;
let currencies = ["USD","AUD","CHF","NOK","RUB","DKK","JPY","CAD","NZD","PLN","CNY","SEK","SGD","HKD","EUR"];
function init() {
	settings = Convenience.getSettings();
	label = new St.Bin({ style_class: 'panel-bitcoin-price' });
	let text = new St.Label({ text: '...' });
	label.set_child(text);

	// Check settings called every 2 seconds
	check_settings = function() {
		let settings_data = Settings.getSettings(settings);
		if(settings_data.reload_now == true) {
			settings_data.reload_now = false;
			settings.set_string("settings-json", JSON.stringify(settings_data));
			let text = new St.Label({ text: '...' });
			label.set_child(text);
			update_price();
		}
		Mainloop.timeout_add_seconds(2, check_settings);
	};
	check_settings();

	// Actual update price function
	update_price = function() {

		// Get the currency setting and make the url
		let settings_data = Settings.getSettings(settings);
		let currency = currencies[settings_data.currency];
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
	};

	// Update price function called regularly
	update_price_regular = function() {
		update_price();
		let settings_data = Settings.getSettings(settings);
		let period = 120; //settings_data.refresh_period;
		Mainloop.timeout_add_seconds(period, update_price_regular);
	};
	update_price_regular();
}

function enable() {
    let children = Main.panel._rightBox.get_children();
    Main.panel._rightBox.insert_child_at_index(label, children.length-2);
}

function disable() {
    Main.panel._rightBox.remove_child(label);
}
