<?php
class TreeAndMenu {

	public static $instance;

	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = 'TreeAndMenu::setup';
		self::$instance = $this;
	}

	/**
	 * Called at extension setup time, install hooks and module resources
	 */
	public static function setup() {
		global $wgOut, $wgParser, $wgExtensionAssetsPath, $wgResourceModules;

		// Add hooks
		$wgParser->setFunctionHook( 'tree', array( self::$instance, 'expandTree' ) );
		$wgParser->setFunctionHook( 'menu', array( self::$instance, 'expandMenu' ) );

		// Add the Fancy Tree scripts and styles
		$path  = $wgExtensionAssetsPath . '/' . basename( __DIR__ ) . '/fancytree';
		$wgResourceModules['ext.fancytree'] = array(
			'scripts'        => array( 'jquery.fancytree.js', 'jquery.fancytree.persist.js', 'jquery.fancytree.mediawiki.js', 'fancytree.js' ),
			'dependencies'   => array( 'jquery.ui.core', 'jquery.effects.blind', 'jquery.cookie' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__ . '/fancytree',
		);
		$wgOut->addModules( 'ext.fancytree' );
		$wgOut->addStyle( "$path/fancytree.css" );
		$wgOut->addJsConfigVars( 'fancytree_path', $path );

		// Add the Suckerfish scripts and styles
		$path  = $wgExtensionAssetsPath . '/' . basename( __DIR__ ) . '/suckerfish';
		$wgResourceModules['ext.suckerfish'] = array(
			'scripts'        => array( 'suckerfish.js' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__ . '/suckerfish',
		);
		$wgOut->addModules( 'ext.suckerfish' );
		$wgOut->addStyle( "$path/suckerfish.css" );
	}

	/**
	 * Expand #tree parser-functions
	 */
	public function expandTree() {
		$args = func_get_args();
		return $this->expandTreeAndMenu( TREEANDMENU_TREE, $args );
	}

	/**
	 * Expand #menu parser-functions
	 */
	public function expandMenu() {
		$args = func_get_args();
		return $this->expandTreeAndMenu( TREEANDMENU_MENU, $args );
	}

	/**
	 * Render a bullet list for either a tree or menu structure
	 */
	private function expandTreeAndMenu( $type, $args ) {

		// Keep a record of recursive tree depth
		static $depth = 0;
		$depth++;

		// First arg is parser, last is the structure
		$parser = array_shift( $args );
		$bullets = array_pop( $args );

		// Convert other args (except class, id, root) into named opts to pass to JS (JSON values are allowed, name-only treated as bool)
		$opts = array();
		$atts = array();
		foreach( $args as $arg ) {
			if ( preg_match( '/^(\\w+?)\\s*=\\s*(.+)$/s', $arg, $m ) ) {
				if( $m[1] == 'class' || $m[1] == 'id' || $m[1] == 'root' ) $atts[$m[1]] = $m[2];
				else $opts[$m[1]] = preg_match( '|^[\[\{]|', $m[2] ) ? json_decode( $m[2] ) : $m[2];
			} else $opts[$opt] = true;
		}

		// Sanitise the bullet structure (remove empty lines and empty bullets) and parse it to html
		$bullets = preg_replace( '|^\*+\s*$|m', '', $bullets );
		$bullets = preg_replace( '|\n+|', "\n", $bullets );
		$html = $parser->parse( $bullets, $parser->getTitle(), $parser->getOptions(), true, false )->getText();		

		// Just keep it as a ul structure if it's within another tree
		if( $depth == 1 ) {

			// Determine the class and id attributes
			$class = $type == TREEANDMENU_TREE ? 'fancytree' : 'suckerfish';
			if( array_key_exists( 'class', $atts ) ) $class .= ' ' . $atts['class'];
			$id = array_key_exists( 'id', $atts ) ? ' id="' . $atts['id'] . '"' : '';

			// If its a tree, we need to add some code to the ul structure
			if( $type == TREEANDMENU_TREE ) {

				// Mark the structure as tree data, wrap in an unclosable top level if root arg passed
				$tree = '<ul id="treeData" style="display:none">';
				if( array_key_exists( 'root', $atts ) ) {
					$html = $tree . '<li>' . $atts['root'] . $html . '</li></ul>';
					$opts['minExpandLevel'] = 2;
				} else $html = preg_replace( '|<ul>|', $tree, $html, 1 );

				// Incorporate options as json encoded data in a div
				$opts = count( $opts ) > 0 ? '<div class="opts" style="display:none">' . json_encode( $opts, JSON_NUMERIC_CHECK ) . '</div>' : '';

				// Assemble it all into a single div
				$html = "<div class=\"$class\"$id>$opts$html</div>";
			}

			// If its a menu, just add the class and id attributes to the ul
			else $html = preg_replace( '|<ul>|', "<ul class=\"$class\"$id>", $html, 1 );
		}

		$depth--;
		return array( $html, 'isHTML' => true, 'noparse' => true );
	}
}
