// Copyright (C) 2013 Aran Dunkley
// Licence: GPLv2+
const Mainloop = imports.mainloop;
const Main = imports.ui.main;
const PanelMenu = imports.ui.panelMenu;
const PopupMenu = imports.ui.popupMenu;
const GLib = imports.gi.GLib;
const Gio = imports.gi.Gio;
const St = imports.gi.St;
const Local = imports.misc.extensionUtils.getCurrentExtension();
const Lang = imports.lang;
const Convenience = Local.imports.convenience;
const Settings = Local.imports.settings;
const Soup = imports.gi.Soup;

let paneltext;
let panelicon;
let panelbox;
let check_settings;
let update_price;
let update_price_regular;
let settings;
let currencies = ["USD","AUD","CHF","NOK","RUB","DKK","JPY","CAD","NZD","PLN","CNY","SEK","SGD","HKD","EUR"];
let extPath;

function init(metadata) {
	extPath = metadata.path;
}

function enable() {

	// Text
	settings = Convenience.getSettings();
	paneltext = new St.Bin({ style_class: 'bitcoinprice-text' });
	let text = new St.Label({ text: '...' });
	paneltext.set_child(text);

	// Icon
	let icon = new St.Icon({ style_class: 'bitcoinprice-icon' });
	icon.set_gicon(new Gio.FileIcon({ file: Gio.file_new_for_path(GLib.build_filenamev([extPath, 'Bitcoin-icon.png']))}));
	panelicon = new St.Bin({ style_class: 'panel-button', reactive: true, can_focus: true, x_fill: true, y_fill: false, track_hover: true });
	panelicon.set_child(icon);

	// Add them both to the panel box
	panelbox = new St.BoxLayout();
	panelbox.add_actor(panelicon);
	panelbox.add_actor(paneltext);	

	// Add the panel box to the panel
    let children = Main.panel._rightBox.get_children();
    Main.panel._rightBox.insert_child_at_index(panelbox, children.length - 2);

	// Check for changes to settings called every 2 seconds
	check_settings = function() {
		let settings_data = Settings.getSettings(settings);
		if(settings_data.reload_now == true) {
			settings_data.reload_now = false;
			settings.set_string("settings-json", JSON.stringify(settings_data));
			let text = new St.Label({ text: '...' });
			paneltext.set_child(text);
			update_price();
		}
		Mainloop.timeout_add_seconds(2, check_settings);
	};
	check_settings();

	// Function to retrieve the price and update the panel label
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
				paneltext.set_child(text);
			}
		});
	};

	// Call the update price function on a regular interval
	update_price_regular = function() {
		update_price();
		let settings_data = Settings.getSettings(settings);
		let period = 120; //settings_data.refresh_period;
		Mainloop.timeout_add_seconds(period, update_price_regular);
	};
	update_price_regular();
}

function disable() {
	Main.panel._rightBox.remove_child(panelbox);
}
