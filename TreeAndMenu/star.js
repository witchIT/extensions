/**
 * Initalise bullet lists with class "tam-star"
 */
window.stars = [];
$( function() {
	$('div.tam-star').each( function() {
		var tree = $(this);
		var root = 'starnode' + window.stars.length;
		var w = window.star_config.width;
		var h = window.star_config.height;
		tree.html( '<ul><li><a href="/">' + window.star_config.root + '</a>' + tree.html() + '</li></ul>' ).css({
			width: w,
			height: h
		});
		$('ul', tree).css('list-style','none');

		if( window.star_config.spokes ) tree.append(
			'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" id="spokes" width="' + w + '" height="' + h + '">'
		);

		// Change all the bullet list li's content into star nodes (divs with an image, a spoke and the li content)
		$('a', this).each( function() {
			var a = $(this);
			var img = window.tamBaseUrl + window.star_config.img_leaf;
			a.wrap('div').css({
				padding: 0,
				margin: 0,
				'background-image': 'none',
				color: 'black',
			});
			var div = a.parent();
			div.attr('class','starnode').html( '<div><img src="' + img + '" /></div>' + div.html() ).css({
				'text-align': 'center',
				position: 'absolute',
				display: 'none'
			});
		});

		// Position all the nodes and add their animation events
		$('div.starnode', this).each( function() {
			var e = $(this);

			// Allow all nodes to access tree root data
			var root;

			// Get the depth of this element
			var li = e.parent();
			var d = 0;
			while( li[0].tagName == 'LI' ) {
				li = li.parent().parent();
				d++;
			}

			// Get the parent <a> or <div> and add item to parents children
			var p = e.parent().parent().parent();
			if( d > 1 ) {
				p = p.children().first();
				$('img', p).attr('src', window.tamBaseUrl + window.star_config.img_node);
				var pdata = getData(p);
				pdata.children.push(e);
				root = pdata.root;

				// Set initial position to parent
				var ox = p.position().left + p.width() / 2;
				var oy = p.position().top + p.height() / 2;
				e.css({
					left: ox - e.width() / 2,
					top: oy - e.height() / 2
				});
			}

			// If root, initialise the commonly accessible root data
			else {
				var div = e.parent().parent().parent();
				root = {
					x: div.width() / 2,
					y: div.height() / 2,
					openDepth: 0
				};
			}

			// Add spoke for this node
			var spoke = false;
			if( window.star_config.spokes ) {
				var spoke = document.createElementNS('http://www.w3.org/2000/svg', 'line');
				spoke.setAttribute('x1',0);
				spoke.setAttribute('y1',0);
				spoke.setAttribute('x2',0);
				spoke.setAttribute('y2',0);
				spoke.setAttribute('style', 'stroke: rgb(128,128,255); stroke-width: 1');
				document.getElementById('spokes').appendChild(spoke);
			}

			// Create a unique ID and persistent data for this element
			e.attr('id', 'starnode' + window.stars.length);
			window.stars.push( {
				root: root,
				children: [],
				parent: p,
				depth: d,
				open: false,
				anim: false,
				spoke: spoke
			});

			// Set a callback to open or close the node when clicked
			e.click( function() { animateNode(this); });
		});

		// Make the root node visible
		var e = $('#'+root);
		e.css({
			display: 'block',
			left: window.star_config.crumbsy - e.width() / 2,
			top: window.star_config.crumbsy - e.height() / 2
		});
	});
});

// Animate the passed node and its children from it's current state to the opposite state
function animateNode(node) {
	var e = $(node);
	var data = getData(e);
	if( data.children.length > 0 ) e.animate( { t: 100 }, {
		duration: window.star_config.duration,
		easing: window.star_config.easing,
		step: function(now, fx) {
			var t = fx.pos;
			var e = $(fx.elem);
			var data = getData(e);
			var root = data.root;
			var display = 'block';
			var o = t * window.star_config.out_spin;
			var d = data.depth;

			// Set node state as animating
			data.anim = true;

			// Hide the labels during animation
			var col = t < 0.9 ? 'white' : 'black';

			// Set initial origin for the children to this elements center
			var ox = e.position().left + e.width() / 2;
			var oy = e.position().top + e.height() / 2;

			// If closing, flip t, and move to depth position in crumbs area
			if( data.open ) {

				// At start of close sequence, change icon to "plus" and set crumbs to parent crumbs state
				if( fx.pos == 0 ) {
					$('img', e).attr('src', window.tamBaseUrl + window.star_config.img_node);
				}

				// Hide node at end of animation
				if( t > 0.9 ) display = 'none';

				// Animate the circle's center to the crumbs
				if( root.openDepth >= d ) {
					ox += ( window.star_config.crumbsx + ( d - 1 ) * ( e.width() + 50 ) - ox ) * t;
					oy += ( window.star_config.crumbsy - oy ) * t;
				}

				// animate to parents location
				else {
					ox += ( root.x - ox ) * t;
					oy += ( root.y - oy ) * t;
				}

				// Set the angle and radius to match the final opening's state
				o = window.star_config.out_spin + t * window.star_config.in_spin;
				t = 1 - t;
			}

			// If opening,
			else {

				// At start of open sequence,
				if( fx.pos == 0 ) {

					// Record depth of currently open node
					root.openDepth = d;

					// Change icon to "minus"
					$('img', e).attr('src', window.tamBaseUrl + window.star_config.img_open);

					// Set any open non-animating nodes to begin closing
					for( var i = 0; i < window.stars.length; i++ ) {
						var id = 'starnode' + i;
						if( id != e.attr('id') ) {
							var ndata = window.stars[i];
							if( ndata.open && !ndata.anim ) animateNode($('#'+id));
						}
					}
				}

				// Animate the circle's center to the root center
				ox += ( root.x - ox ) * t;
				oy += ( root.y - oy ) * t;
			}

			// Position the node at the current origin
			e.css({
				display: 'block',
				left: ox - e.width() / 2,
				top: oy - e.height() / 2
			});

			// Position the children to their locations around the origin
			var n = data.children.length;
			var k = Math.PI * 2 / n;
			var r = t * n * window.star_config.radius;
			for( var i in data.children ) {
				var child = data.children[i];
				var cdata = getData(child);

				// Update this childs node position
				var a = k * i + o;
				var x = Math.cos(a) * r;
				var y = Math.sin(a) * r;
				child.css({
					display: display,
					left: ox + x - child.width() / 2,
					top: oy + y - child.height() / 2,
					color: col
				});

				// Update this childs spoke position
				var spoke = cdata.spoke;
				if( spoke ) {
					if( display == 'none' ) $(spoke).attr({ x1: 0, y1: 0, x2: 0, y2: 0 });
					else $(spoke).attr({
						x1: ox,
						y1: oy - window.star_config.spokev,
						x2: ox + x,
						y2: oy + y - window.star_config.spokev
					})
				}
			}
		},

		// Toggle the status on completion
		complete: function() {
			var e = $(this);
			var data = getData(e);
			var root = data.root;

			// Toggle open state
			data.open = !data.open;

			// Set state to not animating
			data.anim = false;
		}
	});
}

/**
 * Return the passed star node elements data array
 */
function getData(e) {
	return window.stars[e.attr('id').substr(8)];
}
