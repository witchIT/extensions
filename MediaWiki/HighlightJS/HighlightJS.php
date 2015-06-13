<?php
/**
 * HighlightJS extension - wrapper for the highlightjs.org client-side syntax highlighter
 * - usage is the same as the GeSHi extension, e.g. <source lang="html">.....</source>
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @copyright © 2015 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 * 
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

$wgHighlightJsStyle = ''; // Set to the name of the preferred style from the highlight/styles directory

$wgHighlightJsMagic = 'source'; // The name of the tag used to make highlighted code blocks

define( 'HIGHLIGHTJS_VERSION', '1.0.2, 2015-06-13' );

$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'        => 'HighlightJS',
	'author'      => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:HighlightJS',
	'description' => 'Adds syntax highlighting for code blocks using the highlightjs.org client-side syntax highlighter',
	'version'     => HIGHLIGHTJS_VERSION
);

class HighlightJS {

	function __construct() {
		global $wgExtensionFunctions, $wgHooks;
		$wgExtensionFunctions[] = array( $this, 'setup' );
		Hooks::register( 'ParserFirstCallInit', $this );
	}

	public function setup() {
		global $wgOut, $wgResourceModules, $wgExtensionAssetsPath, $wgHighlightJsStyle;

		// Set up JavaScript and CSS resources
		$path = preg_replace( '%^.+?/extensions(/.+$)%', "$wgExtensionAssetsPath$1", __DIR__ );
		$wgResourceModules['ext.highlightjs'] = array(
			'scripts'        => array( 'highlight/highlight.pack.js', 'highlight.js' ),
			'localBasePath'  => __DIR__,
			'remoteBasePath' => $path,
		);
		$wgOut->addModules( 'ext.highlightjs' );

		// Use the Organic Design highlight style if none of highlightjs's styles specified
		$css = $wgHighlightJsStyle ? "highlight/styles/$wgHighlightJsStyle.css" : 'highlight.css';
		$wgOut->addStyle( "$path/$css" );
	}

	/**
	 * Register the new tag function
	 */
	public function onParserFirstCallInit( Parser $parser ) {
		global $wgHighlightJsMagic;
		$parser->setHook( $wgHighlightJsMagic, array( $this, 'expandTag' ) );
		return true;
	}

	/**
	 * Expand the new tag
	 */
	public function expandTag( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgJsMimeType;
		if( !array_key_exists( 'lang', $args ) ) $args['lang'] = 'nohighlight';
		$class = ' class="' . $args['lang'] . ' todo"';
		$script = "<script type=\"$wgJsMimeType\">if('hljsGo' in window) window.hljsGo();</script>";
		return "<pre><code$class>" . htmlspecialchars( trim( $input ) ) . "</code></pre>$script";
	}
}

new HighlightJS();
