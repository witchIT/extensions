/**
 * JavaScript for the Organic Design Maps extension
 * - creates a map with marker positions and custom info overlays
 */
$(document).ready( function() {

	var images = mw.config.get('odMapsPath') + '/images';
	var icon = new google.maps.MarkerImage(
		images + '/marker.png', new google.maps.Size(33, 26), new google.maps.Point(0,0), new google.maps.Point(0,26)
	);


	/**
	 * Initialise the maps on the page
	 */
	$('div.odmap').each(function() {
		var map, json, opt, info,
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

		// Popup an infobox with position and scale when map clicked
		map.addListener('click', function(e) {
			if(info) info.close();
			info = new google.maps.InfoWindow({ content: e.latLng.toString() + ', zoom=' + map.zoom, position: e.latLng });
			info.open(map);
		});

		// Update the markers whenever the scale changes
		map.addListener('zoom_changed', function() {
			updateMarkers();
		});

		// Render the markers (if any)
		updateMarkers();

		/**
		 * Update the markers (dependent on zoom level)
		 */
		function updateMarkers() {
			var i, location, show, visible;
			for(i in opt.locations) {
				location = opt.locations[i];

				// If the marker doesn't currently exist, create it now
				if(!('marker' in location)) {
					location.marker = new MarkerWithLabel({
						position: new google.maps.LatLng(location.lat,location.lon),
						icon: icon,
						location: location,
						labelContent: location.title,
						labelAnchor: new google.maps.Point(-28, 26),
						labelClass: 'odmaps-label',
						visible: false
					});

					// If the marker has any infobox content, build its infowindow now and add a click event to open it
					if(('info' in location) || ('image' in location) || ('images' in location)) {
						location.marker.infow = new google.maps.InfoWindow({ content: renderInfo(location), maxWidth: 300 });
						google.maps.event.addListener(location.marker, 'click', function() {
							if(info) info.close();
							info = this.infow;
							info.open(map, this);
						});
					}
				}

				// Deterime whether the marker should be shown depending on zoom
				show = true;
				if('whenScale' in location) {
					var re = /^([<>])(\d+)$/;
					var m = re.exec(location.whenScale);
					if(m) {
						if(m[1] == '<' && map.zoom >= m[2]) show = false;
						if(m[1] == '>' && map.zoom <= m[2]) show = false;
					}
				}

				// Add or remove the marker from the map
				visible = location.marker.getVisible();
				if(show && !location.marker.visible) location.marker.setMap(map);
				if(!show && location.marker.visible) location.marker.setMap(null);
				location.marker.visible = show;
			}
		}

		/**
		 * Render the HTML for an info popup for the passed location
		 */
		function renderInfo(location) {
			var i,html = '<div class="odmaps-info">';
			html += '<h1>' + location.title + '</h1>';
			if('image' in location) html += makeImage(location.image);
			if('images' in location) {
				for(i in location.images) html += makeImage(location.images[i]);
				html += '<div style="clear:both"></div>';
			}
			if('info' in location) html += location.info.replace(/\n\n/g, '<br />');
			html += '</div>';
			return html;
		};

		/**
		 * Make an image element from the passed URL (links to the full image if it's a wiki thumb URL)
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

