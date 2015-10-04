/**
 * JavaScript for the Organic Design Maps extension
 * - creates a map with marker positions and custom info overlays
 */
$(document).ready( function() {

	/**
	 * Initialise the maps on the page
	 */
	$('div.odmap').each(function() {
		var i, map, location, state, icon, json, opt, info, label,
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
				location.marker = new MarkerWithLabel({
					map: map,
					title: location.title,
					position: new google.maps.LatLng(location.lat,location.lon),
					location: location,
					labelContent: location.title,
					labelAnchor: new google.maps.Point(-13, 38),
					labelClass: 'odmaps-label odmaps-label' + i
				});

				if('info' in location) {
					google.maps.event.addListener(location.marker, 'click', function() {
						if(info) info.close();
						if(!('infow' in this)) this.infow = new google.maps.InfoWindow({ content: renderInfo(this.location), maxWidth: 300 });
						info = this.infow;
						info.open(map, this);
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
			if('image' in location) html += makeImage(location.image);
			html += location.info.replace(/\n\n/g, '<br />');
			html += '</div>';
			return html;
		};

		/**
		 * Make an image element from the passed URL
		 */
		function makeImage(url) {
			var img = '<img src="' + url + '" />';
			var re = /^(.+?\/)thumb\/.+?\d+px-(.+?\.\w\w\w)$/;
			var m = re.exec(url);
			if(m) {
				var title = m[2].replace(/_/g, ' ');
				var href = mw.util.getUrl('File:' + m[2]);
				return '<a href="' + href + '" title="' + title + '">' + img + '</a>';
			}
			return img;
		}
	});
});

