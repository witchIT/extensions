$(document).ready(function() {
	e = document.getElementsByTagName( 'input' );
	for( i = 0; i < e.length; i++ ) {
		if( e[i].name == 'formmailer' ) e[i].name += '-' + mw.config.get('wgFormMailerAP');
	}
});
