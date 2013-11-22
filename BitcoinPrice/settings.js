const Params = imports.misc.params;
 
function getSettings(settings) {
	return JSON.parse(settings.get_string("settings-json"));
}
