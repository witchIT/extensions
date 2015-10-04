/**
 * JavaScript for the Organic Design Maps extension
 * - creates a map with marker positions and custom info overlays
 */
$(document).ready( function() {

	/**
	 * Initialise the maps on the page
	 */
	$('div.odmap').each(function() {
		var i, map, location, state, icon, json, opt, info,
			images = mw.config.get('odMapsPath') + '/images',
			canvas = $(this);

		// Get the parameters for this map from the div and initialise it
		json = $('.options',canvas).text();
		opt = $.parseJSON(json);
		canvas.html('').css('background','none');

		// Initialise some of the options
		opt.canvas = canvas;
		opt.center = new google.maps.LatLng(opt.lat, opt.lon);
		opt.mapTypeId = google.maps.MapTypeId[opt.type.toUpperCase()];
		map = new google.maps.Map(this, opt);

		// Popup an infobox with position when map clicked
		map.addListener('click', function(e) {
			if(info) info.close();
			info = new google.maps.InfoWindow({ content: e.latLng.toString(), position: e.latLng });
			info.open(map);
		});

		// Render the markers (if any)
		if('locations' in opt) {
			for(i in opt.locations) {
				location = opt.locations[i];
				console.log(location);
				location.marker = new google.maps.Marker({
					title: location.title,
					position: new google.maps.LatLng(location.lat,location.lon)
				});
				location.marker.setMap(map);

				if('info' in location) {
					google.maps.event.addListener(location.marker, 'click', function() {
						if(info) info.close();
						info = new google.maps.InfoWindow({ content: renderInfo(location), maxWidth: 300 });
						info.open(map, location.marker);
					});
				}
			}
		}

		/**
		 * Render the HTML for an info popup for the passed location
		 */
		function renderInfo(location) {
			var html = '<div class="odmaps-info">';
			html += '<h1>' + location.title + '</h1>';
			if('image' in location) html += '<img src="' + location.image + '" />';
			html += location.info;
			html += '</div>';
			return html;
		};
	});
});

