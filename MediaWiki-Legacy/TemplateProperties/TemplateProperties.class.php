<?php

class TemplateProperties {

	function __construct() {
		global $wgOut, $wgHooks, $wgParser;
		$wgParser->setFunctionHook( 'properties', array( $this, 'expandProperties' ) );
		$wgParser->setFunctionHook( 'propertylist', array( $this, 'expandPropertyList' ) );
		$wgParser->setFunctionHook( 'propertytable', array( $this, 'expandPropertyTable' ) );

		// Hook into article save
		$wgHooks['ArticleSave'][] = $this;
	}

	/**
	 * When articles are saved, check if they use a template in the property-templates list and cache props if so
	 */
	onArticleSave( &$article ) {
		return true;
	}

	/**
	 * Expand #property - get a value for a property
	 */
	function expandProperty( $parser, $title, $name ) {
		$val = '';
		$title = Title::newFromText( $title );
		if( is_object( $title ) ) {
			$ap = new ArticleProperties( $title );
			$prop = array( $name => null );
			$prop = $ap->properties( $prop );
			$val = $prop[$name];
		}
		return $val;
	}

	/**
	 * Expand #propertylist
	 */
	function expandPropertyList( $parser, $template, $conds, $options = array(), $separator = '' ) {
		$html = '';

		$results = $ap->query( $template, $conds, $options );

		return $html;
	}

	/**
	 * Expand #propertytable - equivalent of #recordtable
	 */
	function expandPropertyTable( $parser, $template, $conds, $options = array(), $attributes = array() ) {
		$html = '';

		$results = $ap->query( $template, $conds, $options );

		$html = $ap->table( $results, $attributes );

		return $html;
	}

}
