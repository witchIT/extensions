const Lang = imports.lang;
const Gtk = imports.gi.Gtk;
const Local = imports.misc.extensionUtils.getCurrentExtension();
const Convenience = Local.imports.convenience;
const Settings = Local.imports.settings;
const _ = imports.gettext.domain(Local.metadata['gettext-domain']).gettext;
 
let main_frame;
let currency_input;
let showcur_input;
let refresh_input;
let settings;
 
function init() {
	settings = Convenience.getSettings();
}

function widget_initliaze() {

	// initilize main frame
	main_frame = new Gtk.Box({ orientation: Gtk.Orientation.VERTICAL, border_width: 10 });

	// Refresh time
	let refresh_box = new Gtk.Box({orientation: Gtk.Orientation.HORIZONTAL});
	let refresh_label = new Gtk.Label({label: "Refresh time (seconds)", xalign: 0 });
	refresh_input = new Gtk.HScale.new_with_range(5, 300, 5);
	refresh_box.pack_start(refresh_label, false, false, 15);
	refresh_box.pack_start(refresh_input, true, true, 15);
	main_frame.add(refresh_box);

	// Currency
	let currency_box = new Gtk.Box({orientation: Gtk.Orientation.HORIZONTAL });
	let currency_label = new Gtk.Label({label: "Currency", xalign: 0, margin_right: 97 });
	currency_input = new Gtk.ComboBoxText();
	let currency_spacer = new Gtk.Box({orientation: Gtk.Orientation.HORIZONTAL, margin_right: 290 });
	let currencies = ["USD","AUD","CHF","NOK","RUB","DKK","JPY","CAD","NZD","PLN","CNY","SEK","SGD","HKD","EUR"];
	for(let i = 0; i < currencies.length; i++) currency_input.append_text(currencies[i]);
	currency_box.pack_start(currency_label, false, false, 15);
	currency_box.pack_start(currency_input, true, true, 15);
	currency_box.pack_start(currency_spacer, true, true, 15);
	main_frame.add(currency_box);
 
	// Show currency
	let showcur_box = new Gtk.Box({orientation: Gtk.Orientation.HORIZONTAL});
	let showcur_label = new Gtk.Label({label: "Show currency in panel", xalign: 0});
	let showcur_spacer = new Gtk.Box({orientation: Gtk.Orientation.HORIZONTAL, margin_right: 300 });
	showcur_input = new Gtk.Switch();
	showcur_box.pack_start(showcur_label, false, false, 15);
	showcur_box.pack_start(showcur_input, true, true, 15);
	showcur_box.pack_start(showcur_spacer, true, true, 15);
	main_frame.add(showcur_box);
}

function widget_connect() {

	let update_settings = function() {
		let data = Settings.getSettings(settings);
		let oldcur = 0;
		let oldshcur = true;
		if('currency' in data) oldcur = data.currency;
		if('show_currency' in data) oldshcur = data.show_currency;
		data.currency = currency_input.get_active();
		data.show_currency = showcur_input.get_active();
		data.refresh_period = refresh_input.get_value();
		if(oldcur != data.currency || oldshcur != data.show_currency) data.reload_now = true;
		settings.set_string("settings-json", JSON.stringify(data));
	};

	currency_input.connect('changed', Lang.bind(this, update_settings));
	showcur_input.connect('notify::active', Lang.bind(this, update_settings));
	refresh_input.connect('value_changed', Lang.bind(this, update_settings));
}


// setting init values
function widget_init_values() {
	let data = Settings.getSettings(settings);
	if('currency' in data) currency_input.set_active(data.currency); else currency_input.set_active(0);
	if('show_currency' in data) showcur_input.set_active(data.show_currency); else showcur_input.set_active(true);
	if('refresh_period' in data) refresh_input.set_value(data.refresh_period); else refresh_input.set_value(120);
}
 
function buildPrefsWidget() {
	widget_initliaze();
	widget_init_values();
	widget_connect();
	main_frame.show_all();
	return main_frame;
}
