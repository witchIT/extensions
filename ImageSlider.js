$(document).ready(function() {

	"use strict";

	var delay = 2;
	var thumbWidth = 100;

	/**
	 * Initialise all image-slider elements in the page and start them sliding
	 */
	$('div.image-slider').each(function() {		
		var div = $(this), w, h, src, thumb, img, prev, next;

		// Initialise data structure in this slider element
		div.data({
			image: 1,
			last: 0,
			dir: 0,
			images: [],
			width: 0,
			height: 0,
		});

		$('img:first',div).load(function() {

			div.data('width', w = $(this).width());
			div.data('height', h = $(this).height());
console.log(w,h);
			// If the slider has class "thumbs" then add another div that will have the thumbs in it
			if(div.hasClass('thumbs')) thumb = $('<div />').addClass('thumbs');

			// Store the image urls found in this slider div in its data and preload them, and add thumbs if set
			$('img', div).css('display','none').each(function() {
				src = $(this).attr('src');
				img = $('<img />').attr('src', src);
				div.data('images').push(src);
				if(thumb) {
					img.width(thumbWidth);
					img.height(h*thumbWidth/w);
					img.css({float: 'left', cursor: 'pointer'});
					img.data('index',div.data('images').length - 1);
					img.click(function() {
						slide($('div.image-slider').has(this), 1, $(this).data('index'));
					});
					thumb.append(img);
				}
			});

			// Restructure the content of this sliders div into layered divs with prev/next buttons
			prev = '<a class="is-prev" href="javascript:">&lt; prev</a>';
			next = '<a class="is-next" href="javascript:">next &gt;</a>';
			div.html( '<div class="is-img1"><div class="is-img2">' + prev + next + '</div></div>' );
			if(thumb) div.append(thumb);
			$('.is-prev', div).click(function() { slide($('div.image-slider').has(this), -1); });
			$('.is-next', div).click(function() { slide($('div.image-slider').has(this), 1); });

			// Set the container size to the image size and other css styles
			$('div',div).css({ padding: 0, width: w, height: h });
			$('.is-prev',div).css({ float: 'left', 'margin-top': h/2 });
			$('.is-next',div).css({ float: 'right', 'margin-top': h/2 });

			// Start the sliding process
			slide(div, 1);
		});
	});

	/**
	 * Start animating the passed div
	 * - dir is -1 or +1 for the direction to animate (left or right)
	 * - n allows the new image to be specified rather than just next/prev (it will scroll upward)
	 */
	function slide(div, dir, n) {
		var l = div.data('images').length,
			w = div.data('width'),
			h = div.data('height'),
			img1, img2;

		// Bail if already animating, else set animation to start
		if(div.data('dir')) return; else div.data('dir', dir);

		// Set the new image either to the next according to the passed direction, or to n if passed
		div.data('last', div.data('image'));
		div.data('image', n === undefined ? (div.data('image') + dir + l) % l : n);
		img1 = div.data('images')[div.data('image')];
		img2 = div.data('images')[div.data('last')];

		// Show next image on regular interval
		if(div.data('timer')) clearTimeout(div.data('timer'));
		div.data('timer', setTimeout(function() { slide(div, 1); }, delay * 1000));

		// Play an animation from the current image to the next
		div.animate({ t: 1 }, {
			duration: 1000,
			step: function(now, fx) {
				var div = $(fx.elem), offset, x1, y1, x2, y2;

				// Set an offset in pixels for the transition between the current and last image
				offset = -div.data('dir') * fx.pos * (n === undefined ? w : h);

				// Calculate the positions of the current and last image (images specified with n scroll upward)
				x1 = n === undefined ? offset + w * dir : 0;
				x2 = n === undefined ? offset : 0;
				y1 = n === undefined ? 0 : offset + h * dir;
				y2 = n === undefined ? 0 : offset;

				// Set the positions of the images with CSS
				$('.is-img1', div).css( 'background', 'url("' + img1 + '") no-repeat ' + x1 + 'px ' + y1 + 'px' );
				$('.is-img2', div).css( 'background', 'url("' + img2 + '") no-repeat ' + x2 + 'px ' + y2 + 'px' );
			},
			complete: function(now, fx) {
				$(this).data('dir', 0); // mark current slider as no longer animating
			}
		});
	};
});
