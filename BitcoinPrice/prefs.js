const Lang = imports.lang;
const Gtk = imports.gi.Gtk;
const Local = imports.misc.extensionUtils.getCurrentExtension();
const Convenience = Local.imports.convenience;
const Settings = Local.imports.settings;
const _ = imports.gettext.domain(Local.metadata['gettext-domain']).gettext;
 
let main_frame;
let currency_box;
let currency_label;
let currency_input;
let reload_box;
let reload_button;
let reload_spacer;
let settings;
let settings_data;
 
// dummy one
function init() {
}
 
function widget_initliaze() {

	// initilize main frame
	main_frame = new Gtk.Box({ orientation: Gtk.Orientation.VERTICAL, border_width: 10 });

	// Currency
	currency_box = new Gtk.Box({orientation: Gtk.Orientation.VERTICAL });
	currency_label = new Gtk.Label({label: "Currency", xalign: 0, margin_right: 30 });
	currency_input = new Gtk.ComboBoxText();
	let currencies = ["USD","AUD","CHF","NOK","RUB","DKK","JPY","CAD","NZD","PLN","CNY","SEK","SGD","HKD","EUR"];
	for(let i = 0; i < currencies.length; i++) currency_input.append_text(currencies[i]);

	// Reload box
	reload_box = new Gtk.Box({orientation: Gtk.Orientation.VERTICAL });
	reload_spacer = new Gtk.Box({orientation: Gtk.Orientation.VERTICAL, vexpand: true, hexpand: true });
	reload_button = new Gtk.Button({label: "Update price now" });
}
 
function widget_packaging() {
	currency_box.pack_start(currency_label, false, false, 15);
	currency_box.pack_start(currency_input, true, true, 15);
	reload_box.pack_start(reload_spacer, true, true, 15);
	reload_box.pack_start(reload_button, false, false, 15);	 
	main_frame.add(currency_box);
	main_frame.add(reload_box);
}

function widget_connect() {
	currency_input.connect('changed', Lang.bind(this, function() {
		settings = Convenience.getSettings();
		settings_data = Settings.getSettings(settings);
		settings_data.currency = currency_input.get_active();
		settings.set_string("settings-json", JSON.stringify(settings_data));
	}));

	reload_button.connect('clicked', Lang.bind(this, function() {
		settings = Convenience.getSettings();
		settings_data = Settings.getSettings(settings);
		settings_data.reload_now = true;
		settings.set_string("settings-json", JSON.stringify(settings_data));
	}));
}
 
// setting init values
function widget_init_values() {
	settings = Convenience.getSettings();
	settings_data = Settings.getSettings(settings);
	currency_input.set_active(settings_data.currency);
}
 
function buildPrefsWidget() {
	widget_initliaze();
	widget_packaging();
	widget_connect();
	widget_init_values();
	main_frame.show_all();
	return main_frame;
}
