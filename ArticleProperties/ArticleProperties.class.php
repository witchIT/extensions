<?php
class ArticleProperties extends Article {

	/**
	 * Contstruct as a normal article with no differences
	 */
	function __construct( $param ) {
		global $wgHooks;

		// Allow sub-classes to have an edit method that can add its own fields
		$wgHooks['EditPage::showEditForm:fields'][] = array( $this, 'onShowEditFormFields' );

		// Allow sub-classes to have a save method that can store its fields into the page_props
		$wgHooks['ArticleSave'][] = $this;

		return parent::__construct( $param );
	}

	/**
	 * When a new article is created, allow PageProperties sub-class to specify if they or their sub-classes should be used for this article
	 */
	public static function onArticleFromTitle( $title, &$page ) {

		// ArticleProperties sub-classes can use this to select a new class for the page Article
		// - a new class name is returned for pre-defined classes
		// - or an array of ( classname, filename ) to lazy-load the class
		$class = null;
		wfRunHooks( 'ArticlePropertiesClassFromTitle', array( &$title, &$class ) );
		if( !$class ) return true;
		if( !is_array( $class ) ) $class = array( $class, false );
		list( $classname, $classfile ) = $class;

		// If a file was specified, declare the class now
		if( $classfile ) {
			require_once( $classfile );
		}

		// Set the page to an instance of the class
		$page = new $classname( $title );

		return true;
	}

	/**
	 * Executed for showEditForm hook of our article types and calls the sub-class edit function if exists
	 */
	function onShowEditFormFields( &$editpage, $out ) {
		$this->edit( $editpage, $out );
		return true;
	}

	/**
	 * Executed for ArticleSave hook of our article types and calls the sub-class save function if exists
	 */
	function onArticleSave( &$article, &$user, &$text, &$summary, $minor, $watchthis, $sectionanchor, &$flags, &$status ) {
		global $wgRequest;
		$this->save( $wgRequest );
		return true;
	}

	function edit( &$editpage, $out ) {
	}

	function save( $request = false ) {
	}

	/**
	 * Add a properties method to interface with the article's page_props
	 */
	public function properties( $props = array() ) {
		$title = $this->getTitle();
		if( $id = $title->getArticleId() ) {
			$changed = false;
			$dbr = wfGetDB( DB_SLAVE );
			$dbw = false;

			// If the input array is empty, return all properties
			if( count( $props ) == 0 ) {
				$res = $dbr->select( 'article_properties', 'ap_propname,ap_value', array( 'ap_page' => $id ) );
				while( $row = $dbr->fetchRow( $res ) ) $props[$row[0]] = $row[1];
				$dbr->freeResult( $res );
			}

			// Otherwise return only those specified
			else {
				$ns = $title->getNamespace();
				foreach( $props as $k => $v1 ) {

					// Read the current value of this property
					$v0 = $dbr->selectField( 'article_properties', 'ap_value', array( 'ap_page' => $id, 'ap_propname' => $k ) );

					// If a key has a null value, then read the value if there was one
					if( $v1 === null ) {
						if( $v0 !== false ) $props[$k] = $v0;
					}

					// Otherwise set the value if it's changed
					elseif( $v0 !== $v1 ) {

						// Get a db connection to write to if we don't have one yet
						if( $dbw === false ) $dbw = wfGetDB( DB_MASTER );

						// Update the existing value in the props table
						if( $v0 === false ) {
							$dbw->insert( 'article_properties', array( 'ap_page' => $id, 'ap_namespace' => $ns, 'ap_propname' => $k, 'ap_value' => $v1 ) );
						}

						// Create this value in the props table
						else {
							$dbw->update( 'article_properties', array( 'ap_value' => $v1 ), array( 'ap_page' => $id, 'ap_propname' => $k ) );
						}

						// add to array that will be sent ot the change event
						$changed[$k] = array( $v0, $v1 );
					}
				}
			}

			if( $changed ) wfRunHooks( 'ArticlePropertiesChanged', array( &$this, &$changed ) );
		}

		return $props;
	}

	/**
	 * Add a static query method to select a list of articles by SQL conditions and options
	 */
	public static function query( $type, $conds, $options = null ) {
		$list = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'article_properties', 'ap_page', $conds, $options );
		while( $row = $dbr->fetchRow( $res ) ) $list[] = Title::newFromId( $row[0] );
		$dbr->freeResult( $res );
		return $list;
	}

	/**
	 * Add a static method to render results in a table format
	 */
	public static function table( &$titles, $atts = array(), $fields = false ) {

		// Open the table
		$html = "<table";
		if( array_key_exists( 'class', $atts ) ) $atts['class'] .= ' ap_results';
		else $atts['class'] = 'ap_results';
		foreach( $atts as $k => $v ) $html .= " $k=\"$v\"";
		$html .= ">\n";

		// Get fields from the first title if none specified
		if( !is_array( $fields ) ) {
			$ap = new ArticleProperties( $titles[0] );
			$fields = array_keys( $ap->properties() );
		}

		// Render the table header
		$html .= "<tr>";
		foreach( $fields as $field ) $html .= "<th>$field</th>";
		$html .= "</tr>\n";

		// Render the rows
		$html .= "<tr>";
		foreach( $titles as $title ) {
			$ap = new ArticleProperties( $title );
			foreach( $fields as $field ) {
				$prop = array( $field => null );
				$ap->properties( $prop );
				$val = $prop[$field];
				$html .= "<td>$val</td>";
			}
		}
		$html .= "</tr>\n";

		// Close the table and return content
		$html .= "</table>\n";
		return $html;
	}

	/**
	 * Set an array of values from the global request
	 */
	function updatePropertiesFromRequest( $names ) {
		global $wgRequest;
		$props = array();
		foreach( $names as $k ) {
			$v = $wgRequest->getText( "wp$k", false );
			if( $v !== false ) $prop[$k] = $v;
		}
		$this->properties( $props );
	}

	/**
	 * Get a value for a field from the current article
	 */
	function getValue( $name, $default = false ) {
		if( !$this->exists() ) return $default;
		$prop = $this->properties( array( $name => null ) );
		return $prop[$name] ? $prop[$name] : $default;
	}

	/**
	 * Render a label element for an input
	 */
	function label( $label, $name = false ) {
		if( $name === false ) $name = ucfirst( $label );
		return "<label for=\"wp$name\">" . wfMsg( $label ) . "</label>";
	}

	/**
	 * Render an input element with current value if the article already exists
	 */
	function input( $name, $default = '' ) {
		$value = $this->getValue( $name, $default );
		return "<input type=\"text\" value=\"$value\" name=\"wp$name\" id=\"wp$name\" />";
	}

	/**
	 * Render combined label and input as a table row
	 */
	function inputRow( $label, $name = false, $default = '', $extra = '' ) {
		$label = $this->label( $label, $name );
		$input = $this->input( $name, $default );
		return "<tr><th>$label</th><td>$input$extra</td></tr>";
	}

	/**
	 * Render a select list with supplied options list and selected/default value from page_props if any
	 */
	function select( $name, $options, $first = '', $default = '', $messages = true ) {
		if( $first === false ) $first = '';
		elseif( $first == '' ) $first = "<option />";
		else $first = "<option value=\"\">$first</option>";
		$value = $this->getValue( $name, $default );
		$html = "<select name=\"wp$name\" id=\"wp$name\">$first";
		foreach( $options as $opt ) {
			$text = $messages ? wfMsg( $opt ) : $opt;
			$selected = $value == $opt ? ' selected="yes"' : '';
			$html .= "<option value=\"$opt\"$selected>$text</option>";
		}
		return $html . "</select>";
	}

	/**
	 * Render a radio option group with supplied options list and selected/default value from page_props if any
	 */
	function options( $name, $options, $default = '' ) {
		$value = $this->getValue( $name, $options[0] );
		$html = '';
		foreach( $options as $opt ) {
			$text = wfMsg( $opt );
			$checked = $value == $opt ? ' checked="yes"' : '';
			$html .= "<input type=\"radio\" name=\"wp$name\" value=\"$opt\"$checked /><label>$text</label>";
		}
		return $html;
	}

	/**
	 * Render a textarea
	 */
	function textarea( $name, $default = '' ) {
		$value = $this->getValue( $name, $default );
		return "<textarea name=\"wp$name\" id=\"wp$name\">$value</textarea>";
	}

}
