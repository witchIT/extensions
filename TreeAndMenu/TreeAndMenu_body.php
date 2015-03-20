<?php
class TreeAndMenu {

	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = 'TreeAndMenu::setup';
	}
		
	public static function setup() {
		global $wgOut, $wgParser, $wgExtensionAssetsPath, $wgResourceModules;

		// Add hooks
		$wgParser->setFunctionHook( 'tree', array( $this, 'expandTree' ) );
		$wgParser->setFunctionHook( 'menu', array( $this, 'expandMenu' ) );

		// Add the Fancy Tree scripts and styles
		$path  = $wgExtensionAssetsPath . '/' . basename( __DIR__ ) . '/fancytree';
		$wgResourceModules['ext.fancytree'] = array(
			'scripts'        => array( 'fancytree.js', 'jquery.fancytree.js', 'jquery.fancytree.persist.js' ),
			'dependencies'   => array( 'jquery.cookie' ),
			'remoteBasePath' => $path,
			'localBasePath'  => __DIR__,
		);
		$wgOut->addModules( 'ext.fancytree' );
		$wgOut->addStyle( "$path/fancytree.css" );

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
		return $this->expandTreeAndMenu( 'fancytree', func_get_args() );
	}

	/**
	 * Expand #menu parser-functions
	 */
	public function expandMenu() {
		return $this->expandTreeAndMenu( 'suckerfish', func_get_args() );
	}

	/**
	 * Expand either kind of parser-function
	 */
	public function expandTreeAndMenu( $class, $opts ) {

		// First arg is parser
		$parser = array_unshift( $opts );

		// Last arg is the tree structure
		$bullets = array_pop( $opts );

		// Convert remaining args to named options
		foreach( $opts as $opt ) if ( preg_match( '/^(\\w+?)\\s*=\\s*(.+)$/s', $opt, $m ) ) $opts[$m[1]] = $m[2]; else $opts[$opt] = true;
		
		// If persist option or class option present add to the class
		if( array_key_exists( 'persist', $opts ) ) $class .= '-persist';
		if( array_key_exists( 'class', $opts ) ) $class .= ' ' . $opts['class'];

		// Parse the bullet structure
		$html = $parser->parse( $bullets, $parser->getTitle(), $parser->getOptions(), true, true )->getText();		

		// Add the class and id if any
		$id = array_key_exists( 'id', $opts ) ? ' id="' . $opts['id'] . '"' : '';
		$html = str_replace( '<ul>', "<ul class=\"$class\"$id>", $html );

		return array( $html, 'isHTML' => true, 'noparse' => true );
	}
}
