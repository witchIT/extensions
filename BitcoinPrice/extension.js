// Copyright (C) 2013 Aran Dunkley
// Licence: GPLv2+
const St = imports.gi.St;
const Main = imports.ui.main;
const Tweener = imports.ui.tweener;
const GLib = imports.gi.GLib;
const Gio = imports.gi.Gio;

let label;
let btcPriceFile;
let monitor;
function init() {

	btcPriceFile = Gio.File.new_for_path(GLib.get_home_dir() + '/.btcprice.txt');
	label = new St.Bin({ style_class: 'panel-bitcoin-price' });
	let price = '$' + btcPriceFile.load_contents(null)[1];
	let text = new St.Label({ text: price.trim() });
	label.set_child(text);
    
	monitor = btcPriceFile.monitor(Gio.FileMonitorFlags.NONE, null);
	monitor.connect('changed', function(file, otherFile, eventType) {
		let [flag, price] = btcPriceFile.load_contents(null);
		if(flag && parseInt(price) > 0) {
			price = '$' + price;
			let text = new St.Label({ text: price.trim() });
			label.set_child(text);
		}
	});
}

function enable() {
    let children = Main.panel._rightBox.get_children();
    Main.panel._rightBox.insert_child_at_index(label, children.length-2);
}

function disable() {
    Main.panel._rightBox.remove_child(label);
}
