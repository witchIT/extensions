$(document).ready(function() {

	"use strict";

	var delay = 5;
	var thumbWidth = 100;

	$('div.image-slider').each(function() {
		var div = $(this), w, h, src, thumb, img, prev, next;

		// Create default data for this slider and store it in the element
		div.data('image', 0);
		div.data('last', 0);
		div.data('dir', 0);
		div.data('images', []);

		// If the slider has class "thumbs" then add another div that will have the thumbs in it
		if(div.hasClass('thumbs')) thumb = $('<div />').addClass('thumbs');

		// Store the image urls found in this slider div in its data and preload them, and add thumbs if set
		$('img', div).css('display','none').each(function() {
			src = $(this).attr('src');
			img = $('<img />').attr('src', src);
			div.data('images').push(src);
			w = $(this).width();
			h = $(this).height();
			if(thumb) {
				img.width(thumbWidth);
				img.height(h*thumbWidth/w);
				img.css({float: 'left', cursor: 'pointer'});
				img.data('index',div.data('images').length - 1);
				img.click(function() {
					slide($('div.image-slider').has(this), 1, w, $(this).data('index'));
				});
				thumb.append(img);
			}
		});

		// Restructure the content of this sliders div into layered divs with prev/next buttons
		prev = '<a class="is-prev" href="javascript:">&lt; prev</a>';
		next = '<a class="is-next" href="javascript:">next &gt;</a>';
		div.html( '<div class="is-img1"><div class="is-img2">' + prev + next + '</div></div>' );
		if(thumb) div.append(thumb);
		$('.is-prev', div).click(function() { slide($('div.image-slider').has(this), -1, w); });
		$('.is-next', div).click(function() { slide($('div.image-slider').has(this), 1, w); });

		// Set the container size to the image size and other css styles
		$('div',div).css({ padding: 0, width: w, height: h });
		$('.is-prev',div).css({ float: 'left', 'margin-top': h/2 });
		$('.is-next',div).css({ float: 'right', 'margin-top': h/2 });

		// Start the sliding process
		slide(div, 1, w);
	});

	 function slide(div, dir, w, n) {
		var l = div.data('images').length;

		// Bail if already animating, else set animation to start
		if(div.data('dir')) return; else div.data('dir', dir);

		// Set the new image either to the next according to the passed direction, or to n if passed
		div.data('last', div.data('image'));
		div.data('image', n === undefined ? (div.data('image') + dir + l) % l : n);

		// Show next image on regular interval
		if(div.data('timer')) clearTimeout(div.data('timer'));
		div.data('timer', setTimeout(function() { slide(div, 1, w); }, delay * 1000));

		// Play an animation from the current image to the next
		div.animate({ t: 1 }, {
			duration: 1000,
			step: function(now, fx) {
				var div = $(fx.elem), offset;

				// Set an offset in pixels for the transition between the current and last image
				offset = -div.data('dir') * fx.pos * w;

				// Set the URL and position for the current image
				$('.is-img1', div).css( 'background', 'transparent url("' + div.data('images')[div.data('image')] 
					+ '") no-repeat ' + (offset + w * dir) + 'px center' );

				// Set the URL and position for the last image
				$('.is-img2', div).css( 'background', 'transparent url("' + div.data('images')[div.data('last')]
					+ '") no-repeat ' + offset + 'px center' );
			},
			complete: function(now, fx) {
				$(this).data('dir', 0); // mark current slider as no longer animating
			}
		});
	};
});
