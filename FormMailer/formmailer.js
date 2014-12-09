$(document).ready(function() {
	e = document.getElementsByTagName( 'input' );
	for( i = 0; i < e.length; i++ ) {
		if( e[i].name == mw.config.get('wgFormMailerAP') ) e[i].name += '-' + mw.config.get('wgFormMailerAP');
	}
});
