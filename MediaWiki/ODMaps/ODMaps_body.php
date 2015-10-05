<?php
class ODMaps {

	// Keep track of multiple maps on same page
	private static $mapid = 1;

	/**
	 * Called when the extension is first loaded
	 */
	public static function onRegistration() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = __CLASS__ . '::setup';
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::onParserFirstCallInit' );
	}

	public static function setup() {
		global $wgOut, $wgResourceModules, $wgExtensionAssetsPath, $IP, $wgAutoloadClasses;

		// This gets the remote path even if it's a symlink (MW1.25+)
		$path = $wgExtensionAssetsPath . str_replace( "$IP/extensions", '', dirname( $wgAutoloadClasses[__CLASS__] ) );
		$wgResourceModules['ext.odmaps']['remoteExtPath'] = $path;
		$wgOut->addModules( 'ext.odmaps' );

		// Add CSS
		$wgOut->addStyle( $path . '/styles/odmaps.css' );

		// Make extension remote path available to script
		$wgOut->addJsConfigVars( 'odMapsPath', $path );

		// Add the google maps API
		$wgOut->addHeadItem( 'GoogleMaps', '<script src="https://maps.googleapis.com/maps/api/js?sensor=false&libraries=places" type="text/javascript"></script>' );
		$wgOut->addHeadItem( 'MarkerWithLabel', '<script src="' . $path . '/modules/markerwithlabel.js" type="text/javascript"></script>' );
	}

	/**
	 * Register parser-functions
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'odmap', __CLASS__ . '::expandOdMap' );
		return true;
	}

	/**
	 * Expand #ajaxmap parser-functions
	 * - pretends to render a map from Extension:Maps to load the necessary resources
	 * - adds our internal parser-function to remove the dummy map and to define our map options in script
	 */
	public static function expandOdMap() {
		global $wgODMapsDefaultOpts;
		$opt = $wgODMapsDefaultOpts;
		$opt['id'] = $id = self::$mapid++;

		// Update options from parameters specified in parser-function
		foreach( func_get_args() as $arg ) {
			if( !is_object( $arg ) && preg_match( "/^(\w+?)\s*=\s*(.*?)\s*$/s", $arg, $m ) ) {
				$k = strtolower( $m[1] );
				$v = $m[2];
				if( ( $k == 'width' || $k == 'height' ) && is_numeric( $v ) ) $v .= 'px';
				if( $k == 'options' && substr( $v, 0, 1 ) == '{' ) {
					$opts2 = json_decode( $v, true );
					if( is_array( $opts2 ) ) $opt = array_merge( $opt, $opts2 );
				} else $opt[$k] = $v;
			}
		}

		// Return a div element for this map with the parameters JSON encoded for the JS (nested divs for adding filter over map)
		$size = ' style="width:' . $opt['width'] . ';height:' . $opt['height'] . '"';
		$json = '<div style="display:none" class="options">' . json_encode( $opt, JSON_NUMERIC_CHECK ) . '</div>';
		return "<div class=\"odmap\"$size id=\"odmap$id\">$json</div>";
	}
}
