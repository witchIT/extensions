/**
 * JavaScript for TrailWikiMaps MediaWiki extension
 * - creates a map with markers positions retrieved via AJAX and custom info overlays with content retrieved via AJAX
 */

function InfoBox( marker ) {
	google.maps.OverlayView.call( this );
	this.latlng_ = marker.position;
	this.map_ = marker.map;
	this.titles_ = marker.titles;
	var me = this;
	this.boundsChangedListener_ = google.maps.event.addListener( this.map_, "bounds_changed", function() {
		return me.panMap.apply( me );
	});
	this.setMap( this.map_ );
	if( window.currentInfobox ) window.currentInfobox.setMap( null );
	window.currentInfobox = this;
}

InfoBox.prototype = new google.maps.OverlayView();

InfoBox.prototype.remove = function() {
	if( this.div_ ) {
		this.div_.parentNode.removeChild( this.div_ );
		this.div_ = null;
	}
};

InfoBox.prototype.draw = function() {
	this.createElement();
	if( !this.div_ ) return;
	var pixPosition = this.getProjection().fromLatLngToDivPixel( this.latlng_ );
	if( !pixPosition ) return;
	this.width_ = $( this.div_ ).width();
	this.height_ = $( this.div_ ).height();
	this.offsetVertical_ = -this.height_;
	this.offsetHorizontal_ = 0;
	this.div_.style.left = ( pixPosition.x + this.offsetHorizontal_ ) + "px";
	this.div_.style.top = ( pixPosition.y + this.offsetVertical_ ) + "px";
	this.div_.style.display = 'block';
};

InfoBox.prototype.createElement = function() {
	var panes = this.getPanes();
	var div = this.div_;
	if(!div) {
		div = this.div_ = document.createElement('div');
		div.className = 'ajaxmap-info';
		var contentDiv = document.createElement('div');
		contentDiv.className = 'ajaxmap-info-content';
		this.loadContent(this.titles_, contentDiv);
		var topDiv = document.createElement('div');
		topDiv.className = 'ajaxmap-info-top';
		titlep = document.createElement('div');
		title = document.createElement('a');
		title.innerHTML = this.titles_[0];
		title.href = mw.util.wikiScript() + '?' + $.param({ title: this.titles_[0] });
		var closeImg = document.createElement('img');
		closeImg.src = '/w/images/5/50/Icon_delete_25.png';
		topDiv.appendChild(closeImg);
		topDiv.appendChild(titlep);
		titlep.appendChild(title);

		function removeInfoBox(ib) {
			return function() {
				ib.setMap(null);
			};
		}

		google.maps.event.addDomListener(closeImg, 'click', removeInfoBox(this));

		div.appendChild(topDiv);
		div.appendChild(contentDiv);
		div.style.display = 'none';
		panes.floatPane.appendChild(div);
		this.panMap();
	} else if(div.parentNode != panes.floatPane) {
		div.parentNode.removeChild(div);
		panes.floatPane.appendChild(div);
	}
}

InfoBox.prototype.panMap = function() {
	var map = this.map_;
	var bounds = map.getBounds();
	if(!bounds) return;

	// The position of the infowindow
	var position = this.latlng_;
	var iwWidth = this.width_;
	var iwHeight = this.height_;
	var iwOffsetX = this.offsetHorizontal_;
	var iwOffsetY = this.offsetVertical_;

	// Padding on the infowindow
	var padX = 0; //40;
	var padY = 0; //40;

	// The degrees per pixel
	var mapDiv = map.getDiv();
	var mapWidth = mapDiv.offsetWidth;
	var mapHeight = mapDiv.offsetHeight;
	var boundsSpan = bounds.toSpan();
	var longSpan = boundsSpan.lng();
	var latSpan = boundsSpan.lat();
	var degPixelX = longSpan / mapWidth;
	var degPixelY = latSpan / mapHeight;

	// The bounds of the map
	var mapWestLng = bounds.getSouthWest().lng();
	var mapEastLng = bounds.getNorthEast().lng();
	var mapNorthLat = bounds.getNorthEast().lat();
	var mapSouthLat = bounds.getSouthWest().lat();

	// The bounds of the infowindow
	var iwWestLng = position.lng() + (iwOffsetX - padX) * degPixelX;
	var iwEastLng = position.lng() + (iwOffsetX + iwWidth + padX) * degPixelX;
	var iwNorthLat = position.lat() - (iwOffsetY - padY) * degPixelY;
	var iwSouthLat = position.lat() - (iwOffsetY + iwHeight + padY) * degPixelY;

	// calculate center shift
	var shiftLng =
	  (iwWestLng < mapWestLng ? mapWestLng - iwWestLng : 0) +
	  (iwEastLng > mapEastLng ? mapEastLng - iwEastLng : 0);
	var shiftLat =
	  (iwNorthLat > mapNorthLat ? mapNorthLat - iwNorthLat : 0) +
	  (iwSouthLat < mapSouthLat ? mapSouthLat - iwSouthLat : 0);

	// The center of the map
	var center = map.getCenter();

	// The new map center
	var centerX = center.lng() - shiftLng;
	var centerY = center.lat() - shiftLat;

	// center the map to the new shifted center
	map.setCenter(new google.maps.LatLng(centerY, centerX));

	// Remove the listener after panning is complete.
	google.maps.event.removeListener(this.boundsChangedListener_);
	this.boundsChangedListener_ = null;
};

// Load content for passed titles and add to target element
InfoBox.prototype.loadContent = function( titles, div ) {
	for( i in titles ) {

		// Add heading/link for trails except first which has its heading in infobox title bar
		if( i > 0 ) {
			var hr = document.createElement('hr');
			div.appendChild(hr);
			var heading = document.createElement('a');
			heading.innerHTML = titles[i];
			heading.href = mw.util.wikiScript() + '?' + $.param({ title: titles[i] });
			div.appendChild(heading);
		}

		// Create a div element for thie info with a loading animation in it
		var target = document.createElement('div');
		div.appendChild(target);
		var loader = document.createElement('img');
		loader.src = '/w/skins/common/images/ajax-loader.gif';
		loader.className = 'ajaxmap-info-loader';
		target.appendChild(loader);

		// Request the content for the target div
		$.ajax({
			type: 'GET',
			url: mw.util.wikiScript(),
			data: { title: titles[i], action: 'trailinfo' },
			dataType: 'html',
			success: function( html ) { this.innerHTML = html; },
			context: target
		});

	}
};

// Format some of the options
window.ajaxmap_opt.center = new google.maps.LatLng( window.ajaxmap_opt.lat, window.ajaxmap_opt.lon );
window.ajaxmap_opt.mapTypeId = google.maps.MapTypeId[window.ajaxmap_opt.type.toUpperCase()];

// Only one infobox at a time
window.currentInfobox = 0;

// Create the map and set canvas size
var canvas = document.getElementById('ajaxmap');
var map = new google.maps.Map( canvas, window.ajaxmap_opt );
canvas.style.width = window.ajaxmap_opt['width'];
canvas.style.height = window.ajaxmap_opt['height'];

// Hard-coded icon for now
var icon = new google.maps.MarkerImage('/w/images/b/b9/Icon_Map_Square.png');

// Retrieve location info and create markers
var data = { action: 'traillocations' };
if( 'query' in window.ajaxmap_opt ) data.query = ajaxmap_opt.query;
$.ajax({
	type: 'POST',
	url: mw.util.wikiScript(),
	data: data,
	dataType: 'json',
	success: function( data ) {
		for( i in data ) {
			var pos = i.split(',');
			var marker = new google.maps.Marker({
				position: new google.maps.LatLng(pos[0], pos[1]),
				icon: icon,
				map: map,
				titles: data[i]
			});
			google.maps.event.addListener( marker, 'click', function() { new InfoBox(this); });
		}
	}
});

