/**
 * Minimal JS for Son of Suckerfish menus
 * - see http://alistapart.com/article/dropdowns
 */
$(document).ready(function() {

	// IE has problems with title attribute in suckerfish menus
	if($.browser.msie) $('.suckerfish a').removeAttr('title');

	// Suckerfish hover fix
	if(window.attachEvent) {
		$('.suckerfish li').mouseenter( function() { this.addClass('sfhover'); });
		$('.suckerfish li').mouseleave( function() { this.removeClass('sfhover'); });
	}
});
