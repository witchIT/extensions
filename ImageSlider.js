$(document).ready( function() {

	var delay = 5;

	$('div.image-slider').each( function() {
		var div = $(this);

		// Create default data for this slider and store it in the element
		div.data('image', 0);
		div.data('dir', 0);
		div.data('images', []);

		// Store the image urls found in this slider div in its data and preload them
		$('img', div).css('display','none').each(function() {
			var src = $(this).attr('src');
			var image = $('<img />').attr('src', src);
			div.data('images').push(src);
			div.data('w', $(this).attr('width'));
			div.data('h', $(this).attr('height'));
		});

		// Restructure the content of this sliders div into a table with prev/next buttons
		var prev = '<a class="is-prev" href="javascript:">&lt; prev</a>';
		var next = '<a class="is-next" href="javascript:">next &gt;</a>';
		div.html( '<table><tr><th><table><tr><td>' + prev + next + '</td></tr></table></th></tr></table>' );
		$('.is-prev', div).click(function() { slide($('div.image-slider').has(this), -1 ); });
		$('.is-next', div).click(function() { slide($('div.image-slider').has(this), 1 ); });

		// Set the cell size to the image size and other css styles
		$('table',div).css({ background: 'transparent', width: div.data('w'), height: div.data('h'), 'border-collapse': 'collapse' });
		$('td,th',div).css({ padding: 0, 'vertical-align': 'middle' });
		$('.is-prev',div).css({ float: 'left' });
		$('.is-next',div).css({ float: 'right' });

		// Initialise the table's images to first image with zero offset
		slide(div, 0);
	});

	 function slide(div, dir) {

		// Set the new image and animate to it (bail if already animating)
		if(div.data('dir')) return;
		div.data('image', div.data('image') + dir);
		div.data('dir', dir);

		// Show next image on regular interval
		if(div.data('timer')) clearTimeout(div.data('timer'));
		div.data('timer', setTimeout(function() { slide(div,1); }, delay * 1000));

		// Play an animation from the current image to the next
		div.animate({ t: 1 }, {
			duration: 1000,
			step: function(now, fx) {
				var div = $(fx.elem);

				// Get the URLs for the current and next image
				var l = div.data('images').length;
				var image = div.data('image');
				image += l * 1000000;
				var next = ( image - dir ) % l;
				image %= l;
				var offset = -(div.data('dir') * fx.pos * div.data('w'));

				// Set the URL and position for the current image
				$('th', div).css('background', 'transparent url("'
					+ div.data('images')[image]
					+ '") no-repeat '
					+ (offset + div.data('w') * dir)
					+ 'px center'
				);

				// Set the URL and position for the next image
				$('td', div).css('background', 'transparent url("'
					+ div.data('images')[next] + '") no-repeat '
					+ offset
					+ 'px center'
				);
			},
			complete: function(now, fx) {
				$(this).data('dir', 0); // mark current slider as no longer animating
			}
		});
	};
});
