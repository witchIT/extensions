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
let save_settings_box;
let save_settings_button;
let save_settings_spacer;
let settings;
let settings_data;
 
// dummy one
function init() {
}
 
function widget_initliaze() {

	// initilize main frame
	main_frame = new Gtk.Box({ orientation: Gtk.Orientation.VERTICAL, border_width: 10 });

	// auth
	currency_box = new Gtk.Box({orientation: Gtk.Orientation.VERTICAL });
	currency_label = new Gtk.Label({label: "Currency", xalign: 0, margin_right: 30 });
	currency_input = new Gtk.ComboBoxText();
	let currencies = ["USD","AUD","CHF","NOK","RUB","DKK","JPY","CAD","NZD","PLN","CNY","SEK","SGD","HKD","EUR"];
	for(let i = 0; i < currencies.length; i++) currency_input.append_text(currencies[i]);

	// save settings box
	save_settings_box = new Gtk.Box({orientation: Gtk.Orientation.VERTICAL });
	save_settings_spacer = new Gtk.Box({orientation: Gtk.Orientation.VERTICAL, vexpand: true, hexpand: true });
	save_settings_button = new Gtk.Button({label: "Save Settings" });
}
 
function widget_packaging() {
	currency_box.pack_start(currency_label, false, false, 15);
	currency_box.pack_start(currency_input, true, true, 15);
	save_settings_box.pack_start(save_settings_spacer, true, true, 15);
	save_settings_box.pack_start(save_settings_button, false, false, 15);	 
	main_frame.add(currency_box);
	main_frame.add(save_settings_box);
}

function widget_connect() {
	currency_input.connect('changed', Lang.bind(this, function() {
		// todo: call update
	}));

	save_settings_button.connect('clicked', Lang.bind(this, function() {
		settings = Convenience.getSettings();
		settings_data = Settings.getSettings(settings);
		settings_data.currency = currency_input.get_active();
		settings.set_string("settings-json", JSON.stringify(settings_data));
	}));
}
 
// setting init values
function widget_init_values() {
	settings = Convenience.getSettings();
	settings_data = Settings.getSettings(settings);
	currency_input.set_active(parseInt(settings_data.currency));
}
 
function buildPrefsWidget() {
	widget_initliaze();
	widget_packaging();
	widget_connect();
	widget_init_values();
	main_frame.show_all();
	return main_frame;
}
