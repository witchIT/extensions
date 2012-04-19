<?php
class ArticleProperties extends Article {

	// These are set by a sub-class if it should use its own database table
	public static $table = false;
	public static $columns = false;
	public static $prefix = '';

	// Some methods can benefit from caching their results
	private static  $cache = array();

	/**
	 * Contstruct as a normal article with no differences
	 */
	function __construct( $param ) {
		global $wgHooks;

		// Initialise the function cache
		self::$cache = array();

		// The text for newly created ArticleProperties articles should be preloaded with a default message
		$wgHooks['EditFormPreloadText'][] = $this;

		// Allow sub-classes to have an edit method that can add its own fields
		$wgHooks['EditPage::showEditForm:fields'][] = array( $this, 'onShowEditFormFields' );

		// Allow sub-classes to have a save method that can store its fields into the page_props
		$wgHooks['ArticleSaveComplete'][] = $this;

		// Remove associated properties when an article is deleted
		$wgHooks['ArticleDeleteComplete'][] = $this;

		return parent::__construct( $param );
	}

	/**
	 * Cache for expensive methods with small inputs and deterministic results
	 * - we use AP_VOID so that storing false or null doesn't confuse the cache logic
	 */
	function cache( $func, $key, $val = AP_VOID ) {
		$key = "$func\x07$key";
		if( $val === AP_VOID ) return array_key_exists( $key, self::$cache ) ? self::$cache[$key] : AP_VOID;
		return self::$cache[$key] = $val;
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
	 * The text for newly created ArticleProperties articles should be preloaded with a default message,
	 * if this isn't done then the article won't get created since text content is needed
	 */
	function onEditFormPreloadText( &$textbox, &$title ) {
		$textbox = wfMsg( 'ap_preloadtext' );
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
	 * - only call sub-classes save() once incase they in turn do article-edits
	 */
	function onArticleSaveComplete( &$article, &$user, $text, $summary, $minor, $watch, $section, &$flags, $rev, &$status, $baseRevId ) {
		global $wgRequest;
		static $done = false;
		if( $done ) return true;
		$done = true;
		$this->save( $wgRequest );
		return true;
	}

	function edit( &$editpage, $out ) {
	}

	function save( $request = false ) {
	}

	/**
	 * After an article is deleted remove it's properties
	 */
	function onArticleDeleteComplete( &$article, &$user, $reason, $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'article_properties', array( 'ap_page' => $id ) );
		return true;
	}

	/**
	 * Convert a property name to a DB column name
	 */
	public static function getColumnName( $name, $prefix = false ) {
		if( AP_VOID !== $cache = self::cache( __METHOD__, $key = "$name\x07$prefix" ) ) return $cache;
		if( $prefix === false ) $prefix = self::$prefix;
		return self::cache( __METHOD__, $key, $prefix . strtolower( $name ) );
	}

	/**
	 * Convert a DB column name to a property name
	 */
	public static function getPropertyName( $name, $prefix = false ) {
		if( AP_VOID !== $cache = self::cache( __METHOD__, $key = "$name\x07$prefix" ) ) return $cache;
		$ret = false;
		foreach( self::$columns as $k => $v ) {
			if( self::getColumnName( $k, $prefix ) == $name ) $ret = $k;
		}
		return self::cache( __METHOD__, $key, $ret );
	}

	/**
	 * Add a properties method to interface with the article's DB data in either page_props or its own table
	 */
	public function properties( $props = array() ) {
		$title = $this->getTitle();
		if( $id = $title->getArticleId() ) {
			$class = get_class( $this );
			$table = $dbr->tableName( $class::$table );
			$prefix = $class::$prefix;
			$change = array();
			$update = array();
			$dbr = wfGetDB( DB_SLAVE );
			$page = $prefix . 'page';

			// Get the row if it exists
			$row = $dbr->selectRow( $table, '*', array( $page => $id ) );

			// If the input array is empty, fill in all values from the row
			if( count( $props ) == 0 ) {
				foreach( $row as $k => $v ) {
					if( $k != $page ) $props[self::getColumnName( $k, $prefix )] = $v;
				}
			}

			// Otherwise return only those specified
			else {
				$ns = $title->getNamespace();
				foreach( $props as $k => $v1 ) {
					$col = self::getColumnName( $k, $prefix );

					// Read the current value of this property
					$v0 = array_key_exists( $col, $row ) ? $row[$col] : false;

					// If a key has a null value, then set to the read value
					if( $v1 === null ) $props[$k] = $v0;

					// Otherwise add to the change and update arrays if changed
					elseif( $v0 !== $v1 ) {
						$change[$k] = array( $v0, $v1 );
						$update[$col] = $v1;
					}
				}
			}

			// If anything changed, update the row and execute the change hook
			if( count( $change ) > 0 ) {
				$dbw = wfGetDB( DB_MASTER );
				$dbw->update( $table, $update, array( $page => $id ) );
				wfRunHooks( 'ArticlePropertiesChanged', array( &$this, &$change ) );
			}
		}

		return $props;
	}


	/**
	 * Simple wrapper to Database::select to abstract caller from table and column names, returns array of title results
	 */
	public static function query( $class, $conds ) {
		$dbr = wfGetDB( DB_SLAVE );
		$table = $dbr->tableName( $class::$table );
		$prefix = $class::$prefix;
		$res = $dbr->select( $table, $prefix . 'page', $conds );
		$titles = array();
		while( $row = $dbr->fetchRow( $res ) ) $titles[] = Title::newFromID( $row[0] );
		$dbr->freeResult( $res );
		return $titles;
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
			if( $v !== false ) $props[$k] = $v;
		}
		$this->properties( $props );
	}

	/**
	 * Get a value for a field from the current article
	 */
	function getValue( $name, $default = false ) {
		if( !$this->getTitle()->exists() ) return $default;
		$prop = $this->properties( array( $name => null ) );
		return $prop[$name] ? $prop[$name] : $default;
	}

	/**
	 * Set a value for a field from the current article
	 */
	function setValue( $name, $value ) {
		if( !$this->getTitle()->exists() ) return false;
		return $this->properties( array( $name => $value ) );
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
	 * - first parameter can be an array of attributes including name and id or just a text name
	 * - Name parameter will be given a preceding "wp" for input and id values
	 */
	function select( $atts, $options, $first = '', $default = '', $messages = true ) {

		// Build attributes
		if( is_array( $atts ) ) {
			$name = $atts['name'];
			$atts['name'] = "wp$name";
		} else {
			$name = $atts;
			$atts = array( 'name' => "wp$name" );
		}
		if( !array_key_exists( 'id', $atts ) ) $atts['id'] = $atts['name'];
		$attstxt = '';
		foreach( $atts as $k => $v ) $attstxt .= " $k=\"$v\"";

		if( $first === false ) $first = '';
		elseif( $first == '' ) $first = "<option />";
		else $first = "<option value=\"\">$first</option>";
		$value = $this->getValue( $name, $default );
		$html = "<select$attstxt>$first";
		foreach( $options as $k => $v ) {
			if( is_numeric( $k ) ) $k = $v;
			$text = $messages ? wfMsg( $v ) : $v;
			$selected = $value == $k ? ' selected="yes"' : '';
			$html .= "<option value=\"$k\"$selected>$text</option>";
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
