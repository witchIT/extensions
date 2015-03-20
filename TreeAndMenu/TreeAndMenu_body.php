<?php
class TreeAndMenu {

	public static $instance;

	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = 'TreeAndMenu::setup';
		self::$instance = $this;
	}
		
	public static function setup() {
		global $wgOut, $wgParser, $wgExtensionAssetsPath, $wgResourceModules;

		// Add hooks
		$wgParser->setFunctionHook( 'tree', array( self::$instance, 'expandTree' ) );
		$wgParser->setFunctionHook( 'menu', array( self::$instance, 'expandMenu' ) );

		// Add the Fancy Tree scripts and styles
		$path  = $wgExtensionAssetsPath . '/' . basename( __DIR__ ) . '/fancytree';
		$wgResourceModules['ext.fancytree'] = array(
			'scripts'        => array( 'jquery.fancytree.js', 'jquery.fancytree.persist.js', 'fancytree.js' ),
			'dependencies'   => array( 'jquery.effects.blind', 'jquery.cookie' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__,
		);
		$wgOut->addModules( 'ext.fancytree' );
		$wgOut->addStyle( "$path/fancytree.css" );
		$wgOut->addJsConfigVars( 'fancytree_path', $path );

		// Add the Suckerfish scripts and styles
		$path  = $wgExtensionAssetsPath . '/' . basename( __DIR__ ) . '/suckerfish';
		$wgResourceModules['ext.suckerfish'] = array(
			'scripts'        => array( 'suckerfish.js' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__,
		);
		$wgOut->addModules( 'ext.suckerfish' );
		$wgOut->addStyle( "$path/suckerfish.css" );
	}

	/**
	 * Expand #tree parser-functions
	 */
	public function expandTree() {
		$args = func_get_args();
		return $this->expandTreeAndMenu( 'fancytree', $args );
	}

	/**
	 * Expand #menu parser-functions
	 */
	public function expandMenu() {
		$args = func_get_args();
		return $this->expandTreeAndMenu( 'suckerfish', $args );
	}

	/**
	 * Render a bullet list for either a tree or menu structure
	 */
	private function expandTreeAndMenu( $class, $opts ) {

		// First arg is parser
		$parser = array_shift( $opts );

		// Last arg is the tree structure, remove any lines that doesn't start with asterisk, or empty lines that do
		$bullets = array_pop( $opts );
		$bullets = preg_replace( '|^[^*].*?$|m', '', $bullets );
		$bullets = preg_replace( '|^\*+\s*$|m', '', $bullets );
		$bullets = preg_replace( '|\n+|', "\n", $bullets );

		// Convert remaining args to named options
		foreach( $opts as $opt ) if ( preg_match( '/^(\\w+?)\\s*=\\s*(.+)$/s', $opt, $m ) ) $opts[$m[1]] = $m[2]; else $opts[$opt] = true;
		
		// If persist option or class option present add to the class
		if( array_key_exists( 'persist', $opts ) ) $class .= '-persist';
		if( array_key_exists( 'class', $opts ) ) $class .= ' ' . $opts['class'];

		// Parse the bullet structure
		$html = $parser->parse( $bullets, $parser->getTitle(), $parser->getOptions(), true, false )->getText();		

		// Add the class and id if any
		$id = array_key_exists( 'id', $opts ) ? ' id="' . $opts['id'] . '"' : '';
		$html = preg_replace( '|<ul>|', "<ul id=\"treeData\" style=\"display:none\">", $html, 1 );
		$html = "<div class=\"$class\"$id>$html</div>";

		return array( $html, 'isHTML' => true, 'noparse' => true );
	}
}
